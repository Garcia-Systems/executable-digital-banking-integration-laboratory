<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Infrastructure\Database;
final class LaboratoryDatabase
{
    public static function create(string $root): \PDO
    {
        $pdo = new \PDO('sqlite::memory:', options: [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec((string) file_get_contents($root . '/database/schema.sql'));
        $pdo->exec((string) file_get_contents($root . '/database/fixtures.sql'));
        return $pdo;
    }
}
