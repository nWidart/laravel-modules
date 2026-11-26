<?php

namespace Nwidart\Modules\Support;

use JsonException;

/**
 * Set or remove one member of the top-level object in a JSON text, keeping every other byte.
 *
 * Decoding and encoding the whole document would rewrite what PHP cannot hold exactly (integers
 * past PHP_INT_MAX, floats with more digits than a double), escapes such as é, and the
 * layout. This edits the text instead: a scanner finds the members of the top-level object, and
 * only the edited member's text changes.
 *
 * The text must be valid JSON holding an object; check it with json_decode() first.
 */
final class JsonObjectEditor
{
    /**
     * JSON whitespace.
     */
    private const WHITESPACE = " \t\n\r";

    /**
     * Set the member $key to $value: replace the value of the last member with that key (the one
     * json_decode() reads), or append the member after the last one.
     *
     * The value is encoded with $flags. In a pretty printed object (a line break before the first
     * member, or no member yet) it is pretty printed with the object's indent and line breaks.
     *
     * @throws JsonException when the value cannot be encoded
     */
    public static function set(string $json, string $key, mixed $value, int $flags = 0): string
    {
        $object = self::scan($json);
        $members = $object['members'];
        $newline = str_contains($json, "\r\n") ? "\r\n" : "\n";

        $first = $members[0] ?? null;
        $gap = substr($json, $object['open'] + 1, ($first['start'] ?? $object['close']) - $object['open'] - 1);
        $pretty = $first === null || str_contains($gap, "\n");
        $indent = $first !== null && $pretty ? substr($gap, strrpos($gap, "\n") + 1) : '';
        $indent = $indent === '' ? '    ' : $indent;

        $encoded = self::encode($value, $flags, $pretty, $indent, $newline);

        foreach (array_reverse($members) as $member) {
            if ($member['key'] === $key) {
                return substr($json, 0, $member['valueStart']).$encoded.substr($json, $member['valueEnd']);
            }
        }

        $name = json_encode($key, ($flags & ~JSON_PRETTY_PRINT) | JSON_THROW_ON_ERROR);
        $text = $pretty ? "{$name}: {$encoded}" : "{$name}:{$encoded}";

        if ($first === null) {
            return substr($json, 0, $object['open'] + 1).$newline.$indent.$text.$newline.substr($json, $object['close']);
        }

        $last = $members[array_key_last($members)];
        $separator = $pretty ? ','.$newline.$indent : ',';

        return substr($json, 0, $last['valueEnd']).$separator.$text.substr($json, $last['valueEnd']);
    }

    /**
     * Remove every member $key of the top-level object, with its separating comma.
     */
    public static function remove(string $json, string $key): string
    {
        while (true) {
            $object = self::scan($json);
            $members = $object['members'];

            $index = null;

            foreach ($members as $i => $member) {
                if ($member['key'] === $key) {
                    $index = $i;

                    break;
                }
            }

            if ($index === null) {
                return $json;
            }

            if (count($members) === 1) {
                // The only member: leave an empty object.
                $json = substr($json, 0, $object['open'] + 1).substr($json, $object['close']);
            } elseif (isset($members[$index + 1])) {
                // Up to the next member: the member, its comma and the space before the next one.
                $json = substr($json, 0, $members[$index]['start']).substr($json, $members[$index + 1]['start']);
            } else {
                // The last member: from the end of the previous value, so its comma goes too.
                $json = substr($json, 0, $members[$index - 1]['valueEnd']).substr($json, $members[$index]['valueEnd']);
            }
        }
    }

    /**
     * Encode a value for a member of the top-level object.
     *
     * @throws JsonException
     */
    private static function encode(mixed $value, int $flags, bool $pretty, string $indent, string $newline): string
    {
        if (! $pretty) {
            return json_encode($value, ($flags & ~JSON_PRETTY_PRINT) | JSON_THROW_ON_ERROR);
        }

        $lines = explode("\n", json_encode($value, $flags | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        // json_encode() indents 4 spaces per level from level 0; the member sits at level 1.
        // Strings hold no raw line breaks, so leading spaces are only ever indentation.
        foreach ($lines as $i => $line) {
            if ($i > 0) {
                $content = ltrim($line, ' ');
                $lines[$i] = str_repeat($indent, intdiv(strlen($line) - strlen($content), 4) + 1).$content;
            }
        }

        return implode($newline, $lines);
    }

    /**
     * Find the members of the top-level object.
     *
     * @return array{open: int, close: int, members: list<array{key: string, start: int, valueStart: int, valueEnd: int}>}
     */
    private static function scan(string $json): array
    {
        $open = self::skipWhitespace($json, 0);
        $position = self::skipWhitespace($json, $open + 1);
        $members = [];

        while ($json[$position] !== '}') {
            $start = $position;
            $keyEnd = self::skipString($json, $start);

            /** @var string $key */
            $key = json_decode(substr($json, $start, $keyEnd - $start));

            // Past the colon.
            $valueStart = self::skipWhitespace($json, self::skipWhitespace($json, $keyEnd) + 1);
            $valueEnd = self::skipValue($json, $valueStart);

            $members[] = ['key' => $key, 'start' => $start, 'valueStart' => $valueStart, 'valueEnd' => $valueEnd];

            $position = self::skipWhitespace($json, $valueEnd);

            if ($json[$position] === ',') {
                $position = self::skipWhitespace($json, $position + 1);
            }
        }

        return ['open' => $open, 'close' => $position, 'members' => $members];
    }

    /**
     * Get the position after the value that starts at $position.
     */
    private static function skipValue(string $json, int $position): int
    {
        $char = $json[$position];

        if ($char === '"') {
            return self::skipString($json, $position);
        }

        if ($char !== '{' && $char !== '[') {
            // A number, true, false or null.
            return $position + strcspn($json, ',}]'.self::WHITESPACE, $position);
        }

        $depth = 0;
        $length = strlen($json);

        while ($position < $length) {
            $char = $json[$position];

            if ($char === '"') {
                $position = self::skipString($json, $position);

                continue;
            }

            if ($char === '{' || $char === '[') {
                $depth++;
            } elseif ($char === '}' || $char === ']') {
                $depth--;
            }

            $position++;

            if ($depth === 0) {
                break;
            }
        }

        return $position;
    }

    /**
     * Get the position after the string that starts (with its quote) at $position.
     */
    private static function skipString(string $json, int $position): int
    {
        $length = strlen($json);
        $position++;

        while ($position < $length) {
            // Jump to the next quote or backslash.
            $position += strcspn($json, '"\\', $position);

            if (($json[$position] ?? '"') === '\\') {
                $position += 2;

                continue;
            }

            return $position + 1;
        }

        return $length;
    }

    /**
     * Get the position of the first character at or after $position that is not whitespace.
     */
    private static function skipWhitespace(string $json, int $position): int
    {
        return $position + strspn($json, self::WHITESPACE, $position);
    }
}
