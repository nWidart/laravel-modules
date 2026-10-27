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
     *
     * When a subtree install fails, the git remote it added is removed again,
     * so the install can be retried. A remote that existed before is kept.
     */
    public function run(): Process
    {
        $addsRemote = $this->installsViaSubtree() && $this->getSubtreeRemoteUrl() === null;

        $process = $this->getProcess();

        $process->setTimeout($this->timeout);

        $console = $this->console;

        $process->run($console === null ? null : function ($type, $line) use ($console) {
            $console->line($line);
        });

        if ($addsRemote && ! $process->isSuccessful()) {
            $this->removeSubtreeRemote();
        }

        return $process;
    }

    /**
     * Get process instance.
     */
    public function getProcess(): Process
    {
        if ($this->installsViaGit()) {
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
        return match ($this->type) {
            'github' => "git@github.com:{$this->name}.git",
            'github-https' => "https://github.com/{$this->name}.git",
            'gitlab' => "git@gitlab.com:{$this->name}.git",
            'bitbucket' => "git@bitbucket.org:{$this->name}.git",
            default => $this->getCustomRepoUrl(),
        };
    }

    /**
     * Get the git repo url when the type is itself a url ('scheme://host/path') or a 'user@host' address.
     */
    private function getCustomRepoUrl(): ?string
    {
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
     * Get the name of the installed module.
     *
     * For git and subtree installs, this is the `name` in the module.json of
     * the installed folder, which may differ from the package name: the
     * repository finds modules by that name. Otherwise, or when the file has
     * no name, this is getModuleName().
     */
    public function getInstalledModuleName(): string
    {
        if ($this->installsViaGit()) {
            $manifest = $this->getInstalledPath().DIRECTORY_SEPARATOR.'module.json';

            if (is_file($manifest)) {
                $name = json_decode((string) file_get_contents($manifest), true)['name'] ?? null;

                if (is_string($name) && $name !== '') {
                    return $name;
                }
            }
        }

        return $this->getModuleName();
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
     * the shell command line. An existing remote with the same URL is reused.
     *
     * @throws InvalidArgumentException When a remote with the module name points to another URL.
     */
    public function installViaSubtree(): Process
    {
        $url = $this->getValidatedRepoUrl();
        $remoteUrl = $this->getSubtreeRemoteUrl();

        if ($remoteUrl !== null && $remoteUrl !== $url) {
            throw new InvalidArgumentException("The git remote [{$this->getModuleName()}] already exists with URL [{$remoteUrl}], not [{$url}]. Remove or rename it, then retry.");
        }

        $subtree = 'git subtree add --prefix="${:MODULE_PATH}" --squash "${:MODULE_NAME}" "${:MODULE_BRANCH}"';

        return Process::fromShellCommandline(
            $remoteUrl === null ? 'git remote add -- "${:MODULE_NAME}" "${:MODULE_REPO_URL}" && '.$subtree : $subtree,
            base_path(),
            [
                'MODULE_NAME' => $this->getModuleName(),
                'MODULE_REPO_URL' => $url,
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
     * `git subtree add` runs in base_path() and rejects a prefix outside the
     * repository, so the destination must live inside the project. `.` and `..`
     * segments are resolved first, so `../x` fails here with a clear message.
     *
     * @throws InvalidArgumentException
     */
    protected function getSubtreePrefix(): string
    {
        $destination = $this->getDestinationPath();
        $path = str_replace('\\', '/', $destination);
        $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

        if (str_starts_with($path, '/') || preg_match('#^[A-Za-z]:/#', $path) === 1) {
            if (! str_starts_with($path, $base)) {
                throw new InvalidArgumentException("Subtree destination [{$destination}] for module [{$this->name}] must be inside the project [{$base}].");
            }

            $path = substr($path, strlen($base));
        }

        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment !== '..') {
                $segments[] = $segment;

                continue;
            }

            if ($segments === []) {
                throw new InvalidArgumentException("Subtree destination [{$destination}] for module [{$this->name}] must be inside the project [{$base}].");
            }

            array_pop($segments);
        }

        if ($segments === []) {
            throw new InvalidArgumentException("Invalid subtree destination [{$destination}] for module [{$this->name}].");
        }

        return implode('/', $segments);
    }

    /**
     * Get the absolute path of a git or subtree install. The processes run in
     * base_path(), so a relative destination is relative to the project.
     */
    protected function getInstalledPath(): string
    {
        $path = $this->tree ? $this->getSubtreePrefix() : $this->getDestinationPath();

        if (str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1) {
            return rtrim($path, '/\\');
        }

        return base_path(rtrim($path, '/\\'));
    }

    /**
     * Determine whether the module is installed from a git repository.
     */
    protected function installsViaGit(): bool
    {
        return $this->type !== null && $this->type !== '' && $this->type !== 'composer';
    }

    /**
     * Determine whether the module is installed as a git subtree.
     */
    protected function installsViaSubtree(): bool
    {
        return $this->tree && $this->installsViaGit();
    }

    /**
     * Get the URL of the project git remote named after the module, null when there is none.
     */
    protected function getSubtreeRemoteUrl(): ?string
    {
        $process = new Process(['git', 'remote', 'get-url', '--', $this->getModuleName()], base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $url = trim($process->getOutput());

        return $url === '' ? null : $url;
    }

    /**
     * Remove the project git remote named after the module.
     */
    protected function removeSubtreeRemote(): void
    {
        (new Process(['git', 'remote', 'remove', '--', $this->getModuleName()], base_path()))->run();
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
