<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Exception;

class InvalidRegex extends LogicException
{
    const INTERNAL_ERROR = 1;
    const BACKTRACK_LIMIT_ERROR = 2;
    const RECURSION_LIMIT_ERROR = 3;
    const BAD_UTF8_ERROR = 4;
    const BAD_UTF8_OFFSET_ERROR = 5;
    const JIT_STACKLIMIT_ERROR = 6;

    public function __construct(string $message = '', int $code = 0)
    {
        if ($message === '') {
            switch ($code) {
                case self::INTERNAL_ERROR:
                    $message = 'Internal error';
                    break;
                case self::BACKTRACK_LIMIT_ERROR:
                    $message = 'Backtrack limit error';
                    break;
                case self::RECURSION_LIMIT_ERROR:
                    $message = 'Recursion limit error';
                    break;
                case self::BAD_UTF8_ERROR:
                    $message = 'Bad UTF-8 error';
                    break;
                case self::BAD_UTF8_OFFSET_ERROR:
                    $message = 'Bad UTF-8 offset error';
                    break;
                case self::JIT_STACKLIMIT_ERROR:
                    $message = 'JIT stack limit error';
                    break;
                default:
                    $message = 'Regular expression error';
            }
        }

        parent::__construct($message, $code);
    }
}
