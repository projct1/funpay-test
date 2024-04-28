<?php

namespace FpDbTest\QueryMatchers;

class QueryMatchManager
{
    protected int $offset = 0;
    protected string $query = '';
    /** @var QueryMatchItem[] $matches массив результата работы preg_match_all */
    protected array $matches = [];

    public function __construct(array $matches, array $args)
    {
        foreach ($matches[0] as $index => $match) {
            $this->matches[] = new QueryMatchItem($match[0], $match[1], $matches[1][$index][0] ?? null, $args[$index]);
        }
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getMatches(): array
    {
        return $this->matches;
    }

    public function replaceMatch(QueryMatchItem $item): void
    {
        $this->query = substr_replace($this->query, $item->value, $item->position + $this->offset, strlen($item->match));
        $this->offset += strlen($item->value) - strlen($item->match);
    }
}
