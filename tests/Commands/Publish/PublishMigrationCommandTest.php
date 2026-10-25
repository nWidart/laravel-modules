<?php

namespace Nwidart\Modules\Tests\Commands\Publish;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class PublishMigrationCommandTest extends BaseTestCase
{
    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
        $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        $this->finder->delete($this->finder->allFiles(base_path('database/migrations')));
        parent::tearDown();
    }

    public function test_it_publishes_module_migrations()
    {
        $code = $this->artisan('module:publish-migration', ['module' => 'Blog']);

        $files = $this->finder->allFiles(base_path('database/migrations'));

        $this->assertCount(1, $files);
        $this->assertSame(0, $code);
    }

    public function test_it_keeps_edited_migrations_and_publishes_new_ones_when_published_again()
    {
        $this->finder->put($this->getModuleBasePath().'/'.config('modules.paths.generator.migration.path').'/.gitkeep', '');
        $this->artisan('module:publish-migration', ['module' => 'Blog']);

        $published = $this->finder->allFiles(base_path('database/migrations'))[0]->getPathname();
        $this->finder->put($published, '<?php // edited by the app');
        $this->artisan('module:make-migration', ['name' => 'create_comments_table', 'module' => 'Blog']);

        $code = $this->artisan('module:publish-migration', ['module' => 'Blog']);
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('already exists', $output);
        $this->assertStringContainsString('Use --force to overwrite them.', $output);
        $this->assertStringNotContainsString('.gitkeep', $output);

        $this->assertSame('<?php // edited by the app', $this->finder->get($published));
        $this->assertCount(2, $this->finder->allFiles(base_path('database/migrations')));
    }

    public function test_it_overwrites_edited_migrations_with_force()
    {
        $this->artisan('module:publish-migration', ['module' => 'Blog']);

        $published = $this->finder->allFiles(base_path('database/migrations'))[0]->getPathname();
        $this->finder->put($published, '<?php // edited by the app');

        $this->mockConsoleOutput = true;
        $this->artisan('module:publish-migration', ['module' => 'Blog', '--force' => true])
            ->doesntExpectOutputToContain('already exists')
            ->assertSuccessful();

        $this->assertStringContainsString("Schema::create('posts'", $this->finder->get($published));
    }
}
