<?php

namespace Recordset\Http;

use Recordset\Support\Filterable;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Database\Eloquent\Builder as EBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Stringable;

class Filter
{
    protected $builder;

    protected $request;

    protected $manager;

    protected $except;

    protected $only;

    public function __construct(Request $request = null)
    {
        $this->request = $request ?: app('request');

        $this->manager = new Filterable($this);
    }

    public function apply(QBuilder | EBuilder $builder)
    {
        $this->builder = $builder;

        return $this->manager->builder($builder);
    }

    public function withTrashed($value = 1)
    {
        if ($this->builder instanceof EBuilder) {
            $softDelete = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->builder->getModel()));
            if ($value && $softDelete) return $this->builder->withTrashed();
        }

        return $this->builder;
    }

    public function onlyTrashed($value = 1)
    {
        if ($this->builder instanceof EBuilder) {
            $softDelete = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->builder->getModel()));
            if ($value && $softDelete) return $this->builder->onlyTrashed();
        }

        return $this->builder;
    }

    public function lastDay($val)
    {
        if ($val <= 0) return $this->builder;

        if (!$this->builder instanceof Builder) return $this->builder;

        if (!$this->builder->getModel()) return $this->builder;

        $column = $this->builder->getModel()::CREATED_AT;
        $toDate = app(\Carbon\Carbon::class)->addDays(-1 * $val);

        return $this->builder->when(fn($q) => $q->where($column, '>=', $toDate));
    }

    public function lastHour($val)
    {
        if ($val <= 0) return $this->builder;

        if (!$this->builder instanceof Builder) return $this->builder;

        if (!$this->builder->getModel()) return $this->builder;

        $column = $this->builder->getModel()::CREATED_AT;
        $toDate = app(\Carbon\Carbon::class)->addHours(-1 * $val);

        return $this->builder->when(fn($q) => $q->where($column, '>=', $toDate));
    }

    public function sort($value, $key = null)
    {
        $columns = $this->manager->getColumns();
        $append = $key ? ("." . $key) : "";
        $order = $this->request->has('descending' . $append) ? 'desc' : 'asc';
        $function = (string)  $this->manager->stringable('sort_' . $value)->camel();

        if (method_exists($this, $function)) {
            return $this->$function($order);
        } else if (strlen($value) && in_array($value, $columns)) {
            return $this->builder->orderBy($value, $order);
        }
        return $this->builder;
    }

    public function search($value = '')
    {
        if (!strlen($value)) return $this->builder;
        $columns = $this->manager->getColumns();
        $searchFields = request('search-fields') ?? request('search_fields', null) ?? request('searchFields', null) ?? [];

        if (gettype($searchFields) == 'string' && strlen($searchFields)) {
            $searchFields = explode(',', $searchFields);
        }

        if (count($searchFields) == 0) $searchFields = $columns;

        $separator = substr_count($value, '|') > 0 ? '|' : ' ';
        $keywords = gettype($value) == 'array'
            ? array($value)
            : explode($separator, (string) $value);

        return $this->builder->where(function ($query) use ($columns, $searchFields, $keywords) {
            $mode = $this->request->has('search-expand') ? 'orWhere' : 'where';
            foreach ($keywords as $keyword) {
                if (strlen($keyword)) {
                    $query->{$mode}(function ($query) use ($columns, $searchFields, $keyword) {
                        foreach ($searchFields as $field) {
                            $method = (string) (new Stringable('search_' . $field))->camel();
                            if (method_exists($this, $method)) {
                                $query->orWhere(function ($q) use ($method, $keyword) {
                                    $this->{$method}($q, $keyword);
                                });
                            } elseif (in_array($field, $columns)) {
                                $query->orWhere($field, 'like', '%' . $keyword . '%');
                            }
                        }
                    });
                }
            }
        });
    }

    public function __call($method, $parameters)
    {
        if (substr($method, 0, 3) == 'get') {

            $property = (string) $this->manager->stringable(substr($method, 3))->camel();

            if (property_exists($this, $property)) return $this->{$property};
        }

        if (substr($method, 0, 5) == 'manager') {

            $property = (string) $this->manager->stringable(substr($method, 5))->camel();

            if (method_exists($this, $property)) return $this->{$property}(...$parameters);
        }

        return $this->{$method}(...$parameters);
    }
}
