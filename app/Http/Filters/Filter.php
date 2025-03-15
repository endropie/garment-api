<?php

namespace App\Http\Filters;

use Recordset\Http\Filter as BaseFilter;

class Filter extends BaseFilter
{
    protected function notIn($id, $index)
    {
        if ($index === 0) {
            $this->builder->whereNotIn('id', $this->request->get('notIn'));
        }

        $this->builder->whereNotIn('id', [$this->request->get('notIn')]);
    }
}
