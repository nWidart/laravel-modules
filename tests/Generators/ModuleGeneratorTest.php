<?php

namespace Nwidart\Modules\Tests\Generators;

use Nwidart\Modules\Generators\ModuleGenerator;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Support\Fixtures\StaticValueReplacement;

class ModuleGeneratorTest extends BaseTestCase
{
    public function test_get_module_returns_the_repository_module_make_sets()
    {
        $repository = $this->app['modules'];

        // ModuleMakeCommand builds the generator this way.
        $generator = (new ModuleGenerator('Blog'))->setModule($repository);

        $this->assertSame($repository, $generator->getModule());
    }

    public function test_get_module_returns_null_without_a_repository()
    {
        $this->assertNull((new ModuleGenerator('Blog'))->getModule());
    }

    public function test_the_fallback_keys_are_replaced_once_when_the_config_lists_them()
    {
        $generator = $this->countingGenerator();

        $generator->replacementsFor('composer');
        $generator->replacementsFor('routes/web');
        $generator->replacementsFor('routes/api');

        $this->assertSame(['APP_FOLDER_NAME' => 1, 'PLURAL_LOWER_NAME' => 2], $generator->calls);
    }

    public function test_the_fallback_keys_are_added_when_an_old_config_lacks_them()
    {
        $this->app['config']->set('modules.stubs.replacements.composer', ['LOWER_NAME']);
        $this->app['config']->set('modules.stubs.replacements.routes/web', ['LOWER_NAME']);
        $this->app['config']->set('modules.stubs.replacements.routes/api', ['LOWER_NAME']);
        $generator = $this->countingGenerator();

        $this->assertArrayHasKey('APP_FOLDER_NAME', $generator->replacementsFor('composer'));
        $this->assertArrayHasKey('PLURAL_LOWER_NAME', $generator->replacementsFor('routes/web'));
        $this->assertArrayHasKey('PLURAL_LOWER_NAME', $generator->replacementsFor('routes/api'));
        $this->assertSame(['APP_FOLDER_NAME' => 1, 'PLURAL_LOWER_NAME' => 2], $generator->calls);
    }

    public function test_a_fallback_key_mapped_to_a_class_keeps_the_class_value()
    {
        $this->app['config']->set('modules.stubs.replacements.composer', [
            'LOWER_NAME',
            'APP_FOLDER_NAME' => StaticValueReplacement::class,
        ]);
        $generator = $this->countingGenerator();

        $replaces = $generator->replacementsFor('composer');

        $this->assertSame(StaticValueReplacement::VALUE, $replaces['APP_FOLDER_NAME']);
        $this->assertSame([], $generator->calls);
    }

    /**
     * A generator built like ModuleMakeCommand's that counts the fallback replacement calls.
     */
    private function countingGenerator(): ModuleGenerator
    {
        $generator = new class('Blog') extends ModuleGenerator
        {
            /** @var array<string, int> */
            public array $calls = [];

            /**
             * @return array<string, string|null>
             */
            public function replacementsFor(string $stub): array
            {
                return $this->getReplacement($stub);
            }

            protected function getAppFolderNameReplacement(): string
            {
                $this->calls['APP_FOLDER_NAME'] = ($this->calls['APP_FOLDER_NAME'] ?? 0) + 1;

                return parent::getAppFolderNameReplacement();
            }

            protected function getPluralLowerNameReplacement(): string
            {
                $this->calls['PLURAL_LOWER_NAME'] = ($this->calls['PLURAL_LOWER_NAME'] ?? 0) + 1;

                return parent::getPluralLowerNameReplacement();
            }
        };

        return $generator->setModule($this->app['modules'])->setAuthor();
    }
}
