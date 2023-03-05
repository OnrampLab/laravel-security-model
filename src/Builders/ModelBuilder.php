<?php

namespace OnrampLab\SecurityModel\Builders;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use OnrampLab\SecurityModel\Contracts\Securable;

class ModelBuilder extends Builder
{
    /**
     * Add a basic where clause to the query.
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (! $this->model instanceof Securable) {
            return parent::where(...func_get_args());
        }

        $useDefault = func_num_args() === 2;
        [$parsedValue, $parsedOperator] = $this->query->prepareValueAndOperator($value, $operator, $useDefault);

        if (empty($parsedValue) || $parsedValue instanceof Closure) {
            return parent::where(...func_get_args());
        }

        if (is_string($column)) {
            $tableName = $this->model->getTable();
            /** @var string $parsedColumn */
            $parsedColumn = preg_replace("/^{$tableName}\./", '', $column);

            if ($this->model->isSearchableEncryptedField($parsedColumn)) {
                $blindIndex = $this->model->generateBlindIndex($parsedColumn, $parsedValue);

                $this->query->whereNested(
                    function ($query) use ($tableName, $parsedColumn, $parsedOperator, $parsedValue, $blindIndex) {
                        // NOTE: Here we will make search condition forward compatible with migrating field
                        $query->where(sprintf('%s.%s', $tableName, $parsedColumn), $parsedOperator, $parsedValue)
                            ->orWhere(sprintf('%s.%s', $tableName, key($blindIndex)), $parsedOperator, current($blindIndex));
                    },
                    $boolean
                );

                return $this;
            }
        }

        return parent::where(...func_get_args());
    }
}
