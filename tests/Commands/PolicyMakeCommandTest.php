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

it('makes policy', function () {
    $code = $this->artisan('module:make-policy', ['name' => 'PostPolicy', 'module' => 'Blog']);

    $policyFile = $this->modulePath . '/Policies/PostPolicy.php';

    expect(is_file($policyFile))->toBeTrue('Policy file was not created.');
    $this->assertMatchesSnapshot($this->finder->get($policyFile));
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.policies.path', 'SuperPolicies');

    $code = $this->artisan('module:make-policy', ['name' => 'PostPolicy', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/SuperPolicies/PostPolicy.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.policies.namespace', 'SuperPolicies');

    $code = $this->artisan('module:make-policy', ['name' => 'PostPolicy', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Policies/PostPolicy.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
