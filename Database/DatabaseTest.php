<?php

namespace FpDbTest\Database;

use FpDbTest\Contracts\DatabaseInterface;

class DatabaseTest
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function testBuildQuery(): bool
    {
        return $this->getResults() === $this->getCorrect();
    }

    protected function getResults(): array
    {
        $results[] = $this->db->buildQuery('SELECT name FROM users WHERE user_id = 1');

        $results[] = $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0',
            ['Jack']
        );

        $results[] = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
            [['name', 'email'], 2, true]
        );

        $results[] = $this->db->buildQuery(
            'UPDATE users SET ?a WHERE user_id = -1',
            [['name' => 'Jack', 'email' => null]]
        );

        foreach ([null, true] as $block) {
            $results[] = $this->db->buildQuery(
                'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
                ['user_id', [1, 2, 3], $block ?? $this->db->skip()]
            );
        }

        //выборка всех юзеров с сортировкой и пагинацией (несколько условных блоков)
        $results[] = $this->db->buildQuery(
            <<<SQL
                SELECT ?#
                FROM users as u
                WHERE {u.id = ?d AND}u.created_at >= ?
                {LIMIT ?d OFFSET ?d}
                {ORDER BY ?# DESC}
            SQL,
            [
                ['u.id', 'u.fio', 'u.created_at'],
                null,
                '2024-04-28',
                20,
                20,
                'u.created_at'
            ]
        );

        return $results;
    }

    protected function getCorrect(): array
    {
        return [
            'SELECT name FROM users WHERE user_id = 1',
            'SELECT * FROM users WHERE name = \'Jack\' AND block = 0',
            'SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1',
            'UPDATE users SET `name` = \'Jack\', `email` = NULL WHERE user_id = -1',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3)',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1',
            <<<SQL
                SELECT `u.id`, `u.fio`, `u.created_at`
                FROM users as u
                WHERE u.created_at >= '2024-04-28'
                LIMIT 20 OFFSET 20
                ORDER BY `u.created_at` DESC
            SQL
        ];
    }
}
