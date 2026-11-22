<?php

namespace Nwidart\Modules\Tests\Process\Concerns;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Process\Process;

trait CreatesLocalGitRepository
{
    /**
     * Temporary directory holding the repositories and install targets.
     */
    protected string $tmpDir;

    protected function createTmpDir(): string
    {
        $this->tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'laravel-modules-'.bin2hex(random_bytes(6));

        mkdir($this->tmpDir, 0777, true);

        return $this->tmpDir;
    }

    protected function deleteTmpDir(): void
    {
        if (isset($this->tmpDir) && is_dir($this->tmpDir)) {
            (new Filesystem)->deleteDirectory($this->tmpDir);
        }
    }

    /**
     * Create a bare repository with a `main` and a `develop` branch and return its file:// URL.
     *
     * Its module.json is named after $module. The default `Blog` repository is the one
     * addBranchToBareRepository() changes.
     */
    protected function createBareRepository(string $module = 'Blog'): string
    {
        $prefix = $module === 'Blog' ? '' : $module.'-';
        $source = $this->tmpDir.DIRECTORY_SEPARATOR.$prefix.'source';
        $bare = $this->tmpDir.DIRECTORY_SEPARATOR.$prefix.'repo.git';

        mkdir($source);
        file_put_contents($source.DIRECTORY_SEPARATOR.'module.json', json_encode(['name' => $module]));

        $this->git(['init', '--quiet', '--initial-branch=main'], $source);
        $this->git(['add', 'module.json'], $source);
        $this->git(['commit', '--quiet', '-m', 'Initial commit'], $source);
        $this->git(['checkout', '--quiet', '-b', 'develop'], $source);
        $this->git(['commit', '--quiet', '--allow-empty', '-m', 'Develop commit'], $source);
        $this->git(['clone', '--quiet', '--bare', $source, $bare], $this->tmpDir);

        return 'file://'.$bare;
    }

    /**
     * Add a branch to the bare repository created by createBareRepository().
     */
    protected function addBranchToBareRepository(string $branch, string $from = 'main'): void
    {
        $this->git(['branch', $branch, $from], $this->tmpDir.DIRECTORY_SEPARATOR.'repo.git');
    }

    /**
     * Create a committed git project in the temporary directory and use it as base_path().
     */
    protected function useGitProjectAsBasePath(): string
    {
        $subtree = new Process(['git', 'subtree', '-h']);
        $subtree->run();

        if (! str_contains($subtree->getOutput().$subtree->getErrorOutput(), 'git subtree add')) {
            $this->markTestSkipped('git subtree is not available.');
        }

        $project = $this->tmpDir.DIRECTORY_SEPARATOR.'project';
        mkdir($project);
        file_put_contents($project.DIRECTORY_SEPARATOR.'composer.json', '{}');

        $this->git(['init', '--quiet', '--initial-branch=main'], $project);
        // `git subtree add` commits without the -c flags of git(), so the project needs its own identity.
        $this->git(['config', 'user.name', 'Laravel Modules'], $project);
        $this->git(['config', 'user.email', 'tests@example.com'], $project);
        $this->git(['config', 'commit.gpgsign', 'false'], $project);
        $this->git(['add', 'composer.json'], $project);
        $this->git(['commit', '--quiet', '-m', 'Initial commit'], $project);

        $this->app->setBasePath($project);

        return $project;
    }

    protected function currentBranch(string $path): string
    {
        return trim($this->git(['rev-parse', '--abbrev-ref', 'HEAD'], $path));
    }

    /**
     * @param  list<string>  $arguments
     */
    protected function git(array $arguments, string $cwd): string
    {
        $process = new Process(array_merge([
            'git',
            '-c', 'user.name=Laravel Modules',
            '-c', 'user.email=tests@example.com',
            '-c', 'commit.gpgsign=false',
        ], $arguments), $cwd);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('git '.implode(' ', $arguments).' failed: '.$process->getErrorOutput());
        }

        return $process->getOutput();
    }
}
