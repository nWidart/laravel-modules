<?php

namespace Nwidart\Modules\Tests\Commands\Make\Concerns;

use FilesystemIterator;
use Illuminate\Support\Facades\Artisan;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Helpers for the --dry-run and --json tests of the generators.
 */
trait InspectsGeneratorRuns
{
    /**
     * Minimal valid arguments for every package generator except module:make, which
     * the module:make tests cover. The module argument is added by the test.
     *
     * @var array<string, array<string, mixed>>
     */
    private const GENERATOR_ARGUMENTS = [
        'module:make-action' => ['name' => 'PublishPost'],
        'module:make-cast' => ['name' => 'Money'],
        'module:make-channel' => ['name' => 'PostChannel'],
        'module:make-class' => ['name' => 'PostPresenter'],
        'module:make-command' => ['name' => 'PublishCommand'],
        'module:make-component' => ['name' => 'Alert'],
        'module:make-component-view' => ['name' => 'alert'],
        'module:make-config' => ['name' => 'posts'],
        'module:make-controller' => ['controller' => 'PostController'],
        'module:make-enum' => ['name' => 'Status'],
        'module:make-event' => ['name' => 'PostPublished'],
        // module:make writes this provider already.
        'module:make-event-provider' => ['--force' => true],
        'module:make-exception' => ['name' => 'PostException'],
        'module:make-factory' => ['name' => 'Post'],
        'module:make-health-indicator' => ['name' => 'Queue'],
        'module:make-helper' => ['name' => 'PostHelper'],
        'module:make-inertia-component' => ['name' => 'PostCard'],
        'module:make-inertia-page' => ['name' => 'Index'],
        'module:make-interface' => ['name' => 'PostContract'],
        'module:make-job' => ['name' => 'PublishPostJob'],
        'module:make-job-middleware' => ['name' => 'RateLimited'],
        'module:make-listener' => ['name' => 'NotifyAuthor'],
        'module:make-mail' => ['name' => 'PostMail'],
        'module:make-middleware' => ['name' => 'EnsurePostIsPublished'],
        'module:make-migration' => ['name' => 'create_posts_table'],
        'module:make-model' => ['model' => 'Post', '--all' => true],
        'module:make-notification' => ['name' => 'PostNotification'],
        'module:make-observer' => ['name' => 'Post'],
        'module:make-policy' => ['name' => 'PostPolicy'],
        'module:make-provider' => ['name' => 'PostServiceProvider'],
        'module:make-replacement' => ['name' => 'VendorName'],
        'module:make-repository' => ['name' => 'PostRepository'],
        'module:make-request' => ['name' => 'PostRequest'],
        'module:make-resource' => ['name' => 'PostResource'],
        'module:make-rule' => ['name' => 'Slug'],
        'module:make-scope' => ['name' => 'PublishedScope'],
        'module:make-seed' => ['name' => 'PostSeeder'],
        'module:make-service' => ['name' => 'PostService'],
        'module:make-test' => ['name' => 'PostTest'],
        'module:make-trait' => ['name' => 'HasPosts'],
        'module:make-view' => ['name' => 'posts/index'],
        // module:make writes this provider already.
        'module:route-provider' => ['--force' => true],
    ];

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{0: int, 1: array<string, mixed>}
     */
    protected function runJson(string $command, array $arguments): array
    {
        $code = Artisan::call($command, $arguments);
        $output = Artisan::output();

        $this->assertStringEndsWith("\n", $output);
        $this->assertCount(1, explode("\n", rtrim($output, "\n")), "Expected one JSON line, got: {$output}");

        return [$code, json_decode($output, true, flags: JSON_THROW_ON_ERROR)];
    }

    /**
     * Every file and folder below a path, with a hash of each file's contents, its mtime and
     * its inode (a file deleted and written again gets a new one).
     *
     * @return array<string, string>
     */
    protected function listing(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        clearstatcache();

        $listing = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            $listing[str_replace('\\', '/', $file->getPathname())] = $file->isDir() || $file->isLink()
                ? 'dir'
                : md5_file($file->getPathname()).'@'.$file->getMTime().'#'.$file->getInode();
        }

        ksort($listing);

        return $listing;
    }

    /**
     * Set the mtime of every file below a path to a fixed time long ago, so a file the next
     * run writes again shows up in changedFiles(). Without it, a file deleted and written
     * again with the same contents within the same second can look unchanged: Linux
     * filesystems such as ext4 often give the new file the inode the deleted one had.
     */
    protected function backdate(string $path): void
    {
        foreach ($this->files($this->listing($path)) as $file) {
            touch($file, 946684800);
        }
    }

    /**
     * The files of a listing, without its folders.
     *
     * @param  array<string, string>  $listing
     * @return list<string>
     */
    protected function files(array $listing): array
    {
        return array_keys(array_filter($listing, fn (string $entry) => $entry !== 'dir'));
    }

    /**
     * The files that are in the second listing and not in the first.
     *
     * @param  array<string, string>  $before
     * @param  array<string, string>  $after
     * @return list<string>
     */
    protected function createdFiles(array $before, array $after): array
    {
        return array_keys(array_filter(
            array_diff_key($after, $before),
            fn (string $entry) => $entry !== 'dir',
        ));
    }

    /**
     * The files that are in both listings and changed: contents, mtime or inode.
     *
     * @param  array<string, string>  $before
     * @param  array<string, string>  $after
     * @return list<string>
     */
    protected function changedFiles(array $before, array $after): array
    {
        return array_keys(array_filter(
            array_intersect_key($after, $before),
            fn (string $entry, string $path) => $entry !== 'dir' && $entry !== $before[$path],
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    protected function withoutMigrationDates(array $paths): array
    {
        return array_map(fn (string $path) => (string) preg_replace('/\d{4}_\d{2}_\d{2}_\d{6}_/', 'DATE_', $path), $paths);
    }
}
