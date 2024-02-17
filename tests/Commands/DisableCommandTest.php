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

it('disables a module', function () {
    /** @var Module $blogModule */
    $blogModule = $this->app[RepositoryInterface::class]->find('Blog');
    $blogModule->disable();

    $code = $this->artisan('module:disable', ['module' => ['Blog']]);

    expect($blogModule->isDisabled())->toBeTrue();
    expect($code)->toBe(0);
});

it('disables array of modules', function () {
    /** @var Module $blogModule */
    $blogModule = $this->app[RepositoryInterface::class]->find('Blog');
    $blogModule->enable();

    /** @var Module $taxonomyModule */
    $taxonomyModule = $this->app[RepositoryInterface::class]->find('Taxonomy');
    $taxonomyModule->enable();

    $code = $this->artisan('module:disable', ['module' => ['Blog','Taxonomy']]);

    expect($blogModule->isDisabled() && $taxonomyModule->isDisabled())->toBeTrue();
    expect($code)->toBe(0);
});

it('disables all modules', function () {
    /** @var Module $blogModule */
    $blogModule = $this->app[RepositoryInterface::class]->find('Blog');
    $blogModule->enable();

    /** @var Module $taxonomyModule */
    $taxonomyModule = $this->app[RepositoryInterface::class]->find('Taxonomy');
    $taxonomyModule->enable();

    $code = $this->artisan('module:disable', ['--all' => true]);

    expect($blogModule->isDisabled() && $taxonomyModule->isDisabled())->toBeTrue();
    expect($code)->toBe(0);
});
