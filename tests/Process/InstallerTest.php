<?php

namespace Nwidart\Modules\Tests\Process;

use InvalidArgumentException;
use LogicException;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Process\Installer;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Process\Concerns\CreatesLocalGitRepository;
use Symfony\Component\Process\Process;

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

    public function test_composer_install_without_a_version_requires_dev_master()
    {
        $process = (new Installer('acme/blog'))->getProcess();

        $this->assertSame("'composer' 'require' 'acme/blog:dev-master'", $process->getCommandLine());
    }

    public function test_composer_type_installs_via_composer()
    {
        $process = (new Installer('acme/blog', '^1.0', 'composer'))->getProcess();

        $this->assertSame("'composer' 'require' 'acme/blog:^1.0'", $process->getCommandLine());
    }

    public function test_git_install_without_a_version_checks_out_master()
    {
        $this->addBranchToBareRepository('master');
        $destination = $this->tmpDir.DIRECTORY_SEPARATOR.'Blog';

        $process = (new Installer('acme/blog', null, $this->repoUrl))
            ->setPath($destination)
            ->getProcess();

        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertSame('master', $process->getEnv()['MODULE_BRANCH']);
        $this->assertSame('master', $this->currentBranch($destination));
    }

    public function test_git_install_without_a_path_uses_the_repository_module_path()
    {
        $repository = $this->app[RepositoryInterface::class];

        $process = (new Installer('acme/blog', 'develop', $this->repoUrl))
            ->setRepository($repository)
            ->getProcess();

        $this->assertSame($repository->getModulePath('Blog'), $process->getEnv()['MODULE_PATH']);
    }

    public function test_subtree_install_without_a_path_uses_the_module_path_relative_to_the_project()
    {
        $repository = $this->app[RepositoryInterface::class];
        $this->assertSame(base_path('modules/Blog/'), $repository->getModulePath('Blog'));

        $process = (new Installer('acme/blog', 'develop', $this->repoUrl, true))
            ->setRepository($repository)
            ->getProcess();

        $this->assertSame('modules/Blog', $process->getEnv()['MODULE_PATH']);
        $this->assertSame(base_path(), $process->getWorkingDirectory());
    }

    public function test_subtree_install_makes_an_absolute_path_inside_the_project_relative()
    {
        $process = (new Installer('acme/blog', 'develop', $this->repoUrl, true))
            ->setPath(base_path('Modules/Blog/'))
            ->getProcess();

        $this->assertSame('Modules/Blog', $process->getEnv()['MODULE_PATH']);
    }

    public function test_subtree_install_rejects_an_absolute_path_outside_the_project()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be inside the project');

        (new Installer('acme/blog', 'develop', $this->repoUrl, true))
            ->setPath($this->tmpDir.DIRECTORY_SEPARATOR.'Blog')
            ->getProcess();
    }

    public function test_subtree_install_rejects_a_sibling_directory_sharing_the_project_prefix()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be inside the project');

        (new Installer('acme/blog', 'develop', $this->repoUrl, true))
            ->setPath(rtrim(base_path(), '/').'-other/Modules/Blog')
            ->getProcess();
    }

    public function test_git_install_without_a_path_or_repository_fails_with_a_clear_message()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable to resolve the destination path for module [acme/blog]');

        (new Installer('acme/blog', 'develop', $this->repoUrl))->getProcess();
    }

    public function test_run_without_a_console_still_runs_the_process()
    {
        $destination = $this->tmpDir.DIRECTORY_SEPARATOR.'Blog';

        $process = (new Installer('acme/blog', 'develop', $this->repoUrl))
            ->setPath($destination)
            ->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertSame('develop', $this->currentBranch($destination));
    }

    public function test_subtree_install_resolves_dot_segments_inside_the_project()
    {
        $process = (new Installer('acme/blog', 'develop', $this->repoUrl, true))
            ->setPath('./Modules/Shop/../Blog/')
            ->getProcess();

        $this->assertSame('Modules/Blog', $process->getEnv()['MODULE_PATH']);
    }

    public function test_subtree_install_rejects_a_relative_path_outside_the_project()
    {
        foreach (['../Blog', 'Modules/../../Blog', base_path('Modules/../../Blog')] as $path) {
            try {
                (new Installer('acme/blog', 'develop', $this->repoUrl, true))
                    ->setPath($path)
                    ->getProcess();

                $this->fail("Path [{$path}] should be rejected.");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('must be inside the project', $e->getMessage());
            }
        }
    }

    public function test_subtree_install_rejects_the_project_root()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid subtree destination [Modules/..]');

        (new Installer('acme/blog', 'develop', $this->repoUrl, true))
            ->setPath('Modules/..')
            ->getProcess();
    }

    public function test_a_failed_subtree_install_removes_its_remote_so_a_retry_succeeds()
    {
        $project = $this->useGitProjectAsBasePath();

        $failed = (new Installer('acme/blog', 'missing-branch', $this->repoUrl, true))
            ->setPath('Modules/Blog')
            ->run();

        $this->assertFalse($failed->isSuccessful());
        $this->assertSame('', trim($this->git(['remote'], $project)));

        $process = (new Installer('acme/blog', 'develop', $this->repoUrl, true))
            ->setPath('Modules/Blog')
            ->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertFileExists($project.'/Modules/Blog/module.json');
        // A successful install keeps the remote, for later `git subtree pull`.
        $this->assertSame($this->repoUrl, trim($this->git(['remote', 'get-url', 'Blog'], $project)));
    }

    public function test_subtree_install_reuses_an_existing_remote_with_the_same_url()
    {
        $project = $this->useGitProjectAsBasePath();
        $this->git(['remote', 'add', 'Blog', $this->repoUrl], $project);

        $process = (new Installer('acme/blog', 'develop', $this->repoUrl, true))
            ->setPath('Modules/Blog')
            ->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertStringNotContainsString('git remote add', $process->getCommandLine());
        $this->assertFileExists($project.'/Modules/Blog/module.json');
    }

    public function test_a_failed_subtree_install_keeps_a_remote_that_existed_before()
    {
        $project = $this->useGitProjectAsBasePath();
        $this->git(['remote', 'add', 'Blog', $this->repoUrl], $project);

        $process = (new Installer('acme/blog', 'missing-branch', $this->repoUrl, true))
            ->setPath('Modules/Blog')
            ->run();

        $this->assertFalse($process->isSuccessful());
        $this->assertSame($this->repoUrl, trim($this->git(['remote', 'get-url', 'Blog'], $project)));
    }

    public function test_subtree_install_rejects_an_existing_remote_with_another_url()
    {
        $project = $this->useGitProjectAsBasePath();
        $this->git(['remote', 'add', 'Blog', 'https://example.com/other/blog.git'], $project);

        try {
            (new Installer('acme/blog', 'develop', $this->repoUrl, true))
                ->setPath('Modules/Blog')
                ->run();

            $this->fail('The install should be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('The git remote [Blog] already exists with URL [https://example.com/other/blog.git]', $e->getMessage());
        }

        $this->assertSame('https://example.com/other/blog.git', trim($this->git(['remote', 'get-url', 'Blog'], $project)));
        $this->assertDirectoryDoesNotExist($project.'/Modules/Blog');
    }

    public function test_installed_module_name_comes_from_the_installed_module_json()
    {
        $destination = $this->tmpDir.DIRECTORY_SEPARATOR.'BlogModule';
        $installer = (new Installer('acme/blog-module', 'develop', $this->repoUrl))->setPath($destination);

        $this->assertSame('BlogModule', $installer->getInstalledModuleName());

        $this->assertTrue($installer->run()->isSuccessful());
        $this->assertSame('Blog', $installer->getInstalledModuleName());
    }

    public function test_installed_module_name_resolves_a_relative_subtree_prefix_from_the_project()
    {
        $project = $this->useGitProjectAsBasePath();
        mkdir($project.'/Modules/BlogModule', 0777, true);
        file_put_contents($project.'/Modules/BlogModule/module.json', '{"name": "Blog"}');

        $installer = (new Installer('acme/blog-module', 'develop', $this->repoUrl, true))->setPath('Modules/BlogModule');

        $this->assertSame('Blog', $installer->getInstalledModuleName());
    }

    public function test_installed_module_name_of_a_composer_install_is_the_module_name()
    {
        $this->assertSame('BlogModule', (new Installer('acme/blog-module', '^1.0'))->getInstalledModuleName());
    }

    /**
     * Create a committed git project in the temporary directory and use it as base_path().
     */
    private function useGitProjectAsBasePath(): string
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
}
