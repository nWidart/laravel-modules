<?php

namespace Nwidart\Modules\Process;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Symfony\Component\Process\Process;

class Installer
{
    /**
     * The module name.
     *
     * @var string
     */
    protected string $name;

    /**
     * The version of module being installed.
     *
     * @var string
     */
    protected string $version;

    /**
     * The module repository instance.
     *
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    /**
     * The console command instance.
     *
     * @var Command
     */
    protected Command $console;

    /**
     * The destination path.
     *
     * @var string
     */
    protected string $path;

    /**
     * The process timeout.
     *
     * @var int
     */
    protected int $timeout = 3360;

    /**
     * @var string
     */
    private string $type = '';

    /**
     * @var bool
     */
    private bool $tree = false;

    /**
     * The constructor.
     *
     * @param  string  $name
     * @param  string  $version
     * @param  string  $type
     * @param  bool  $tree
     */
    public function __construct(string $name, string $version = '', string $type = '', bool $tree = false)
    {
        $this->name = $name;
        $this->version = $version;
        $this->type = $type;
        $this->tree = $tree;
    }

    /**
     * Set destination path.
     *
     * @param  string  $path
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Set the module repository instance.
     *
     * @return $this
     */
    public function setRepository(RepositoryInterface $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Set console command instance.
     *
     *
     * @return $this
     */
    public function setConsole(Command $console): self
    {
        $this->console = $console;

        return $this;
    }

    /**
     * Set process timeout.
     *
     * @param  int  $timeout
     * @return $this
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Run the installation process.
     *
     * @return Process
     */
    public function run(): Process
    {
        $process = $this->getProcess();

        $process->setTimeout($this->timeout);

        $process->run(function ($type, $line) {
            $this->console->line($line);
        });

        return $process;
    }

    /**
     * Get process instance.
     *
     * @return Process
     */
    public function getProcess(): Process
    {
        if ($this->type) {
            if ($this->tree) {
                return $this->installViaSubtree();
            }

            return $this->installViaGit();
        }

        return $this->installViaComposer();
    }

    /**
     * Get destination path.
     *
     * @return string
     */
    public function getDestinationPath(): string
    {
        if ($this->path) {
            return $this->path;
        }

        return $this->repository->getModulePath($this->getModuleName());
    }

    /**
     * Get git repo url.
     *
     * @return string
     */
    public function getRepoUrl(): string
    {
        switch ($this->type) {
            case 'github':
                return "git@github.com:{$this->name}.git";

            case 'github-https':
                return "https://github.com/{$this->name}.git";

            case 'gitlab':
                return "git@gitlab.com:{$this->name}.git";

            case 'bitbucket':
                return "git@bitbucket.org:{$this->name}.git";

            default:

                // Check of type 'scheme://host/path'
                if (filter_var($this->type, FILTER_VALIDATE_URL)) {
                    return $this->type;
                }

                // Check of type 'user@host'
                if (filter_var($this->type, FILTER_VALIDATE_EMAIL)) {
                    return "{$this->type}:{$this->name}.git";
                }

                return '';
        }
    }

    /**
     * Get branch name.
     *
     * @return string
     */
    public function getBranch(): string
    {
        return $this->version === '' ? 'master' : $this->version;
    }

    /**
     * Get module name.
     *
     * @return string
     */
    public function getModuleName(): string
    {
        $parts = explode('/', $this->name);

        return Str::studly(end($parts));
    }

    /**
     * Get composer package name.
     *
     * @return string
     */
    public function getPackageName(): string
    {
        if ($this->version === '') {
            return $this->name.':dev-master';
        }

        return $this->name.':'.$this->version;
    }

    /**
     * Install the module via git.
     *
     * @return Process
     */
    public function installViaGit(): Process
    {
        return Process::fromShellCommandline(sprintf(
            'cd %s && git clone %s %s && cd %s && git checkout %s',
            base_path(),
            $this->getRepoUrl(),
            $this->getDestinationPath(),
            $this->getDestinationPath(),
            $this->getBranch()
        ));
    }

    /**
     * Install the module via git subtree.
     *
     * @return Process
     */
    public function installViaSubtree(): Process
    {
        return Process::fromShellCommandline(sprintf(
            'cd %s && git remote add %s %s && git subtree add --prefix=%s --squash %s %s',
            base_path(),
            $this->getModuleName(),
            $this->getRepoUrl(),
            $this->getDestinationPath(),
            $this->getModuleName(),
            $this->getBranch()
        ));
    }

    /**
     * Install the module via composer.
     *
     * @return Process
     */
    public function installViaComposer(): Process
    {
        return Process::fromShellCommandline(sprintf(
            'cd %s && composer require %s',
            base_path(),
            $this->getPackageName()
        ));
    }
}
