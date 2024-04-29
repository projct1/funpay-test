<?php

namespace FpDbTest\Database;

use FpDbTest\Contracts\DatabaseInterface;
use FpDbTest\Enums\CustomQueryParamModifiersEnum;
use FpDbTest\QueryMatchers\QueryMatchItem;
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
            $this->guessError($query, $args);

            $manager = new QueryMatchManager($matches, $args);

            $manager->setQuery($query);

            foreach ($manager->getMatches() as $item) {
                $item->isConditionalBlock() ? $this->processBlock($item) : $this->processSpecifier($item);

                $manager->replaceMatch($item);
            }

            return $manager->getQuery();
        }

        return $query;
    }

    protected function guessError(string $query, array $args): void
    {
        if (($cntMatches = substr_count($query, '?')) !== $cntArgs = count($args)) {
            throw new InvalidArgumentException(
                "Количество спецификаторов ($cntMatches) в запросе « $query » не совпадает с количеством переданных значений ($cntArgs)"
            );
        }
    }

    protected function processSpecifier(QueryMatchItem $item): void
    {
        $item->setValues(
            [CustomQueryParamModifiersEnum::from($item->match)->transform($item->values[0], $this->mysqli)]
        );
    }

    protected function processBlock(QueryMatchItem $item): void
    {
        /**
         * Если нет спецификаторов, то смысла от условного блока нет.
         * Скорее всего ошибка.
         * Можно было его просто удалить, но, скорее всего, забыли добавить спецификатор, либо где-то на уровень выше ошибка.
         */
        if ($item->hasntSpecifier()) {
            throw new InvalidArgumentException("Условный блок $item->match не имеет спецификаторов");
        }

        if ($item->shouldntSkip($this->skip())) { //если переданное значение не нужно пропускать
            $item->setValues([$this->buildQuery($item->group, $item->values)]); //парсим отдельно условный блок
        }
    }

    public function skip()
    {
        return null;
    }
}
