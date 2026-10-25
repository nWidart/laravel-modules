<?php

namespace Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Tests\BaseTestCase;

class UpdatePhpunitCoverageTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (file_exists(base_path('modules_statuses.json'))) {
            unlink(base_path('modules_statuses.json'));
        }

        if (file_exists(base_path('phpunit.xml'))) {
            unlink(base_path('phpunit.xml'));
        }
    }

    public function test_no_phpunit_file()
    {
        $code = $this->artisan('module:update-phpunit-coverage');

        $this->assertSame(100, $code);
    }

    public function test_no_modules_statuses_file()
    {
        $this->makePhpunit();

        $code = $this->artisan('module:update-phpunit-coverage');

        $this->assertSame(99, $code);
    }

    protected function tearDown(): void
    {
        foreach (['Blog', 'Shop', 'Old'] as $module) {
            (new Filesystem)->deleteDirectory(base_path("modules/{$module}"));
        }

        @unlink(base_path('modules_statuses.json'));
        @unlink(base_path('phpunit.xml'));

        parent::tearDown();
    }

    public function test_it_keeps_user_entries_and_only_manages_module_entries()
    {
        $this->makeModuleAppFolders('Blog', 'Shop', 'Old');
        $this->MakeModulesStatuses(json_encode(['Blog' => true, 'Shop' => false]));
        $this->writePhpunitSource(<<<'XML'
  <source>
    <include>
      <directory>app</directory>
      <directory suffix=".php">./src/Support</directory>
      <directory suffix=".php">./modules/Shop/app/</directory>
      <directory suffix=".php">./modules/Old/app</directory>
      <directory>./modules/Shop/routes</directory>
    </include>
    <exclude>
      <directory>./app/Legacy</directory>
    </exclude>
  </source>
XML);

        $this->assertSame(0, $this->artisan('module:update-phpunit-coverage'));

        $this->assertSame([
            ['app', null],
            ['./src/Support', '.php'],
            ['./modules/Shop/routes', null],
            ['./modules/Blog/app/', '.php'],
        ], $this->includedDirectories());
        $this->assertStringContainsString('<directory>./app/Legacy</directory>', file_get_contents(base_path('phpunit.xml')));
    }

    public function test_it_keeps_an_existing_entry_of_an_enabled_module_and_runs_idempotently()
    {
        $this->makeModuleAppFolders('Blog', 'Shop');
        $this->MakeModulesStatuses(json_encode(['Blog' => true, 'Shop' => true]));
        $this->writePhpunitSource(<<<'XML'
  <source>
    <include>
      <directory>app</directory>
      <directory suffix="Model.php">modules/Blog/app</directory>
    </include>
  </source>
XML);

        $this->artisan('module:update-phpunit-coverage');
        $first = file_get_contents(base_path('phpunit.xml'));
        $this->artisan('module:update-phpunit-coverage');

        $this->assertSame($first, file_get_contents(base_path('phpunit.xml')));
        $this->assertSame([
            ['app', null],
            ['modules/Blog/app', 'Model.php'],
            ['./modules/Shop/app/', '.php'],
        ], $this->includedDirectories());
    }

    public function test_it_creates_the_source_element_when_it_is_missing()
    {
        $this->makeModuleAppFolders('Blog');
        $this->MakeModulesStatuses(json_encode(['Blog' => true]));
        $this->writePhpunitSource('');

        $this->assertSame(0, $this->artisan('module:update-phpunit-coverage'));

        $this->assertSame([
            ['./app', null],
            ['./modules/Blog/app/', '.php'],
        ], $this->includedDirectories());
    }

    public function test_it_creates_the_include_element_when_source_has_none()
    {
        $this->makeModuleAppFolders('Blog');
        $this->MakeModulesStatuses(json_encode(['Blog' => true]));
        $this->writePhpunitSource(<<<'XML'
  <source>
    <exclude>
      <directory>./app/Legacy</directory>
    </exclude>
  </source>
XML);

        $this->assertSame(0, $this->artisan('module:update-phpunit-coverage'));

        $this->assertSame([
            ['./app', null],
            ['./modules/Blog/app/', '.php'],
        ], $this->includedDirectories());
        $this->assertStringContainsString('<directory>./app/Legacy</directory>', file_get_contents(base_path('phpunit.xml')));
    }

    public function test_it_only_changes_the_added_and_removed_lines()
    {
        $this->makeModuleAppFolders('Blog', 'Shop');
        $this->MakeModulesStatuses(json_encode(['Blog' => true, 'Shop' => false]));
        file_put_contents(base_path('phpunit.xml'), $this->formattedPhpunit(<<<'XML'
    <source>
        <include>
            <!-- The application code. -->
            <directory>app</directory>
            <directory suffix='.php'>./src</directory>
            <directory
                suffix="Test.php"
            >./lib</directory>
            <directory suffix=".php">./modules/Shop/app/</directory>
        </include>
    </source>

XML));

        $this->assertSame(0, $this->artisan('module:update-phpunit-coverage'));

        $this->assertSame($this->formattedPhpunit(<<<'XML'
    <source>
        <include>
            <!-- The application code. -->
            <directory>app</directory>
            <directory suffix='.php'>./src</directory>
            <directory
                suffix="Test.php"
            >./lib</directory>
            <directory suffix=".php">./modules/Blog/app/</directory>
        </include>
    </source>

XML), file_get_contents(base_path('phpunit.xml')));
    }

    public function test_a_created_source_element_follows_the_file_indentation()
    {
        $this->makeModuleAppFolders('Blog');
        $this->MakeModulesStatuses(json_encode(['Blog' => true]));
        file_put_contents(base_path('phpunit.xml'), $this->formattedPhpunit(''));

        $this->assertSame(0, $this->artisan('module:update-phpunit-coverage'));

        $this->assertSame($this->formattedPhpunit('', <<<'XML'

    <source>
        <include>
            <directory>./app</directory>
            <directory suffix=".php">./modules/Blog/app/</directory>
        </include>
    </source>
XML), file_get_contents(base_path('phpunit.xml')));
    }

    public function test_a_created_include_element_follows_the_file_indentation()
    {
        $this->makeModuleAppFolders('Blog');
        $this->MakeModulesStatuses(json_encode(['Blog' => true]));
        file_put_contents(base_path('phpunit.xml'), $this->formattedPhpunit(<<<'XML'
    <source>
        <exclude>
            <directory>./app/Legacy</directory>
        </exclude>
    </source>

XML));

        $this->assertSame(0, $this->artisan('module:update-phpunit-coverage'));

        $this->assertSame($this->formattedPhpunit(<<<'XML'
    <source>
        <include>
            <directory>./app</directory>
            <directory suffix=".php">./modules/Blog/app/</directory>
        </include>
        <exclude>
            <directory>./app/Legacy</directory>
        </exclude>
    </source>

XML), file_get_contents(base_path('phpunit.xml')));
    }

    /**
     * A phpunit.xml indented with 4 spaces, with a multi-line root tag.
     */
    private function formattedPhpunit(string $source, string $end = ''): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
{$source}    <php>
        <env name="APP_ENV" value="testing" />
    </php>{$end}
</phpunit>

XML;
    }

    public function test_it_warns_about_a_module_entry_it_cannot_remove()
    {
        $this->makeModuleAppFolders('Shop');
        $this->MakeModulesStatuses(json_encode(['Shop' => false]));
        $xml = $this->formattedPhpunit(<<<'XML'
    <source>
        <include>
            <directory>app</directory>
            <directory
                suffix=".php"
            >./modules/Shop/app/</directory>
        </include>
    </source>

XML);
        file_put_contents(base_path('phpunit.xml'), $xml);

        $this->assertSame(0, $this->artisan('module:update-phpunit-coverage'));

        $this->assertStringContainsString('Could not remove the entry [modules/Shop/app] from phpunit.xml', Artisan::output());
        $this->assertSame($xml, file_get_contents(base_path('phpunit.xml')));
    }

    public function test_invalid_phpunit_xml_fails_with_a_clear_message()
    {
        $this->MakeModulesStatuses(json_encode([]));
        file_put_contents(base_path('phpunit.xml'), '<phpunit><source>');

        $this->assertSame(97, $this->artisan('module:update-phpunit-coverage'));
        $this->assertStringContainsString('Error parsing XML from', Artisan::output());
    }

    private function makeModuleAppFolders(string ...$modules): void
    {
        foreach ($modules as $module) {
            mkdir(base_path("modules/{$module}/app"), 0777, true);
        }
    }

    private function writePhpunitSource(string $source): void
    {
        file_put_contents(base_path('phpunit.xml'), <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
  <testsuites>
    <testsuite name="Unit">
      <directory>tests/Unit</directory>
    </testsuite>
  </testsuites>
{$source}
</phpunit>
XML);
    }

    /**
     * @return list<array{0: string, 1: string|null}>
     */
    private function includedDirectories(): array
    {
        $xml = simplexml_load_file(base_path('phpunit.xml'));

        return array_map(
            fn ($directory) => [(string) $directory, isset($directory['suffix']) ? (string) $directory['suffix'] : null],
            $xml->xpath('/phpunit/source/include/directory')
        );
    }

    private function MakePhpunit()
    {
        $phpunit = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd" bootstrap="vendor/autoload.php" colors="true">
  <testsuites>
    <testsuite name="Modules">
      <directory suffix="Test.php">./Modules/*/Tests/Feature</directory>
      <directory suffix="Test.php">./Modules/*/Tests/Unit</directory>
    </testsuite>
  </testsuites>
  <source>
    <include>
      <directory>./app</directory>
    </include>
  </source>
  <php>
    <env name="APP_ENV" value="testing"/>
    <env name="BCRYPT_ROUNDS" value="4"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="PULSE_ENABLED" value="false"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="TELESCOPE_ENABLED" value="false"/>
  </php>
</phpunit>
XML;

        file_put_contents(base_path('phpunit.xml'), $phpunit);
    }

    private function MakeModulesStatuses($value)
    {
        $modulesStatusPath = base_path('modules_statuses.json');
        file_put_contents($modulesStatusPath, $value);
    }
}
