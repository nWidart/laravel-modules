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
     */
    protected function createBareRepository(): string
    {
        $source = $this->tmpDir.DIRECTORY_SEPARATOR.'source';
        $bare = $this->tmpDir.DIRECTORY_SEPARATOR.'repo.git';

        mkdir($source);
        file_put_contents($source.DIRECTORY_SEPARATOR.'module.json', '{"name": "Blog"}');

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
