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

it('makes rule', function () {
    $code = $this->artisan('module:make-rule', ['name' => 'UniqueRule', 'module' => 'Blog']);

    $ruleFile = $this->modulePath . '/Rules/UniqueRule.php';

    expect(is_file($ruleFile))->toBeTrue('Rule file was not created.');
    $this->assertMatchesSnapshot($this->finder->get($ruleFile));
    expect($code)->toBe(0);
});

it('makes implicit rule', function () {
    $code = $this->artisan('module:make-rule', ['name' => 'ImplicitUniqueRule', 'module' => 'Blog', '--implicit' => true]);

    $ruleFile = $this->modulePath . '/Rules/ImplicitUniqueRule.php';

    expect(is_file($ruleFile))->toBeTrue('Rule file was not created.');
    $this->assertMatchesSnapshot($this->finder->get($ruleFile));
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.rules.path', 'SuperRules');

    $code = $this->artisan('module:make-rule', ['name' => 'UniqueRule', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/SuperRules/UniqueRule.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.rules.namespace', 'SuperRules');

    $code = $this->artisan('module:make-rule', ['name' => 'UniqueRule', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Rules/UniqueRule.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
