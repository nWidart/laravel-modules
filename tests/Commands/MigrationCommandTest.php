<?php

namespace Nwidart\Modules\tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class MigrationCommandTest extends BaseTestCase
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;
    /**
     * @var string
     */
    private $modulePath;

    public function setUp()
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown()
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_generates_a_new_migration_class()
    {
        $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);

        $files = $this->finder->allFiles($this->modulePath . '/Database/Migrations');

        $this->assertCount(1, $files);
    }

    /** @test */
    public function it_generates_correct_create_migration_file_content()
    {
        $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

        $this->assertEquals($this->expectedCreateMigrationContent(), $file);
    }

    /** @test */
    public function it_generates_correct_add_migration_file_content()
    {
        $this->artisan('module:make-migration', ['name' => 'add_something_to_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

        $this->assertEquals($this->expectedAddMigrationContent(), $file);
    }

    /** @test */
    public function it_generates_correct_delete_migration_file_content()
    {
        $this->artisan('module:make-migration', ['name' => 'delete_something_from_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

        $this->assertEquals($this->expectedDeleteMigrationContent(), $file);
    }

    /** @test */
    public function it_generates_correct_drop_migration_file_content()
    {
        $this->artisan('module:make-migration', ['name' => 'drop_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

        $this->assertEquals($this->expectedDropMigrationContent(), $file);
    }

    /** @test */
    public function it_generates_correct_default_migration_file_content()
    {
        $this->artisan('module:make-migration', ['name' => 'something_random_name', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

        $this->assertEquals($this->expectedDefaultMigrationContent(), $file);
    }

    private function expectedCreateMigrationContent()
    {
        return <<<TEXT
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint \$table) {
            \$table->increments('id');

            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}

TEXT;
    }

    private function expectedAddMigrationContent()
    {
        return <<<TEXT
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomethingToPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('posts', function (Blueprint \$table) {

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('posts', function (Blueprint \$table) {

        });
    }
}

TEXT;
    }

    private function expectedDeleteMigrationContent()
    {
        return <<<TEXT
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteSomethingFromPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('posts', function (Blueprint \$table) {

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('posts', function (Blueprint \$table) {

        });
    }
}

TEXT;
    }

    private function expectedDropMigrationContent()
    {
        return <<<TEXT
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('posts');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('posts', function (Blueprint \$table) {
            \$table->increments('id');

            \$table->timestamps();
        });
    }
}

TEXT;
    }

    private function expectedDefaultMigrationContent()
    {
        return <<<TEXT
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SomethingRandomName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

TEXT;
    }
}
