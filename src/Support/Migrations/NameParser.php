<?php

namespace Nwidart\Modules\Support\Migrations;

class NameParser
{
    /**
     * The migration name.
     *
     * @var string
     */
    protected string $name;

    /**
     * The array data.
     *
     * @var array
     */
    protected array $data = [];

    /**
     * The available schema actions.
     *
     * @var array
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
     *
     * @param  string  $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->data = $this->fetchData();
    }

    /**
     * Get original migration name.
     *
     * @return string
     */
    public function getOriginalName(): string
    {
        return $this->name;
    }

    /**
     * Get schema type or action.
     *
     * @return string
     */
    public function getAction(): string
    {
        return head($this->data);
    }

    /**
     * Get the table will be used.
     *
     * @return string
     */
    public function getTableName(): string
    {
        $matches = array_reverse($this->getMatches());

        return array_shift($matches);
    }

    /**
     * Get matches data from regex.
     *
     * @return array
     */
    public function getMatches(): array
    {
        preg_match($this->getPattern(), $this->name, $matches);

        return $matches;
    }

    /**
     * Get name pattern.
     *
     * @return string
     */
    public function getPattern(): string
    {
        return match ($action = $this->getAction()) {
            'add', 'append', 'update', 'insert' => "/{$action}_(.*)_to_(.*)_table/",
            'delete', 'remove', 'alter' => "/{$action}_(.*)_from_(.*)_table/",
            default => "/{$action}_(.*)_table/",
        };
    }

    /**
     * Fetch the migration name to an array data.
     *
     * @return array
     */
    protected function fetchData(): array
    {
        return explode('_', $this->name);
    }

    /**
     * Get the array data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Determine whether the given type is same with the current schema action or type.
     *
     *
     * @param string $type
     * @return bool
     */
    public function is(string $type): bool
    {
        return $type === $this->getAction();
    }

    /**
     * Determine whether the current schema action is an adding action.
     *
     * @return bool
     */
    public function isAdd(): bool
    {
        return in_array($this->getAction(), $this->actions['add']);
    }

    /**
     * Determine whether the current schema action is a deleting action.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return in_array($this->getAction(), $this->actions['delete']);
    }

    /**
     * Determine whether the current schema action is a creating action.
     *
     * @return bool
     */
    public function isCreate(): bool
    {
        return in_array($this->getAction(), $this->actions['create']);
    }

    /**
     * Determine whether the current schema action is a dropping action.
     *
     * @return bool
     */
    public function isDrop(): bool
    {
        return in_array($this->getAction(), $this->actions['drop']);
    }
}
