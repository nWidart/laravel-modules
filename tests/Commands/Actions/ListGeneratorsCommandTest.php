<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Commands\Actions\ListGeneratorsCommand;
use Nwidart\Modules\Commands\Make\GeneratorCommand;
use Nwidart\Modules\Commands\Make\ModuleMakeCommand;
use Nwidart\Modules\Providers\ConsoleServiceProvider;
use Nwidart\Modules\Tests\BaseTestCase;

class ListGeneratorsCommandTest extends BaseTestCase
{
    private const GENERATOR_KEYS = ['name', 'group', 'description', 'arguments', 'options'];

    private const ARGUMENT_KEYS = ['name', 'required', 'array', 'default', 'description'];

    private const OPTION_KEYS = ['name', 'shortcut', 'accepts_value', 'value_required', 'array', 'negatable', 'default', 'description'];

    public function test_json_lists_every_package_generator()
    {
        $generators = $this->generators();
        $names = array_column($generators, 'name');

        $this->assertCount(43, $generators);
        $this->assertContains('module:make', $names);
        $this->assertContains('module:route-provider', $names);
        $this->assertNotContains('module:generators', $names);

        $sorted = $names;
        sort($sorted);
        $this->assertSame($sorted, $names);

        foreach ($generators as $generator) {
            $this->assertSame(self::GENERATOR_KEYS, array_keys($generator), $generator['name']);
            $this->assertSame(ListGeneratorsCommand::GROUPS[$generator['name']] ?? null, $generator['group'], $generator['name']);
            $this->assertNotSame(ListGeneratorsCommand::OTHER_GROUP, $generator['group'], $generator['name']);

            foreach ($generator['arguments'] as $argument) {
                $this->assertSame(self::ARGUMENT_KEYS, array_keys($argument), $generator['name']);
            }

            foreach ($generator['options'] as $option) {
                $this->assertSame(self::OPTION_KEYS, array_keys($option), $generator['name']);
            }
        }
    }

    public function test_every_package_generator_has_a_group()
    {
        $names = ConsoleServiceProvider::defaultCommands()
            ->filter(fn (string $class) => is_subclass_of($class, GeneratorCommand::class) || $class === ModuleMakeCommand::class)
            ->map(fn (string $class) => $this->app->make($class)->getName())
            ->sort()
            ->values()
            ->all();

        $this->assertCount(43, $names);

        foreach ($names as $name) {
            $this->assertArrayHasKey($name, ListGeneratorsCommand::GROUPS, "Generator {$name} has no group in ListGeneratorsCommand::GROUPS.");
        }

        $groups = array_keys(ListGeneratorsCommand::GROUPS);
        sort($groups);
        $this->assertSame($names, $groups, 'ListGeneratorsCommand::GROUPS lists a command that is not a package generator.');
    }

    public function test_json_describes_the_model_generator()
    {
        $model = $this->generator('module:make-model');

        $this->assertSame('data', $model['group']);
        $this->assertSame('Create a new model for the specified module.', $model['description']);

        $this->assertSame([
            ['name' => 'model', 'required' => true, 'array' => false, 'default' => null, 'description' => 'The name of model will be created.'],
            ['name' => 'module', 'required' => false, 'array' => false, 'default' => null, 'description' => 'The name of module will be used.'],
        ], $model['arguments']);

        $options = array_column($model['options'], null, 'name');

        $this->assertSame(
            ['name' => 'fillable', 'shortcut' => null, 'accepts_value' => true, 'value_required' => false, 'array' => false, 'negatable' => false, 'default' => null, 'description' => 'The fillable attributes.'],
            $options['fillable'],
        );

        // -f belongs to --factory on this generator, so --force has no shortcut.
        $this->assertSame('f', $options['factory']['shortcut']);
        $this->assertSame(
            ['name' => 'force', 'shortcut' => null, 'accepts_value' => false, 'value_required' => false, 'array' => false, 'negatable' => false, 'default' => false, 'description' => 'Overwrite the file if it already exists'],
            $options['force'],
        );

        // Definition order: the constructor adds --force after the command's own options.
        $this->assertSame('force', array_key_last($options));
    }

    public function test_json_lists_the_force_shortcut_when_it_is_free()
    {
        $options = array_column($this->generator('module:make-action')['options'], null, 'name');

        $this->assertSame('f', $options['force']['shortcut']);
        $this->assertFalse($options['force']['default']);
    }

    public function test_json_leaves_out_application_wide_options()
    {
        $applicationOptions = array_keys(Artisan::all()['module:generators']->getApplication()->getDefinition()->getOptions());

        $this->assertContains('help', $applicationOptions);
        $this->assertContains('env', $applicationOptions);

        foreach ($this->generators() as $generator) {
            $names = array_column($generator['options'], 'name');

            $this->assertSame([], array_values(array_intersect($names, $applicationOptions)), $generator['name']);
            $this->assertNotContains('command', array_column($generator['arguments'], 'name'), $generator['name']);
        }
    }

    public function test_json_output_is_only_one_line_of_json()
    {
        $code = Artisan::call('module:generators', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $this->assertStringEndsWith("}\n", $output);
        $this->assertSame(1, substr_count($output, "\n"));
        $this->assertIsArray(json_decode($output, true, flags: JSON_THROW_ON_ERROR));
    }

    public function test_json_output_has_no_ansi_codes_with_ansi()
    {
        Artisan::call('module:generators', ['--json' => true, '--ansi' => true]);
        $output = Artisan::output();

        $this->assertStringNotContainsString("\e[", $output);
        $this->assertCount(43, json_decode($output, true, flags: JSON_THROW_ON_ERROR)['generators']);
    }

    public function test_an_app_generator_is_listed_in_the_other_group()
    {
        $this->app[Kernel::class]->registerCommand(new AppWidgetMakeCommand);

        Artisan::call('module:generators', ['--json' => true, '--ansi' => true]);
        $output = Artisan::output();

        $generators = array_column(json_decode($output, true, flags: JSON_THROW_ON_ERROR)['generators'], null, 'name');

        $this->assertCount(44, $generators);
        $this->assertSame('other', $generators['app:make-widget']['group']);
        $this->assertSame('Create a <info>widget</info>.', $generators['app:make-widget']['description']);
        $this->assertSame(['name', 'module'], array_column($generators['app:make-widget']['arguments'], 'name'));
        $this->assertSame(['force'], array_column($generators['app:make-widget']['options'], 'name'));
        $this->assertStringNotContainsString("\e[", $output);
    }

    public function test_text_output_is_a_table()
    {
        $code = Artisan::call('module:generators');
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $this->assertMatchesRegularExpression('/\|\s+Command\s+\|\s+Group\s+\|\s+Description\s+\|/', $output);
        $this->assertMatchesRegularExpression('/\|\s+module:make-model\s+\|\s+data\s+\|\s+Create a new model for the specified module\.\s+\|/', $output);
        $this->assertMatchesRegularExpression('/\|\s+module:route-provider\s+\|\s+http\s+\|/', $output);
        $this->assertStringNotContainsString('"generators"', $output);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function generators(): array
    {
        Artisan::call('module:generators', ['--json' => true]);

        return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR)['generators'];
    }

    /**
     * @return array<string, mixed>
     */
    private function generator(string $name): array
    {
        return array_column($this->generators(), null, 'name')[$name];
    }
}

class AppWidgetMakeCommand extends GeneratorCommand
{
    protected $signature = 'app:make-widget {name} {module?}';

    protected $description = 'Create a <info>widget</info>.';

    protected function getTemplateContents()
    {
        return '';
    }

    protected function getDestinationFilePath()
    {
        return '';
    }
}
