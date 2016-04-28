<?php

namespace mindplay\sql\framework;

use mindplay\sql\model\InsertQuery;
use mindplay\sql\model\Schema;
use mindplay\sql\model\SelectQuery;
use mindplay\sql\model\Table;
use mindplay\sql\model\Type;
use mindplay\unbox\Container;
use UnexpectedValueException;

class Database implements TypeProvider, TableFactory
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Driver $driver
     */
    public function __construct(Driver $driver)
    {
        $this->container = new Container();

        $this->container->set(Driver::class, $driver);

        // self-register:

        $this->container->set(self::class, $this);
        $this->container->set(get_class($this), $this);
        $this->container->set(TypeProvider::class, $this);
        $this->container->set(TableFactory::class, $this);
    }
    
    /**
     * @inheritdoc
     */
    public function getType($type)
    {
        if (! $this->container->has($type)) {
            $this->container->register($type); // auto-wiring (for Types with no special constructor dependencies)
        }

        $type = $this->container->get($type);

        if (! $type instanceof Type) {
            $class_name = get_class($type);

            throw new UnexpectedValueException("{$class_name} does not implement the Type interface");
        }

        return $type;
    }

    /**
     * @param string Schema class-name
     *                      
     * @return Schema
     */
    public function getSchema($schema)
    {
        if (! $this->container->has($schema)) {
            $this->container->register($schema); // auto-wiring (for Schema with no special constructor dependencies)
        }

        $schema = $this->container->get($schema);
        
        if (! $schema instanceof Schema) {
            $class_name = get_class($schema);

            throw new UnexpectedValueException("{$class_name} does not extend the Schema class");
        }
        
        return $schema;
    }

    /**
     * @inheritdoc
     */
    public function createTable($class_name, $table_name, $alias)
    {
        return $this->container->create($class_name, ['name' => $table_name, 'alias' => $alias]);
    }

    /**
     * @param Table $from
     * 
     * @return SelectQuery
     */
    public function select(Table $from)
    {
        return $this->container->create(SelectQuery::class, ['root' => $from]);
    }

    /**
     * @param Table                  $into
     * @param mixed[]|mixed[][]|null $record optional record map (or list of record maps) where Column name => value
     * 
     * @return InsertQuery
     */
    public function insert(Table $into, $record = null)
    {
        return $this->container->create(InsertQuery::class, ['table' => $into, 'record' => $record]);
    }
}
