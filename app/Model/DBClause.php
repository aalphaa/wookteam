<?php

namespace App\Model;


use Closure;

class DBClause
{
    private $identify;

    public function __construct()
    {
        $this->identify = "";
    }

    public function on($first, $operator = null, $second = null, $boolean = 'and')
    {
        $identify = $this->addEncode($first);
        $identify.= $this->addEncode($operator);
        $identify.= $this->addEncode($second);
        $identify.= $this->addEncode($boolean);
        $this->identify.= $identify;
    }

    public function orOn($first, $operator = null, $second = null)
    {
        $this->on($first, $operator, $second, 'or');
    }

    public function toString()
    {
        return $this->identify;
    }

    private function addEncode($value)
    {
        $encodeName = '';
        if (is_array($value)) {
            ksort($value);
            foreach ($value AS $key => $val) {
                $encodeName .= ':' . $key . '=' . $this->addEncode($val) . ';';
            }
        } elseif ($value instanceof Closure) {
            $join = new DBClause();
            call_user_func($value, $join);
            $encodeName .= ':' . $join->toString() . ';';
        } elseif (is_string($value) || is_numeric($value)) {
            $encodeName .= ':' . $value . ';';
        } else {
            $encodeName .= ':' . var_export($value, true) . ';';
        }
        return $encodeName;
    }
}