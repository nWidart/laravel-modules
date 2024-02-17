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

it('generates a new event class', function () {
    $code = $this->artisan(
        'module:make-listener',
        ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated']
    );

    expect(is_file($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct sync event with content', function () {
    $code = $this->artisan(
        'module:make-listener',
        ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated']
    );

    $file = $this->finder->get($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generated correct sync event in a subdirectory with content', function () {
    $code = $this->artisan(
        'module:make-listener',
        ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'User/WasCreated']
    );

    $file = $this->finder->get($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generated correct sync duck event with content', function () {
    $code = $this->artisan(
        'module:make-listener',
        ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog']
    );

    $file = $this->finder->get($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generated correct queued event with content', function () {
    $code = $this->artisan(
        'module:make-listener',
        ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated', '--queued' => true]
    );

    $file = $this->finder->get($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generated correct queued event in a subdirectory with content', function () {
    $code = $this->artisan(
        'module:make-listener',
        ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'User/WasCreated', '--queued' => true]
    );

    $file = $this->finder->get($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generated correct queued duck event with content', function () {
    $code = $this->artisan(
        'module:make-listener',
        ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--queued' => true]
    );

    $file = $this->finder->get($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.listener.path', 'Events/Handlers');

    $code = $this->artisan(
        'module:make-listener',
        ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog']
    );

    $file = $this->finder->get($this->modulePath . '/Events/Handlers/NotifyUsersOfANewPost.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.listener.namespace', 'Events\\Handlers');

    $code = $this->artisan(
        'module:make-listener',
        ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog']
    );

    $file = $this->finder->get($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
