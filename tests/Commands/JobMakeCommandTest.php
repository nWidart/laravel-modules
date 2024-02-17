<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Contracts\RepositoryInterface;

uses(\Spatie\Snapshots\MatchesSnapshots::class);

beforeEach(function () {
    $this->modulePath = base_path('modules/Blog');
    $this->finder = $this->app['files'];
    $this->artisan('module:make', ['name' => ['Blog']]);
});

afterEach(function () {
    $this->app[RepositoryInterface::class]->delete('Blog');
});

it('generates the job class', function () {
    $code = $this->artisan('module:make-job', ['name' => 'SomeJob', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Jobs/SomeJob.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-job', ['name' => 'SomeJob', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Jobs/SomeJob.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generated correct sync job file with content', function () {
    $code = $this->artisan('module:make-job', ['name' => 'SomeJob', 'module' => 'Blog', '--sync' => true]);

    $file = $this->finder->get($this->modulePath . '/Jobs/SomeJob.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.jobs.path', 'SuperJobs');

    $code = $this->artisan('module:make-job', ['name' => 'SomeJob', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/SuperJobs/SomeJob.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.jobs.namespace', 'SuperJobs');

    $code = $this->artisan('module:make-job', ['name' => 'SomeJob', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Jobs/SomeJob.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
