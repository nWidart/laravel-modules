<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SurfaceCommandTest extends BaseTestCase
{
    /**
     * A module.json with an empty object, slashes, non-ASCII text and a float, which a
     * re-encode through an associative array or default json_encode flags would change.
     */
    private const MODULE_JSON = <<<'JSON'
{
    "name": "Blog",
    "alias": "blog",
    "description": "Posts at https://example.com/blog, déjà vu",
    "keywords": [],
    "priority": 0,
    "weight": 1.0,
    "providers": [
        "Modules\\Blog\\Providers\\BlogServiceProvider"
    ],
    "aliases": {},
    "files": []
}

JSON;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_says_when_no_surface_is_stored()
    {
        [$code, $output] = $this->surface([]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Module [Blog] has no surface stored.', $output);
    }

    public function test_json_shows_a_missing_surface_as_null()
    {
        [$code, $output] = $this->surface(['--json' => true]);

        $this->assertSame(0, $code);
        $this->assertSame(
            json_encode(['module' => 'Blog', 'path' => $this->path(), 'surface' => null, 'changed' => false], JSON_UNESCAPED_SLASHES)."\n",
            $output,
        );
    }

    public function test_it_shows_the_stored_surface()
    {
        $this->surface(['--set' => true, '--class' => ['Modules\Blog\Models\Post'], '--route' => ['api/posts/{post}'], '--command' => ['blog:publish']]);

        [$code, $output] = $this->surface([]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('The surface of module [Blog]:', $output);
        $this->assertMatchesRegularExpression('/Classes \.+ 1 *\n  Modules\\\\Blog\\\\Models\\\\Post\n/', $output);
        $this->assertMatchesRegularExpression('/Routes \.+ 1 *\n  api\/posts\/\{post\}\n/', $output);
        $this->assertMatchesRegularExpression('/Commands \.+ 1 *\n  blog:publish\n/', $output);

        [$code, $output] = $this->surface(['--json' => true]);

        $this->assertSame(0, $code);
        $this->assertSame(
            ['classes' => ['Modules\Blog\Models\Post'], 'routes' => ['api/posts/{post}'], 'commands' => ['blog:publish']],
            json_decode($output, true)['surface'],
        );
    }

    public function test_it_sets_the_surface_deduplicated_and_sorted()
    {
        [$code, $output] = $this->surface([
            '--set' => true,
            '--class' => ['\Modules\Blog\Models\Post', 'Modules\Blog\Contracts\PostRepository', 'MODULES\BLOG\MODELS\POST'],
            '--route' => ['/api/posts/{post}/', 'api/posts', '/', 'api/posts', 'API/posts'],
            '--command' => ['blog:publish', 'Blog:publish', 'blog:publish'],
            '--json' => true,
        ]);

        $surface = [
            'classes' => ['Modules\Blog\Contracts\PostRepository', 'Modules\Blog\Models\Post'],
            'routes' => ['/', 'API/posts', 'api/posts', 'api/posts/{post}'],
            'commands' => ['Blog:publish', 'blog:publish'],
        ];

        $this->assertSame(0, $code);
        $this->assertSame(['module' => 'Blog', 'path' => $this->path(), 'surface' => $surface, 'changed' => true], json_decode($output, true));
        $this->assertSame($surface, json_decode(file_get_contents($this->path()), true)['surface']);
    }

    public function test_set_alone_stores_three_empty_lists()
    {
        file_put_contents($this->path(), self::MODULE_JSON);

        [$code] = $this->surface(['--set' => true]);

        $this->assertSame(0, $code);
        $this->assertSame(str_replace("    \"files\": []\n}", <<<'JSON'
    "files": [],
    "surface": {
        "classes": [],
        "routes": [],
        "commands": []
    }
}
JSON, self::MODULE_JSON), file_get_contents($this->path()));
    }

    public function test_it_keeps_the_rest_of_module_json_intact()
    {
        file_put_contents($this->path(), self::MODULE_JSON);

        [$code] = $this->surface(['--set' => true, '--class' => ['Modules\Blog\Models\Post'], '--route' => ['api/posts']]);

        $this->assertSame(0, $code);
        $this->assertSame(<<<'JSON'
{
    "name": "Blog",
    "alias": "blog",
    "description": "Posts at https://example.com/blog, déjà vu",
    "keywords": [],
    "priority": 0,
    "weight": 1.0,
    "providers": [
        "Modules\\Blog\\Providers\\BlogServiceProvider"
    ],
    "aliases": {},
    "files": [],
    "surface": {
        "classes": [
            "Modules\\Blog\\Models\\Post"
        ],
        "routes": [
            "api/posts"
        ],
        "commands": []
    }
}

JSON, file_get_contents($this->path()));
    }

    public function test_it_replaces_a_stored_surface_in_place()
    {
        file_put_contents($this->path(), "{\n    \"name\": \"Blog\",\n    \"surface\": {\n        \"classes\": [\"Old\"]\n    },\n    \"files\": []\n}");

        [$code] = $this->surface(['--set' => true, '--command' => ['blog:publish']]);

        $this->assertSame(0, $code);
        // No trailing newline before, none after.
        $this->assertSame(<<<'JSON'
{
    "name": "Blog",
    "surface": {
        "classes": [],
        "routes": [],
        "commands": [
            "blog:publish"
        ]
    },
    "files": []
}
JSON, file_get_contents($this->path()));
    }

    public function test_setting_the_same_surface_again_does_not_touch_the_file()
    {
        $this->surface(['--set' => true, '--class' => ['Modules\Blog\Models\Post']]);

        // Reformat the file (escaped slashes, two-space indent): a no-op set must not rewrite it.
        $contents = json_encode(json_decode(file_get_contents($this->path())), JSON_PRETTY_PRINT);
        $contents = str_replace('    ', '  ', $contents);
        file_put_contents($this->path(), $contents);
        touch($this->path(), $mtime = time() - 100);

        [$code, $output] = $this->surface(['--set' => true, '--class' => ['\Modules\Blog\Models\Post'], '--json' => true]);

        clearstatcache();
        $this->assertSame(0, $code);
        $this->assertFalse(json_decode($output, true)['changed']);
        $this->assertSame($contents, file_get_contents($this->path()));
        $this->assertSame($mtime, filemtime($this->path()));
    }

    public function test_it_says_when_a_set_changes_nothing()
    {
        $this->surface(['--set' => true]);

        [$code, $output] = $this->surface(['--set' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('The surface of module [Blog] is unchanged:', $output);
    }

    public function test_it_sets_the_surface_from_standard_input()
    {
        [$code, $output] = $this->surfaceWithStdin(['--json' => true], json_encode([
            'routes' => ['/api/posts/'],
            'commands' => ['blog:publish'],
        ]));

        $this->assertSame(0, $code, $output);
        $this->assertSame(
            ['classes' => [], 'routes' => ['api/posts'], 'commands' => ['blog:publish']],
            json_decode($output, true)['surface'],
        );
        $this->assertSame(['classes' => [], 'routes' => ['api/posts'], 'commands' => ['blog:publish']], $this->storedSurface());
    }

    public function test_it_reads_a_long_list_from_standard_input()
    {
        $classes = array_map(fn (int $i) => sprintf('Modules\Blog\Models\Model%03d', $i), range(150, 1));

        [$code, $output] = $this->surfaceWithStdin(['--json' => true], json_encode(['classes' => $classes]));

        $sorted = array_reverse($classes);

        $this->assertSame(0, $code, $output);
        $this->assertCount(150, $this->storedSurface()['classes']);
        $this->assertSame($sorted, $this->storedSurface()['classes']);
        $this->assertSame($sorted, json_decode($output, true)['surface']['classes']);
    }

    public static function invalidStandardInput(): array
    {
        return [
            'not json' => ['{"classes": [', null, null],
            'empty' => ['', null, null],
            'not an object' => ['["Modules\\\\Blog\\\\Models\\\\Post"]', null, null],
            'unknown key' => ['{"classes": [], "views": []}', null, 'views'],
            'list is an object' => ['{"classes": {"a": "Modules\\\\Blog"}}', 'classes', null],
            'list is a string' => ['{"routes": "api/posts"}', 'routes', null],
            'entry is not a string' => ['{"commands": ["blog:publish", 3]}', 'commands', null],
        ];
    }

    #[DataProvider('invalidStandardInput')]
    public function test_it_rejects_invalid_standard_input(string $stdin, ?string $list, ?string $value)
    {
        $before = file_get_contents($this->path());

        [$code, $output] = $this->surfaceWithStdin(['--json' => true], $stdin);
        $document = json_decode($output, true);

        $this->assertSame(2, $code);
        $this->assertSame('Blog', $document['module']);
        $this->assertCount(1, $document['errors']);
        $this->assertSame($list, $document['errors'][0]['list']);
        $this->assertSame($value, $document['errors'][0]['value']);
        $this->assertSame($before, file_get_contents($this->path()));
    }

    public function test_command_is_an_option_on_the_command_line()
    {
        $output = new BufferedOutput;

        $code = $this->app[Kernel::class]->handle(new ArgvInput([
            'artisan', 'module:surface', 'Blog', '--set', '--command', 'blog:publish', '--command=blog:sync', '--route', 'api/posts', '--json',
        ]), $output);

        $this->assertSame(0, $code);
        $this->assertSame(
            ['classes' => [], 'routes' => ['api/posts'], 'commands' => ['blog:publish', 'blog:sync']],
            json_decode($output->fetch(), true)['surface'],
        );
    }

    public function test_it_clears_the_surface()
    {
        file_put_contents($this->path(), self::MODULE_JSON);
        $this->surface(['--set' => true, '--class' => ['Modules\Blog\Models\Post']]);

        [$code, $output] = $this->surface(['--clear' => true, '--json' => true]);

        $this->assertSame(0, $code);
        $this->assertSame(['module' => 'Blog', 'path' => $this->path(), 'surface' => null, 'changed' => true], json_decode($output, true));
        $this->assertSame(self::MODULE_JSON, file_get_contents($this->path()));
    }

    public function test_clearing_a_missing_surface_changes_nothing()
    {
        $before = file_get_contents($this->path());

        [$code, $output] = $this->surface(['--clear' => true, '--json' => true]);

        $this->assertSame(0, $code);
        $this->assertFalse(json_decode($output, true)['changed']);
        $this->assertSame($before, file_get_contents($this->path()));

        [$code, $output] = $this->surface(['--clear' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Module [Blog] has no surface stored. Nothing to remove.', $output);
    }

    public function test_the_module_returns_the_new_surface_in_the_same_process()
    {
        $module = $this->app[RepositoryInterface::class]->find('Blog');
        $this->assertNull($module->get('surface'));

        $this->surface(['--set' => true, '--command' => ['blog:publish']]);

        $surface = ['classes' => [], 'routes' => [], 'commands' => ['blog:publish']];
        $this->assertSame($surface, $module->get('surface'));
        $this->assertSame($surface, $this->app[RepositoryInterface::class]->find('Blog')->get('surface'));
        $this->assertSame($surface, module('Blog', true)->get('surface'));

        $this->surface(['--clear' => true]);

        $this->assertNull($this->app[RepositoryInterface::class]->find('Blog')->get('surface'));
    }

    public function test_an_unknown_module_fails()
    {
        [$code, $output] = $this->surface(['module' => 'Shop', '--json' => true]);

        $this->assertSame(1, $code);
        $this->assertSame(
            ['module' => 'Shop', 'errors' => [['list' => null, 'value' => null, 'message' => 'Module [Shop] does not exist.']]],
            json_decode($output, true),
        );
    }

    public function test_an_invalid_module_json_fails()
    {
        // The repository already read the file, so the command finds the module and reads the broken file.
        file_put_contents($this->path(), '{"name": "Blog",');

        [$code, $output] = $this->surface(['--set' => true, '--json' => true]);

        $this->assertSame(1, $code);
        $this->assertStringStartsWith('Could not read '.$this->path(), json_decode($output, true)['errors'][0]['message']);
        $this->assertSame('{"name": "Blog",', file_get_contents($this->path()));
    }

    public function test_an_unreadable_module_json_fails()
    {
        $this->skipUnlessPermissionsApply();

        chmod($this->path(), 0000);

        try {
            [$code, $output] = $this->surface(['--json' => true]);
        } finally {
            chmod($this->path(), 0644);
        }

        $this->assertSame(1, $code);
        $this->assertStringStartsWith('Could not read '.$this->path(), json_decode($output, true)['errors'][0]['message']);
    }

    public function test_an_unreadable_module_json_found_by_a_new_scan_fails()
    {
        $this->skipUnlessPermissionsApply();

        chmod($this->path(), 0000);

        try {
            // The lookup scans the module.json files again and cannot read this one.
            $this->app[RepositoryInterface::class]->resetModules();

            [$code, $output] = $this->surface(['--json' => true]);
        } finally {
            chmod($this->path(), 0644);
            $this->app[RepositoryInterface::class]->resetModules();
        }

        $document = json_decode($output, true);

        $this->assertSame(1, $code);
        $this->assertSame('Blog', $document['module']);
        $this->assertNull($document['errors'][0]['list']);
        $this->assertStringContainsString('Permission denied', $document['errors'][0]['message']);
    }

    public function test_an_invalid_module_json_found_by_a_new_scan_fails()
    {
        $before = file_get_contents($this->path());
        file_put_contents($this->path(), '{"name": "Blog",');

        try {
            $this->app[RepositoryInterface::class]->resetModules();

            [$code, $output] = $this->surface(['--set' => true, '--route' => ['q'], '--json' => true]);
            $after = file_get_contents($this->path());
        } finally {
            file_put_contents($this->path(), $before);
            $this->app[RepositoryInterface::class]->resetModules();
        }

        $this->assertSame(1, $code);
        $this->assertStringStartsWith('Error processing file: '.$this->path(), json_decode($output, true)['errors'][0]['message']);
        $this->assertSame('{"name": "Blog",', $after);
    }

    public function test_a_module_skipped_as_broken_fails_with_the_reason()
    {
        $this->app['config']->set('modules.skip_broken_modules', true);

        $before = file_get_contents($this->path());
        file_put_contents($this->path(), '{"name": "Blog",');

        try {
            $this->app[RepositoryInterface::class]->resetModules();

            [$code, $output] = $this->surface(['--json' => true]);
        } finally {
            file_put_contents($this->path(), $before);
            $this->app[RepositoryInterface::class]->resetModules();
        }

        $this->assertSame(1, $code);
        $this->assertStringStartsWith(
            'Module [Blog] was skipped because it is broken: Error processing file: ',
            json_decode($output, true)['errors'][0]['message'],
        );
    }

    public static function invalidEntries(): array
    {
        return [
            'class with two leading backslashes' => ['--class', '\\\\Modules\Blog\Models\Post'],
            'class starting with a digit' => ['--class', 'Modules\1Blog'],
            'class with a trailing backslash' => ['--class', 'Modules\Blog\\'],
            'class with a wildcard' => ['--class', 'Modules\Blog\*'],
            'class with a space' => ['--class', 'Modules\Blog Post'],
            'class with a trailing newline' => ['--class', "Modules\\Blog\n"],
            'empty class' => ['--class', ''],
            'empty route' => ['--route', ''],
            'route with a space' => ['--route', 'api/my posts'],
            'route with a leading space' => ['--route', ' api/posts'],
            'command with an empty segment' => ['--command', 'blog::publish'],
            'command ending with a colon' => ['--command', 'blog:'],
            'command with a space' => ['--command', 'blog publish'],
            'command with a trailing newline' => ['--command', "blog:publish\n"],
        ];
    }

    #[DataProvider('invalidEntries')]
    public function test_it_rejects_an_invalid_entry_and_writes_nothing(string $option, string $entry)
    {
        $before = file_get_contents($this->path());

        [$code, $output] = $this->surface([
            '--set' => true,
            '--class' => ['Modules\Blog\Models\Post'],
            $option => [$entry, ['--class' => 'Modules\Blog\Post', '--route' => 'api/posts', '--command' => 'blog:publish'][$option]],
            '--json' => true,
        ]);
        $document = json_decode($output, true);

        $this->assertSame(2, $code);
        $this->assertCount(1, $document['errors']);
        $this->assertSame(['--class' => 'classes', '--route' => 'routes', '--command' => 'commands'][$option], $document['errors'][0]['list']);
        $this->assertSame($entry, $document['errors'][0]['value']);
        $this->assertSame($before, file_get_contents($this->path()));
    }

    public function test_it_reports_every_invalid_entry()
    {
        [$code, $output] = $this->surface([
            '--set' => true,
            '--class' => ['1Foo', 'Modules\Blog\Models\Post', 'Foo\\'],
            '--route' => ['api posts'],
            '--command' => ['a::b', 'blog <fg=red>x</>'],
        ]);

        $this->assertSame(2, $code);
        $this->assertStringContainsString('"1Foo" is not a fully qualified class name.', $output);
        $this->assertStringContainsString('"Foo\" is not a fully qualified class name.', $output);
        $this->assertStringContainsString('The route "api posts" contains whitespace.', $output);
        $this->assertStringContainsString('"a::b" is not a valid command name.', $output);
        $this->assertStringContainsString('"blog <fg=red>x</>" is not a valid command name.', $output);
        $this->assertNull($this->storedSurface());
    }

    public static function conflictingOptions(): array
    {
        return [
            'set and clear' => [['--set' => true, '--clear' => true]],
            'class without set' => [['--class' => ['Modules\Blog\Models\Post']]],
            'command with clear' => [['--clear' => true, '--command' => ['blog:publish']]],
            'stdin without set' => [['--stdin' => true]],
            'stdin and a list option' => [['--set' => true, '--stdin' => true, '--route' => ['api/posts']]],
        ];
    }

    #[DataProvider('conflictingOptions')]
    public function test_conflicting_options_are_a_usage_error(array $options)
    {
        $before = file_get_contents($this->path());

        [$code, $output] = $this->surface([...$options, '--json' => true]);
        $document = json_decode($output, true);

        $this->assertSame(2, $code);
        $this->assertSame('Blog', $document['module']);
        $this->assertNull($document['errors'][0]['list']);
        $this->assertSame($before, file_get_contents($this->path()));
    }

    private function skipUnlessPermissionsApply(): void
    {
        if (PHP_OS_FAMILY === 'Windows' || (function_exists('posix_geteuid') && posix_geteuid() === 0)) {
            $this->markTestSkipped('File permissions do not stop this user from reading files.');
        }
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function surface(array $parameters): array
    {
        $code = Artisan::call('module:surface', ['module' => 'Blog', ...$parameters]);

        return [$code, Artisan::output()];
    }

    /**
     * Run the command with --set --stdin through the console kernel, with the given text on standard input.
     *
     * @return array{0: int, 1: string}
     */
    private function surfaceWithStdin(array $parameters, string $stdin): array
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $stdin);
        rewind($stream);

        $input = new ArrayInput(['command' => 'module:surface', 'module' => 'Blog', '--set' => true, '--stdin' => true, ...$parameters]);
        $input->setStream($stream);

        $output = new BufferedOutput;
        $code = $this->app[Kernel::class]->handle($input, $output);

        return [$code, $output->fetch()];
    }

    private function path(): string
    {
        return base_path('modules/Blog/module.json');
    }

    private function storedSurface(): ?array
    {
        return json_decode(file_get_contents($this->path()), true)['surface'] ?? null;
    }
}
