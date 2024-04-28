<?php

namespace FpDbTest\Database;

use FpDbTest\Contracts\DatabaseInterface;
use FpDbTest\Enums\CustomQueryParamModifiersEnum;
use FpDbTest\QueryMatchers\QueryMatchManager;
use InvalidArgumentException;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        if (preg_match_all(CustomQueryParamModifiersEnum::regexp(), $query, $matches, PREG_OFFSET_CAPTURE)) {
            if (($cntMatches = count($matches[0])) !== $cntArgs = count($args)) {
                throw new InvalidArgumentException(
                    "Количество спецификаторов ($cntMatches) в запросе « $query » не совпадает с количеством переданных значений ($cntArgs)"
                );
            }

            $manager = new QueryMatchManager($matches, $args);

            $manager->setQuery($query);

            foreach ($manager->getMatches() as $index => $item) {
                if ($item->isConditionalBlock()) { //если попался условный блок
                    /*
                     * Если нет спецификаторов, то какой смысл от условного блока?
                     * Скорее всего ошибка.
                     * Можно было его просто удалить, но, скорее всего, забыли добавить или где-то на уровень выше произошла ошибка.
                     */
                    if ($item->hasntSpecifier()) {
                        throw new InvalidArgumentException("Условный блок $item->match не имеет спецификаторов");
                    }

                    if ($item->shouldntSkip($this->skip())) { //если переданное значение не нужно пропускать
                        $item->setValue(
                            $this->buildQuery($item->group, array_slice($args, $index)) //парсим отдельно условный блок
                        );
                    }
                } else { //обрабатываем обычные спецификаторы
                    $item->setValue(
                        CustomQueryParamModifiersEnum::from($item->match)->transform($item->value, $this->mysqli)
                    );
                }

                $manager->replaceMatch($item);
            }

            return $manager->getQuery();
        }

        return $query;
    }

    public function skip()
    {
        return null;
    }
}
