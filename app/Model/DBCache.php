<?php

namespace App\Model;

use Cache;
use Closure;
use DateTime;
use Illuminate\Pagination\Paginator;

/**
 * 数据库模型 缓存读取
 *
 * Class DBCache
 * @package App\Model
 */
class DBCache
{
    protected $table = null;

    protected $attributes = [];

    protected $builder = null;

    private $__cache = true;               //启用缓存（默认启用）
    private $__cacheMinutes = 1;           //缓存时间（分钟, 默认1分钟）
    private $__removeCache = false;        //删除缓存
    private $__join = [];
    private $__take = [];
    private $__where = [];
    private $__whereRaw = [];
    private $__whereIn = [];
    private $__select = [];
    private $__selectRaw = [];
    private $__orderBy = [];
    private $__orderByRaw = [];
    private $__inRandomOrder = false;

    public function __construct($tabel = null, array $attributes = [])
    {
        $this->initParameter();
        if ($tabel) {
            $this->table = $tabel;
        }
        if ($attributes) {
            $this->attributes = $attributes;
        }
    }

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * 是否缓存
     *
     * @param bool $isCache
     * @return $this
     */
    public function cache($isCache = true)
    {
        $this->__cache = $isCache ? true : false;
        return $this;
    }

    public function noCache()
    {
        $this->__cache = false;
        return $this;
    }

    /**
     * 缓存时间（分钟）
     *
     * @param $Minutes
     * @return $this
     */
    public function cacheMinutes($Minutes)
    {
        if ($Minutes === 'year') {
            $Minutes = 365 * 24 * 60;
        }elseif ($Minutes === 'month') {
            $Minutes = 30 * 24 * 60;
        }elseif ($Minutes === 'day') {
            $Minutes = 24 * 60;
        }elseif ($Minutes === 'hour') {
            $Minutes = 60;
        }
        if ($Minutes instanceof DateTime) {
            $this->__cacheMinutes = $Minutes;
        }else{
            $this->__cacheMinutes = max($Minutes, 1);
        }
        return $this;
    }

    /**
     * 删除缓存
     */
    public function removeCache()
    {
        $this->__removeCache = true;
        return $this;
    }
    public function forgetCache($cacheKey)
    {
        $this->__removeCache = false;
        return Cache::forget($cacheKey);
    }

    /* ******************************************************************************** */
    /* ******************************************************************************** */

    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        $this->__join[] = [
            $table,
            $first,
            $operator,
            $second,
            $type,
            $where
        ];
        return $this;
    }

    public function take($var)
    {
        $this->__take[] = $var;
        return $this;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->__where[] = [
            $column,
            $operator,
            $value,
            $boolean
        ];
        return $this;
    }

    public function whereRaw($sql, $bindings = [], $boolean = 'and')
    {
        if ($sql === null) {
            return $this;
        }
        $this->__whereRaw[] = [
            $sql,
            $bindings,
            $boolean
        ];
        return $this;
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $this->__whereIn[] = [
            $column,
            $values,
            $boolean,
            $not
        ];
        return $this;
    }
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function select($var = ['*'])
    {
        $this->__select[] = is_array($var) ? $var : func_get_args();
        return $this;
    }

    public function selectRaw($expression, array $bindings = [])
    {
        $this->__selectRaw[] = [
            $expression,
            $bindings
        ];
        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->__orderBy[] = [
            $column,
            $direction
        ];
        return $this;
    }
    public function orderByDesc($column)
    {
        return $this->orderBy($column, 'desc');
    }

    public function orderByRaw($sql, $bindings = [])
    {
        $this->__orderByRaw[] = [
            $sql,
            $bindings
        ];
        return $this;
    }

    public function inRandomOrder($isRand = true)
    {
        $this->__inRandomOrder = $isRand?true:false;
        return $this;
    }

    /* ******************************************************************************** */
    /* ******************************************************************************** */
    /* ******************************************************************************** */

    /**
     * 初始化参数
     */
    private function initParameter() {
        $this->__cache = true;
        $this->__cacheMinutes = 1;
        $this->__removeCache = false;
        //
        $this->__join = [];
        $this->__take = [];
        $this->__where = [];
        $this->__whereRaw = [];
        $this->__whereIn = [];
        $this->__select = [];
        $this->__selectRaw = [];
        $this->__orderBy = [];
        $this->__orderByRaw = [];
        $this->__inRandomOrder = false;
    }

    /**
     * 获取缓存标识
     * @param string $type      查询方式
     * @param string $attach    附加标识
     * @return string
     */
    private function identify($type, $attach = '') {
        $identify = $this->addEncode($this->attributes);
        $identify.= $this->addEncode($this->__join);
        $identify.= $this->addEncode($this->__take);
        $identify.= $this->addEncode($this->__where);
        $identify.= $this->addEncode($this->__whereRaw);
        $identify.= $this->addEncode($this->__whereIn);
        $identify.= $this->addEncode($this->__select);
        $identify.= $this->addEncode($this->__selectRaw);
        $identify.= $this->addEncode($this->__orderBy);
        $identify.= $this->addEncode($this->__orderByRaw);
        $identify.= $this->addEncode($this->__inRandomOrder);
        $identify.= $this->addEncode($attach);
        //
        return 'DBCache:' . $this->table . ':' . $type . '::' . md5($identify);
    }

    private function addEncode($value)
    {
        $encodeName = '';
        if (is_array($value)) {
            sort($value);
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

    /**
     * 创建对象
     * @return $this|DOModel|null
     */
    private function builder() {
        if ($this->builder === null) {
            $this->builder = new DOModel($this->table, $this->attributes);
        }
        $builder = $this->builder;
        //
        foreach ($this->__join AS $item) {
            $builder = $builder->join($item[0], $item[1], $item[2], $item[3], $item[4], $item[5]);
        }
        foreach ($this->__take AS $item) {
            $builder = $builder->take($item);
        }
        foreach ($this->__where AS $item) {
            $builder = $builder->where($item[0], $item[1], $item[2], $item[3]);
        }
        foreach ($this->__whereRaw AS $item) {
            $builder = $builder->whereRaw($item[0], $item[1], $item[2]);
        }
        foreach ($this->__whereIn AS $item) {
            $builder = $builder->whereIn($item[0], $item[1], $item[2], $item[3]);
        }
        foreach ($this->__select AS $item) {
            $builder = $builder->select($item);
        }
        foreach ($this->__selectRaw AS $item) {
            $builder = $builder->selectRaw($item[0], $item[1]);
        }
        foreach ($this->__orderBy AS $item) {
            $builder = $builder->orderBy($item[0], $item[1]);
        }
        foreach ($this->__orderByRaw AS $item) {
            $builder = $builder->orderByRaw($item[0], $item[1]);
        }
        if ($this->__inRandomOrder) {
            $builder = $builder->inRandomOrder();
        }
        //
        $this->initParameter();
        return $builder;
    }

    /* ******************************************************************************** */
    /* ******************************************************************************** */
    /* ******************************************************************************** */

    /**
     * 静态方法
     *
     * @param string $tabel
     * @return DBCache
     */
    public static function table($tabel = '')
    {
        return new DBCache($tabel);
    }

    /**
     * 获取一条数据
     *
     * @param array $columns
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        if ($this->__cache) {
            $cacheKey = $this->identify('first', $columns);
            if ($this->__removeCache) {
                return $this->forgetCache($cacheKey);
            }
            $result = Cache::remember($cacheKey, $this->__cacheMinutes, function() use ($columns) {
                return $this->builder()->first($columns);
            });
        }else{
            $result = $this->builder()->first($columns);
        }
        return isset($result->original)?$result->original:null;
    }

    /**
     * 获取所有数据
     *
     * @param array $columns
     * @return array|bool
     */
    public function get($columns = ['*'])
    {
        if ($this->__cache) {
            $cacheKey = $this->identify('get', $columns);
            if ($this->__removeCache) {
                return $this->forgetCache($cacheKey);
            }
            $result = Cache::remember($cacheKey, $this->__cacheMinutes, function() use ($columns) {
                return $this->builder()->get($columns);
            });
        }else{
            $result = $this->builder()->get($columns);
        }
        //
        $array = [];
        foreach ($result AS $key=>$item) {
            $array[$key] = isset($item->original)?$item->original:null;
        }
        return $array;
    }

    /**
     * 查询结果的计数
     *
     * @param string $columns
     * @return int|bool
     */
    public function count($columns = '*')
    {
        if ($this->__cache) {
            $cacheKey = $this->identify('count', $columns);
            if ($this->__removeCache) {
                return $this->forgetCache($cacheKey);
            }
            $result = Cache::remember($cacheKey, $this->__cacheMinutes, function() use ($columns) {
                return $this->builder()->count($columns);
            });
        }else{
            $result = $this->builder()->count($columns);
        }
        return intval($result);
    }

    /**
     * 从查询的第一个结果中获得一个列的值
     *
     * @param $column
     * @return mixed
     */
    public function value($column)
    {
        if ($this->__cache) {
            $cacheKey = $this->identify('value', $column);
            if ($this->__removeCache) {
                return $this->forgetCache($cacheKey);
            }
            $result = Cache::remember($cacheKey, $this->__cacheMinutes, function() use ($column) {
                $value = $this->builder()->value($column);
                return $value ? $value : '';
            });
        }else{
            $result = $this->builder()->value($column);
        }
        return $result;
    }

    /**
     * 统计输出
     *
     * @param $column
     * @return bool|mixed
     */
    public function sum($column)
    {
        if ($this->__cache) {
            $cacheKey = $this->identify('sum', $column);
            if ($this->__removeCache) {
                return $this->forgetCache($cacheKey);
            }
            $result = Cache::remember($cacheKey, $this->__cacheMinutes, function() use ($column) {
                $sum = $this->builder()->sum($column);
                return $sum ? $sum : 0;
            });
        }else{
            $result = $this->builder()->sum($column);
        }
        return $result;
    }

    /**
     * 获取分页数据
     * @param null $perPage
     * @param array $columns
     * @param string $pageName
     * @param null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|mixed
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        if ($this->__cache) {
            $attach = $this->addEncode($perPage);
            $attach.= $this->addEncode($columns);
            $attach.= $this->addEncode($pageName);
            $attach.= $this->addEncode(Paginator::resolveCurrentPage($pageName));
            $cacheKey = $this->identify('paginate', $attach);
            if ($this->__removeCache) {
                return $this->forgetCache($cacheKey);
            }
            $result = Cache::remember($cacheKey, $this->__cacheMinutes, function() use ($perPage, $columns, $pageName, $page) {
                return $this->builder()->paginate($perPage, $columns, $pageName, $page);
            });
        }else{
            $result = $this->builder()->paginate($perPage, $columns, $pageName, $page);
        }
        $array = [];
        foreach ($result AS $key=>$item) {
            $array[$key] = isset($item->original)?$item->original:null;
        }
        return [
            "currentPage" => $result->currentPage(),
            "firstItem" => $result->firstItem(),
            "hasMorePages" => $result->hasMorePages(),
            "lastItem" => $result->lastItem(),
            "lastPage" => $result->lastPage(),
            "nextPageUrl" => $result->nextPageUrl(),
            "previousPageUrl" => $result->previousPageUrl(),
            "perPage" => $result->perPage(),
            "total" => $result->total(),
            "lists" => $array,
        ];
    }
}
