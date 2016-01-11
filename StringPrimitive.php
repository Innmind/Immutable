<?php

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\TypeException;
use Innmind\Immutable\Exception\RegexException;
use Innmind\Immutable\Exception\SubstringException;

class StringPrimitive implements PrimitiveInterface, StringableInterface
{
    private $value;

    public function __construct($value)
    {
        if (!is_string($value)) {
            throw new TypeException('Value must be a string');
        }

        $this->value = (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toPrimitive()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * Split the string into a collection of ones
     *
     * @param string $delimiter
     *
     * @return TypedCollection
     */
    public function split($delimiter = null)
    {
        $delimiter = (string) $delimiter;
        $parts = empty($delimiter) ?
                str_split($this->value) : explode($delimiter, $this->value);
        $strings = [];

        foreach ($parts as $part) {
            $strings[] = new self($part);
        }

        return new TypedCollection(
            self::class,
            $strings
        );
    }

    /**
     * Returns a collection of the string splitted by the given chunk size
     *
     * @param int $size
     *
     * @return TypedCollection
     */
    public function chunk($size = 1)
    {
        return $this->split()->chunk((int) $size);
    }

    /**
     * Returns the position of the first occurence of the string
     *
     * @param string $needle
     * @param int $offset
     *
     * @throws SubstringException If the string is not found
     *
     * @return int
     */
    public function pos($needle, $offset = 0)
    {
        $position = mb_strpos($this->value, (string) $needle, (int) $offset);

        if ($position === false) {
            throw new SubstringException(sprintf(
                'Substring "%s" not found',
                $needle
            ));
        }

        return (int) $position;
    }

    /**
     * Replace all occurences of the search string with the replacement one
     *
     * @param string $search
     * @param string $replacement
     *
     * @return StringPrimitive
     */
    public function replace($search, $replacement)
    {
        return new self(str_replace(
            (string) $search,
            (string) $replacement,
            $this->value
        ));
    }

    /**
     * Returns the string following the given delimiter
     *
     * @param string $delimiter
     *
     * @throws SubstringException If the string is not found
     *
     * @return StringInterface
     */
    public function str($delimiter)
    {
        $sub = mb_strstr($this->value, (string) $delimiter);

        if ($sub === false) {
            throw new SubstringException(sprintf(
                'Substring "%s" not found',
                $delimiter
            ));
        }

        return new self($sub);
    }

    /**
     * Return the string in upper case
     *
     * @return StringPrimitive
     */
    public function toUpper()
    {
        return new self(mb_strtoupper($this->value));
    }

    /**
     * Return the string in lower case
     *
     * @return StringPrimitive
     */
    public function toLower()
    {
        return new self(mb_strtolower($this->value));
    }

    /**
     * Return the string length
     *
     * @return int
     */
    public function length()
    {
        return strlen($this->value);
    }

    /**
     * Reverse the string
     *
     * @return StringPrimitive
     */
    public function reverse()
    {
        return new self(strrev($this->value));
    }

    /**
     * Pad the string
     *
     * @param int $length
     * @param string $character
     * @param int $direction
     *
     * @return StringPrimitive
     */
    public function pad($length, $character = ' ', $direction = STR_PAD_RIGHT)
    {
        return new self(str_pad(
            $this->value,
            (int) $length,
            (string) $character,
            $direction
        ));
    }

    /**
     * Pad to the right
     *
     * @param int $length
     * @param string $character
     *
     * @return StringPrimitive
     */
    public function rightPad($length, $character = ' ')
    {
        return $this->pad($length, $character, STR_PAD_RIGHT);
    }

    /**
     * Pad to the left
     *
     * @param int $length
     * @param string $character
     *
     * @return StringPrimitive
     */
    public function leftPad($length, $character = ' ')
    {
        return $this->pad($length, $character, STR_PAD_LEFT);
    }

    /**
     * Pad both sides
     *
     * @param int $length
     * @param string $character
     *
     * @return StringPrimitive
     */
    public function uniPad($length, $character = ' ')
    {
        return $this->pad($length, $character, STR_PAD_BOTH);
    }

    /**
     * Find length of initial segment not matching mask
     *
     * @param string $mask
     * @param int $start
     * @param int $length
     *
     * @return int
     */
    public function cspn($mask, $start = 0, $length = null)
    {
        $start = (int) $start;

        if ($length === null) {
            $value = strcspn($this->value, (string) $mask, $start);
        } else {
            $value = strcspn(
                $this->value,
                (string) $mask,
                $start,
                (int) $length
            );
        }

        return (int) $value;
    }

    /**
     * Repeat the string n times
     *
     * @param int $repeat
     *
     * @return StringPrimitive
     */
    public function repeat($repeat)
    {
        return new self(str_repeat($this->value, (int) $repeat));
    }

    /**
     * Shuffle the string
     *
     * @return StringPrimitive
     */
    public function shuffle()
    {
        return new self(str_shuffle($this->value));
    }

    /**
     * Strip slashes
     *
     * @return StringPrimitive
     */
    public function stripSlashes()
    {
        return new self(stripslashes($this->value));
    }

    /**
     * Strip C-like slashes
     *
     * @return StringPrimitive
     */
    public function stripCSlashes()
    {
        return new self(stripcslashes($this->value));
    }

    /**
     * Return the word count
     *
     * @param string $charlist
     *
     * @return int
     */
    public function wordCount($charlist = '')
    {
        return (int) str_word_count(
            $this->value,
            0,
            (string) $charlist
        );
    }

    /**
     * Return the collection of words
     *
     * @param string $charlist
     *
     * @return TypedCollection
     */
    public function words($charlist = '')
    {
        $words = str_word_count($this->value, 2, (string) $charlist);

        foreach ($words as &$word) {
            $word = new self($word);
        }

        return new TypedCollection(
            self::class,
            $words
        );
    }

    /**
     * Split the string using a regular expression
     *
     * @param string $regex
     * @param int $limit
     * @param int $flags
     *
     * @return TypedCollection
     */
    public function pregSplit($regex, $limit = -1, $flags = 0)
    {
        $strings = preg_split((string) $regex, $this->value, $limit, $flags);

        foreach ($strings as &$string) {
            $string = new self($string);
        }

        return new TypedCollection(
            self::class,
            $strings
        );
    }

    /**
     * Check if the string match the given regular expression
     *
     * @param string $regex
     * @param int $offset
     *
     * @throws Exception If the regex failed
     *
     * @return bool
     */
    public function match($regex, $offset = 0)
    {
        $matches = [];
        $value = preg_match((string) $regex, $this->value, $matches, 0, $offset);

        if ($value === false) {
            throw new RegexException('', preg_last_error());
        }

        return (bool) $value;
    }

    /**
     * Return a collection of the elements matching the regex
     *
     * @param string $regex
     * @param int $flags
     * @param int $offset
     *
     * @throws Exception If the regex failed
     *
     * @return TypedCollection
     */
    public function getMatches($regex, $flags = 0, $offset = 0)
    {
        $matches = [];
        $value = preg_match(
            (string) $regex,
            $this->value,
            $matches,
            $flags,
            $offset
        );

        foreach ($matches as &$match) {
            $match = new self($match);
        }

        if ($value === false) {
            throw new RegexException('', preg_last_error());
        }

        return new TypedCollection(self::class, $matches);
    }

    /**
     * Replace part of the string by using a regular expression
     *
     * @param string $regex
     * @param string $replacement
     * @param int $limit
     *
     * @throws Exception If the regex failed
     *
     * @return StringPrimitive
     */
    public function pregReplace($regex, $replacement, $limit = -1)
    {
        $value = preg_replace(
            (string) $regex,
            (string) $replacement,
            $this->value,
            $limit
        );

        if ($value === null) {
            throw new RegexException('', preg_last_error());
        }

        return new self($value);
    }

    /**
     * Return part of the string
     *
     * @param int $start
     * @param int $length
     *
     * @return StringPrimitive
     */
    public function substring($start, $length = null)
    {
        if ($length === null) {
            $sub = substr($this->value, (int) $start);
        } else {
            $sub = substr($this->value, (int) $start, (int) $length);
        }

        return new self($sub);
    }

    /**
     * Return a formatted string
     *
     * @return StringPrimitive
     */
    public function sprintf()
    {
        $params = func_get_args();
        array_unshift($params, $this->value);
        $formatted = call_user_func_array('sprintf', $params);

        return new self($formatted);
    }
}
