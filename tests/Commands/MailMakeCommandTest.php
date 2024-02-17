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

it('generates the mail class', function () {
    $code = $this->artisan('module:make-mail', ['name' => 'SomeMail', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Emails/SomeMail.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-mail', ['name' => 'SomeMail', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Emails/SomeMail.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.emails.path', 'SuperEmails');

    $code = $this->artisan('module:make-mail', ['name' => 'SomeMail', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/SuperEmails/SomeMail.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.emails.namespace', 'SuperEmails');

    $code = $this->artisan('module:make-mail', ['name' => 'SomeMail', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Emails/SomeMail.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
