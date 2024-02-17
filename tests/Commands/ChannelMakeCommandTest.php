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

it('generates the channel class', function () {
    $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Broadcasting/WelcomeChannel.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Broadcasting/WelcomeChannel.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.channels.path', 'SuperChannel');

    $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/SuperChannel/WelcomeChannel.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.channels.namespace', 'SuperChannel');

    $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Broadcasting/WelcomeChannel.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
