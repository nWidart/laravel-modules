<?php

/*
 * Extra module content for the module-weight table of benchmarks/bench.php.
 *
 * Writes the same files into a module whatever the package version, so base and head get
 * identical content on top of their own module:make output. The files go where the generated
 * module providers load them from with the default config (modules.paths.generator):
 * routes/web.php, resources/views, database/migrations, config and lang.
 */

const PROFILES = [
    'light' => ['routes' => 0, 'views' => 0, 'migrations' => 0, 'configs' => 0, 'locales' => [], 'lang_files' => 0],
    'medium' => ['routes' => 10, 'views' => 5, 'migrations' => 3, 'configs' => 2, 'locales' => ['en'], 'lang_files' => 2],
    'heavy' => ['routes' => 30, 'views' => 20, 'migrations' => 10, 'configs' => 5, 'locales' => ['en', 'fr', 'de'], 'lang_files' => 2],
];

/**
 * Give one module the extra files of a profile, after removing those of an earlier profile.
 *
 * @param  array{routes: int, views: int, migrations: int, configs: int, locales: list<string>, lang_files: int}  $profile
 */
function applyWeight(string $dir, string $name, array $profile): void
{
    $lower = strtolower($name);

    $previous = [
        ...glob("{$dir}/config/weight-*.php"),
        ...glob("{$dir}/resources/views/weight/*.blade.php"),
        ...glob("{$dir}/database/migrations/*_weight_*.php"),
        ...glob("{$dir}/lang/*/weight-*.php"),
    ];
    array_map('unlink', $previous);

    // routes/web.php is the module's generated file plus the profile's routes.
    $original = "{$dir}/routes/web.php.original";

    if (! is_file($original)) {
        copy("{$dir}/routes/web.php", $original);
    }

    $routes = file_get_contents($original);

    if ($profile['routes'] > 0) {
        $routes = rtrim($routes)."\n\n";
    }

    for ($i = 1; $i <= $profile['routes']; $i++) {
        $routes .= "Route::get('/{$lower}/weight-{$i}', fn () => '{$lower} weight {$i}')->name('{$lower}.weight.{$i}');\n";
    }

    file_put_contents("{$dir}/routes/web.php", $routes);

    for ($i = 1; $i <= $profile['views']; $i++) {
        write("{$dir}/resources/views/weight/view-{$i}.blade.php", VIEW, ['{title}' => "{$name} view {$i}", '{class}' => "{$lower}-{$i}"]);
    }

    for ($i = 1; $i <= $profile['migrations']; $i++) {
        $file = sprintf('%s/database/migrations/2024_01_01_%06d_weight_create_%s_weight_%d_table.php', $dir, $i, $lower, $i);
        write($file, MIGRATION, ['{table}' => "{$lower}_weight_{$i}"]);
    }

    for ($i = 1; $i <= $profile['configs']; $i++) {
        write("{$dir}/config/weight-{$i}.php", CONFIG, ['{name}' => "{$name} {$i}"]);
    }

    foreach ($profile['locales'] as $locale) {
        for ($i = 1; $i <= $profile['lang_files']; $i++) {
            write("{$dir}/lang/{$locale}/weight-{$i}.php", LANG, ['{name}' => "{$name} {$locale} {$i}"]);
        }
    }
}

/**
 * @param  array<string, string>  $replace
 */
function write(string $file, string $template, array $replace): void
{
    @mkdir(dirname($file), 0777, true);
    file_put_contents($file, strtr($template, $replace));
}

const VIEW = <<<'BLADE'
<div class="{class}">
    <h1>{{ $title ?? '{title}' }}</h1>
    @foreach ($items ?? [] as $item)
        <p>{{ $item }}</p>
    @endforeach
</div>

BLADE;

const MIGRATION = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{table}', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{table}');
    }
};

PHP;

const CONFIG = <<<'PHP'
<?php

return [
    'name' => '{name}',
    'enabled' => true,
    'cache' => ['store' => 'file', 'ttl' => 3600],
    'pagination' => ['per_page' => 15, 'max' => 100],
    'features' => [
        'search' => ['enabled' => true, 'driver' => 'database'],
        'export' => ['enabled' => false, 'formats' => ['csv', 'xlsx']],
    ],
];

PHP;

const LANG = <<<'PHP'
<?php

return [
    'title' => '{name}',
    'welcome' => 'Welcome, :name',
    'empty' => 'Nothing here yet.',
    'actions' => ['save' => 'Save', 'cancel' => 'Cancel', 'delete' => 'Delete'],
];

PHP;
