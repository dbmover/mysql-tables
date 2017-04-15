<?php

/**
 * @package Dbmover
 * @subpackage Mysql
 * @subpackage Tables
 */

namespace Dbmover\Mysql\Tables;

use Dbmover\Tables;
use Dbmover\Core\Loader;
use PDO;

class Plugin extends Tables\Plugin
{
    public function __construct(Loader $loader)
    {
        parent::__construct($loader);
        $this->columns = $this->loader->getPdo()->prepare(
            "SELECT
                CONCAT('`', column_name, '`') column_name,
                column_default,
                is_nullable,
                column_type
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE ((TABLE_CATALOG = ? AND TABLE_SCHEMA = 'public') OR TABLE_SCHEMA = ?)
                AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION ASC");
    }

    public function __invoke(string $sql) : string
    {
        // Todo: support for switching storage engine (MyISAM, InnoDB etc)
        return parent::__invoke($sql);
    }

    protected function modifyColumn(string $table, string $column, array $definition) : string
    {
        return "ALTER TABLE $table CHANGE COLUMN $column {$definition['_definition']};";
    }

    protected function checkTableStatus(string $table, string $sql)
    {
        $sql = preg_replace_callback(
            "@^\s*([^\s]+)@m",
            function ($matches) {
                if (!preg_match("@^`.*?`$@", $matches[1]) && $matches[1] != 'PRIMARY') {
                    $matches[1] = "`{$matches[1]}`";
                }
                return $matches[1];
            },
            $sql
        );
        return parent::checkTableStatus($table, $sql);
    }
}

