<?php

namespace mindplay\sql\model\query;

use mindplay\sql\model\Driver;
use mindplay\sql\model\schema\Column;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\TypeProvider;
use RuntimeException;

/**
 * This class represents an INSERT query.
 */
class InsertQuery extends Query
{
    private Driver $driver;
    private Table $table;

    /**
     * @var Column[]
     */
    private array $columns;

    /**
     * @var string[] list of tuple expressions
     */
    private $tuples = [];

    /**
     * @param Driver       $driver
     * @param TypeProvider $types
     * @param Table        $table Table to INSERT into
     */
    public function __construct(Driver $driver, TypeProvider $types, Table $table)
    {
        parent::__construct($types);

        if ($table->getAlias()) {
            throw new RuntimeException("can't insert into a Table instance with an alias");
        }

        $this->driver = $driver;

        $this->table = $table;

        $this->columns = array_filter(
            $table->listColumns(),
            function (Column $column) {
                return $column->isAuto() === false;
            }
        );
    }


    /**
     * Add a record to this INSERT query.
     *
     * @param array<string,mixed> $record record map (where Column name => value)
     *
     * @return $this
     */
    public function add(array $record): static
    {
        $placeholders = [];

        $tuple_num = count($this->tuples);

        foreach ($this->columns as $col_index => $column) {
            $name = $column->getName();

            if (array_key_exists($name, $record)) {
                $value = $record[$name];
            } elseif ($column->isRequired() === false) {
                $value = $column->getDefault();
            } else {
                throw new RuntimeException("required value '{$name}' missing from tuple # {$tuple_num}");
            }

            $placeholder = "c{$tuple_num}_{$col_index}";

            $placeholders[] = ":{$placeholder}";

            $this->bind($placeholder, $value, $column->getType());
        }

        $this->tuples[] = "(" . implode(", ", $placeholders) . ")";

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSQL(): string
    {
        if (count($this->tuples) === 0) {
            throw new RuntimeException("no records added to this query");
        }

        $table = "{$this->table}";

        $quoted_column_names = implode(
            ", ",
            array_map(
                function (Column $column) {
                    return $this->driver->quoteName($column->getName());
                },
                $this->columns
            )
        );

        return "INSERT INTO {$table} ({$quoted_column_names}) VALUES\n" . implode(",\n", $this->tuples);
    }
}
