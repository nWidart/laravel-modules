<?php

namespace Nwidart\Modules\Tests\Process;

use InvalidArgumentException;
use Nwidart\Modules\Process\Installer;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Process\Concerns\CreatesLocalGitRepository;

class InstallerTest extends BaseTestCase
{
    use CreatesLocalGitRepository;

    private string $repoUrl;

    private string $marker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTmpDir();
        $this->repoUrl = $this->createBareRepository();
        $this->marker = $this->tmpDir.DIRECTORY_SEPARATOR.'pwned';
    }

    protected function tearDown(): void
    {
        $this->deleteTmpDir();

        parent::tearDown();
    }

    public function test_it_clones_and_checks_out_into_a_path_containing_spaces()
    {
        $destination = $this->tmpDir.DIRECTORY_SEPARATOR.'my modules'.DIRECTORY_SEPARATOR.'Blog';

        $process = (new Installer('acme/blog', 'develop', $this->repoUrl))
            ->setPath($destination)
            ->getProcess();

        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertFileExists($destination.DIRECTORY_SEPARATOR.'module.json');
        $this->assertSame('develop', $this->currentBranch($destination));
        $this->assertSame(base_path(), $process->getWorkingDirectory());
    }

    public function test_it_checks_out_a_commit_sha()
    {
        $destination = $this->tmpDir.DIRECTORY_SEPARATOR.'Blog';
        $sha = trim($this->git(['rev-parse', 'main'], $this->tmpDir.DIRECTORY_SEPARATOR.'source'));

        $process = (new Installer('acme/blog', $sha, $this->repoUrl))
            ->setPath($destination)
            ->getProcess();

        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertSame($sha, trim($this->git(['rev-parse', 'HEAD'], $destination)));
    }

    public function test_it_does_not_run_commands_injected_through_the_version()
    {
        foreach (["develop;touch {$this->marker}", "develop && touch {$this->marker}", "\$(touch {$this->marker})"] as $i => $version) {
            $process = (new Installer('acme/blog', $version, $this->repoUrl))
                ->setPath($this->tmpDir.DIRECTORY_SEPARATOR."Blog{$i}")
                ->getProcess();

            $process->run();

            $this->assertFalse($process->isSuccessful(), "Version [{$version}] should not check out.");
            $this->assertFileDoesNotExist($this->marker);
        }
    }

    public function test_it_does_not_run_commands_injected_through_the_path()
    {
        $destination = $this->tmpDir.DIRECTORY_SEPARATOR."Blog;touch {$this->marker};";

        $process = (new Installer('acme/blog', 'develop', $this->repoUrl))
            ->setPath($destination)
            ->getProcess();

        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertDirectoryExists($destination);
        $this->assertFileDoesNotExist($this->marker);
    }

    public function test_it_does_not_run_commands_injected_through_the_repo_url()
    {
        // ${IFS} instead of a space, so the URL still passes FILTER_VALIDATE_URL.
        $type = $this->repoUrl.';touch${IFS}'.$this->marker;
        $this->assertNotFalse(filter_var($type, FILTER_VALIDATE_URL));

        $process = (new Installer('acme/blog', 'develop', $type))
            ->setPath($this->tmpDir.DIRECTORY_SEPARATOR.'Blog')
            ->getProcess();

        $process->run();

        $this->assertFalse($process->isSuccessful());
        $this->assertFileDoesNotExist($this->marker);
    }

    public function test_git_command_line_contains_no_user_values()
    {
        $process = (new Installer('acme/blog;touch /tmp/pwned', 'main', 'github-https'))
            ->setPath('/var/www/my app/Modules/Blog')
            ->getProcess();

        $this->assertStringNotContainsString('acme', $process->getCommandLine());
        $this->assertStringNotContainsString('/var/www', $process->getCommandLine());
        $this->assertSame('https://github.com/acme/blog;touch /tmp/pwned.git', $process->getEnv()['MODULE_REPO_URL']);
        $this->assertSame('/var/www/my app/Modules/Blog', $process->getEnv()['MODULE_PATH']);
        $this->assertSame('main', $process->getEnv()['MODULE_BRANCH']);
    }

    public function test_subtree_command_line_contains_no_user_values()
    {
        $process = (new Installer('acme/blog', 'main;touch /tmp/pwned', 'deploy@git.example.com', true))
            ->setPath('Modules/Blog')
            ->getProcess();

        $this->assertStringNotContainsString('touch', $process->getCommandLine());
        $this->assertStringNotContainsString('example.com', $process->getCommandLine());
        $this->assertSame(base_path(), $process->getWorkingDirectory());
        $this->assertSame([
            'MODULE_NAME' => 'Blog',
            'MODULE_REPO_URL' => 'deploy@git.example.com:acme/blog.git',
            'MODULE_PATH' => 'Modules/Blog',
            'MODULE_BRANCH' => 'main;touch /tmp/pwned',
        ], $process->getEnv());
    }

    public function test_it_rejects_a_version_that_git_would_parse_as_an_option()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Installer('acme/blog', '--orphan=evil', $this->repoUrl))
            ->setPath($this->tmpDir.DIRECTORY_SEPARATOR.'Blog')
            ->getProcess();
    }

    public function test_it_rejects_a_type_that_does_not_resolve_to_a_repo_url()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Installer('acme/blog', 'main', 'not-a-repo'))
            ->setPath($this->tmpDir.DIRECTORY_SEPARATOR.'Blog')
            ->getProcess();
    }

    public function test_composer_install_passes_the_package_as_one_argument()
    {
        $process = (new Installer('acme/blog;touch /tmp/pwned', '^1.0 || $(id)'))->getProcess();

        $this->assertSame(
            "'composer' 'require' 'acme/blog;touch /tmp/pwned:^1.0 || $(id)'",
            $process->getCommandLine()
        );
        $this->assertSame(base_path(), $process->getWorkingDirectory());
    }
}
