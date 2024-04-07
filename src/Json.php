<?php

namespace Nwidart\Modules;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Exceptions\InvalidJsonException;

class Json
{
    /**
     * The file path.
     *
     * @var string
     */
    protected string $path;

    /**
     * The laravel filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * The attributes' collection.
     *
     * @var Collection
     */
    protected Collection $attributes;

    /**
     * The constructor.
     *
     * @param string $path
     * @param Filesystem|null $filesystem
     * @throws Exception
     */
    public function __construct(string $path, Filesystem $filesystem = null)
    {
        $this->path = $path;
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->attributes = Collection::make($this->getAttributes());
    }

    /**
     * Get filesystem.
     *
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Set filesystem.
     *
     * @param Filesystem $filesystem
     *
     * @return $this
     */
    public function setFilesystem(Filesystem $filesystem): static
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set path.
     *
     * @param mixed $path
     *
     * @return $this
     */
    public function setPath(mixed $path): static
    {
        $this->path = (string) $path;

        return $this;
    }

    /**
     * Make new instance.
     *
     * @param string $path
     * @param Filesystem|null $filesystem
     *
     * @return static
     * @throws Exception
     */
    public static function make(string $path, Filesystem $filesystem = null): static
    {
        return new static($path, $filesystem);
    }

    /**
     * Get file content.
     *
     * @return string
     * @throws FileNotFoundException
     */
    public function getContents(): string
    {
        return $this->filesystem->get($this->getPath());
    }

    /**
     *  Decode contents as array.
     *
     * @return array
     * @throws InvalidJsonException|FileNotFoundException
     */
    public function decodeContents(): array
    {
        $attributes =  json_decode($this->getContents(), 1);

        // any JSON parsing errors should throw an exception
        if (json_last_error() > 0) {
            throw new InvalidJsonException('Error processing file: ' . $this->getPath() . '. Error: ' . json_last_error_msg());
        }

        return $attributes;
    }

    /**
     * Get file contents as array, either from the cache or from
     * the json content file if the cache is disabled.
     * @return array
     * @throws Exception
     */
    public function getAttributes(): array
    {
        if (config('modules.cache.enabled') === false) {
            return $this->decodeContents();
        }

        return app('cache')->store(config('modules.cache.driver'))->remember($this->getPath(), config('modules.cache.lifetime'), function () {
            return $this->decodeContents();
        });
    }

    /**
     * Convert the given array data to pretty json.
     *
     * @param array|null $data
     *
     * @return string
     */
    public function toJsonPretty(array $data = null): string
    {
        return json_encode($data ?: $this->attributes, JSON_PRETTY_PRINT);
    }

    /**
     * Update json contents from array data.
     *
     * @param array $data
     *
     * @return bool
     */
    public function update(array $data): bool
    {
        $this->attributes = new Collection(array_merge($this->attributes->toArray(), $data));

        return $this->save();
    }

    /**
     * Set a specific key & value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set(string $key, mixed $value): static
    {
        $this->attributes->offsetSet($key, $value);

        return $this;
    }

    /**
     * Save the current attributes array to the file storage.
     *
     * @return bool
     */
    public function save(): bool
    {
        return $this->filesystem->put($this->getPath(), $this->toJsonPretty());
    }

    /**
     * Handle magic method __get.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Get the specified attribute from json file.
     *
     * @param $key
     * @param null $default
     *
     * @return mixed
     */
    public function get($key, $default = null): mixed
    {
        return $this->attributes->get($key, $default);
    }

    /**
     * Handle call to __call method.
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments = [])
    {
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $arguments);
        }

        return call_user_func_array([$this->attributes, $method], $arguments);
    }

    /**
     * Handle call to __toString method.
     *
     * @return string
     * @throws FileNotFoundException
     */
    public function __toString()
    {
        return $this->getContents();
    }
}
