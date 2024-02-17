<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;

beforeEach(function () {
    $this->artisan('module:make', ['name' => ['Blog']]);
    $this->artisan('module:make', ['name' => ['Taxonomy']]);
});

afterEach(function () {
    $this->app[RepositoryInterface::class]->delete('Blog');
    $this->app[RepositoryInterface::class]->delete('Taxonomy');
});

it('enables a module', function () {
    /** @var Module $blogModule */
    $blogModule = $this->app[RepositoryInterface::class]->find('Blog');
    $blogModule->disable();

    $code = $this->artisan('module:enable', ['module' => 'Blog']);

    expect($blogModule->isEnabled())->toBeTrue();
    expect($code)->toBe(0);
});

it('enables all modules', function () {
    /** @var Module $blogModule */
    $blogModule = $this->app[RepositoryInterface::class]->find('Blog');
    $blogModule->disable();

    /** @var Module $taxonomyModule */
    $taxonomyModule = $this->app[RepositoryInterface::class]->find('Taxonomy');
    $taxonomyModule->disable();

    $code = $this->artisan('module:enable', ['--all' => true]);

    expect($blogModule->isEnabled() && $taxonomyModule->isEnabled())->toBeTrue();
    expect($code)->toBe(0);
});
