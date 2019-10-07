<?php

namespace Nwidart\Modules\Entities;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'path',
    ];

    /**
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|void|null
     */
    public static function findByName(string $name)
    {
        return static::query()->where('name', $name)->first();
    }

    /**
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|void|null
     */
    public static function findByNameOrFail(string $name)
    {
        return static::query()->where('name', $name)->firstOrFail();
    }

    /**
     * @param string $name
     * @param string $path
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    public static function findByNameOrCreate(string $name, string $path)
    {
        return static::query()->firstOrCreate(['name' => $name, 'path' => $path]);
    }

    /**
     * @return mixed
     */
    public static function deleteAll()
    {
        return static::query()
            ->delete();
    }

    /**
     * @param $column
     * @param $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function orderBy($column, $direction)
    {
        return static::query()
            ->orderBy($column, $direction);
    }

    /**
     * Determine whether the given status same with a module status.
     *
     * @param $status
     * @return bool
     */
    public function hasStatus(bool $status)
    {
        return $this->is_active === $status;
    }

    /**
     * Set active state for a module.
     *
     * @param bool $active
     */
    public function setActive(bool $active)
    {
        $this->is_active = $active;
        $this->save();
    }
}
