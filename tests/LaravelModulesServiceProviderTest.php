<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidActivatorClass;


it('binds modules key to repository class', function () {
    expect(app(RepositoryInterface::class))->toBeInstanceOf(RepositoryInterface::class);
    expect(app('modules'))->toBeInstanceOf(RepositoryInterface::class);
});

it('binds activator to activator class', function () {
    expect(app(ActivatorInterface::class))->toBeInstanceOf(ActivatorInterface::class);
});

it('throws exception if config is invalid', function () {
    $this->expectException(InvalidActivatorClass::class);

    $this->app['config']->set('modules.activators.file', ['class' => null]);

    expect(app(ActivatorInterface::class))->toBeInstanceOf(ActivatorInterface::class);
});
