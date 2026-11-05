<?php
declare(strict_types=1);

namespace NewGen;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * Every PHP suite of the battery extends this. The one rule it enforces before anything else: the database it is
 * about to read and write must be a test copy — its name ends in "_test" — or the whole run stops.
 */
abstract class NewGenTestCase extends CIUnitTestCase
{
    protected static ?BaseConnection $conn = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$conn = Database::connect();
        $name = (string) (self::$conn->query('SELECT DATABASE() AS d')->getRowArray()['d'] ?? '');
        if (!preg_match('/_test$/', $name)) {
            fwrite(STDERR, "\nREFUSED: the database is '{$name}', not a test copy (its name must end in _test). Nothing was run.\n");
            exit(99);
        }
    }

    protected function db(): BaseConnection
    {
        return self::$conn ?? (self::$conn = Database::connect());
    }

    protected function one(string $sql, array $binds = [])
    {
        $row = $this->db()->query($sql, $binds)->getRowArray();
        return $row === null ? null : array_values($row)[0];
    }

    protected function rows(string $sql, array $binds = []): array
    {
        return $this->db()->query($sql, $binds)->getResultArray();
    }

    protected static function appPath(string $rel = ''): string
    {
        return APPPATH . ltrim($rel, '/');
    }

    protected static function rootPath(string $rel = ''): string
    {
        return ROOTPATH . ltrim($rel, '/');
    }
}
