<?php

namespace Commands;

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
