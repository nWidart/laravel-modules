<?php

/*
 * Benchmark harness: what does a change cost as the module count grows?
 *
 * Builds one throwaway Laravel 13 app per side (base and head), each requiring its own
 * checkout of the package through a symlinked path repository, generates a pool of modules
 * with that side's own module:make, then for each step N moves exactly N modules into
 * Modules/ and times the scenarios below, alternating base and head on every run.
 *
 * The module-weight table then keeps 100 modules and adds identical routes, views, migrations,
 * config and lang files to both sides (benchmarks/weight.php), to separate the package's cost
 * per module from the cost of what is inside each module.
 *
 *   composer bench                                   # quick: steps 10,100,500, base = merge-base with origin/master
 *   composer bench -- --profile=heavy                # quick plus the weight table (light and heavy)
 *   composer bench -- --full                         # steps 0,10,50,100,250,500, every scenario and weight profile
 *   php benchmarks/bench.php --base=v13.5.0 --steps=100 --runs=9 --json=build/bench/report.json
 *
 * Options: --base=<git ref> --head=<package checkout> --quick --full --steps=a,b,c --runs=N
 *          --profile=medium,heavy --json=<file> --markdown=<file>
 *
 * Composer also reads `--profile` after `composer bench --`, and then prefixes the progress lines
 * with its own memory and time: harmless.
 *
 * The Markdown report goes to stdout, progress to stderr. Work files live in build/bench/:
 * delete that directory to rebuild the cached Laravel skeleton.
 */

require __DIR__.'/weight.php';

const MARKER = '<!-- laravel-modules-benchmark -->';
const WARMUPS = 2;
const QUICK_RUNS = 10;
const FULL_RUNS = 15;
const DUMP_RUNS = 5;
const QUICK_STEPS = [10, 100, 500];
const FULL_STEPS = [0, 10, 50, 100, 250, 500];
const WEIGHT_MODULES = 100;
const MAX_STEP = 500;
const MIN_RUNS = 3;

$started = hrtime(true);
$root = dirname(__DIR__);
$work = $root.'/build/bench';

try {
    exit(main(options($argv), $root, $work, $started));
} catch (Throwable $e) {
    fwrite(STDERR, "\nbench: ".$e->getMessage()."\n");
    exit(1);
}

/**
 * Parse and check the options before anything is set up.
 *
 * @param  list<string>  $argv
 * @return array<string, string|false>
 */
function options(array $argv): array
{
    $values = ['base', 'head', 'steps', 'runs', 'profile', 'json', 'markdown'];
    $opts = getopt('', [...array_map(fn ($name) => "{$name}:", $values), 'quick', 'full']);

    // getopt() drops an option given without a value, such as --steps=.
    foreach ($values as $name) {
        if (! isset($opts[$name]) && preg_grep("/^--{$name}(=|$)/", $argv) !== []) {
            throw new RuntimeException("--{$name} needs a value.");
        }
    }

    if (isset($opts['steps'])) {
        foreach (explode(',', (string) $opts['steps']) as $step) {
            if (! ctype_digit($step) || (int) $step > MAX_STEP) {
                throw new RuntimeException('--steps takes a comma-separated list of module counts from 0 to '.MAX_STEP.", got [{$opts['steps']}].");
            }
        }
    }

    if (isset($opts['runs']) && (! ctype_digit((string) $opts['runs']) || (int) $opts['runs'] < MIN_RUNS)) {
        throw new RuntimeException('--runs must be a whole number of at least '.MIN_RUNS.", got [{$opts['runs']}]: with fewer runs the IQR cannot tell noise from change.");
    }

    return $opts;
}

/**
 * @param  array<string, string|false>  $opts
 */
function main(array $opts, string $root, string $work, int $started): int
{
    $full = isset($opts['full']);
    $steps = isset($opts['steps'])
        ? array_values(array_unique(array_map('intval', explode(',', (string) $opts['steps']))))
        : ($full ? FULL_STEPS : QUICK_STEPS);
    sort($steps);
    $runs = isset($opts['runs']) ? (int) $opts['runs'] : ($full ? FULL_RUNS : QUICK_RUNS);
    $profiles = profiles($full, isset($opts['profile']) ? (string) $opts['profile'] : null);

    @mkdir($work, 0777, true);

    $headDir = realpath((string) ($opts['head'] ?? $root)) ?: throw new RuntimeException('--head is not a directory.');
    $baseRef = (string) ($opts['base'] ?? 'merge-base of HEAD and origin/master');
    $baseSha = $opts['base'] ?? trim(run(['git', 'merge-base', 'HEAD', 'origin/master'], $root)['out']);
    $baseSha = trim(run(['git', 'rev-parse', '--verify', $baseSha.'^{commit}'], $root)['out']);
    $baseDir = checkoutBase($root, $work, $baseSha);

    $sides = [
        'base' => ['dir' => $baseDir, 'sha' => $baseSha, 'dirty' => false],
        'head' => [
            'dir' => $headDir,
            'sha' => trim(run(['git', 'rev-parse', 'HEAD'], $headDir)['out']),
            'dirty' => trim(run(['git', 'status', '--porcelain'], $headDir)['out']) !== '',
        ],
    ];

    $skeleton = skeleton($work);
    $poolSize = max(max($steps), $profiles === [] ? 0 : WEIGHT_MODULES);
    $names = array_map(fn (int $i) => sprintf('Bench%03d', $i), $poolSize > 0 ? range(1, $poolSize) : []);
    $sides = buildApps($work, $sides, $skeleton, $names);

    $countScenarios = array_filter(scenarios($full, max($steps)), fn ($s) => in_array('count', $s['tables'], true) && ($full || ! $s['full_only']));
    $weightScenarios = array_filter(scenarios($full, max($steps)), fn ($s) => in_array('weight', $s['tables'], true));
    $results = [];

    foreach ($steps as $step) {
        fillModules($sides, $names, $step);

        foreach ($countScenarios as $name => $scenario) {
            if ($scenario['at']($step)) {
                $results[$name][$step] = measure("step {$step}: {$name}", $scenario, $sides, $scenario['runs'] ?? $runs);
            }
        }
    }

    $weights = $profiles === [] ? [] : measureWeights($sides, $skeleton, $names, $profiles, $weightScenarios, $results, $runs);

    $runCounts = [];

    foreach ($countScenarios + ($profiles === [] ? [] : $weightScenarios) as $name => $scenario) {
        $runCounts[$name] = $scenario['runs'] ?? $runs;
    }

    $meta = [
        'base' => ['ref' => $baseRef, 'sha' => $baseSha],
        'head' => [
            'path' => $headDir,
            'label' => $headDir === realpath($root) ? null : displayPath($headDir, realpath($root)),
            'sha' => $sides['head']['sha'],
            'dirty' => $sides['head']['dirty'],
        ],
        'php' => PHP_VERSION,
        'laravel' => trim(run([...php(), 'artisan', '--version'], $sides['base']['app'])['out']),
        'os' => php_uname('s').' '.php_uname('r').' '.php_uname('m'),
        'cpu' => cpu(),
        'opcache' => implode(' ', opcache(['opcache' => '<per-side dir>'])),
        'xdebug' => 'not loaded in measured runs',
        'mode' => isset($opts['steps']) ? 'custom' : ($full ? 'full' : 'quick'),
        'full' => $full,
        'steps' => $steps,
        'profiles' => $profiles,
        'runs' => $runCounts,
        'warmups' => WARMUPS,
        'total_s' => round((hrtime(true) - $started) / 1e9, 1),
        'measuring_s' => round(measuring() / 1e9, 1),
    ];

    $report = [
        'meta' => $meta,
        'summary' => summary($results, $weights),
        'scenarios' => $results,
        'weights' => $profiles === [] ? null : [
            'modules' => WEIGHT_MODULES,
            'profiles' => array_intersect_key(PROFILES, array_flip($profiles)),
            'scenarios' => $weights,
        ],
    ];
    $markdown = markdown($report);

    echo $markdown;

    if (isset($opts['markdown'])) {
        file_put_contents((string) $opts['markdown'], $markdown);
    }

    if (isset($opts['json'])) {
        file_put_contents((string) $opts['json'], json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    return 0;
}

/**
 * The weight profiles to run: all with --full, light plus the --profile ones otherwise, none by default.
 *
 * @return list<string>
 */
function profiles(bool $full, ?string $option): array
{
    if ($full) {
        return array_keys(PROFILES);
    }

    if ($option === null) {
        return [];
    }

    $wanted = array_map('trim', explode(',', $option));

    foreach ($wanted as $profile) {
        if (! isset(PROFILES[$profile])) {
            throw new RuntimeException("Unknown --profile [{$profile}]. Use ".implode(', ', array_keys(PROFILES)).'.');
        }
    }

    // light is the reference row, and comes for free when step 100 ran.
    return array_values(array_intersect(array_keys(PROFILES), ['light', ...$wanted]));
}

/**
 * Every scenario, in run order. dump-autoload goes last: its post-autoload-dump clears Laravel's caches.
 *
 * `tables` says which tables a scenario is part of. `full_only` scenarios are skipped in quick mode.
 *
 * @return array<string, array<string, mixed>>
 */
function scenarios(bool $full, int $largest): array
{
    $always = fn (int $step) => true;
    $probe = fn (array $side) => [...php(), ...opcache($side), __DIR__.'/probe.php', $side['app']];
    $artisan = fn (string ...$args) => fn (array $side) => [...php(), ...opcache($side), 'artisan', ...$args];
    $probeMetric = 'probe time: from before vendor/autoload.php to after terminate()';

    return [
        'boot' => [
            'title' => 'HTTP boot, uncached: GET / through the HTTP kernel, no config, route or event cache.',
            'metric' => $probeMetric,
            'probe' => true,
            'tables' => ['count', 'weight'],
            'full_only' => false,
            'at' => $always,
            'cmd' => $probe,
        ],
        'boot-cached' => [
            'title' => 'HTTP boot after `php artisan optimize` (config, routes, events, views, bootstrap/cache/modules.php).',
            'metric' => $probeMetric,
            'probe' => true,
            'tables' => ['count', 'weight'],
            'full_only' => false,
            'at' => $always,
            'cmd' => $probe,
            'before' => fn (array $side) => artisanSetup($side, 'optimize'),
            'after' => fn (array $side) => artisanSetup($side, 'optimize:clear'),
        ],
        'artisan-list' => [
            'title' => '`php artisan list`.',
            'metric' => 'process wall time',
            'tables' => ['count'],
            'full_only' => true,
            'at' => $always,
            'cmd' => $artisan('list'),
        ],
        'module-list' => [
            'title' => '`php artisan module:list`.',
            'metric' => 'process wall time',
            'tables' => ['count'],
            'full_only' => false,
            'at' => $always,
            'cmd' => $artisan('module:list'),
        ],
        'migrate-status' => [
            'title' => '`php artisan migrate:status`, after `php artisan migrate` ran every module migration (the package hooks module migration paths into the Migrator).',
            'metric' => 'process wall time',
            'tables' => ['weight'],
            'full_only' => false,
            'at' => $always,
            'cmd' => $artisan('migrate:status'),
        ],
        'dump-autoload' => [
            'title' => '`composer dump-autoload` (merge plugin reads every module composer.json; the app has optimize-autoloader on).'
                .($full ? '' : ' Largest step only.'),
            'metric' => 'process wall time',
            'tables' => ['count'],
            'full_only' => true,
            'at' => fn (int $step) => $full || $step === $largest,
            'cmd' => fn (array $side) => [...composer(), 'dump-autoload', '--no-interaction'],
            'runs' => DUMP_RUNS,
        ],
    ];
}

/**
 * The weight table: a fixed 100 modules, with each profile's extra files on both sides.
 *
 * The light rows of scenarios that also ran at step 100 are those step-100 results: same modules, same runs.
 *
 * @param  array<string, array<string, mixed>>  $sides
 * @param  list<string>  $names
 * @param  list<string>  $profiles
 * @param  array<string, array<string, mixed>>  $scenarios
 * @param  array<string, array<int, array<string, mixed>>>  $results
 * @return array<string, array<string, array<string, mixed>>>
 */
function measureWeights(array $sides, string $skeleton, array $names, array $profiles, array $scenarios, array $results, int $runs): array
{
    fillModules($sides, $names, WEIGHT_MODULES);

    $rows = [];

    foreach ($profiles as $profile) {
        progress("weight {$profile}: adding files to ".WEIGHT_MODULES.' modules and migrating');

        foreach ($sides as $side) {
            foreach (array_slice($names, 0, WEIGHT_MODULES) as $name) {
                applyWeight("{$side['app']}/Modules/{$name}", $name, PROFILES[$profile]);
            }

            copy("{$skeleton}/database/database.sqlite", "{$side['app']}/database/database.sqlite");
        }

        parallel(perSide($sides, fn ($side) => artisanSetup($side, 'optimize:clear')));
        parallel(perSide($sides, fn ($side) => artisanSetup($side, 'migrate', '--force')));

        foreach ($scenarios as $name => $scenario) {
            if ($profile === 'light' && isset($results[$name][WEIGHT_MODULES])) {
                $rows[$name][$profile] = $results[$name][WEIGHT_MODULES] + ['reused' => 'step '.WEIGHT_MODULES];

                continue;
            }

            $rows[$name][$profile] = measure("weight {$profile}: {$name}", $scenario, $sides, $scenario['runs'] ?? $runs);
        }
    }

    return $rows;
}

/**
 * Time one scenario: warmups, then base and head alternately.
 *
 * @param  array<string, mixed>  $scenario
 * @param  array<string, array<string, mixed>>  $sides
 * @return array<string, mixed>
 */
function measure(string $label, array $scenario, array $sides, int $runs): array
{
    progress("{$label} ({$runs} runs)");

    foreach ($sides as $side) {
        // Every scenario starts from an empty opcache file cache and fills it in its warmups,
        // so files cached by the previous scenario or step do not carry over.
        resetDir($side['opcache']);
        resetDir($side['setup_opcache']);
    }

    if (isset($scenario['before'])) {
        parallel(perSide($sides, $scenario['before']));
    }

    $started = hrtime(true);
    $samples = ['base' => [], 'head' => []];

    for ($i = -WARMUPS; $i < $runs; $i++) {
        foreach ($sides as $key => $side) {
            $result = run($scenario['cmd']($side), $side['app']);
            $sample = ['wall' => $result['ms']];

            if ($scenario['probe'] ?? false) {
                $probe = json_decode($result['out'], true) ?: throw new RuntimeException("{$key} probe printed no JSON: ".$result['out']);

                if ($probe['status'] !== 200 || ! $probe['opcache']) {
                    throw new RuntimeException("{$key} probe: HTTP {$probe['status']}, opcache ".($probe['opcache'] ? 'on' : 'off').'.');
                }

                $sample += $probe;
            }

            if ($i >= 0) {
                $samples[$key][] = $sample;
            }
        }
    }

    measuring(hrtime(true) - $started);

    if (isset($scenario['after'])) {
        parallel(perSide($sides, $scenario['after']));
    }

    return compare($samples, ($scenario['probe'] ?? false) ? 'ms' : 'wall');
}

/**
 * Add to, and return, the nanoseconds spent in measured and warmup runs.
 */
function measuring(int $add = 0): int
{
    static $total = 0;

    return $total += $add;
}

/**
 * @param  array{base: list<array<string, mixed>>, head: list<array<string, mixed>>}  $samples
 * @return array<string, mixed>
 */
function compare(array $samples, string $metric): array
{
    $sides = [];

    foreach ($samples as $key => $list) {
        $values = array_column($list, $metric);
        $sides[$key] = stats($values) + ['samples' => array_map(fn ($v) => round($v, 3), $values)];

        if ($metric === 'ms') {
            $sides[$key]['wall'] = stats(array_column($list, 'wall'));

            foreach (['memory', 'files', 'providers'] as $field) {
                $sides[$key][$field] = (int) percentile(array_column($list, $field), 50);
            }
        }
    }

    [$base, $head] = [$sides['base'], $sides['head']];
    $deltaPct = ($head['median'] - $base['median']) / $base['median'] * 100;
    $noisePct = max($base['iqr'] / $base['median'], $head['iqr'] / $head['median']) * 100;

    $verdict = abs($deltaPct) >= 3 && abs($deltaPct) >= 2 * $noisePct
        ? ($deltaPct > 0 ? 'REGRESSION' : 'faster')
        : 'within noise';

    $row = [
        'base' => $base,
        'head' => $head,
        'delta_ms' => round($head['median'] - $base['median'], 3),
        'delta_pct' => round($deltaPct, 2),
        'noise_pct' => round($noisePct, 2),
        'verdict' => $verdict,
    ];

    if ($metric === 'ms') {
        foreach (['memory', 'files', 'providers'] as $field) {
            $row["delta_{$field}"] = $head[$field] - $base[$field];
        }
    }

    return $row;
}

/**
 * @param  list<float|int>  $values
 * @return array{median: float, p95: float, iqr: float}
 */
function stats(array $values): array
{
    return [
        'median' => round(percentile($values, 50), 3),
        'p95' => round(percentile($values, 95), 3),
        'iqr' => round(percentile($values, 75) - percentile($values, 25), 3),
    ];
}

/**
 * Percentile with linear interpolation between the closest ranks.
 *
 * @param  list<float|int>  $values
 */
function percentile(array $values, float $p): float
{
    sort($values);
    $rank = ($p / 100) * (count($values) - 1);
    $low = (int) floor($rank);
    $high = (int) ceil($rank);

    return $values[$low] + ($values[$high] - $values[$low]) * ($rank - $low);
}

/**
 * The worst regression, else the best improvement, else nothing, over both tables.
 *
 * @param  array<string, array<int, array<string, mixed>>>  $results
 * @param  array<string, array<string, array<string, mixed>>>  $weights
 * @return array<string, mixed>|null
 */
function summary(array $results, array $weights): ?array
{
    $cells = [];

    foreach ($results as $scenario => $rows) {
        foreach ($rows as $step => $row) {
            $cells[] = ['scenario' => $scenario, 'where' => "at {$step} modules"] + $row;
        }
    }

    foreach ($weights as $scenario => $rows) {
        foreach ($rows as $profile => $row) {
            if (! isset($row['reused'])) {
                $cells[] = ['scenario' => $scenario, 'where' => 'at '.WEIGHT_MODULES." {$profile} modules"] + $row;
            }
        }
    }

    $cells = array_filter($cells, fn ($cell) => $cell['verdict'] !== 'within noise');
    usort($cells, fn ($a, $b) => $b['delta_pct'] <=> $a['delta_pct']);

    foreach ($cells as $cell) {
        if ($cell['verdict'] === 'REGRESSION') {
            return $cell;
        }
    }

    return end($cells) ?: null;
}

/**
 * @param  array<string, mixed>  $report
 */
function markdown(array $report): string
{
    $meta = $report['meta'];
    $summary = $report['summary'];
    $definitions = scenarios($meta['full'], max($meta['steps']));

    $line = match (true) {
        $summary === null => 'No change beyond noise.',
        $summary['verdict'] === 'REGRESSION' => sprintf('Worst regression: **%s** %s, %s.', $summary['scenario'], $summary['where'], delta($summary)),
        default => sprintf('No regression. Biggest gain: **%s** %s, %s.', $summary['scenario'], $summary['where'], delta($summary)),
    };

    $runs = [];

    foreach ($meta['runs'] as $name => $count) {
        $runs[$count][] = $name;
    }

    $runs = implode('; ', array_map(fn ($count, $names) => implode(', ', $names).": {$count}", array_keys($runs), $runs));
    $head = '`'.substr($meta['head']['sha'], 0, 12).'`'.($meta['head']['dirty'] ? ' + uncommitted changes (dirty)' : '')
        .($meta['head']['label'] === null ? '' : " in `{$meta['head']['label']}`");

    $md = [
        MARKER,
        '## laravel-modules benchmark',
        '',
        $line,
        '',
        '| | |',
        '|---|---|',
        sprintf('| Base | `%s` (%s) |', substr($meta['base']['sha'], 0, 12), $meta['base']['ref']),
        sprintf('| Head | %s |', $head),
        sprintf('| PHP | %s, Xdebug %s |', $meta['php'], $meta['xdebug']),
        sprintf('| Laravel | %s |', $meta['laravel']),
        sprintf('| OS / CPU | %s / %s |', $meta['os'], $meta['cpu']),
        sprintf('| opcache | `%s`, file cache emptied before each scenario |', $meta['opcache']),
        sprintf('| Steps | %s (%s)%s |', implode(', ', $meta['steps']), $meta['mode'], $meta['profiles'] === [] ? '' : '; weight profiles: '.implode(', ', $meta['profiles'])),
        sprintf('| Runs per side | %s; base and head alternating, after %d warmups per side |', $runs, $meta['warmups']),
        sprintf('| Total time | %s (setup %s, measuring %s) |', duration($meta['total_s']), duration($meta['total_s'] - $meta['measuring_s']), duration($meta['measuring_s'])),
        '',
        '## Module count',
        '',
        'Modules as `module:make` generates them with each side\'s own stubs.',
    ];

    foreach ($report['scenarios'] as $name => $rows) {
        array_push($md, ...table("### {$name}", $definitions[$name], $rows, 'Modules'));
    }

    if ($report['weights'] !== null) {
        $profiles = [];

        foreach ($report['weights']['profiles'] as $profile => $p) {
            $profiles[] = $p['routes'] === 0
                ? "**{$profile}**: `module:make` output only"
                : sprintf('**%s**: plus %d routes, %d Blade views, %d migrations, %d config files and %d lang files in %s',
                    $profile, $p['routes'], $p['views'], $p['migrations'], $p['configs'], $p['lang_files'], implode(', ', $p['locales']));
        }

        $md[] = '';
        $md[] = '## Module weight ('.WEIGHT_MODULES.' modules)';
        $md[] = '';
        $reused = array_keys(array_filter($report['weights']['scenarios'], fn ($rows) => isset($rows['light']['reused'])));
        $md[] = 'The same extra files on both sides (`benchmarks/weight.php`), per module. '.implode('. ', $profiles).'.'
            .($reused === [] ? '' : ' The light '.implode(' and ', $reused).' rows repeat the '.WEIGHT_MODULES.'-module rows above.');

        foreach ($report['weights']['scenarios'] as $name => $rows) {
            array_push($md, ...table("### {$name} by module weight", $definitions[$name], $rows, 'Profile'));
        }
    }

    $md[] = '';
    $md[] = 'Values are medians, ± is the interquartile range. A cell is a REGRESSION or faster only when |Δ median| ≥ 3% '
        .'and ≥ 2 × the larger IQR of the two sides (as % of their median); otherwise it is within noise. '
        .'Memory is `memory_get_peak_usage(true)`. Files and providers are `get_included_files()` and loaded service providers.';

    return implode("\n", $md)."\n";
}

/**
 * @param  array<string, mixed>  $scenario
 * @param  array<int|string, array<string, mixed>>  $rows
 * @return list<string>
 */
function table(string $heading, array $scenario, array $rows, string $column): array
{
    $lines = [
        '',
        $heading,
        '',
        $scenario['title'].' ms = '.$scenario['metric'].'.',
        '',
        "| {$column} | base ms | head ms | Δ | Memory Δ | Files Δ | Providers Δ | Verdict |",
        '|---:|---:|---:|---:|---:|---:|---:|---|',
    ];

    foreach ($rows as $key => $row) {
        $probe = isset($row['delta_memory']);
        $lines[] = sprintf(
            '| %s | %s | %s | %s | %s | %s | %s | %s |',
            $key,
            cell($row['base']),
            cell($row['head']),
            delta($row),
            $probe ? signed($row['delta_memory'] / 1048576, 1).' MB' : '–',
            $probe ? signed($row['delta_files']) : '–',
            $probe ? signed($row['delta_providers']) : '–',
            $row['verdict'] === 'REGRESSION' ? '**REGRESSION**' : $row['verdict'],
        );
    }

    return $lines;
}

/**
 * @param  array<string, float>  $side
 */
function cell(array $side): string
{
    return sprintf('%s ±%s', number_format($side['median'], 1), number_format($side['iqr'], 1));
}

/**
 * @param  array<string, mixed>  $row
 */
function delta(array $row): string
{
    return sprintf('%s ms (%s%%)', signed($row['delta_ms'], 1), signed($row['delta_pct'], 1));
}

function signed(float|int $value, int $decimals = 0): string
{
    $text = number_format(abs($value), $decimals);

    return match (true) {
        round(abs($value), $decimals) == 0 => $text,
        $value > 0 => '+'.$text,
        default => '-'.$text,
    };
}

/**
 * The path relative to the current checkout, or under ~ when that is shorter.
 */
function displayPath(string $path, string $from): string
{
    $to = explode('/', trim($path, '/'));
    $base = explode('/', trim($from, '/'));

    while ($to !== [] && $base !== [] && $to[0] === $base[0]) {
        array_shift($to);
        array_shift($base);
    }

    $relative = implode('/', [...array_fill(0, count($base), '..'), ...$to]);
    $home = rtrim((string) getenv('HOME'), '/');
    $tilde = $home !== '' && str_starts_with($path, $home.'/') ? '~'.substr($path, strlen($home)) : $path;

    return strlen($relative) <= strlen($tilde) ? $relative : $tilde;
}

function duration(float $seconds): string
{
    return $seconds >= 60 ? sprintf('%dm %02ds', intdiv((int) $seconds, 60), (int) $seconds % 60) : sprintf('%.1fs', $seconds);
}

/**
 * Check out the base ref in a temporary git worktree, removed again when the run ends.
 */
function checkoutBase(string $root, string $work, string $sha): string
{
    $dir = $work.'/base-'.substr($sha, 0, 12);

    removeWorktree($root, $dir);
    progress('checking out base '.substr($sha, 0, 12));
    run(['git', 'worktree', 'add', '--detach', '--quiet', $dir, $sha], $root);

    register_shutdown_function(fn () => removeWorktree($root, $dir));

    if (function_exists('pcntl_async_signals')) {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, fn () => exit(130));
        pcntl_signal(SIGTERM, fn () => exit(143));
    }

    return $dir;
}

function removeWorktree(string $root, string $dir): void
{
    if (is_dir($dir)) {
        run(['git', 'worktree', 'remove', '--force', $dir], $root, check: false);
        removeDir($dir);
    }

    run(['git', 'worktree', 'prune'], $root, check: false);
}

/**
 * A plain Laravel 13 app, created once and copied for each side.
 */
function skeleton(string $work): string
{
    $dir = $work.'/skeleton';

    if (! is_file($dir.'/artisan')) {
        progress('creating the laravel/laravel ^13 skeleton (cached in build/bench/skeleton)');
        removeDir($dir);
        run([...composer(), 'create-project', 'laravel/laravel', $dir, '^13.0', '--prefer-dist', '--no-interaction', '--quiet'], $work);
    }

    return $dir;
}

/**
 * Copy the skeleton for each side, require the side's package checkout, and generate the
 * module pool with its module:make. Both sides build at the same time: nothing is measured yet.
 *
 * @param  array<string, array<string, mixed>>  $sides
 * @param  list<string>  $names
 * @return array<string, array<string, mixed>>
 */
function buildApps(string $work, array $sides, string $skeleton, array $names): array
{
    progress('building the base and head apps');

    foreach ($sides as $key => $side) {
        $sides[$key]['app'] = "{$work}/apps/{$key}";
        $sides[$key]['opcache'] = "{$work}/opcache/{$key}";
        $sides[$key]['setup_opcache'] = "{$work}/opcache/setup-{$key}";
        resetDir($sides[$key]['setup_opcache']);
        removeDir($sides[$key]['app']);
    }

    @mkdir($work.'/apps', 0777, true);
    parallel(array_map(fn ($side) => [['cp', '-R', $skeleton, $side['app']], $work], array_values($sides)));

    foreach ($sides as $side) {
        $composer = json_decode(file_get_contents($side['app'].'/composer.json'), true);
        $composer['repositories'] = [['type' => 'path', 'url' => $side['dir'], 'options' => ['symlink' => true]]];
        $composer['config']['allow-plugins']['wikimedia/composer-merge-plugin'] = true;
        $composer['extra']['merge-plugin'] = ['include' => ['Modules/*/composer.json']];
        file_put_contents($side['app'].'/composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        // The session is not under test: keep the sqlite writes of the database driver out of the timings.
        $env = preg_replace('/^SESSION_DRIVER=.*$/m', 'SESSION_DRIVER=array', file_get_contents($side['app'].'/.env'));
        file_put_contents($side['app'].'/.env', $env);
    }

    parallel(perSide($sides, fn () => [...composer(), 'require', 'nwidart/laravel-modules:*@dev', '--no-interaction', '--quiet']));
    parallel(perSide($sides, fn ($side) => artisanSetup($side, 'vendor:publish', '--provider=Nwidart\Modules\LaravelModulesServiceProvider', '--tag=config')));

    if ($names !== []) {
        progress('generating the module pools ('.count($names).' modules per side)');
        parallel(perSide($sides, fn ($side) => artisanSetup($side, 'module:make', ...$names)));
    }

    foreach ($sides as $side) {
        @mkdir($side['app'].'/Modules');
        rename($side['app'].'/Modules', $side['app'].'/ModulesPool');
        mkdir($side['app'].'/Modules');
    }

    return $sides;
}

/**
 * Leave exactly the first $count pool modules in Modules/ on both sides, all enabled, with fresh autoload and caches.
 *
 * @param  array<string, array<string, mixed>>  $sides
 * @param  list<string>  $names
 */
function fillModules(array $sides, array $names, int $count): void
{
    progress("moving {$count} modules into both apps");
    $wanted = array_slice($names, 0, $count);

    foreach ($sides as $side) {
        foreach ($names as $name) {
            [$from, $to] = in_array($name, $wanted, true) ? ['ModulesPool', 'Modules'] : ['Modules', 'ModulesPool'];

            if (is_dir("{$side['app']}/{$from}/{$name}")) {
                rename("{$side['app']}/{$from}/{$name}", "{$side['app']}/{$to}/{$name}");
            }
        }

        $statuses = $wanted === [] ? new stdClass : array_fill_keys($wanted, true);
        file_put_contents($side['app'].'/modules_statuses.json', json_encode($statuses, JSON_PRETTY_PRINT)."\n");
        resetDir($side['setup_opcache']);
    }

    parallel(perSide($sides, fn () => [...composer(), 'dump-autoload', '--no-interaction', '--quiet']));
    parallel(perSide($sides, fn ($side) => artisanSetup($side, 'optimize:clear')));

    foreach ($sides as $side) {
        $found = count(glob($side['app'].'/Modules/*/module.json'));

        if ($found !== $count) {
            throw new RuntimeException("{$side['app']}/Modules holds {$found} modules, expected {$count}.");
        }
    }
}

/**
 * The PHP command for child processes: this PHP binary with the same ini settings, minus Xdebug.
 *
 * Child processes always get an explicit ini file. Under `composer bench` this process may run
 * with Composer's Xdebug-free ini while a bare `php` would load Xdebug again.
 *
 * @return list<string>
 */
function php(): array
{
    static $command = null;

    if ($command !== null) {
        return $command;
    }

    $files = array_filter([php_ini_loaded_file(), ...array_map('trim', explode(',', (string) php_ini_scanned_files()))]);
    $ini = implode("\n", array_map('file_get_contents', $files));
    $ini = preg_replace('/^\s*zend_extension\s*=.*xdebug.*$/mi', ';$0', $ini);

    $path = dirname(__DIR__).'/build/bench/php.ini';
    file_put_contents($path, $ini);

    return $command = [PHP_BINARY, '-n', '-c', $path];
}

/**
 * The opcache flags of measured runs, with the side's file cache (or another of its cache dirs).
 *
 * @param  array<string, mixed>  $side
 * @return list<string>
 */
function opcache(array $side, string $dir = 'opcache'): array
{
    // file_update_protection=0: by default opcache skips files changed in the last 2 seconds,
    // such as the bootstrap/cache files `artisan optimize` has just written.
    return ['-d', 'opcache.enable_cli=1', '-d', 'opcache.file_update_protection=0', '-d', 'opcache.file_cache='.$side[$dir]];
}

/**
 * An artisan command for setup (not measured), with opcache and its own file cache.
 *
 * Without opcache, every artisan process after `route:cache` compiles the cached routes file
 * from scratch: 25 s at 500 modules, so `optimize` and `optimize:clear` took about 30 s each.
 *
 * @param  array<string, mixed>  $side
 * @return list<string>
 */
function artisanSetup(array $side, string ...$args): array
{
    return [...php(), '-d', 'memory_limit=-1', ...opcache($side, 'setup_opcache'), 'artisan', ...$args];
}

/**
 * Composer, run by the Xdebug-free PHP when it is a PHP script, so it does not restart itself on every run.
 *
 * @return list<string>
 */
function composer(): array
{
    static $command = null;

    if ($command === null) {
        $path = getenv('COMPOSER_BINARY') ?: trim(run(['sh', '-c', 'command -v composer'], '/', check: false)['out']);
        $command = $path !== '' && str_contains((string) file_get_contents($path, length: 64), 'php') ? [...php(), $path] : ['composer'];
    }

    return $command;
}

/**
 * One job per side for parallel(): the command, run in the side's app.
 *
 * @param  array<string, array<string, mixed>>  $sides
 * @return list<array{0: list<string>, 1: string}>
 */
function perSide(array $sides, Closure $command): array
{
    return array_map(fn ($side) => [$command($side), $side['app']], array_values($sides));
}

/**
 * Run the jobs at the same time and wait for all of them. Setup only: never while measuring.
 *
 * @param  list<array{0: list<string>, 1: string}>  $jobs
 */
function parallel(array $jobs): void
{
    $started = array_map(fn ($job) => start($job[0], $job[1]), $jobs);

    foreach ($started as $job) {
        finish($job);
    }
}

/**
 * Run a command without a shell; wall time is taken around the whole process.
 *
 * @param  list<string>  $command
 * @return array{out: string, err: string, code: int, ms: float}
 */
function run(array $command, string $cwd, bool $check = true): array
{
    return finish(start($command, $cwd), $check);
}

/**
 * @param  list<string>  $command
 * @return array<string, mixed>
 */
function start(array $command, string $cwd): array
{
    $out = tempnam(sys_get_temp_dir(), 'bench');
    $err = tempnam(sys_get_temp_dir(), 'bench');
    $started = hrtime(true);

    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, $cwd)
        ?: throw new RuntimeException('Cannot start `'.implode(' ', $command).'`.');

    return compact('command', 'cwd', 'out', 'err', 'started', 'process');
}

/**
 * @param  array<string, mixed>  $job
 * @return array{out: string, err: string, code: int, ms: float}
 */
function finish(array $job, bool $check = true): array
{
    $code = proc_close($job['process']);
    $ms = (hrtime(true) - $job['started']) / 1e6;

    $result = ['out' => file_get_contents($job['out']), 'err' => file_get_contents($job['err']), 'code' => $code, 'ms' => $ms];
    unlink($job['out']);
    unlink($job['err']);

    if ($check && $code !== 0) {
        $tail = implode("\n", array_slice(explode("\n", trim($result['err'] ?: $result['out'])), -15));

        throw new RuntimeException('`'.implode(' ', $job['command'])."` failed in {$job['cwd']} (exit {$code}):\n{$tail}");
    }

    return $result;
}

function resetDir(string $dir): void
{
    removeDir($dir);
    mkdir($dir, 0777, true);
}

function removeDir(string $dir): void
{
    if (is_dir($dir) || is_link($dir)) {
        run(['rm', '-rf', $dir], dirname($dir));
    }
}

function cpu(): string
{
    if (PHP_OS_FAMILY === 'Darwin') {
        $name = trim(run(['sysctl', '-n', 'machdep.cpu.brand_string'], '/')['out']);
        $cores = trim(run(['sysctl', '-n', 'hw.ncpu'], '/')['out']);
    } else {
        preg_match('/^model name\s*:\s*(.+)$/m', (string) @file_get_contents('/proc/cpuinfo'), $match);
        $name = $match[1] ?? 'unknown CPU';
        $cores = trim(run(['nproc'], '/', check: false)['out']);
    }

    return "{$name}, {$cores} cores";
}

function progress(string $message): void
{
    fwrite(STDERR, '['.date('H:i:s')."] {$message}\n");
}
