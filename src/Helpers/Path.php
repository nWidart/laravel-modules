<?php

namespace Nwidart\Modules\Helpers;

use Illuminate\Support\Str;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Exception\RuntimeException;

/**
 * Contains utility methods for handling path and namespace strings.
 *
 * The methods in this class are able to deal with both UNIX and Windows paths
 * with both forward and backward slashes. All methods return normalized parts
 * containing only forward slashes and no excess "." and ".." segments.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Thomas Schulz <mail@king2500.net>
 * @author Théo Fidry <theo.fidry@gmail.com>
 * @author Solomon Ochepa <solomonochepa@gmail.com>
 */
final class Path
{
    /**
     * The number of buffer entries that triggers a cleanup operation.
     */
    private const CLEANUP_THRESHOLD = 1250;

    /**
     * The buffer size after the cleanup operation.
     */
    private const CLEANUP_SIZE = 1000;

    /**
     * Buffers input/output of {@link canonicalize()}.
     *
     * @var array<string, string>
     */
    private static array $buffer = [];

    private static int $bufferSize = 0;

    public function __construct() {}

    /**
     * Clean up a given path/namespace, replacing directory separators.
     */
    public static function clean(string $path, string $ds = DIRECTORY_SEPARATOR, string $replace = '\\'): string
    {
        if ($ds === $replace) {
            $replace = ($ds === '/') ? '\\' : '/';
        }

        return Str::of($path)->rtrim($ds)
            ->replace($replace, $ds)
            ->explode($ds)
            ->filter(fn ($segment, $key) => $key == 0 or ! empty($segment))
            ->implode($ds);
    }

    /**
     * Get a StudlyCase representation of the given path/namespace.
     */
    public static function studly(string $path, $ds = '/'): string
    {
        return collect(explode($ds, Path::clean($path, $ds)))->map(fn ($path) => Str::studly($path))->implode($ds);
    }

    public static function lower(string $string): string
    {
        if (false !== $encoding = mb_detect_encoding($string, null, true)) {
            return mb_strtolower($string, $encoding);
        }

        return strtolower($string);
    }

    /**
     * Canonicalizes the given path.
     *
     * During normalization, all slashes are replaced by forward slashes ("/").
     * Furthermore, all "." and ".." segments are removed as far as possible.
     * ".." segments at the beginning of relative paths are not removed.
     *
     * ```php
     * echo Path::canonicalize("\symfony\puli\..\css\style.css");
     * // => /symfony/css/style.css
     *
     * echo Path::canonicalize("../css/./style.css");
     * // => ../css/style.css
     * ```
     *
     * This method is able to deal with both UNIX and Windows paths.
     */
    public static function canonicalize(string $path): string
    {
        if ($path === '') {
            return '';
        }

        // This method is called by many other methods in this class. Buffer
        // the canonicalized paths to make up for the severe performance
        // decrease.
        if (isset(self::$buffer[$path])) {
            return self::$buffer[$path];
        }

        // Replace "~" with user's home directory.
        if ($path[0] === '~') {
            $path = self::home_directory().substr($path, 1);
        }

        $path = self::clean($path);

        [$root, $pathWithoutRoot] = self::split($path);

        $canonicalParts = self::canonical_parts($root, $pathWithoutRoot);

        // Add the root directory again
        self::$buffer[$path] = $canonicalPath = $root.implode('/', $canonicalParts);
        self::$bufferSize++;

        // Clean up regularly to prevent memory leaks
        if (self::$bufferSize > self::CLEANUP_THRESHOLD) {
            self::$buffer = \array_slice(self::$buffer, -self::CLEANUP_SIZE, null, true);
            self::$bufferSize = self::CLEANUP_SIZE;
        }

        return $canonicalPath;
    }

    /**
     * Returns the directory part of the path.
     *
     * This method is similar to PHP's dirname(), but handles various cases
     * where dirname() returns a weird result:
     *
     *  - dirname() does not accept backslashes on UNIX
     *  - dirname("C:/symfony") returns "C:", not "C:/"
     *  - dirname("C:/") returns ".", not "C:/"
     *  - dirname("C:") returns ".", not "C:/"
     *  - dirname("symfony") returns ".", not ""
     *  - dirname() does not canonicalize the result
     *
     * This method fixes these shortcomings and behaves like dirname()
     * otherwise.
     *
     * The result is a canonical path.
     *
     * @return string The canonical directory part. Returns the root directory
     *                if the root directory is passed. Returns an empty string
     *                if a relative path is passed that contains no slashes.
     *                Returns an empty string if an empty string is passed.
     */
    public static function directory(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $path = self::canonicalize($path);

        // Maintain scheme
        if (false !== $schemeSeparatorPosition = strpos($path, '://')) {
            $scheme = substr($path, 0, $schemeSeparatorPosition + 3);
            $path = substr($path, $schemeSeparatorPosition + 3);
        } else {
            $scheme = '';
        }

        if (false === $dirSeparatorPosition = strrpos($path, '/')) {
            return '';
        }

        // Directory equals root directory "/"
        if ($dirSeparatorPosition === 0) {
            return $scheme.'/';
        }

        // Directory equals Windows root "C:/"
        if ($dirSeparatorPosition === 2 && ctype_alpha($path[0]) && $path[1] === ':') {
            return $scheme.substr($path, 0, 3);
        }

        return $scheme.substr($path, 0, $dirSeparatorPosition);
    }

    /**
     * Returns canonical path of the user's home directory.
     *
     * Supported operating systems:
     *
     *  - UNIX
     *  - Windows8 and upper
     *
     * If your operating system or environment isn't supported, an exception is thrown.
     *
     * The result is a canonical path.
     *
     * @throws RuntimeException If your operating system or environment isn't supported
     */
    public static function home_directory(): string
    {
        // For UNIX support
        if (getenv('HOME')) {
            return self::canonicalize(getenv('HOME'));
        }

        // For >= Windows8 support
        if (getenv('HOMEDRIVE') && getenv('HOMEPATH')) {
            return self::canonicalize(getenv('HOMEDRIVE').getenv('HOMEPATH'));
        }

        throw new RuntimeException("Cannot find the home directory path: Your environment or operating system isn't supported.");
    }

    /**
     * Returns the root directory of a path.
     *
     * The result is a canonical path.
     *
     * @return string The canonical root directory. Returns an empty string if
     *                the given path is relative or empty.
     */
    public static function root(string $path): string
    {
        if ($path === '') {
            return '';
        }

        // Maintain scheme
        if (false !== $schemeSeparatorPosition = strpos($path, '://')) {
            $scheme = substr($path, 0, $schemeSeparatorPosition + 3);
            $path = substr($path, $schemeSeparatorPosition + 3);
        } else {
            $scheme = '';
        }

        $firstCharacter = $path[0];

        // UNIX root "/" or "\" (Windows style)
        if ($firstCharacter === '/' || $firstCharacter === '\\') {
            return $scheme.'/';
        }

        $length = \strlen($path);

        // Windows root
        if ($length > 1 && $path[1] === ':' && ctype_alpha($firstCharacter)) {
            // Special case: "C:"
            if ($length === 2) {
                return $scheme.$path.'/';
            }

            // Normal case: "C:/ or "C:\"
            if ($path[2] === '/' || $path[2] === '\\') {
                return $scheme.$firstCharacter.$path[1].'/';
            }
        }

        return '';
    }

    /**
     * Returns the file name without the extension from a file path.
     *
     * @param  string|null  $extension  if specified, only that extension is cut
     *                                  off (may contain leading dot)
     */
    public static function filename(string $path, ?string $extension = null): string
    {
        if ($path === '') {
            return '';
        }

        if ($extension !== null) {
            // remove extension and trailing dot
            return rtrim(basename($path, $extension), '.');
        }

        return pathinfo($path, \PATHINFO_FILENAME);
    }

    /**
     * Returns the extension from a file path (without leading dot).
     *
     * @param  bool  $forceLowerCase  forces the extension to be lower-case
     */
    public static function extension(string $path, bool $forceLowerCase = false): string
    {
        if ($path === '') {
            return '';
        }

        $extension = pathinfo($path, \PATHINFO_EXTENSION);

        if ($forceLowerCase) {
            $extension = self::lower($extension);
        }

        return $extension;
    }

    /**
     * Returns whether the path has an (or the specified) extension.
     *
     * @param  string  $path  the path string
     * @param  string|string[]|null  $extensions  if null or not provided, checks if
     *                                            an extension exists, otherwise
     *                                            checks for the specified extension
     *                                            or array of extensions (with or
     *                                            without leading dot)
     * @param  bool  $ignoreCase  whether to ignore case-sensitivity
     */
    public static function has_extension(string $path, $extensions = null, bool $ignoreCase = false): bool
    {
        if ($path === '') {
            return false;
        }

        $actualExtension = self::extension($path, $ignoreCase);

        // Only check if path has any extension
        if ($extensions === [] || $extensions === null) {
            return $actualExtension !== '';
        }

        if (\is_string($extensions)) {
            $extensions = [$extensions];
        }

        foreach ($extensions as $key => $extension) {
            if ($ignoreCase) {
                $extension = self::lower($extension);
            }

            // remove leading '.' in extensions array
            $extensions[$key] = ltrim($extension, '.');
        }

        return \in_array($actualExtension, $extensions, true);
    }

    /**
     * Changes the extension of a path string.
     *
     * @param  string  $path  The path string with filename.ext to change.
     * @param  string  $extension  new extension (with or without leading dot)
     * @return string the path string with new file extension
     */
    public static function change_extension(string $path, string $extension): string
    {
        if ($path === '') {
            return '';
        }

        $actualExtension = self::extension($path);
        $extension = ltrim($extension, '.');

        // No extension for paths
        if (str_ends_with($path, '/')) {
            return $path;
        }

        // No actual extension in path
        if (! $actualExtension) {
            return $path.(str_ends_with($path, '.') ? '' : '.').$extension;
        }

        return substr($path, 0, -\strlen($actualExtension)).$extension;
    }

    /**
     * Turns a relative path into an absolute path in canonical form.
     *
     * Usually, the relative path is appended to the given base path. Dot
     * segments ("." and "..") are removed/collapsed and all slashes turned
     * into forward slashes.
     *
     * ```php
     * echo Path::absolute("../style.css", "/symfony/puli/css");
     * // => /symfony/puli/style.css
     * ```
     *
     * If an absolute path is passed, that path is returned unless its root
     * directory is different than the one of the base path. In that case, an
     * exception is thrown.
     *
     * ```php
     * Path::absolute("/style.css", "/symfony/puli/css");
     * // => /style.css
     *
     * Path::absolute("C:/style.css", "C:/symfony/puli/css");
     * // => C:/style.css
     *
     * Path::absolute("C:/style.css", "/symfony/puli/css");
     * // InvalidArgumentException
     * ```
     *
     * If the base path is not an absolute path, an exception is thrown.
     *
     * The result is a canonical path.
     *
     * @param  string  $basePath  an absolute base path
     *
     * @throws InvalidArgumentException if the base path is not absolute or if
     *                                  the given path is an absolute path with
     *                                  a different root than the base path
     */
    public static function absolute(string $path, string $basePath): string
    {
        if ($basePath === '') {
            throw new InvalidArgumentException(\sprintf('The base path must be a non-empty string. Got: "%s".', $basePath));
        }

        if (! self::is_absolute($basePath)) {
            throw new InvalidArgumentException(\sprintf('The base path "%s" is not an absolute path.', $basePath));
        }

        if (self::is_absolute($path)) {
            return self::canonicalize($path);
        }

        if (false !== $schemeSeparatorPosition = strpos($basePath, '://')) {
            $scheme = substr($basePath, 0, $schemeSeparatorPosition + 3);
            $basePath = substr($basePath, $schemeSeparatorPosition + 3);
        } else {
            $scheme = '';
        }

        return $scheme.self::canonicalize(rtrim($basePath, '/\\').'/'.$path);
    }

    public static function is_absolute(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        // Strip scheme
        if (false !== ($schemeSeparatorPosition = strpos($path, '://')) && $schemeSeparatorPosition !== 1) {
            $path = substr($path, $schemeSeparatorPosition + 3);
        }

        $firstCharacter = $path[0];

        // UNIX root "/" or "\" (Windows style)
        if ($firstCharacter === '/' || $firstCharacter === '\\') {
            return true;
        }

        // Windows root
        if (\strlen($path) > 1 && ctype_alpha($firstCharacter) && $path[1] === ':') {
            // Special case: "C:"
            if (\strlen($path) === 2) {
                return true;
            }

            // Normal case: "C:/ or "C:\"
            if ($path[2] === '/' || $path[2] === '\\') {
                return true;
            }
        }

        return false;
    }

    /**
     * Turns a path into a relative path.
     *
     * The relative path is created relative to the given base path:
     *
     * ```php
     * echo Path::relative("/symfony/style.css", "/symfony/puli");
     * // => ../style.css
     * ```
     *
     * If a relative path is passed and the base path is absolute, the relative
     * path is returned unchanged:
     *
     * ```php
     * Path::relative("style.css", "/symfony/puli/css");
     * // => style.css
     * ```
     *
     * If both paths are relative, the relative path is created with the
     * assumption that both paths are relative to the same directory:
     *
     * ```php
     * Path::relative("style.css", "symfony/puli/css");
     * // => ../../../style.css
     * ```
     *
     * If both paths are absolute, their root directory must be the same,
     * otherwise an exception is thrown:
     *
     * ```php
     * Path::relative("C:/symfony/style.css", "/symfony/puli");
     * // InvalidArgumentException
     * ```
     *
     * If the passed path is absolute, but the base path is not, an exception
     * is thrown as well:
     *
     * ```php
     * Path::relative("/symfony/style.css", "symfony/puli");
     * // InvalidArgumentException
     * ```
     *
     * If the base path is not an absolute path, an exception is thrown.
     *
     * The result is a canonical path.
     *
     * @throws InvalidArgumentException if the base path is not absolute or if
     *                                  the given path has a different root
     *                                  than the base path
     */
    public static function relative(string $path, ?string $basePath = null): string
    {
        $path = self::canonicalize($path);
        $basePath = self::canonicalize($basePath ?? base_path());

        [$root, $relativePath] = self::split($path);
        [$baseRoot, $relativeBasePath] = self::split($basePath);

        // If the base path is given as absolute path and the path is already
        // relative, consider it to be relative to the given absolute path
        // already
        if ($root === '' && $baseRoot !== '') {
            // If base path is already in its root
            if ($relativeBasePath === '') {
                $relativePath = ltrim($relativePath, './\\');
            }

            return $relativePath;
        }

        // If the passed path is absolute, but the base path is not, we
        // cannot generate a relative path
        if ($root !== '' && $baseRoot === '') {
            throw new InvalidArgumentException(\sprintf('The absolute path "%s" cannot be made relative to the relative path "%s". You should provide an absolute base path instead.', $path, $basePath));
        }

        // Fail if the roots of the two paths are different
        if ($baseRoot && $root !== $baseRoot) {
            throw new InvalidArgumentException(\sprintf('The path "%s" cannot be made relative to "%s", because they have different roots ("%s" and "%s").', $path, $basePath, $root, $baseRoot));
        }

        if ($relativeBasePath === '') {
            return $relativePath;
        }

        // Build a "../../" prefix with as many "../" parts as necessary
        $parts = explode('/', $relativePath);
        $baseParts = explode('/', $relativeBasePath);
        $dotDotPrefix = '';

        // Once we found a non-matching part in the prefix, we need to add
        // "../" parts for all remaining parts
        $match = true;

        foreach ($baseParts as $index => $basePart) {
            if ($match && isset($parts[$index]) && $basePart === $parts[$index]) {
                unset($parts[$index]);

                continue;
            }

            $match = false;
            $dotDotPrefix .= '../';
        }

        return rtrim($dotDotPrefix.implode('/', $parts), '/');
    }

    public static function is_relative(string $path): bool
    {
        return ! self::is_absolute($path);
    }

    /**
     * Returns the longest common base path in canonical form of a set of paths or
     * `null` if the paths are on different Windows partitions.
     *
     * Dot segments ("." and "..") are removed/collapsed and all slashes turned
     * into forward slashes.
     *
     * ```php
     * $basePath = Path::base_path(
     *     '/symfony/css/style.css',
     *     '/symfony/css/..'
     * );
     * // => /symfony
     * ```
     *
     * The root is returned if no common base path can be found:
     *
     * ```php
     * $basePath = Path::base_path(
     *     '/symfony/css/style.css',
     *     '/puli/css/..'
     * );
     * // => /
     * ```
     *
     * If the paths are located on different Windows partitions, `null` is
     * returned.
     *
     * ```php
     * $basePath = Path::base_path(
     *     'C:/symfony/css/style.css',
     *     'D:/symfony/css/..'
     * );
     * // => null
     * ```
     */
    public static function base_path(string ...$paths): ?string
    {
        [$bpRoot, $basePath] = self::split(self::canonicalize(reset($paths)));

        for (next($paths); key($paths) !== null && $basePath !== ''; next($paths)) {
            [$root, $path] = self::split(self::canonicalize(current($paths)));

            // If we deal with different roots (e.g. C:/ vs. D:/), it's time
            // to quit
            if ($root !== $bpRoot) {
                return null;
            }

            // Make the base path shorter until it fits into path
            while (true) {
                if ($basePath === '.') {
                    // No more base paths
                    $basePath = '';

                    // next path
                    continue 2;
                }

                // Prevent false positives for common prefixes
                // see is_base_path()
                if (str_starts_with($path.'/', $basePath.'/')) {
                    // next path
                    continue 2;
                }

                $basePath = \dirname($basePath);
            }
        }

        return $bpRoot.$basePath;
    }

    /**
     * Returns whether a path is a base path of another path.
     *
     * Dot segments ("." and "..") are removed/collapsed and all slashes turned
     * into forward slashes.
     *
     * ```php
     * Path::is_base_path('/symfony', '/symfony/css');
     * // => true
     *
     * Path::is_base_path('/symfony', '/symfony');
     * // => true
     *
     * Path::is_base_path('/symfony', '/symfony/..');
     * // => false
     *
     * Path::is_base_path('/symfony', '/puli');
     * // => false
     * ```
     */
    public static function is_base_path(string $basePath, string $ofPath): bool
    {
        $basePath = self::canonicalize($basePath);
        $ofPath = self::canonicalize($ofPath);

        // Append slashes to prevent false positives when two paths have
        // a common prefix, for example /base/foo and /base/foobar.
        // Don't append a slash for the root "/", because then that root
        // won't be discovered as common prefix ("//" is not a prefix of
        // "/foobar/").
        return str_starts_with($ofPath.'/', rtrim($basePath, '/').'/');
    }

    /**
     * Returns whether the given path is on the local filesystem.
     */
    public static function is_local(string $path): bool
    {
        return $path !== '' && ! str_contains($path, '://');
    }

    /**
     * Splits a canonical path into its root directory and the remainder.
     *
     * If the path has no root directory, an empty root directory will be
     * returned.
     *
     * If the root directory is a Windows style partition, the resulting root
     * will always contain a trailing slash.
     *
     * list ($root, $path) = Path::split("C:/symfony")
     * // => ["C:/", "symfony"]
     *
     * list ($root, $path) = Path::split("C:")
     * // => ["C:/", ""]
     *
     * @return array{string, string} an array with the root directory and the remaining relative path
     */
    public static function split(string $path): array
    {
        if ($path === '') {
            return ['', ''];
        }

        // Remember scheme as part of the root, if any
        if (false !== $schemeSeparatorPosition = strpos($path, '://')) {
            $root = substr($path, 0, $schemeSeparatorPosition + 3);
            $path = substr($path, $schemeSeparatorPosition + 3);
        } else {
            $root = '';
        }

        $length = \strlen($path);

        // Remove and remember root directory
        if (str_starts_with($path, '/')) {
            $root .= '/';
            $path = $length > 1 ? substr($path, 1) : '';
        } elseif ($length > 1 && ctype_alpha($path[0]) && $path[1] === ':') {
            if ($length === 2) {
                // Windows special case: "C:"
                $root .= $path.'/';
                $path = '';
            } elseif ($path[2] === '/') {
                // Windows normal case: "C:/"..
                $root .= substr($path, 0, 3);
                $path = $length > 3 ? substr($path, 3) : '';
            }
        }

        return [$root, $path];
    }

    /**
     * Joins two or more path strings into a canonical path.
     */
    public static function join(string ...$paths): string
    {
        $finalPath = null;
        $wasScheme = false;

        foreach ($paths as $path) {
            if ($path === '') {
                continue;
            }

            if ($finalPath === null) {
                // For first part we keep slashes, like '/top', 'C:\' or 'phar://'
                $finalPath = $path;
                $wasScheme = str_contains($path, '://');

                continue;
            }

            // Only add slash if previous part didn't end with '/' or '\'
            if (! \in_array(substr($finalPath, -1), ['/', '\\'], true)) {
                $finalPath .= '/';
            }

            // If first part included a scheme like 'phar://' we allow \current part to start with '/', otherwise trim
            $finalPath .= $wasScheme ? $path : ltrim($path, '/');
            $wasScheme = false;
        }

        if ($finalPath === null) {
            return '';
        }

        return self::canonicalize($finalPath);
    }

    /**
     * @return string[]
     */
    public static function canonical_parts(string $root, string $pathWithoutRoot): array
    {
        $parts = explode('/', $pathWithoutRoot);

        $canonicalParts = [];

        // Collapse "." and "..", if possible
        foreach ($parts as $part) {
            if ($part === '.' || $part === '') {
                continue;
            }

            // Collapse ".." with the previous part, if one exists
            // Don't collapse ".." if the previous part is also ".."
            if ($part === '..' && \count($canonicalParts) > 0 && $canonicalParts[\count($canonicalParts) - 1] !== '..') {
                array_pop($canonicalParts);

                continue;
            }

            // Only add ".." prefixes for relative paths
            if ($part !== '..' || $root === '') {
                $canonicalParts[] = $part;
            }
        }

        return $canonicalParts;
    }
}
