<?php

namespace FpDbTest\Enums;

use FpDbTest\Contracts\Transformable;
use InvalidArgumentException;
use mysqli;

/**
 * Список допустимых типов для биндинга значений в запросе (через знак вопроса) на основе типа значения
 */
enum AllowedTypesEnum implements Transformable
{
    case string;
    case integer;
    case double;
    case boolean;
    case null;

    public function transform(mixed $value, mysqli $mysqli): mixed
    {
        return match ($this) {
            self::string => "'{$mysqli->escape_string($value)}'",
            self::integer => intval($value),
            self::double => doubleval($value),
            self::boolean => self::integer->transform($value, $mysqli),
            self::null => 'NULL'
        };
    }

    public static function byType(mixed $var): self
    {
        if (self::isAllowed($var)) {
            return self::{self::getType($var)};
        }

        throw new InvalidArgumentException('Недопустимый тип ' . self::getType($var));
    }

    public static function isAllowed($value): bool
    {
        return in_array(self::getType($value), array_column(self::cases(), 'name'));
    }

    public static function getType(mixed $value): string
    {
        return strtolower(gettype($value));
    }
}
