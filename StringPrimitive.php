<?php

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\TypeException;
use Innmind\Immutable\Exception\RegexException;

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
    public function split($delimiter)
    {
        $delimiter = (string) $delimiter;

        return new TypedCollection(
            self::class,
            empty($delimiter) ?
                str_split($this->value) : explode($delimiter, $this->value)
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
        $size = (int) $size;

        return new TypedCollection(
            self::class,
            str_split($this->value, $size)
        );
    }

    /**
     * Returns the position of the first occurence of the string
     *
     * @param string $needle
     * @param int $offset
     *
     * @throws Exception If the string is not found
     *
     * @return IntegerPrimitive
     */
    public function pos($needle, $offset = 0)
    {
        $position = mb_strpos($this->value, (string) $needle, (int) $offset);

        if ($position === false) {
            throw new \Exception('String not found');
        }

        return new IntegerPrimitive($position);
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
     * @throws Exception If the string is not found
     *
     * @return StringInterface
     */
    public function str($delimiter)
    {
        $sub = strstr($this->value, (string) $delimiter);

        if ($sub === false) {
            throw new \Exception('String not found');
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
     * @return IntegerPrimitive
     */
    public function length()
    {
        return new IntegerPrimitive(strlen($this->value));
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
     * @return IntegerPrimitive
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

        return new IntegerPrimitive($value);
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
     * @return IntegerPrimitive
     */
    public function wordCount($charlist = '')
    {
        return new IntegerPrimitive(str_word_count(
            $this->value,
            0,
            (string) $charlist
        ));
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
        return new TypedCollection(
            self::class,
            str_word_count($this->value, 2, (string) $charlist)
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
        return new TypedCollection(
            self::class,
            preg_split((string) $regex, $this->value, $limit, $flags)
        );
    }

    /**
     * Check if the string match the given regular expression
     *
     * @param string $regex
     * @param int $flags
     * @param int $offset
     *
     * @throws Exception If the regex failed
     *
     * @return BooleanPrimitive
     */
    public function match($regex, $flags = 0, $offset = 0)
    {
        $value = preg_match((string) $regex, $this->value, null, $flags, $offset);

        if ($value === false) {
            throw new RegexException('', preg_last_error());
        }

        return new BooleanPrimitive((bool) $value);
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
}
