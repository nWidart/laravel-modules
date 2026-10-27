<?php

namespace Nwidart\Modules\Support\Migrations;

class NameParser
{
    /**
     * The migration name.
     */
    protected string $name;

    /**
     * The array data: the migration name split on "_".
     *
     * @var array<int, string>
     */
    protected array $data = [];

    /**
     * The available schema actions.
     *
     * @var array<string, array<int, string>>
     */
    protected array $actions = [
        'create' => [
            'create',
            'make',
        ],
        'delete' => [
            'delete',
            'remove',
        ],
        'add' => [
            'add',
            'update',
            'append',
            'insert',
        ],
        'drop' => [
            'destroy',
            'drop',
        ],
    ];

    /**
     * The constructor.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->data = $this->fetchData();
    }

    /**
     * Get original migration name.
     */
    public function getOriginalName(): string
    {
        return $this->name;
    }

    /**
     * Get schema type or action.
     */
    public function getAction(): string
    {
        return head($this->data);
    }

    /**
     * Get the table will be used.
     */
    public function getTableName(): ?string
    {
        $matches = array_reverse($this->getMatches());

        $table = array_shift($matches);

        if ($table !== null) {
            return $table;
        }

        foreach ($this->getFallbackPatterns() as $pattern) {
            if (preg_match($pattern, $this->name, $matches)) {
                return end($matches);
            }
        }

        return null;
    }

    /**
     * Get the patterns tried when the name does not match getPattern(), such as
     * "alter_users_table", "update_users_table" or names without a "_table" suffix.
     * Like Laravel's TableGuesser, the table is the part after "_to_" or "_from_".
     *
     * @return array<int, string>
     */
    protected function getFallbackPatterns(): array
    {
        $action = $this->getAction();

        if (in_array($action, $this->actions['add'])) {
            $preposition = 'to';
        } elseif (in_array($action, [...$this->actions['delete'], 'alter'])) {
            $preposition = 'from';
        } elseif (in_array($action, [...$this->actions['create'], ...$this->actions['drop']])) {
            return ["/^{$action}_(.+)$/"];
        } else {
            return [];
        }

        return [
            "/^{$action}_.+_{$preposition}_(.+)$/",
            "/^{$action}_(.+)_table$/",
            "/^{$action}_(.+)$/",
        ];
    }

    /**
     * Get matches data from regex.
     *
     * @return array<int, string>
     */
    public function getMatches(): array
    {
        preg_match($this->getPattern(), $this->name, $matches);

        return $matches;
    }

    /**
     * Get name pattern.
     */
    public function getPattern(): string
    {
        $action = $this->getAction();

        return match ($action) {
            'add', 'append', 'update', 'insert' => "/{$action}_(.*)_to_(.*)_table/",
            'delete', 'remove', 'alter' => "/{$action}_(.*)_from_(.*)_table/",
            default => "/{$action}_(.*)_table/",
        };
    }

    /**
     * Fetch the migration name to an array data.
     *
     * @return array<int, string>
     */
    protected function fetchData(): array
    {
        return explode('_', $this->name);
    }

    /**
     * Get the array data.
     *
     * @return array<int, string>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Determine whether the given type is same with the current schema action or type.
     *
     * @param  string  $type
     */
    public function is($type): bool
    {
        return $type === $this->getAction();
    }

    /**
     * Determine whether the current schema action is a adding action.
     */
    public function isAdd(): bool
    {
        return in_array($this->getAction(), $this->actions['add']);
    }

    /**
     * Determine whether the current schema action is a deleting action.
     */
    public function isDelete(): bool
    {
        return in_array($this->getAction(), $this->actions['delete']);
    }

    /**
     * Determine whether the current schema action is a creating action.
     */
    public function isCreate(): bool
    {
        return in_array($this->getAction(), $this->actions['create']);
    }

    /**
     * Determine whether the current schema action is a dropping action.
     */
    public function isDrop(): bool
    {
        return in_array($this->getAction(), $this->actions['drop']);
    }
}
