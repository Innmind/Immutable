<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\{
    Exception\RegexException,
    Exception\SubstringException,
    Exception\LogicException
};

final class Str
{
    private const PAD_RIGHT = STR_PAD_RIGHT;
    private const PAD_LEFT = STR_PAD_LEFT;
    private const PAD_BOTH = STR_PAD_BOTH;
    private const PREG_NO_FLAGS = 0;
    private const PREG_SPLIT_NO_EMPTY = PREG_SPLIT_NO_EMPTY;
    private const PREG_SPLIT_DELIM_CAPTURE = PREG_SPLIT_DELIM_CAPTURE;
    private const PREG_SPLIT_OFFSET_CAPTURE = PREG_SPLIT_OFFSET_CAPTURE;
    private const PREG_OFFSET_CAPTURE = PREG_OFFSET_CAPTURE;

    private string $value;
    private ?string $encoding;

    private function __construct(string $value, string $encoding = null)
    {
        $this->value = $value;
        $this->encoding = $encoding;
    }

    public static function of(string $value, string $encoding = null): self
    {
        return new self($value, $encoding);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->value;
    }

    public function encoding(): self
    {
        if (\is_null($this->encoding)) {
            $this->encoding = \mb_internal_encoding();
        }

        return new self($this->encoding);
    }

    public function toEncoding(string $encoding): self
    {
        return new self($this->value, $encoding);
    }

    /**
     * Split the string into a collection of ones
     *
     * @return Sequence<self>
     */
    public function split(string $delimiter = null): Sequence
    {
        if (\is_null($delimiter) || $delimiter === '') {
            return $this->chunk();
        }

        $parts = \explode($delimiter, $this->value);
        $sequence = Sequence::of(self::class);

        foreach ($parts as $part) {
            $sequence = $sequence->add(new self($part, $this->encoding));
        }

        return $sequence;
    }

    /**
     * Returns a collection of the string splitted by the given chunk size
     *
     * @return Sequence<self>
     */
    public function chunk(int $size = 1): Sequence
    {
        $sequence = Sequence::of(self::class);
        $parts = \mb_str_split($this->value, $size, (string) $this->encoding());

        foreach ($parts as $value) {
            $sequence = $sequence->add(new self($value, $this->encoding));
        }

        return $sequence;
    }

    /**
     * Returns the position of the first occurence of the string
     *
     * @throws SubstringException If the string is not found
     */
    public function position(string $needle, int $offset = 0): int
    {
        $position = \mb_strpos($this->value, $needle, $offset, (string) $this->encoding());

        if ($position === false) {
            throw new SubstringException(\sprintf(
                'Substring "%s" not found',
                $needle
            ));
        }

        return (int) $position;
    }

    /**
     * Replace all occurences of the search string with the replacement one
     */
    public function replace(string $search, string $replacement): self
    {
        if (!$this->contains($search)) {
            return $this;
        }

        return $this
            ->split($search)
            ->join($replacement);
    }

    /**
     * Returns the string following the given delimiter
     *
     * @throws SubstringException If the string is not found
     */
    public function str(string $delimiter): self
    {
        $sub = \mb_strstr($this->value, $delimiter, false, (string) $this->encoding());

        if ($sub === false) {
            throw new SubstringException(\sprintf(
                'Substring "%s" not found',
                $delimiter
            ));
        }

        return new self($sub, $this->encoding);
    }

    /**
     * Return the string in upper case
     */
    public function toUpper(): self
    {
        return new self(\mb_strtoupper($this->value), $this->encoding);
    }

    /**
     * Return the string in lower case
     */
    public function toLower(): self
    {
        return new self(\mb_strtolower($this->value), $this->encoding);
    }

    /**
     * Return the string length
     */
    public function length(): int
    {
        return \mb_strlen($this->value, (string) $this->encoding());
    }

    public function empty(): bool
    {
        return $this->value === '';
    }

    /**
     * Reverse the string
     */
    public function reverse(): self
    {
        return $this
            ->chunk()
            ->reverse()
            ->join('');
    }

    /**
     * Pad to the right
     */
    public function rightPad(int $length, string $character = ' '): self
    {
        return $this->pad($length, $character, self::PAD_RIGHT);
    }

    /**
     * Pad to the left
     */
    public function leftPad(int $length, string $character = ' '): self
    {
        return $this->pad($length, $character, self::PAD_LEFT);
    }

    /**
     * Pad both sides
     */
    public function uniPad(int $length, string $character = ' '): self
    {
        return $this->pad($length, $character, self::PAD_BOTH);
    }

    /**
     * Find length of initial segment not matching mask
     */
    public function cspn(string $mask, int $start = 0, int $length = null): int
    {
        if ($length === null) {
            $value = \strcspn($this->value, $mask, $start);
        } else {
            $value = \strcspn(
                $this->value,
                $mask,
                $start,
                $length
            );
        }

        return (int) $value;
    }

    /**
     * Repeat the string n times
     */
    public function repeat(int $repeat): self
    {
        return new self(\str_repeat($this->value, $repeat), $this->encoding);
    }

    /**
     * Shuffle the string
     */
    public function shuffle(): self
    {
        $parts = $this->chunk()->toArray();
        \shuffle($parts);

        return new self(\implode('', $parts), $this->encoding);
    }

    public function stripSlashes(): self
    {
        return new self(\stripslashes($this->value), $this->encoding);
    }

    /**
     * Strip C-like slashes
     */
    public function stripCSlashes(): self
    {
        return new self(\stripcslashes($this->value), $this->encoding);
    }

    /**
     * Return the word count
     */
    public function wordCount(string $charlist = ''): int
    {
        return (int) \str_word_count(
            $this->value,
            0,
            $charlist
        );
    }

    /**
     * Return the collection of words
     *
     * @return Map<int, self>
     */
    public function words(string $charlist = ''): Map
    {
        $words = \str_word_count($this->value, 2, $charlist);
        $map = Map::of('int', self::class);

        foreach ($words as $position => $word) {
            $map = $map->put($position, new self($word, $this->encoding));
        }

        return $map;
    }

    /**
     * Split the string using a regular expression
     *
     * @return Sequence<self>
     */
    public function pregSplit(string $regex, int $limit = -1): Sequence
    {
        $strings = \preg_split($regex, $this->value, $limit);
        $sequence = Sequence::of(self::class);

        foreach ($strings as $string) {
            $sequence = $sequence->add(new self($string, $this->encoding));
        }

        return $sequence;
    }

    /**
     * Check if the string match the given regular expression
     *
     * @throws Exception If the regex failed
     */
    public function matches(string $regex): bool
    {
        if (\func_num_args() !== 1) {
            throw new LogicException('Offset is no longer supported');
        }

        return RegExp::of($regex)->matches($this);
    }

    /**
     * Return a collection of the elements matching the regex
     *
     * @throws Exception If the regex failed
     *
     * @return Map<scalar, self>
     */
    public function capture(string $regex): Map
    {
        return RegExp::of($regex)->capture($this);
    }

    /**
     * Replace part of the string by using a regular expression
     *
     * @throws Exception If the regex failed
     */
    public function pregReplace(
        string $regex,
        string $replacement,
        int $limit = -1
    ): self {
        $value = \preg_replace(
            $regex,
            $replacement,
            $this->value,
            $limit
        );

        if ($value === null) {
            throw new RegexException('', \preg_last_error());
        }

        return new self($value, $this->encoding);
    }

    /**
     * Return part of the string
     */
    public function substring(int $start, int $length = null): self
    {
        if ($this->length() === 0) {
            return $this;
        }

        $sub = \mb_substr($this->value, $start, $length, (string) $this->encoding());

        return new self($sub, $this->encoding);
    }

    public function take(int $size): self
    {
        return $this->substring(0, $size);
    }

    public function takeEnd(int $size): self
    {
        return $this->substring(-$size);
    }

    public function drop(int $size): self
    {
        return $this->substring($size);
    }

    public function dropEnd(int $size): self
    {
        return $this->substring(0, $this->length() - $size);
    }

    /**
     * Return a formatted string
     */
    public function sprintf(...$values): self
    {
        return new self(\sprintf($this->value, ...$values), $this->encoding);
    }

    /**
     * Return the string with the first letter as uppercase
     */
    public function ucfirst(): self
    {
        return $this
            ->substring(0, 1)
            ->toUpper()
            ->append((string) $this->substring(1));
    }

    /**
     * Return the string with the first letter as lowercase
     */
    public function lcfirst(): self
    {
        return $this
            ->substring(0, 1)
            ->toLower()
            ->append((string) $this->substring(1));
    }

    /**
     * Return a CamelCase representation of the string
     */
    public function camelize(): self
    {
        return $this
            ->pregSplit('/_| /')
            ->map(function(self $part) {
                return $part->ucfirst();
            })
            ->join('')
            ->toEncoding((string) $this->encoding());
    }

    /**
     * Append a string at the end of the current one
     */
    public function append(string $string): self
    {
        return new self((string) $this.$string, $this->encoding);
    }

    /**
     * Prepend a string at the beginning of the current one
     */
    public function prepend(string $string): self
    {
        return new self($string.(string) $this, $this->encoding);
    }

    /**
     * Check if the 2 strings are equal
     */
    public function equals(self $string): bool
    {
        return (string) $this === (string) $string;
    }

    /**
     * Trim the string
     */
    public function trim(string $mask = null): self
    {
        return new self(
            $mask === null ? \trim((string) $this) : \trim((string) $this, $mask),
            $this->encoding
        );
    }

    /**
     * Trim the right side of the string
     */
    public function rightTrim(string $mask = null): self
    {
        return new self(
            $mask === null ? \rtrim((string) $this) : \rtrim((string) $this, $mask),
            $this->encoding
        );
    }

    /**
     * Trim the left side of the string
     */
    public function leftTrim(string $mask = null): self
    {
        return new self(
            $mask === null ? \ltrim((string) $this) : \ltrim((string) $this, $mask),
            $this->encoding
        );
    }

    /**
     * Check if the given string is present in the current one
     */
    public function contains(string $value): bool
    {
        try {
            $this->position($value);

            return true;
        } catch (SubstringException $e) {
            return false;
        }
    }

    /**
     * Check if the current string starts with the given string
     */
    public function startsWith(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        try {
            return $this->position($value) === 0;
        } catch (SubstringException $e) {
            return false;
        }
    }

    /**
     * Check if the current string ends with the given string
     */
    public function endsWith(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        return (string) $this->takeEnd(self::of($value, $this->encoding)->length()) === $value;
    }

    /**
     * Quote regular expression characters
     */
    public function pregQuote(string $delimiter = ''): self
    {
        return new self(\preg_quote((string) $this, $delimiter), $this->encoding);
    }

    /**
     * Pad the string
     */
    private function pad(
        int $length,
        string $character = ' ',
        int $direction = self::PAD_RIGHT
    ): self {
        return new self(\str_pad(
            $this->value,
            $length,
            $character,
            $direction
        ), $this->encoding);
    }
}
