<?php

namespace mindplay\sql\framework\pdo;

use InvalidArgumentException;
use mindplay\sql\framework\PreparedStatement;
use mindplay\sql\model\Driver;
use mindplay\sql\model\TypeProvider;
use PDO;
use PDOStatement;

/**
 * This class implements a Prepared Statement adapter for PDO Statements.
 */
class PreparedPDOStatement implements PreparedStatement
{
    /**
     * @var PDOStatement
     */
    private $handle;

    /**
     * @var PDOExceptionMapper
     */
    private $exception_mapper;

    /**
     * @var TypeProvider
     */
    private $types;

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var bool
     */
    private $executed = false;

    /**
     * @param PDOStatement       $handle
     * @param PDOExceptionMapper $exception_mapper
     * @param TypeProvider       $types
     */
    public function __construct(PDOStatement $handle, PDOExceptionMapper $exception_mapper, TypeProvider $types)
    {
        $this->handle = $handle;
        $this->exception_mapper = $exception_mapper;
        $this->types = $types;
    }

    /**
     * @inheritdoc
     */
    public function bind($name, $value)
    {
        static $PDO_TYPE = [
            'integer' => PDO::PARAM_INT,
            'double'  => PDO::PARAM_STR, // bind as string, since there's no float type in PDO
            'string'  => PDO::PARAM_STR,
            'boolean' => PDO::PARAM_BOOL,
            'NULL'    => PDO::PARAM_NULL,
        ];

        $value_type = gettype($value);

        $scalar_type = "scalar.{$value_type}";

        if ($this->types->hasType($scalar_type)) {
            $type = $this->types->getType($scalar_type);

            $value = $type->convertToSQL($value);

            $value_type = gettype($value);
        }

        if (isset($PDO_TYPE[$value_type])) {
            $this->handle->bindValue($name, $value, $PDO_TYPE[$value_type]);

            $this->params[$name] = $value;
        } else {
            throw new InvalidArgumentException("unexpected value type: {$value_type}");
        }
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (@$this->handle->execute()) {
            $this->executed = true;
        } else {
            list($sql_state, $error_code, $error_message) = $this->handle->errorInfo();

            $exception_type = $this->exception_mapper->getExceptionType($sql_state, $error_code, $error_message);

            throw new $exception_type($this->handle->queryString, $this->params, "{$sql_state}: {$error_message}",
                $error_code);
        }
    }

    /**
     * @inheritdoc
     */
    public function fetch()
    {
        if (! $this->executed) {
            $this->execute();
        }

        $result = $this->handle->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getRowsAffected()
    {
        return $this->handle->rowCount();
    }
}
