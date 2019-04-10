<?php

namespace mindplay\sql\model\components;

use InvalidArgumentException;

/**
 * This trait implements the stand-alone `LIMIT` clause (without `OFFSET`) supported
 * by the MySQL `UPDATE` and `DELETE` query-builders.
 */
trait Limit
{
    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @param int $limit max. number of records
     *
     * @return $this
     *
     * @throws InvalidArgumentException if the given limit is less than 1
     */
    public function limit($limit)
    {
        if ($limit < 1) {
            throw new InvalidArgumentException("limit out of range: {$limit}");
        }

        $this->limit = $limit;

        return $this;
    }
}
