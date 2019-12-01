<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Exception;

class ElementNotFound extends InvalidArgumentException implements Exception
{
    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        switch (\gettype($value)) {
            case 'object':
                $class = \get_class($value);
                $id = \spl_object_id($value);
                $message = "object($class)#$id";
                break;

            case 'int':
            case 'integer':
            case 'float':
            case 'string':
            case 'double':
                $message = (string) $value;
                break;

            case 'NULL':
                $message = 'null';
                break;

            case 'boolean':
                $message = $value ? 'true' : 'false';
                break;

            default:
                $message = \gettype($value);
                break;
        }

        parent::__construct($message);
    }
}
