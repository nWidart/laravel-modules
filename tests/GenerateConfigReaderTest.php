<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Config\GeneratorPath;

it('can read a configuration value with new format', function () {
    $seedConfig = GenerateConfigReader::read('seeder');

    expect($seedConfig)->toBeInstanceOf(GeneratorPath::class);
    expect($seedConfig->getPath())->toEqual('Database/Seeders');
    expect($seedConfig->generate())->toBeTrue();
});

it('can read a configuration value with new format set to false', function () {
    $this->app['config']->set('modules.paths.generator.seeder', ['path' => 'Database/Seeders', 'generate' => false]);

    $seedConfig = GenerateConfigReader::read('seeder');

    expect($seedConfig)->toBeInstanceOf(GeneratorPath::class);
    expect($seedConfig->getPath())->toEqual('Database/Seeders');
    expect($seedConfig->generate())->toBeFalse();
});

it('can read a configuration value with old format', function () {
    $this->app['config']->set('modules.paths.generator.seeder', 'Database/Seeders');

    $seedConfig = GenerateConfigReader::read('seeder');

    expect($seedConfig)->toBeInstanceOf(GeneratorPath::class);
    expect($seedConfig->getPath())->toEqual('Database/Seeders');
    expect($seedConfig->generate())->toBeTrue();
});

it('can read a configuration value with old format set to false', function () {
    $this->app['config']->set('modules.paths.generator.seeder', false);

    $seedConfig = GenerateConfigReader::read('seeder');

    expect($seedConfig)->toBeInstanceOf(GeneratorPath::class);
    expect($seedConfig->getPath())->toBeFalse();
    expect($seedConfig->generate())->toBeFalse();
});

it('can guess namespace from path', function () {
    $this->app['config']->set('modules.paths.generator.provider', ['path' => 'Base/Providers', 'generate' => true]);

    $config = GenerateConfigReader::read('provider');

    expect($config->getPath())->toEqual('Base/Providers');
    expect($config->getNamespace())->toEqual('Base\Providers');
});
