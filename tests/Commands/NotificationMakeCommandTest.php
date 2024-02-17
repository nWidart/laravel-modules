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

it('generates the notification class', function () {
    $code = $this->artisan('module:make-notification', ['name' => 'WelcomeNotification', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Notifications/WelcomeNotification.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-notification', ['name' => 'WelcomeNotification', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Notifications/WelcomeNotification.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.notifications.path', 'SuperNotifications');

    $code = $this->artisan('module:make-notification', ['name' => 'WelcomeNotification', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/SuperNotifications/WelcomeNotification.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.notifications.namespace', 'SuperNotifications');

    $code = $this->artisan('module:make-notification', ['name' => 'WelcomeNotification', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Notifications/WelcomeNotification.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
