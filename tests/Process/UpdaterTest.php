<?php

namespace Nwidart\Modules\Tests\Process;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Process\Updater;
use Nwidart\Modules\Tests\BaseTestCase;
use Symfony\Component\Process\Process;

class UpdaterTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    private string $cwd;

    private string $composerJson;

    private string $marker;

    protected function setUp(): void
    {
        parent::setUp();

        // Updater::update() changes the working directory and rewrites the app composer.json.
        $this->cwd = getcwd();
        $this->composerJson = file_get_contents(base_path('composer.json'));
        $this->marker = sys_get_temp_dir().DIRECTORY_SEPARATOR.'laravel-modules-pwned-'.bin2hex(random_bytes(6));

        // Keep the commands free of the trailing " --quiet" flag.
        config(['modules.composer.composer-output' => true]);

        $this->createModule('Blog');
        $this->repository = $this->app[RepositoryInterface::class];
    }

    protected function tearDown(): void
    {
        $this->repository->delete('Blog');
        file_put_contents(base_path('composer.json'), $this->composerJson);
        chdir($this->cwd);
        @unlink($this->marker);

        parent::tearDown();
    }

    public function test_it_escapes_malicious_package_names_and_versions()
    {
        $packages = [
            'acme/foo";touch '.$this->marker.';"' => '^1.0',
            'acme/bar' => '^2.0 || $(touch '.$this->marker.')',
        ];
        $devPackages = [
            "acme/baz';touch {$this->marker};'" => '`touch '.$this->marker.'`',
        ];

        $this->writeModuleComposer(['require' => $packages, 'require-dev' => $devPackages]);

        $commands = $this->runUpdater();

        $this->assertCount(2, $commands);
        $this->assertStringStartsWith('composer require ', $commands[0]);
        $this->assertStringStartsWith('composer require --dev ', $commands[1]);

        // Run the captured commands through a real shell, with composer swapped for printf,
        // to prove every "name:version" reaches composer as exactly one argument.
        $this->assertSame(
            ['acme/foo";touch '.$this->marker.';":^1.0', 'acme/bar:^2.0 || $(touch '.$this->marker.')'],
            $this->shellArguments(substr($commands[0], strlen('composer require ')))
        );
        $this->assertSame(
            ["acme/baz';touch {$this->marker};':`touch {$this->marker}`"],
            $this->shellArguments(substr($commands[1], strlen('composer require --dev ')))
        );
        $this->assertFileDoesNotExist($this->marker);
    }

    public function test_it_keeps_regular_version_constraints_intact()
    {
        $this->writeModuleComposer(['require' => ['acme/foo' => '^1.0 || ^2.0', 'acme/bar' => 'dev-main']]);

        $commands = $this->runUpdater();

        $this->assertSame(["composer require 'acme/foo:^1.0 || ^2.0' 'acme/bar:dev-main' "], $commands);
    }

    public function test_it_runs_nothing_without_requirements()
    {
        $this->writeModuleComposer([]);

        $this->assertSame([], $this->runUpdater());
    }

    public function test_it_adds_a_scripts_section_when_the_app_composer_json_has_none()
    {
        $this->writeAppComposer(['name' => 'acme/app', 'require-dev' => new \stdClass]);
        $this->writeModuleComposer(['scripts' => ['post-update-cmd' => ['@php artisan blog:sync']]]);

        $this->runUpdater();

        $composer = $this->readAppComposer();
        $this->assertSame(['post-update-cmd' => ['@php artisan blog:sync']], $composer['scripts']);
        // An empty object stays an object.
        $this->assertStringContainsString('"require-dev": {}', file_get_contents(base_path('composer.json')));
    }

    public function test_it_merges_scripts_defined_as_strings()
    {
        $this->writeAppComposer(['scripts' => [
            'post-update-cmd' => '@php artisan vendor:publish',
            'test' => 'phpunit',
        ]]);
        $this->writeModuleComposer(['scripts' => [
            'post-update-cmd' => '@php artisan blog:sync',
            'test' => ['phpunit', 'pint --test'],
            'blog' => '@php artisan blog:run',
        ]]);

        $this->runUpdater();

        $this->assertSame([
            'post-update-cmd' => ['@php artisan vendor:publish', '@php artisan blog:sync'],
            'test' => ['phpunit', 'pint --test'],
            'blog' => '@php artisan blog:run',
        ], $this->readAppComposer()['scripts']);
    }

    public function test_merged_scripts_are_written_as_a_json_list()
    {
        $this->writeAppComposer(['scripts' => ['post-autoload-dump' => ['@php artisan package:discover', '@php artisan blog:sync']]]);
        $this->writeModuleComposer(['scripts' => ['post-autoload-dump' => ['@php artisan package:discover', '@php artisan blog:cache']]]);

        $this->runUpdater();

        $contents = file_get_contents(base_path('composer.json'));
        $this->assertSame(
            ['@php artisan package:discover', '@php artisan blog:sync', '@php artisan blog:cache'],
            $this->readAppComposer()['scripts']['post-autoload-dump']
        );
        $this->assertStringNotContainsString('"2":', $contents);
        $this->assertStringEndsWith("}\n", $contents);
    }

    public function test_it_leaves_the_app_composer_json_untouched_without_module_scripts()
    {
        $this->writeAppComposer(['name' => 'acme/app', 'require-dev' => new \stdClass]);
        $before = file_get_contents(base_path('composer.json'));
        $this->writeModuleComposer([]);

        $this->runUpdater();

        $this->assertSame($before, file_get_contents(base_path('composer.json')));
    }

    private function writeAppComposer(array $attributes): void
    {
        file_put_contents(base_path('composer.json'), json_encode($attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function readAppComposer(): array
    {
        return json_decode(file_get_contents(base_path('composer.json')), true);
    }

    private function writeModuleComposer(array $attributes): void
    {
        file_put_contents(
            $this->repository->getModulePath('Blog').'composer.json',
            json_encode(array_merge(['name' => 'nwidart/blog'], $attributes))
        );
    }

    /**
     * @return list<string>
     */
    private function runUpdater(): array
    {
        $updater = new class($this->repository) extends Updater
        {
            /** @var list<string> */
            public array $commands = [];

            public function run(string $command)
            {
                $this->commands[] = $command;
            }
        };

        $updater->update('Blog');

        return $updater->commands;
    }

    /**
     * @return list<string>
     */
    private function shellArguments(string $arguments): array
    {
        $process = Process::fromShellCommandline("printf '%s\\n' {$arguments}");
        $process->mustRun();

        return explode("\n", rtrim($process->getOutput(), "\n"));
    }
}
