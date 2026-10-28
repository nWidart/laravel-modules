<?php

namespace Nwidart\Modules\Support\Migrations;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

/**
 * @implements Arrayable<string, array<int, string>>
 */
class SchemaParser implements Arrayable
{
    /**
     * The array of custom attributes.
     *
     * @var array<string, string>
     */
    protected array $customAttributes = [
        'remember_token' => 'rememberToken()',
        'soft_delete' => 'softDeletes()',
    ];

    /**
     * The Blueprint methods that reverse the custom attributes.
     *
     * @var array<string, string>
     */
    protected array $customDropAttributes = [
        'remember_token' => 'dropRememberToken()',
        'soft_delete' => 'dropSoftDeletes()',
    ];

    /**
     * The migration schema.
     */
    protected ?string $schema;

    /**
     * The relationship keys.
     *
     * @var array<int, string>
     */
    protected array $relationshipKeys = [
        'belongsTo',
    ];

    /**
     * Create new instance.
     */
    public function __construct(?string $schema = null)
    {
        $this->schema = $schema;
    }

    /**
     * Parse a string to array of formatted schema.
     *
     * The result is keyed by column, so a column that is given more than once
     * (such as belongsTo) keeps only its last definition. Use getFields() to
     * get every field in order.
     *
     * @return array<string, array<int, string>>
     */
    public function parse(?string $schema): array
    {
        $this->schema = $schema;

        $parsed = [];

        foreach ($this->getFields() as [$column, $attributes]) {
            $parsed[$column] = $attributes;
        }

        return $parsed;
    }

    /**
     * Get every field of the schema in order, as [column, attributes] pairs.
     *
     * @return list<array{0: string, 1: array<int, string>}>
     */
    public function getFields(): array
    {
        $fields = [];

        foreach ($this->getSchemas() as $schema) {
            $column = $this->getColumn($schema);

            $fields[] = [$column, $this->getAttributes($column, $schema)];
        }

        return $fields;
    }

    /**
     * Get array of schema.
     *
     * Fields are separated by commas. Whitespace is removed, except inside
     * quoted strings, and commas inside parentheses or quotes are kept.
     *
     * @return list<string>
     */
    public function getSchemas(): array
    {
        if (is_null($this->schema)) {
            return [];
        }

        $schemas = $this->split($this->removeWhitespace($this->schema), ',');

        return array_values(array_filter($schemas, fn (string $schema) => $schema !== ''));
    }

    /**
     * Convert string migration to array.
     *
     * @return array<string, array<int, string>>
     */
    #[\Override]
    public function toArray(): array
    {
        return $this->parse($this->schema);
    }

    /**
     * Render the migration to formatted script.
     */
    public function render(): string
    {
        $results = '';

        foreach ($this->getFields() as [$column, $attributes]) {
            $results .= $this->createField($column, $attributes);
        }

        return $results;
    }

    /**
     * Render up migration fields.
     */
    public function up(): string
    {
        return $this->render();
    }

    /**
     * Render down migration fields.
     */
    public function down(): string
    {
        $results = '';

        foreach ($this->getFields() as [$column, $attributes]) {
            $attributes = [head($attributes)];
            $results .= $this->createField($column, $attributes, 'remove');
        }

        return $results;
    }

    /**
     * Create field.
     *
     * @param  array<int, string>  $attributes
     */
    public function createField(string $column, array $attributes, string $type = 'add'): string
    {
        $results = "\t\t\t".'$table';

        foreach ($attributes as $key => $field) {
            if (in_array($column, $this->relationshipKeys)) {
                $results .= $this->{"{$type}RelationColumn"}($key, $field, $column);
            } else {
                $results .= $this->{"{$type}Column"}($key, $field, $column);
            }
        }

        return $results.';'.PHP_EOL;
    }

    /**
     * Add relation column.
     */
    protected function addRelationColumn(int $key, string $field, ?string $column = null): string
    {
        if ($key === 0) {
            $relatedColumn = $this->getRelatedColumn($field);

            return "->integer('{$relatedColumn}')->unsigned();".PHP_EOL."\t\t\t"."\$table->foreign('{$relatedColumn}')";
        }
        if ($key === 1) {
            return "->references('{$field}')";
        }
        if ($key === 2) {
            return "->on('{$field}')";
        }
        if (Str::contains($field, '(')) {
            return '->'.$field;
        }

        return '->'.$field.'()';
    }

    /**
     * Remove relation column: drop the foreign key and the column that addRelationColumn() adds.
     */
    protected function removeRelationColumn(int $key, string $field, ?string $column = null): string
    {
        if ($key !== 0) {
            return '';
        }

        $relatedColumn = $this->getRelatedColumn($field);

        return "->dropForeign(['{$relatedColumn}']);".PHP_EOL."\t\t\t"."\$table->dropColumn('{$relatedColumn}')";
    }

    /**
     * Get the foreign key column for a related model, for example "user" becomes "user_id".
     */
    protected function getRelatedColumn(string $field): string
    {
        return Str::snake(class_basename($field)).'_id';
    }

    /**
     * Format field to script.
     */
    protected function addColumn(int $key, string $field, string $column): string
    {
        if ($this->hasCustomAttribute($column)) {
            return '->'.$field;
        }

        if ($key == 0) {
            return '->'.$this->getColumnType($field, $column);
        }

        if (Str::contains($field, '(')) {
            return '->'.$field;
        }

        return '->'.$field.'()';
    }

    /**
     * Get the column type call, for example "decimal(8,2)" becomes "decimal('price', 8, 2)".
     */
    protected function getColumnType(string $field, string $column): string
    {
        if (! preg_match('/^(\w+)\((.*)\)$/s', $field, $matches)) {
            return $field."('".$column."')";
        }

        $arguments = array_filter($this->split($matches[2], ','), fn (string $argument) => $argument !== '');

        return $matches[1].'('.implode(', ', ["'".$column."'", ...$arguments]).')';
    }

    /**
     * Format field to script.
     */
    protected function removeColumn(int $key, string $field, string $column): string
    {
        if ($this->hasCustomAttribute($column)) {
            return '->'.($this->customDropAttributes[$column] ?? $field);
        }

        return '->dropColumn('."'".$column."')";
    }

    /**
     * Get column name from schema.
     */
    public function getColumn(string $schema): string
    {
        return $this->split($schema, ':')[0];
    }

    /**
     * Get column attributes.
     *
     * @return array<int, string>
     */
    public function getAttributes(string $column, string $schema): array
    {
        if ($this->hasCustomAttribute($column)) {
            return $this->getCustomAttribute($column);
        }

        $segments = $this->split($schema, ':');

        return count($segments) > 1 ? array_slice($segments, 1) : $segments;
    }

    /**
     * Determine whether the given column is exist in customAttributes array.
     */
    public function hasCustomAttribute(string $column): bool
    {
        return array_key_exists($column, $this->customAttributes);
    }

    /**
     * Get custom attributes value.
     *
     * @return array<int, string>
     */
    public function getCustomAttribute(string $column): array
    {
        return (array) $this->customAttributes[$column];
    }

    /**
     * Split the value on the delimiter, except inside parentheses, brackets, or quoted strings.
     *
     * @return list<string>
     */
    protected function split(string $value, string $delimiter): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $quote = null;
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if ($quote !== null) {
                $current .= $char;

                if ($char === '\\' && $i + 1 < $length) {
                    $current .= $value[++$i];
                } elseif ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === "'" || $char === '"') {
                $quote = $char;
            } elseif ($char === '(' || $char === '[') {
                $depth++;
            } elseif (($char === ')' || $char === ']') && $depth > 0) {
                $depth--;
            } elseif ($char === $delimiter && $depth === 0) {
                $parts[] = $current;
                $current = '';

                continue;
            }

            $current .= $char;
        }

        $parts[] = $current;

        return $parts;
    }

    /**
     * Remove whitespace from the value, except inside quoted strings.
     */
    protected function removeWhitespace(string $value): string
    {
        $result = '';
        $quote = null;
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if ($quote !== null) {
                $result .= $char;

                if ($char === '\\' && $i + 1 < $length) {
                    $result .= $value[++$i];
                } elseif ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === "'" || $char === '"') {
                $quote = $char;
            } elseif (ctype_space($char)) {
                continue;
            }

            $result .= $char;
        }

        return $result;
    }
}
