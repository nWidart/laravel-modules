<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use JsonException;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\FileRepository;
use Nwidart\Modules\Module;
use Nwidart\Modules\Support\JsonObjectEditor;
use Nwidart\Modules\Traits\WritesJsonOutput;
use RuntimeException;
use stdClass;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Throwable;

/**
 * Show, set or clear the accepted public surface of a module: the "surface" key of its module.json.
 *
 * The stored value is plain, deterministic JSON, so tools can read it without PHP: always the
 * three lists, in this order, each deduplicated and sorted by byte order. An absent key means
 * no surface was accepted; empty lists mean the module offers nothing.
 *
 * @phpstan-type Surface array{classes: list<string>, routes: list<string>, commands: list<string>}
 * @phpstan-type SurfaceError array{list: string|null, value: string|null, message: string}
 */
class SurfaceCommand extends Command
{
    use WritesJsonOutput;

    /**
     * The lists of a surface, in the order they are stored, with the option that adds to each.
     */
    private const LISTS = ['classes' => 'class', 'routes' => 'route', 'commands' => 'command'];

    /**
     * A fully qualified class name without a leading backslash: PHP identifiers joined by backslashes.
     */
    private const CLASS_PATTERN = '/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(\\\\[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)*$/D';

    /**
     * A console command name, by Symfony's rule: segments without whitespace, joined by colons.
     */
    private const COMMAND_PATTERN = '/^[^\s:]+(:[^\s:]+)*$/uD';

    /**
     * The flags the surface value is encoded with: pretty printed like the json stub, slashes and unicode as is.
     */
    private const SURFACE_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:surface';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show, set or clear the public surface of a module (classes, routes and commands).';

    public function handle(): int
    {
        $this->enterJsonMode();

        $name = (string) $this->argument('module');

        if (($usage = $this->usageError()) !== null) {
            return $this->failWith($name, [self::problem(null, null, $usage)], self::INVALID);
        }

        // A lookup can scan the module.json files again, and one of them can be unreadable or invalid.
        try {
            $repository = $this->laravel->make(RepositoryInterface::class);
            $module = $repository->find($name);
            $broken = $module === null ? $this->brokenReason($repository, $name) : null;
        } catch (Throwable $exception) {
            return $this->failWith($name, [self::problem(null, null, $exception->getMessage())], self::FAILURE);
        }

        if ($module === null) {
            $message = $broken === null
                ? "Module [{$name}] does not exist."
                : "Module [{$name}] was skipped because it is broken: {$broken}";

            return $this->failWith($name, [self::problem(null, null, $message)], self::FAILURE);
        }

        $requested = null;

        if ($this->option('set')) {
            [$requested, $errors, $code] = $this->requestedSurface();

            if ($requested === null) {
                return $this->failWith($name, $errors, $code);
            }
        }

        $path = $module->getPath().'/module.json';
        $files = $this->laravel->make(Filesystem::class);

        try {
            $contents = $files->get($path);
            $document = json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            return $this->failWith($name, [self::problem(null, null, "Could not read {$path}: {$exception->getMessage()}")], self::FAILURE);
        }

        if (! $document instanceof stdClass) {
            return $this->failWith($name, [self::problem(null, null, "{$path} does not hold a JSON object.")], self::FAILURE);
        }

        if ($this->option('set') || $this->option('clear')) {
            try {
                $json = $this->newContents($contents, $document, $requested);
                $changed = $json !== null;

                if ($json !== null) {
                    $this->beforeWrite($path);

                    // Another program (an editor, a tool) may have saved the file since it was read: keep its change.
                    if ($files->get($path) !== $contents) {
                        return $this->failWith($name, [self::problem(null, null, 'module.json changed while module:surface ran; run it again.')], self::FAILURE);
                    }

                    $this->write($module, $files, $path, $json, $requested);
                }
            } catch (Throwable $exception) {
                return $this->failWith($name, [self::problem(null, null, "Could not write {$path}: {$exception->getMessage()}")], self::FAILURE);
            }

            $surface = $requested;
        } else {
            [$surface, $errors] = $this->storedSurface($document);
            $changed = false;

            if ($errors !== []) {
                return $this->failWith($name, $errors, self::FAILURE);
            }
        }

        $this->report($module->getName(), $surface, $changed);

        if ($this->inJsonMode()) {
            $this->writeJson(['module' => $module->getName(), 'path' => $path, 'surface' => $surface, 'changed' => $changed]);
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getArguments()
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of the module'],
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['set', null, InputOption::VALUE_NONE, 'Replace the surface with the given classes, routes and commands (lists not given are stored empty)'],
            ['clear', null, InputOption::VALUE_NONE, 'Remove the surface from module.json'],
            ['class', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'A fully qualified class name in the surface (with --set, repeatable)'],
            ['route', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'A route URI in the surface, as route:list shows it (with --set, repeatable)'],
            ['command', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'A console command name in the surface (with --set, repeatable)'],
            ['stdin', null, InputOption::VALUE_NONE, 'With --set, read the surface as one JSON object {"classes":[],"routes":[],"commands":[]} from standard input'],
            ['json', null, InputOption::VALUE_NONE, 'Print the result as one JSON document'],
        ];
    }

    /**
     * Read standard input, for --stdin.
     *
     * The input's stream is used when one is set (tests set one), else STDIN.
     *
     * @throws RuntimeException when standard input cannot be read
     */
    protected function readStandardInput(): string
    {
        $stream = $this->input instanceof StreamableInputInterface ? $this->input->getStream() : null;
        $stream ??= defined('STDIN') ? STDIN : fopen('php://stdin', 'r');

        $contents = is_resource($stream) ? stream_get_contents($stream) : false;

        if ($contents === false) {
            throw new RuntimeException('Could not read standard input.');
        }

        return $contents;
    }

    /**
     * Get why a module the repository skipped ('skip_broken_modules' config) is broken, or null.
     */
    private function brokenReason(RepositoryInterface $repository, string $name): ?string
    {
        if (! $repository instanceof FileRepository) {
            return null;
        }

        foreach ($repository->getBrokenModules() as $label => $reason) {
            if (strcasecmp((string) $label, $name) === 0) {
                return $reason;
            }
        }

        return null;
    }

    /**
     * Get the error in how the options are combined, if any.
     */
    private function usageError(): ?string
    {
        $listed = array_values(array_filter(self::LISTS, fn (string $option) => $this->option($option) !== []));
        $listed = implode(', ', array_map(fn (string $option) => "--{$option}", $listed));

        return match (true) {
            $this->option('set') && $this->option('clear') => 'Use either --set or --clear, not both.',
            ! $this->option('set') && $listed !== '' => "{$listed} can only be used with --set.",
            ! $this->option('set') && $this->option('stdin') => '--stdin can only be used with --set.',
            $this->option('stdin') && $listed !== '' => "--stdin cannot be combined with {$listed}.",
            default => null,
        };
    }

    /**
     * Get the surface that --set stores, from the options or standard input, normalised.
     *
     * @return array{0: Surface|null, 1: list<SurfaceError>, 2: int}
     */
    private function requestedSurface(): array
    {
        if ($this->option('stdin')) {
            try {
                $input = $this->readStandardInput();
            } catch (RuntimeException $exception) {
                return [null, [self::problem(null, null, $exception->getMessage())], self::FAILURE];
            }

            // Windows PowerShell can prefix piped text with a byte order mark.
            $input = str_starts_with($input, "\xEF\xBB\xBF") ? substr($input, 3) : $input;

            try {
                $object = json_decode($input, false, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                return [null, [self::problem(null, null, "Standard input is not valid JSON: {$exception->getMessage()}.")], self::INVALID];
            }

            if (! $object instanceof stdClass) {
                return [null, [self::problem(null, null, 'Standard input must hold one JSON object with the keys classes, routes and commands.')], self::INVALID];
            }

            [$lists, $errors] = $this->listsOf($object, 'standard input');
        } else {
            $lists = [];
            $errors = [];

            foreach (self::LISTS as $list => $option) {
                $lists[$list] = array_values((array) $this->option($option));
            }
        }

        if ($errors !== []) {
            return [null, $errors, self::INVALID];
        }

        [$surface, $errors] = $this->normalise($lists);

        return $errors === [] ? [$surface, [], self::SUCCESS] : [null, $errors, self::INVALID];
    }

    /**
     * Get the lists of a surface object. Missing lists are empty; unknown keys and lists that
     * are not arrays are errors.
     *
     * @return array{0: array<string, list<mixed>>, 1: list<SurfaceError>}
     */
    private function listsOf(stdClass $object, string $source): array
    {
        $lists = [];
        $errors = [];

        foreach (get_object_vars($object) as $key => $value) {
            $key = (string) $key;

            if (! array_key_exists($key, self::LISTS)) {
                $errors[] = self::problem(null, $key, "Unknown key \"{$key}\" in {$source}; the keys are classes, routes and commands.");
            } elseif (! is_array($value)) {
                $errors[] = self::problem($key, null, "The {$key} in {$source} must be an array of strings.");
            } else {
                $lists[$key] = array_values($value);
            }
        }

        return [$lists, $errors];
    }

    /**
     * Validate and normalise each list: every entry is checked, then the valid ones are
     * deduplicated and sorted by byte order.
     *
     * @param  array<string, list<mixed>>  $lists
     * @return array{0: Surface, 1: list<SurfaceError>}
     */
    private function normalise(array $lists): array
    {
        $surface = [];
        $errors = [];

        foreach (array_keys(self::LISTS) as $list) {
            $kept = [];

            foreach ($lists[$list] ?? [] as $index => $entry) {
                if (! is_string($entry)) {
                    $errors[] = self::problem($list, null, sprintf('Entry %d of %s is not a string.', $index + 1, $list));

                    continue;
                }

                if (! mb_check_encoding($entry, 'UTF-8')) {
                    $errors[] = self::problem($list, null, sprintf('Entry %d of %s is not valid UTF-8.', $index + 1, $list));

                    continue;
                }

                try {
                    $value = $this->normaliseEntry($list, $entry);
                } catch (InvalidArgumentException $exception) {
                    $errors[] = self::problem($list, $entry, $exception->getMessage());

                    continue;
                }

                // PHP class names are case-insensitive: the first spelling given is kept.
                $kept[$list === 'classes' ? strtolower($value) : $value] ??= $value;
            }

            $values = array_values(array_map('strval', $kept));
            sort($values, SORT_STRING);

            $surface[$list] = $values;
        }

        /** @var Surface $surface */
        return [$surface, $errors];
    }

    /**
     * Validate one entry of a list and return its stored form.
     *
     * @throws InvalidArgumentException when the entry is not valid
     */
    private function normaliseEntry(string $list, string $entry): string
    {
        if ($list === 'classes') {
            $class = str_starts_with($entry, '\\') ? substr($entry, 1) : $entry;

            if (preg_match(self::CLASS_PATTERN, $class) !== 1) {
                throw new InvalidArgumentException("\"{$entry}\" is not a fully qualified class name.");
            }

            return $class;
        }

        if ($list === 'routes') {
            if ($entry === '') {
                throw new InvalidArgumentException('A route cannot be empty.');
            }

            if (preg_match('/\s/u', $entry) === 1) {
                throw new InvalidArgumentException("The route \"{$entry}\" contains whitespace.");
            }

            $route = trim($entry, '/');

            return $route === '' ? '/' : $route;
        }

        if (preg_match(self::COMMAND_PATTERN, $entry) !== 1) {
            throw new InvalidArgumentException("\"{$entry}\" is not a valid command name.");
        }

        return $entry;
    }

    /**
     * Get the surface stored in a module.json document, or null when it has none.
     *
     * Each entry must pass the --set rules and already be in its normalised form. Valid
     * entries are returned as stored: spelling, order and duplicates unchanged.
     *
     * @return array{0: Surface|null, 1: list<SurfaceError>}
     */
    private function storedSurface(stdClass $document): array
    {
        if (! property_exists($document, 'surface')) {
            return [null, []];
        }

        if (! $document->surface instanceof stdClass) {
            return [null, [self::problem(null, null, 'The surface in module.json must be an object.')]];
        }

        [$lists, $errors] = $this->listsOf($document->surface, 'the surface in module.json');

        $surface = [];

        foreach (array_keys(self::LISTS) as $list) {
            foreach ($lists[$list] ?? [] as $index => $entry) {
                if (! is_string($entry)) {
                    $errors[] = self::problem($list, null, sprintf('Entry %d of %s in module.json is not a string.', $index + 1, $list));

                    continue;
                }

                // An entry is valid when --set would store it as it is (json_decode() only returns valid UTF-8).
                try {
                    $normalised = $this->normaliseEntry($list, $entry);
                } catch (InvalidArgumentException $exception) {
                    $errors[] = self::problem($list, $entry, $exception->getMessage());

                    continue;
                }

                if ($normalised !== $entry) {
                    $errors[] = self::problem($list, $entry, "\"{$entry}\" is not stored in its normalised form \"{$normalised}\".");
                }
            }

            $surface[$list] = $lists[$list] ?? [];
        }

        /** @var Surface $surface */
        return $errors === [] ? [$surface, []] : [null, $errors];
    }

    /**
     * Get the module.json text with the surface stored (null removes it), or null when nothing changes.
     *
     * Only the text of the "surface" member changes: it replaces the old value in place, is
     * appended after the last member, or is removed with its comma. Every other byte of the
     * file stays as it is (numbers PHP cannot hold exactly, escapes, layout). Nothing changes
     * when the stored surface already is the requested one.
     *
     * @param  Surface|null  $surface
     *
     * @throws JsonException
     */
    private function newContents(string $contents, stdClass $document, ?array $surface): ?string
    {
        $stored = property_exists($document, 'surface');

        $unchanged = $surface === null
            ? ! $stored
            : $stored && json_encode($document->surface) === json_encode($surface);

        if ($unchanged) {
            return null;
        }

        $json = $surface === null
            ? JsonObjectEditor::remove($contents, 'surface')
            : JsonObjectEditor::set($contents, 'surface', $surface, self::SURFACE_FLAGS);

        return $json === $contents ? null : $json;
    }

    /**
     * Write module.json and update the decoded copy the module and the repository share.
     *
     * @param  Surface|null  $surface
     *
     * @throws RuntimeException when the file cannot be written
     */
    private function write(Module $module, Filesystem $files, string $path, string $json, ?array $surface): void
    {
        // Write in place, like the package's other module.json writes: the file keeps its owner,
        // group, mode and ACLs, and a symlinked module.json stays a symlink.
        if ($files->put($path, $json, true) === false) {
            throw new RuntimeException('the file could not be written.');
        }

        $cached = $module->json();

        if ($surface === null) {
            $cached->forget('surface');
        } else {
            $cached->set('surface', $surface);
        }
    }

    /**
     * Runs after the new module.json is built and before the file is read again and written.
     * Tests change the file here to check that a concurrent edit is kept.
     */
    protected function beforeWrite(string $path): void {}

    /**
     * Print the result for humans.
     *
     * @param  Surface|null  $surface
     */
    private function report(string $module, ?array $surface, bool $changed): void
    {
        if ($this->option('clear')) {
            $this->components->info($changed
                ? "The surface of module [{$module}] was removed."
                : "Module [{$module}] has no surface stored. Nothing to remove.");

            return;
        }

        if ($surface === null) {
            $this->components->info("Module [{$module}] has no surface stored.");

            return;
        }

        $this->components->info(match (true) {
            ! $this->option('set') => "The surface of module [{$module}]:",
            $changed => "The surface of module [{$module}] was saved:",
            default => "The surface of module [{$module}] is unchanged:",
        });

        foreach ($surface as $list => $entries) {
            $this->components->twoColumnDetail('<fg=green;options=bold>'.ucfirst($list).'</>', (string) count($entries));

            foreach ($entries as $entry) {
                $this->line('  '.OutputFormatter::escape($entry));
            }
        }

        $this->newLine();
    }

    /**
     * Print the errors and return the exit code; with --json, print the errors document too.
     *
     * @param  list<SurfaceError>  $errors
     */
    private function failWith(string $module, array $errors, int $code): int
    {
        foreach ($errors as $error) {
            $this->components->error(OutputFormatter::escape($error['message']));
        }

        if ($this->inJsonMode()) {
            $this->writeJson(['module' => $module, 'errors' => $errors]);
        }

        return $code;
    }

    /**
     * @return SurfaceError
     */
    private static function problem(?string $list, ?string $value, string $message): array
    {
        return ['list' => $list, 'value' => $value, 'message' => $message];
    }
}
