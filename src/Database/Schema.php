<?php

namespace ZhuiTech\BootLaravel\Database;

use Closure;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Facade;

/**
 * Class Schema
 * @package Jialeo\LaravelSchemaExtend
 * @method static create(string $table_name, Closure $callback)
 */
class Schema extends Facade
{
    /**
     * Get a schema builder instance for the default connection.
     *
     * @return Builder
     */
    protected static function getFacadeAccessor(): Builder
    {
        $connection = static::$app['db']->connection();
        return static::useCustomGrammar($connection);
    }

    /**
     * Get a schema builder instance for a connection.
     *
     * @param string $name
     * @return Builder
     */
    public static function connection(string $name): Builder
    {
        $connection = static::$app['db']->connection($name);
        return static::useCustomGrammar($connection);
    }

    /**
     * lead the system to load custom Grammar
     * @param $connection
     * @return mixed
     */
    protected static function useCustomGrammar($connection): mixed
    {
        // just for MySqlGrammar
        if (get_class($connection) === 'Illuminate\Database\MySqlConnection') {
            $MySqlGrammar = $connection->withTablePrefix(new MySqlGrammar);
            $connection->setSchemaGrammar($MySqlGrammar);
        }

        return $connection->getSchemaBuilder();
    }

}
