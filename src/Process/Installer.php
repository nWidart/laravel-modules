<?php

namespace Nwidart\Modules\Process;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Symfony\Component\Process\Process;

class Installer
{
    /**
     * The module name.
     */
    protected string $name;

    /**
     * The version of module being installed, null for the default
     * (`dev-master` via composer, `master` via git).
     */
    protected ?string $version = null;

    /**
     * The module repository instance, used to resolve the default destination path.
     */
    protected ?RepositoryInterface $repository = null;

    /**
     * The console command instance, receives the process output when set.
     */
    protected ?Command $console = null;

    /**
     * The destination path, null to use the repository module path.
     */
    protected ?string $path = null;

    /**
     * The process timeout.
     */
    protected int $timeout = 3360;

    /**
     * Type
     */
    private ?string $type;

    /**
     * Tree
     */
    private bool $tree;

    /**
     * The constructor.
     */
    public function __construct(string $name, ?string $version = null, ?string $type = null, bool $tree = false)
    {
        $this->name = $name;
        $this->version = $version;
        $this->type = $type;
        $this->tree = $tree;
    }

    /**
     * Set destination path.
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Set the module repository instance.
     */
    public function setRepository(RepositoryInterface $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Set console command instance.
     */
    public function setConsole(Command $console): self
    {
        $this->console = $console;

        return $this;
    }

    /**
     * Set process timeout.
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Run the installation process.
     */
    public function run(): Process
    {
        $process = $this->getProcess();

        $process->setTimeout($this->timeout);

        $console = $this->console;

        $process->run($console === null ? null : function ($type, $line) use ($console) {
            $console->line($line);
        });

        return $process;
    }

    /**
     * Get process instance.
     */
    public function getProcess(): Process
    {
        if ($this->type !== null && $this->type !== '' && $this->type !== 'composer') {
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
     * @throws LogicException
     */
    public function getDestinationPath(): string
    {
        if ($this->path !== null && $this->path !== '') {
            return $this->path;
        }

        if ($this->repository === null) {
            throw new LogicException("Unable to resolve the destination path for module [{$this->name}]: call setPath() or setRepository() first.");
        }

        return $this->repository->getModulePath($this->getModuleName());
    }

    /**
     * Get git repo url.
     */
    public function getRepoUrl(): ?string
    {
        switch ($this->type) {
            case 'github':
                return "git@github.com:{$this->name}.git";

            case 'github-https':
                return "https://github.com/{$this->name}.git";

            case 'gitlab':
                return "git@gitlab.com:{$this->name}.git";

                break;

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

                return null;
        }
    }

    /**
     * Get branch name.
     */
    public function getBranch(): string
    {
        return is_null($this->version) ? 'master' : $this->version;
    }

    /**
     * Get module name.
     */
    public function getModuleName(): string
    {
        $parts = explode('/', $this->name);

        return Str::studly(end($parts));
    }

    /**
     * Get composer package name.
     */
    public function getPackageName(): string
    {
        if (is_null($this->version)) {
            return $this->name.':dev-master';
        }

        return $this->name.':'.$this->version;
    }

    /**
     * Install the module via git.
     *
     * The values are passed as process environment placeholders, so Symfony
     * escapes each one as a single argument instead of interpolating it into
     * the shell command line.
     */
    public function installViaGit(): Process
    {
        return Process::fromShellCommandline(
            'git clone -- "${:MODULE_REPO_URL}" "${:MODULE_PATH}" && git -C "${:MODULE_PATH}" checkout "${:MODULE_BRANCH}"',
            base_path(),
            [
                'MODULE_REPO_URL' => $this->getValidatedRepoUrl(),
                'MODULE_PATH' => $this->getDestinationPath(),
                'MODULE_BRANCH' => $this->getValidatedBranch(),
            ]
        );
    }

    /**
     * Install the module via git subtree.
     *
     * The values are passed as process environment placeholders, so Symfony
     * escapes each one as a single argument instead of interpolating it into
     * the shell command line.
     */
    public function installViaSubtree(): Process
    {
        return Process::fromShellCommandline(
            'git remote add -- "${:MODULE_NAME}" "${:MODULE_REPO_URL}" && git subtree add --prefix="${:MODULE_PATH}" --squash "${:MODULE_NAME}" "${:MODULE_BRANCH}"',
            base_path(),
            [
                'MODULE_NAME' => $this->getModuleName(),
                'MODULE_REPO_URL' => $this->getValidatedRepoUrl(),
                'MODULE_PATH' => $this->getSubtreePrefix(),
                'MODULE_BRANCH' => $this->getValidatedBranch(),
            ]
        );
    }

    /**
     * Install the module via composer.
     */
    public function installViaComposer(): Process
    {
        return new Process(['composer', 'require', $this->getPackageName()], base_path());
    }

    /**
     * Get the git repo url, failing when the type does not resolve to one.
     *
     * @throws InvalidArgumentException
     */
    protected function getValidatedRepoUrl(): string
    {
        $url = $this->getRepoUrl();

        if ($url === null || $url === '') {
            throw new InvalidArgumentException("Unable to resolve a git repository URL for module [{$this->name}] with type [{$this->type}].");
        }

        return $url;
    }

    /**
     * Get the subtree prefix, relative to the project root.
     *
     * `git subtree add` runs in base_path() and rejects an absolute prefix,
     * so an absolute destination must live inside the project.
     *
     * @throws InvalidArgumentException
     */
    protected function getSubtreePrefix(): string
    {
        $destination = $this->getDestinationPath();
        $path = str_replace('\\', '/', $destination);

        if (str_starts_with($path, '/') || preg_match('#^[A-Za-z]:/#', $path) === 1) {
            $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

            if (! str_starts_with($path, $base)) {
                throw new InvalidArgumentException("Subtree destination [{$destination}] for module [{$this->name}] must be inside the project [{$base}].");
            }

            $path = substr($path, strlen($base));
        }

        $prefix = rtrim($path, '/');

        if ($prefix === '') {
            throw new InvalidArgumentException("Invalid subtree destination [{$destination}] for module [{$this->name}].");
        }

        return $prefix;
    }

    /**
     * Get the branch name, rejecting values git would parse as an option.
     *
     * @throws InvalidArgumentException
     */
    protected function getValidatedBranch(): string
    {
        $branch = $this->getBranch();

        if ($branch === '' || str_starts_with($branch, '-')) {
            throw new InvalidArgumentException("Invalid version [{$branch}] for module [{$this->name}].");
        }

        return $branch;
    }
}
