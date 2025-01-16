<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\InvalidRegex;

/**
 * @psalm-immutable
 */
final class Str implements \Stringable
{
    private string $value;
    private Str\Encoding $encoding;

    private function __construct(string $value, ?Str\Encoding $encoding = null)
    {
        $this->value = $value;
        $this->encoding = $encoding ?? Str\Encoding::utf8;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @psalm-pure
     */
    public static function of(string $value, ?Str\Encoding $encoding = null): self
    {
        return new self($value, $encoding);
    }

    /**
     * Concatenate all elements with the given separator
     *
     * @param Set<string|\Stringable>|Sequence<string|\Stringable> $structure
     */
    public function join(Set|Sequence $structure): self
    {
        return new self(
            \implode($this->value, $structure->toList()),
            $this->encoding,
        );
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function encoding(): Str\Encoding
    {
        return $this->encoding;
    }

    public function toEncoding(Str\Encoding $encoding): self
    {
        return self::of($this->value, $encoding);
    }

    /**
     * @param callable(self): bool $filter
     *
     * @return Maybe<self>
     */
    public function maybe(callable $filter): Maybe
    {
        return Maybe::just($this)->filter($filter);
    }

    /**
     * Split the string into a collection of ones
     *
     * @return Sequence<self>
     */
    public function split(?string $delimiter = null): Sequence
    {
        if (\is_null($delimiter) || $delimiter === '') {
            return $this->chunk();
        }

        $parts = \explode($delimiter, $this->value);
        /** @var Sequence<self> */
        $sequence = Sequence::of();

        foreach ($parts as $part) {
            $sequence = ($sequence)(new self($part, $this->encoding));
        }

        return $sequence;
    }

    /**
     * Returns a collection of the string splitted by the given chunk size
     *
     * @param positive-int $size
     *
     * @return Sequence<self>
     */
    public function chunk(int $size = 1): Sequence
    {
        /** @var Sequence<self> */
        $sequence = Sequence::of();
        $parts = \mb_str_split($this->value, $size, $this->encoding->toString());

        foreach ($parts as $value) {
            $sequence = ($sequence)(new self($value, $this->encoding));
        }

        return $sequence;
    }

    /**
     * Returns the position of the first occurence of the string
     *
     * @param 0|positive-int $offset
     *
     * @return Maybe<0|positive-int>
     */
    public function position(string|\Stringable $needle, int $offset = 0): Maybe
    {
        $position = \mb_strpos($this->value, (string) $needle, $offset, $this->encoding->toString());

        if ($position === false) {
            /** @var Maybe<0|positive-int> */
            return Maybe::nothing();
        }

        /** @var Maybe<0|positive-int> */
        return Maybe::just($position);
    }

    /**
     * Replace all occurences of the search string with the replacement one
     */
    public function replace(string|\Stringable $search, string|\Stringable $replacement): self
    {
        if (!$this->contains($search)) {
            return $this;
        }

        $parts = $this
            ->split((string) $search)
            ->map(static fn($v) => $v->toString());

        return self::of((string) $replacement, $this->encoding)->join($parts);
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
     *
     * @return 0|positive-int
     */
    public function length(): int
    {
        return \mb_strlen($this->value, $this->encoding->toString());
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
        $parts = $this
            ->chunk()
            ->reverse()
            ->map(static fn($v) => $v->toString());

        return self::of('', $this->encoding)->join($parts);
    }

    /**
     * Pad to the right
     *
     * @param positive-int $length
     */
    public function rightPad(int $length, string|\Stringable $character = ' '): self
    {
        return $this->pad($length, $character, \STR_PAD_RIGHT);
    }

    /**
     * Pad to the left
     *
     * @param positive-int $length
     */
    public function leftPad(int $length, string|\Stringable $character = ' '): self
    {
        return $this->pad($length, $character, \STR_PAD_LEFT);
    }

    /**
     * Pad both sides
     *
     * @param positive-int $length
     */
    public function uniPad(int $length, string|\Stringable $character = ' '): self
    {
        return $this->pad($length, $character, \STR_PAD_BOTH);
    }

    /**
     * Repeat the string n times
     *
     * @param positive-int $repeat
     */
    public function repeat(int $repeat): self
    {
        return new self(\str_repeat($this->value, $repeat), $this->encoding);
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
    public function wordCount(string|\Stringable $charlist = ''): int
    {
        return \str_word_count(
            $this->value,
            0,
            (string) $charlist,
        );
    }

    /**
     * Return the collection of words
     *
     * @return Map<int, self>
     */
    public function words(string|\Stringable $charlist = ''): Map
    {
        /** @var list<string> */
        $words = \str_word_count($this->value, 2, (string) $charlist);
        /** @var Map<int, self> */
        $map = Map::of();

        foreach ($words as $position => $word) {
            $map = ($map)($position, new self($word, $this->encoding));
        }

        return $map;
    }

    /**
     * Split the string using a regular expression
     *
     * @return Sequence<self>
     */
    public function pregSplit(string|\Stringable $regex, int $limit = -1): Sequence
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        $strings = \preg_split((string) $regex, $this->value, $limit);

        if ($strings === false) {
            /** @psalm-suppress ImpureFunctionCall */
            throw new InvalidRegex('', \preg_last_error());
        }

        /** @var Sequence<self> */
        $sequence = Sequence::of();

        foreach ($strings as $string) {
            $sequence = ($sequence)(new self($string, $this->encoding));
        }

        return $sequence;
    }

    /**
     * Check if the string match the given regular expression
     *
     * @throws InvalidRegex If the regex failed
     */
    public function matches(string|\Stringable $regex): bool
    {
        return RegExp::of((string) $regex)->matches($this);
    }

    /**
     * Return a collection of the elements matching the regex
     *
     * @throws InvalidRegex If the regex failed
     *
     * @return Map<int|string, self>
     */
    public function capture(string|\Stringable $regex): Map
    {
        return RegExp::of((string) $regex)->capture($this);
    }

    /**
     * Replace part of the string by using a regular expression
     *
     * @throws InvalidRegex If the regex failed
     */
    public function pregReplace(
        string|\Stringable $regex,
        string|\Stringable $replacement,
        int $limit = -1,
    ): self {
        /** @psalm-suppress ArgumentTypeCoercion */
        $value = \preg_replace(
            (string) $regex,
            (string) $replacement,
            $this->value,
            $limit,
        );

        if ($value === null) {
            /** @psalm-suppress ImpureFunctionCall */
            throw new InvalidRegex('', \preg_last_error());
        }

        return new self($value, $this->encoding);
    }

    /**
     * Return part of the string
     *
     * @param 0|positive-int $length
     */
    public function substring(int $start, ?int $length = null): self
    {
        if ($this->empty()) {
            return $this;
        }

        $sub = \mb_substr($this->value, $start, $length, $this->encoding->toString());

        return new self($sub, $this->encoding);
    }

    /**
     * @param 0|positive-int $size
     */
    public function take(int $size): self
    {
        return $this->substring(0, $size);
    }

    /**
     * @param 0|positive-int $size
     */
    public function takeEnd(int $size): self
    {
        return $this->substring(-$size);
    }

    /**
     * @param 0|positive-int $size
     */
    public function drop(int $size): self
    {
        return $this->substring($size);
    }

    /**
     * @param 0|positive-int $size
     */
    public function dropEnd(int $size): self
    {
        return $this->substring(0, \max(0, $this->length() - $size));
    }

    /**
     * Return a formatted string
     */
    public function sprintf(string ...$values): self
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
            ->append($this->substring(1)->toString());
    }

    /**
     * Return the string with the first letter as lowercase
     */
    public function lcfirst(): self
    {
        return $this
            ->substring(0, 1)
            ->toLower()
            ->append($this->substring(1)->toString());
    }

    /**
     * Return a camelCase representation of the string
     */
    public function camelize(): self
    {
        $words = $this
            ->pregSplit('/_| /')
            ->map(static fn(self $part) => $part->ucfirst()->toString());

        return self::of('', $this->encoding)
            ->join($words)
            ->lcfirst();
    }

    /**
     * Append a string at the end of the current one
     */
    public function append(string|\Stringable $string): self
    {
        return new self($this->value.((string) $string), $this->encoding);
    }

    /**
     * Prepend a string at the beginning of the current one
     */
    public function prepend(string|\Stringable $string): self
    {
        return new self(((string) $string).$this->value, $this->encoding);
    }

    /**
     * Check if the 2 strings are equal
     */
    public function equals(self $string): bool
    {
        return $this->toString() === $string->toString();
    }

    /**
     * Trim the string
     */
    public function trim(?string $mask = null): self
    {
        return new self(
            $mask === null ? \trim($this->value) : \trim($this->value, $mask),
            $this->encoding,
        );
    }

    /**
     * Trim the right side of the string
     */
    public function rightTrim(?string $mask = null): self
    {
        return new self(
            $mask === null ? \rtrim($this->value) : \rtrim($this->value, $mask),
            $this->encoding,
        );
    }

    /**
     * Trim the left side of the string
     */
    public function leftTrim(?string $mask = null): self
    {
        return new self(
            $mask === null ? \ltrim($this->value) : \ltrim($this->value, $mask),
            $this->encoding,
        );
    }

    /**
     * Check if the given string is present in the current one
     */
    public function contains(string|\Stringable $value): bool
    {
        return \mb_strpos($this->value, (string) $value, 0, $this->encoding->toString()) !== false;
    }

    /**
     * Check if the current string starts with the given string
     */
    public function startsWith(string|\Stringable $value): bool
    {
        if ($value === '') {
            return true;
        }

        return \mb_strpos($this->value, (string) $value, 0, $this->encoding->toString()) === 0;
    }

    /**
     * Check if the current string ends with the given string
     */
    public function endsWith(string|\Stringable $value): bool
    {
        $value = (string) $value;

        if ($value === '') {
            return true;
        }

        $length = self::of($value, $this->encoding)->length();

        return $this->takeEnd($length)->toString() === $value;
    }

    /**
     * Quote regular expression characters
     */
    public function pregQuote(string|\Stringable $delimiter = ''): self
    {
        return new self(\preg_quote($this->value, (string) $delimiter), $this->encoding);
    }

    /**
     * @param callable(string, Str\Encoding): string $map Second string is the encoding
     */
    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->value, $this->encoding), $this->encoding);
    }

    /**
     * @param callable(string, Str\Encoding): self $map Second string is the encoding
     */
    public function flatMap(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this->value, $this->encoding);
    }

    /**
     * Pad the string
     */
    private function pad(int $length, string|\Stringable $character, int $direction): self
    {
        return new self(\str_pad(
            $this->value,
            $length,
            (string) $character,
            $direction,
        ), $this->encoding);
    }
}
