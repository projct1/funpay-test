<?php

namespace FpDbTest\Enums;

use FpDbTest\Contracts\Transformable;
use InvalidArgumentException;
use mysqli;

/**
 * Список допустимых спецификаторов для преобразования значений, переданных в запрос
 */
enum CustomQueryParamModifiersEnum: string implements Transformable
{
    case integer = '?d'; //конвертация в целое число
    case double = '?f';  //конвертация в число с плавающей точкой; назвал double вместо float, так как gettype возвращает double; чтобы не городить лишних условий
    case array = '?a';   //массив значений
    case mixed = '?#';   //идентификатор или массив идентификаторов
    case default = '?';  //конвертация на основе типа переданного значения

    public function transform(mixed $value, mysqli $mysqli): mixed
    {
        return match ($this) {
            self::integer => AllowedTypesEnum::integer->transform($value, $mysqli),
            self::double => AllowedTypesEnum::double->transform($value, $mysqli),
            self::array => is_array($value) ? $this->processArraySpecifier($value, $mysqli) : $this->throwError($value),
            self::mixed => $this->processMixedSpecifier($value, $mysqli),
            self::default => AllowedTypesEnum::byType($value)->transform($value, $mysqli)
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @link https://regex101.com/r/vUK9wr/3 */
    public static function regexp(): string
    {
        return '/(?(?={)(?:{(.+)})|(?:' . implode('|', array_map('preg_quote', self::values())) . '))/U';
    }

    protected function throwError(mixed $value)
    {
        throw new InvalidArgumentException(
            sprintf('Значение для спецификатора %s не может быть %s', $this->value, gettype($value))
        );
    }

    protected function processArraySpecifier(mixed $value, mysqli $mysqli): string
    {
        return implode(
            ', ',
            array_is_list($value) //если обычный массив-список
                ? call_user_func([self::class, 'escapeArrayValues'], $value, $mysqli)
                : array_map(
                    fn(string $k, ?string $v): string => "`$k` = " . AllowedTypesEnum::byType($v)->transform($v, $mysqli),
                    array_keys($value),
                    array_values($value)
                )
        );
    }

    protected function processMixedSpecifier(array|string $value, mysqli $mysqli): string
    {
        if (is_array($value)) {
            return '`' . implode('`, `', $this->escapeArrayValues($value, $mysqli)) . '`';
        }

        if (is_string($value)) {
            return "`{$mysqli->escape_string($value)}`";
        }

        $this->throwError($value);
    }

    protected function escapeArrayValues(array $data, mysqli $mysqli): array
    {
        return array_map(fn(mixed $value) => $mysqli->escape_string($value), $data);
    }
}
