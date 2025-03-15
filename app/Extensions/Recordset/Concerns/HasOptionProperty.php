<?php
namespace Recordset\Concerns;

use Illuminate\Support\Arr;

trait HasOptionProperty
{

    public function setOptions(array $array, $columnName = "option")
    {
        foreach ($array as $key => $value) {
            $this->fillJsonAttribute($columnName ."->". $key, $value);
        }
    }

    public function setOption($key, $value, $columnName = "option")
    {
        $this->fillJsonAttribute($columnName ."->". $key, $value);
    }

    public function getOption($key, $default = null, $columnName = "option")
    {
        return $this->getArrayProperty($columnName ."->". $key, $default);
    }

    public function getArrayProperty($key, $defalut = null)
    {
        $index = str_replace('->', '.', $key);
        $col = explode('.', $index)[0] ?? '';
        if (count(explode('.', $index)) > 1) {
            $insideKey = str_replace("$col.", "", $index);
            $value = Arr::get($this->getAttribute($col), $insideKey, $defalut);

            if ($this->hasCast($index)) {
                if (parent::preventsAccessingMissingAttributes() &&
                    ! array_key_exists($index, $this->attributes) &&
                    ($this->isEnumCastable($index) ||
                     in_array($this->getCastType($index), parent::$primitiveCastTypes))) {
                    $this->throwMissingAttributeExceptionIfApplicable($index);
                }

                return $this->castAttribute($index, $value);
            }
            return $value;
        }

        return $this->getAttribute($col);
    }
}
