<?php

namespace mindplay\sql\postgres;

use mindplay\sql\model\Database;
use mindplay\sql\model\DatabaseContainer;
use mindplay\sql\model\DatabaseContainerFactory;
use mindplay\sql\model\Driver;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\types\BoolType;
use mindplay\sql\model\types\FloatType;
use PDO;

class PostgresDatabase extends Database implements Driver
{
    protected function bootstrap(DatabaseContainerFactory $factory): void
    {
        $factory->set(Driver::class, $this);
        
        $factory->register(BoolType::class, function () {
            return BoolType::get(true, false);
        });
        
        $factory->alias("scalar.boolean", BoolType::class);

        $factory->register("scalar.double", FloatType::class);
    }

    public function createConnection(PDO $pdo): PostgresConnection
    {
        return $this->container->create(PostgresConnection::class, ['pdo' => $pdo]);
    }

    /**
     * @inheritdoc
     */
    public function quoteName(string $name): string
    {
        return '"' . $name . '"';
    }

    /**
     * @inheritdoc
     */
    public function quoteTableName(string|null $schema, string $table): string
    {
        return $schema
            ? '"' . $schema . '"."' . $table . '"'
            : '"' . $table . '"';
    }

    /**
     * @param Table $from
     *
     * @return PostgresSelectQuery
     */
    public function select(Table $from)
    {
        return $this->container->create(PostgresSelectQuery::class, [Table::class => $from]);
    }
    
    /**
     * @param Table $into
     *
     * @return PostgresInsertQuery
     */
    public function insert(Table $into)
    {
        return $this->container->create(PostgresInsertQuery::class, [Table::class => $into]);
    }

    /**
     * @param Table $table
     *
     * @return PostgresUpdateQuery
     */
    public function update(Table $table)
    {
        return $this->container->create(PostgresUpdateQuery::class, [Table::class => $table]);
    }

    /**
     * @param Table $table
     *
     * @return PostgresDeleteQuery
     */
    public function delete(Table $table)
    {
        return $this->container->create(PostgresDeleteQuery::class, [Table::class => $table]);
    }
}
