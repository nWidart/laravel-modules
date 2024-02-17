<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Laravel\LaravelFileRepository;

it('binds repository interface with implementation', function () {
    expect(app(RepositoryInterface::class))->toBeInstanceOf(LaravelFileRepository::class);
});
