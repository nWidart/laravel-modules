<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Nwidart\Modules\Support\Stub;

class StubTest extends BaseTestCase
{
    /**
     * @var Filesystem
     */
    private $finder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->finder->delete([
            base_path('my-command.php'),
            base_path('stub-override-exists.php'),
            base_path('stub-override-not-exists.php'),
            base_path('stub-single-pass.stub'),
            base_path('stub-sections.stub'),
        ]);
    }

    public function test_it_initialises_a_stub_instance()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $this->assertTrue(Str::contains($stub->getPath(), 'src/Commands/stubs/model.stub'));
        $this->assertEquals(['NAME' => 'Name'], $stub->getReplaces());
    }

    public function test_it_sets_new_replaces_array()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->replace(['VENDOR' => 'MyVendor']);
        $this->assertEquals(['VENDOR' => 'MyVendor'], $stub->getReplaces());
    }

    public function test_it_stores_stub_to_specific_path()
    {
        $stub = new Stub('/command.stub', [
            'COMMAND_NAME' => 'my:command',
            'NAMESPACE' => 'Blog\Commands',
            'CLASS' => 'MyCommand',
        ]);

        $stub->saveTo(base_path(), 'my-command.php');

        $this->assertTrue($this->finder->exists(base_path('my-command.php')));
    }

    public function test_it_sets_new_path()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->setPath('/new-path/');

        $this->assertTrue(Str::contains($stub->getPath(), 'Commands/stubs/new-path/'));
    }

    public function test_use_default_stub_if_override_not_exists()
    {
        $stub = new Stub('/command.stub', [
            'COMMAND_NAME' => 'my:command',
            'NAMESPACE' => 'Blog\Commands',
            'CLASS' => 'MyCommand',
        ]);

        $stub->setBasePath(__DIR__.'/stubs');

        $stub->saveTo(base_path(), 'stub-override-not-exists.php');

        $this->assertTrue($this->finder->exists(base_path('stub-override-not-exists.php')));
    }

    public function test_use_override_stub_if_exists()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->setBasePath(__DIR__.'/stubs');

        $stub->saveTo(base_path(), 'stub-override-exists.php');

        $this->assertTrue($this->finder->exists(base_path('stub-override-exists.php')));
        $this->assertEquals('stub-override', $this->finder->get(base_path('stub-override-exists.php')));
    }

    public function test_it_does_not_replace_tokens_inside_replacement_values()
    {
        $this->finder->put(base_path('stub-single-pass.stub'), '$FIRST$ $SECOND$');
        Stub::setBasePath(base_path());

        $stub = new Stub('/stub-single-pass.stub', [
            'FIRST' => 'literal $SECOND$',
            'SECOND' => 'second',
        ]);

        $this->assertSame('literal $SECOND$ second', $stub->render());

        $stub->replace([
            'SECOND' => 'second',
            'FIRST' => 'literal $SECOND$',
        ]);

        $this->assertSame('literal $SECOND$ second', $stub->render());
    }

    public function test_it_keeps_an_enabled_section_and_drops_only_its_marker_lines()
    {
        $stub = $this->sectionsStub("a\n    %%START_FOO%%\n    \$NAME$\n    %%END_FOO%%\nb\n");

        $this->assertSame("a\n    Blog\nb\n", $stub->sections(['FOO' => true])->render());
    }

    public function test_it_drops_a_disabled_section_with_its_marker_lines()
    {
        $stub = $this->sectionsStub("a\n    %%START_FOO%%\n    \$NAME$\n\n    %%END_FOO%%\nb\n");

        $this->assertSame("a\nb\n", $stub->sections(['FOO' => false])->render());
    }

    public function test_it_handles_every_occurrence_of_a_section_and_markers_at_the_end_of_the_file()
    {
        $stub = $this->sectionsStub("%%START_FOO%%\none\n%%END_FOO%%\nkeep\n%%START_FOO%%\ntwo\n%%END_FOO%%");

        $this->assertSame("keep\n", $stub->sections(['FOO' => false])->render());
        $this->assertSame("one\nkeep\ntwo\n", $stub->sections(['FOO' => true])->render());
    }

    public function test_it_handles_windows_line_endings_in_sections()
    {
        $stub = $this->sectionsStub("a\r\n%%START_FOO%%\r\nfoo\r\n%%END_FOO%%\r\nb\r\n");

        $this->assertSame("a\r\nb\r\n", $stub->sections(['FOO' => false])->render());
        $this->assertSame("a\r\nfoo\r\nb\r\n", $stub->sections(['FOO' => true])->render());
    }

    public function test_it_leaves_sections_that_are_not_set_and_other_percent_text_untouched()
    {
        $contents = "%%START_BAR%%\nbar\n%%END_BAR%%\n%foo% sprintf('%d%%', 5) %%START_FOO%% inline\n%%START_FOO%%\nfoo\n%%END_FOO%%\n";
        $stub = $this->sectionsStub($contents);

        $this->assertSame($contents, $stub->render());
        $this->assertSame(
            "%%START_BAR%%\nbar\n%%END_BAR%%\n%foo% sprintf('%d%%', 5) %%START_FOO%% inline\n",
            $stub->sections(['FOO' => false])->render()
        );
    }

    public function test_it_escapes_section_tags_and_ignores_markers_added_by_replacement_values()
    {
        $stub = $this->sectionsStub("%%START_A.B%%\nkeep\n%%END_A.B%%\n%%START_AXB%%\nother\n%%END_AXB%%\n\$NAME$\n");
        $stub->replace(['NAME' => "%%START_A.B%%\nvalue\n%%END_A.B%%"]);

        $this->assertSame(
            "%%START_AXB%%\nother\n%%END_AXB%%\n%%START_A.B%%\nvalue\n%%END_A.B%%\n",
            $stub->sections(['A.B' => false])->render()
        );
        $this->assertSame(['A.B' => false], $stub->getSections());
    }

    public function test_it_handles_a_section_nested_in_a_section_with_another_tag()
    {
        $stub = $this->sectionsStub("a\n%%START_FOO%%\nfoo\n%%START_BAR%%\nbar\n%%END_BAR%%\n%%END_FOO%%\nb\n");

        $this->assertSame("a\nfoo\nbar\nb\n", $stub->sections(['FOO' => true, 'BAR' => true])->render());
        $this->assertSame("a\nfoo\nb\n", $stub->sections(['FOO' => true, 'BAR' => false])->render());
        $this->assertSame("a\nb\n", $stub->sections(['FOO' => false, 'BAR' => true])->render());
        $this->assertSame("a\nb\n", $stub->sections(['BAR' => true, 'FOO' => false])->render());
    }

    private function sectionsStub(string $contents): Stub
    {
        $this->finder->put(base_path('stub-sections.stub'), $contents);
        Stub::setBasePath(base_path());

        return new Stub('/stub-sections.stub', ['NAME' => 'Blog']);
    }
}
