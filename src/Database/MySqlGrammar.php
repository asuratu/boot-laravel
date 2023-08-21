<?php

namespace ZhuiTech\BootLaravel\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as BaseMySqlGrammar;
use Illuminate\Support\Fluent;

class MySqlGrammar extends BaseMySqlGrammar
{
    /**
     * Compile a create table command.
     *
     * @param Blueprint  $blueprint
     * @param Fluent     $command
     * @param Connection $connection
     * @return array
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command, Connection $connection): array
    {
        $sql = parent::compileCreate($blueprint, $command, $connection);

        // 添加注释
        if (isset($blueprint->comment)) {
            $blueprint->comment = str_replace("'", "\'", $blueprint->comment);
            $sql[] = 'alter table ' . $this->wrapTable($blueprint->getTable()) . " comment = '" . $blueprint->comment . "'";
        }

        return $sql;
    }


    /**
     * 重载typeInteger
     * Create the column definition for an integer type.
     *
     * @param Fluent $column
     * @return string
     */
    public function typeInteger(Fluent $column): string
    {
        $length_str = !empty($column->length) ? '(' . $column->length . ')' : '';

        return 'int' . $length_str;
    }

    /**
     * 重载typeBigInteger
     * Create the column definition for a big integer type.
     *
     * @param Fluent $column
     * @return string
     */
    protected function typeBigInteger(Fluent $column): string
    {
        $length_str = !empty($column->length) ? '(' . $column->length . ')' : '';

        return 'bigint' . $length_str;
    }

    /**
     * 重载typeMediumInteger
     * Create the column definition for a medium integer type.
     *
     * @param Fluent $column
     * @return string
     */
    protected function typeMediumInteger(Fluent $column): string
    {
        $length_str = !empty($column->length) ? '(' . $column->length . ')' : '';

        return 'mediumint' . $length_str;
    }

    /**
     * 重载typeTinyInteger
     * Create the column definition for a tiny integer type.
     *
     * @param Fluent $column
     * @return string
     */
    protected function typeTinyInteger(Fluent $column): string
    {
        $length_str = !empty($column->length) ? '(' . $column->length . ')' : '';

        return 'tinyint' . $length_str;
    }

    /**
     * 重载typeSmallInteger
     * Create the column definition for a small integer type.
     *
     * @param Fluent $column
     * @return string
     */
    protected function typeSmallInteger(Fluent $column): string
    {
        $length_str = !empty($column->length) ? '(' . $column->length . ')' : '';

        return 'smallint' . $length_str;
    }
}
