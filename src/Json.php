<?php

namespace Nwidart\Modules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Nwidart\Modules\Exceptions\InvalidJsonException;

class Json
{
    /**
     * The file path.
     */
    protected string $path;

    /**
     * The laravel filesystem instance.
     */
    protected Filesystem $filesystem;

    /**
     * The attributes collection.
     */
    protected ?Collection $attributes = null;

    /**
     * The constructor.
     */
    public function __construct($path, ?Filesystem $filesystem = null)
    {
        $this->path = (string) $path;
        $this->filesystem = $filesystem ?: new Filesystem;
        $this->attributes = Collection::make($this->getAttributes());
    }

    /**
     * Get filesystem.
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Set filesystem.
     */
    public function setFilesystem(Filesystem $filesystem): self
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Get path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set path.
     */
    public function setPath($path): self
    {
        $this->path = (string) $path;

        return $this;
    }

    /**
     * Make new instance.
     */
    public static function make(string $path, ?Filesystem $filesystem = null): static
    {
        return new static($path, $filesystem);
    }

    /**
     * Get file content.
     */
    public function getContents(): string
    {
        return $this->filesystem->get($this->getPath());
    }

    /**
     *  Decode contents as array.
     *
     * @throws InvalidJsonException
     */
    public function decodeContents(): array
    {
        $attributes = $this->filesystem->json($this->getPath());

        // any JSON parsing errors should throw an exception
        if (json_last_error() > 0) {
            throw new InvalidJsonException('Error processing file: '.$this->getPath().'. Error: '.json_last_error_msg());
        }

        return $attributes;
    }

    /**
     * Get file contents as array, either from the cache or from
     * the json content file if the cache is disabled.
     *
     * @throws \Exception
     */
    public function getAttributes(): array
    {
        return $this->attributes ? $this->attributes->toArray() : $this->decodeContents();
    }

    /**
     * Convert the given array data to pretty json.
     */
    public function toJsonPretty(?array $data = null): string
    {
        return json_encode($data ?: $this->attributes, JSON_PRETTY_PRINT);
    }

    /**
     * Update json contents from array data.
     */
    public function update(array $data): bool
    {
        $this->attributes = new Collection(array_merge($this->attributes->toArray(), $data));

        return $this->save();
    }

    /**
     * Set a specific key & value.
     */
    public function set(string $key, $value): self
    {
        $this->attributes->offsetSet($key, $value);

        return $this;
    }

    /**
     * Save the current attributes array to the file storage.
     */
    public function save(): bool
    {
        return $this->filesystem->put($this->getPath(), $this->toJsonPretty());
    }

    /**
     * Handle magic method __get.
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Get the specified attribute from json file.
     */
    public function get(string $key, $default = null)
    {
        return $this->attributes->get($key, $default);
    }

    /**
     * Handle call to __call method.
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
     */
    public function __toString(): string
    {
        return $this->getContents();
    }
}
