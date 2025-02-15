<?php

namespace Nwidart\Modules\Support\Migrations;

class NameParser
{
    /**
     * The migration name.
     */
    protected string $name;

    /**
     * The array data.
     */
    protected array $data = [];

    /**
     * The available schema actions.
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
    public function getTableName(): string
    {
        $matches = array_reverse($this->getMatches());

        return array_shift($matches);
    }

    /**
     * Get matches data from regex.
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
        switch ($action = $this->getAction()) {
            case 'add':
            case 'append':
            case 'update':
            case 'insert':
                return "/{$action}_(.*)_to_(.*)_table/";

                break;

            case 'delete':
            case 'remove':
            case 'alter':
                return "/{$action}_(.*)_from_(.*)_table/";

                break;

            default:
                return "/{$action}_(.*)_table/";

                break;
        }
    }

    /**
     * Fetch the migration name to an array data.
     */
    protected function fetchData(): array
    {
        return explode('_', $this->name);
    }

    /**
     * Get the array data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Determine whether the given type is same with the current schema action or type.
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
