<?php

namespace Nwidart\Modules\Tests\Commands;

use Illuminate\Contracts\Console\Kernel;
use Nwidart\Modules\Commands\Actions\ModuleDeleteCommand;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Commands\Database\MigrateFreshCommand;
use Nwidart\Modules\Commands\Database\MigrateRefreshCommand;
use Nwidart\Modules\Commands\Database\MigrateResetCommand;
use Nwidart\Modules\Commands\Database\MigrateRollbackCommand;
use Nwidart\Modules\Contracts\ConfirmableCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ProhibitedCommandsTest extends BaseTestCase
{
    private const CONFIRMATION = 'Are you sure you want to run this command?';

    private const PROHIBITED = 'This command is prohibited from running in this environment.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');

        $this->app[Kernel::class]->registerCommand(new ThirdPartyConfirmableCommand);

        // In production an allowed confirmable command asks for confirmation, a prohibited one
        // refuses before it asks: declining the question keeps every check free of side effects.
        $this->app['env'] = 'production';
        $this->mockConsoleOutput = true;
    }

    protected function tearDown(): void
    {
        Module::prohibitDestructiveCommands(false);
        BaseCommand::prohibit(false);
        $this->app['env'] = 'testing';

        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    /**
     * @return array<string, array{class-string<BaseCommand>, string}>
     */
    public static function destructiveCommands(): array
    {
        return [
            'module:migrate-fresh' => [MigrateFreshCommand::class, 'module:migrate-fresh'],
            'module:migrate-refresh' => [MigrateRefreshCommand::class, 'module:migrate-refresh'],
            'module:migrate-reset' => [MigrateResetCommand::class, 'module:migrate-reset'],
            'module:migrate-rollback' => [MigrateRollbackCommand::class, 'module:migrate-rollback'],
            'module:delete' => [ModuleDeleteCommand::class, 'module:delete'],
        ];
    }

    /**
     * @param  class-string<BaseCommand>  $class
     */
    #[DataProvider('destructiveCommands')]
    public function test_prohibiting_a_command_blocks_only_that_command(string $class, string $command): void
    {
        $class::prohibit();

        $this->assertProhibited($command);

        foreach (self::destructiveCommands() as [, $other]) {
            if ($other !== $command) {
                $this->assertAllowed($other);
            }
        }

        $this->assertAllowed('module:third-party');
    }

    /**
     * @param  class-string<BaseCommand>  $class
     */
    #[DataProvider('destructiveCommands')]
    public function test_a_prohibited_command_can_be_allowed_again(string $class, string $command): void
    {
        $class::prohibit();
        $class::prohibit(false);

        $this->assertAllowed($command);
    }

    public function test_the_facade_prohibits_all_destructive_commands(): void
    {
        Module::prohibitDestructiveCommands();

        foreach (self::destructiveCommands() as [, $command]) {
            $this->assertProhibited($command);
        }
    }

    public function test_the_facade_allows_all_destructive_commands_again(): void
    {
        Module::prohibitDestructiveCommands();
        Module::prohibitDestructiveCommands(false);

        foreach (self::destructiveCommands() as [, $command]) {
            $this->assertAllowed($command);
        }
    }

    public function test_a_command_that_is_not_confirmable_is_never_prohibited(): void
    {
        Module::prohibitDestructiveCommands();
        BaseCommand::prohibit();

        $this->artisan('module:enable', ['module' => ['Blog']])
            ->doesntExpectOutputToContain(self::PROHIBITED)
            ->assertSuccessful()
            ->run();
    }

    public function test_a_third_party_confirmable_command_keeps_the_base_command_flag(): void
    {
        BaseCommand::prohibit();

        $this->assertProhibited('module:third-party');

        foreach (self::destructiveCommands() as [, $command]) {
            $this->assertAllowed($command);
        }
    }

    public function test_the_facade_does_not_prohibit_a_third_party_confirmable_command(): void
    {
        Module::prohibitDestructiveCommands();

        $this->assertAllowed('module:third-party');
    }

    private function assertProhibited(string $command): void
    {
        $this->artisan($command, ['module' => ['Blog']])
            ->expectsOutputToContain(self::PROHIBITED)
            ->assertExitCode(1)
            ->run();
    }

    private function assertAllowed(string $command): void
    {
        $this->artisan($command, ['module' => ['Blog']])
            ->expectsConfirmation(self::CONFIRMATION, 'no')
            ->doesntExpectOutputToContain(self::PROHIBITED)
            ->assertExitCode(1)
            ->run();
    }
}

class ThirdPartyConfirmableCommand extends BaseCommand implements ConfirmableCommand
{
    protected $name = 'module:third-party';

    protected $description = 'A confirmable command from another package.';

    /**
     * @param  string  $name
     */
    public function executeAction($name): void
    {
        $this->line("Ran for {$name}");
    }
}
