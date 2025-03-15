<?php
namespace Recordset\Concerns;

trait HasGenerateNumber
{

    public function setAutoNumber($prefix, $digits = 5, $separator = '/', $strtime = null, $strset = null)
    {
        $strtime = $strtime ?? date("Y" .$separator ."m");
        $strset = $strset ?? $prefix . $separator . $strtime . $separator . "{number}";

        $numberset = (string) str($strset)->replace("{number}", "");

        $number = $this->getNumberNextQuery($numberset, $digits);
        $number = (string) str($strset)->replace("{number}", $number);

        $this->setAttribute('number', $number);
        $this->save();
    }

    public function getNumberNextQuery($string, $digits = 5, $fieldName = 'number')
    {
        $next = $this->when($this->forceDeleting === false, function ($q) {
                return $q->withTrashed();
            })
            ->selectRaw("MAX(REPLACE($fieldName, '$string', '') * 1) AS N")
            ->where($fieldName, 'LIKE', "%$string%")->get()->max('N');

        $next = (int) $next + 1;

        return (strlen($next) >= $digits) ? $next : str_pad($next, $digits, '0', STR_PAD_LEFT);
    }

    protected function getNumberPeriod($date, $arrayFormats, $separator = '/')
    {
        return collect($arrayFormats)->map(function($str) use ($date) {
            if (str($str)->startsWith('ROMAN')) {
                return $this->numberToRoman((int) date(explode(':', $str)[1] ?? '', strtotime($date)));
            }
            else return date($str, strtotime($date));
        })->join($separator);
    }

    public function scopeFindByNumber($query, $value, $fieldName = 'number')
    {
        return $query->where($fieldName, $value)->get()->first();
    }

    public function scopeFindByNumberOrFail($query, $value, $fieldName = 'number')
    {
        return $query->findByNumber($value, $fieldName)->get()->first() ?? abort(404);
    }

    public static function  numberToRoman($number) {
        $map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }
}
