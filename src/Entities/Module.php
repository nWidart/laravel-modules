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
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|void|null
     */
    public static function findByNameOrCreate(string $name)
    {
        return static::query()->firstOrCreate(['name' => $name]);
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
