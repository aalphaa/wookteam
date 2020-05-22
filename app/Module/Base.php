<?php

namespace App\Module;

use App\Model\DBCache;
use Cache;
use DB;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Redirect;
use Request;
use Storage;

class Base
{

    /**
     * 获取数组或对象值
     * @param $obj
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function val($obj, $key = '', $default = '')
    {
        if (is_object($obj)) $obj = Base::object2array($obj);
        if (is_int($key)) {
            if (isset($obj[$key])){
                $obj = $obj[$key];
            }else{
                $obj = "";
            }
        }elseif (!empty($key)){
            $arr = explode(".", str_replace("|", ".", $key));
            foreach ($arr as $val){
                if (isset($obj[$val])){
                    $obj = $obj[$val];
                }else{
                    $obj = ""; break;
                }
            }
        }
        if ($default && empty($obj)) $obj = $default;
        return $obj;
    }

    /**
     * 获取当前uri
     * @return string
     */
    public static function getUrl()
    {
        return Request::getRequestUri();
    }

    /**
     * 跳转
     * @param null $url
     * @return mixed
     */
    public static function goUrl($url = null)
    {
        if (empty($url)) {
            $url = Base::getUrl();
        }
        return Redirect::to($url, 301);
    }

    /**
     * 默认显示
     * @param $str
     * @param $val
     * @param $default
     * @return mixed
     */
    public static function nullShow($str, $val, $default = '')
    {
        return $str ? ($default ? $default : $str) : $val;
    }

    /**
     * 补零
     * @param $str
     * @param int $length       长度
     * @param bool $before      是否补在前面
     * @return string
     */
    public static function zeroFill($str, $length = 0, $before = true)
    {
        if (strlen($str) >= $length) {
            return $str;
        }
        $_str = '';
        for ($i = 0; $i < $length; $i++) {
            $_str .= '0';
        }
        if ($before) {
            $_ret = substr($_str . $str, $length * -1);
        } else {
            $_ret = substr($str . $_str, 0, $length);
        }
        return $_ret;
    }

    /**
     * 新建目录
     * @param $path
     * @return mixed
     */
    public static function makeDir($path)
    {
        try{
            Storage::makeDirectory($path);
        } catch (Exception $e) {}
        if (!file_exists($path)){
            self::makeDir(dirname($path));
            @mkdir($path, 0777);
            @chmod($path, 0777);
        }
        return $path;
    }

    /**
     * 删除目录
     * @param $path
     */
    public static function deleteDir($path)
    {
        Storage::deleteDirectory($path);
    }

    /**
     * 删除目录及目录下所有的文件
     * @param $dirName
     * @param bool $undeleteDir 不删除目录（只删除文件）
     */
    public static function deleteDirAndFile($dirName, $undeleteDir = false)
    {
        if ($handle = opendir($dirName)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir($dirName . "/" . $item)) {
                        self::deleteDirAndFile($dirName . "/" . $item);
                    } else {
                        @unlink($dirName . "/" . $item);
                    }
                }
            }
            closedir($handle);
            if ($undeleteDir === false) {
                rmdir($dirName);
            }
        }
    }

    /**
     * 去除html
     * @param $text
     * @param int $length
     * @return mixed|string
     */
    public static function getHtml($text, $length = 255)
    {
        $text = Base::cutStr(strip_tags($text), $length, 0, "...");
        return $text;
    }

    /**
     *
     * 截取字符串
     * @param string $string 字符串
     * @param int $length 截取长度
     * @param int $start 何处开始
     * @param string $dot 超出尾部添加
     * @param string $charset 默认编码
     * @return mixed|string
     */
    public static function cutStr($string, $length, $start = 0, $dot = '', $charset = 'utf-8')
    {
        if (strtolower($charset) == 'utf-8') {
            if (Base::getStrlen($string) <= $length) return $string;
            $strcut = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);
            $strcut = Base::utf8Substr($strcut, $length, $start);
            $strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
            return $strcut . $dot;
        } else {
            $length = $length * 2;
            if (strlen($string) <= $length) return $string;
            $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);
            $strcut = '';
            for ($i = 0; $i < $length; $i++) {
                $strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
            }
            $strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
        }
        return $strcut . $dot;
    }

    /**
     * PHP获取字符串中英文混合长度
     * @param string $str 字符串
     * @param string $charset 编码
     * @return float            返回长度，1中文=1位，2英文=1位
     */
    public static function getStrlen($str, $charset = 'utf-8')
    {
        if (strtolower($charset) == 'utf-8') {
            $str = iconv('utf-8', 'GBK//IGNORE', $str);
        }
        $num = strlen($str);
        $cnNum = 0;
        for ($i = 0; $i < $num; $i++) {
            if (ord(substr($str, $i + 1, 1)) > 127) {
                $cnNum++;
                $i++;
            }
        }
        $enNum = $num - ($cnNum * 2);
        $number = ($enNum / 2) + $cnNum;
        return ceil($number);
    }

    /**
     * PHP截取UTF-8字符串，解决半字符问题。
     * @param string $str 源字符串
     * @param int $len 左边的子串的长度
     * @param int $start 何处开始
     * @return string           取出的字符串, 当$len小于等于0时, 会返回整个字符串
     */
    public static function utf8Substr($str, $len, $start = 0)
    {
        $len = $len * 2;
        $new_str = [];
        for ($i = 0; $i < $len; $i++) {
            $temp_str = substr($str, 0, 1);
            if (ord($temp_str) > 127) {
                $i++;
                if ($i < $len) {
                    $new_str[] = substr($str, 0, 3);
                    $str = substr($str, 3);
                }
            } else {
                $new_str[] = substr($str, 0, 1);
                $str = substr($str, 1);
            }
        }
        return join(array_slice($new_str, $start));
    }

    /**
     * 将字符串转换为数组
     * @param string    $data 字符串
     * @param array     $default 为空时返回的默认数组
     * @return    array    返回数组格式，如果，data为空，则返回$default
     */
    public static function string2array($data, $default = [])
    {
        if (is_array($data)) {
            return $data ? $data : $default;
        }
        $data = trim($data);
        if ($data == '') return $default;
        if (strpos(strtolower($data), 'array') === 0 && strtolower($data) !== 'array') {
            @ini_set('display_errors', 'on');
            @eval("\$array = $data;");
            @ini_set('display_errors', 'off');
        } else {
            if (strpos($data, '{\\') === 0) {
                $data = stripslashes($data);
            }
            $array = json_decode($data, true);
        }
        return isset($array) && is_array($array) && $data ? $array : $default;
    }

    /**
     * 将数组转换为字符串
     * @param    array $data 数组
     * @param    int $isformdata 如果为0，则不使用new_stripslashes处理，可选参数，默认为1
     * @return    string    返回字符串，如果，data为空，则返回空
     */
    public static function array2string($data, $isformdata = 1)
    {
        if ($data == '' || empty($data)) return '';
        if ($isformdata) $data = Base::newStripslashes($data);
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            return Base::newAddslashes(json_encode($data));
        } else {
            return Base::newAddslashes(json_encode($data, JSON_FORCE_OBJECT));
        }
    }

    /**
     * 将数组转换为字符串 (格式化)
     * @param    array $data 数组
     * @param    int $isformdata 如果为0，则不使用new_stripslashes处理，可选参数，默认为1
     * @return    string    返回字符串，如果，data为空，则返回空
     */
    public static function array2string_discard($data, $isformdata = 1)
    {
        if ($data == '' || empty($data)) return '';
        if ($isformdata) $data = Base::newStripslashes($data);
        return var_export($data, TRUE);
    }

    /**
     * json字符串转换成array
     * @param $string
     * @return array|mixed
     */
    public static function json2array($string)
    {
        if (is_array($string)) {
            return $string;
        }
        try {
            return json_decode($string, true);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * array转换成功json字符串
     * @param $array
     * @return string
     */
    public static function array2json($array)
    {
        if (!is_array($array)) {
            return $array;
        }
        try {
            return json_encode($array);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * 叠加数组或对象
     * @param object|array $array
     * @param array $over
     * @return object|array
     */
    public static function array_over(&$array, $over = [])
    {
        if (is_array($over)) {
            foreach ($over AS $key=>$val) {
                if (is_array($array)) {
                    $array[$key] = $val;
                }
                if (is_object($array)) {
                    $array->$key = $val;
                }
            }
        }
        return $array;
    }

    /**
     * 获取数组第一个值
     * @param $array
     * @return mixed
     */
    public static function arrayFirst($array)
    {
        $val = '';
        if (is_array($array)) {
            foreach ($array AS $item) {
                $val = $item;
                break;
            }
        }
        return $val;
    }

    /**
     * 获取数组最后一个值
     * @param $array
     * @return mixed
     */
    public static function arrayLast($array)
    {
        $val = '';
        if (is_array($array)) {
            foreach (array_reverse($array) AS $item) {
                $val = $item;
                break;
            }
        }
        return $val;
    }

    /**
     * 数组指定值作为数组键
     * @param $array
     * @param $valkey
     * @return array
     */
    public static function arrayValkey($array, $valkey)
    {
        self::coll2array($array);
        if (is_array($array)) {
            $items = [];
            foreach ($array AS $item) {
                $items[$item[$valkey]] = $item;
            }
            $array = $items;
        }
        return $array;
    }

    /**
     * 对象转数组
     * @param $array
     * @return array
     */
    public static function object2array(&$array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = Base::object2array($value);
            }
        }
        return $array;
    }

    /**
     * 数据库返回对象转数组
     * @param $array
     * @return array
     */
    public static function coll2array(&$array)
    {
        if (is_object($array) && $array) {
            $obj = $array;
            $array = [];
            foreach ($obj AS $key=>$item) {
                $array[$key] = $item;
            }
            Base::object2array($array);
        }
        return $array;
    }

    /**
     * 数据库返回对象转数组
     * @param $array
     * @return array
     */
    public static function DBC2A($array)
    {
        return Base::coll2array($array);
    }

    /**
     * 数据库自更新数组
     * @param $array
     * @return array|\Illuminate\Database\Query\Expression
     */
    public static function DBUP($array) {
        if (is_array($array)) {
            foreach ($array AS $key => $val) {
                $array[$key] = DB::raw(Base::DBRAW($key . ($val < 0 ? '-' : '+') . abs($val)));
            }
        } else {
            $array = DB::raw(Base::DBRAW($array));
        }
        return $array;
    }

    /**
     * 数据库更新或插入
     * @param $builder
     * @param $where
     * @param $update
     * @param array $insert
     * @return bool
     */
    public static function DBUPIN($builder, $where, $update, $insert = [])
    {
        if (is_string($builder)) {
            $builder = DB::table($builder);
        }
        if (!$builder->where($where)->exists()) {
            return $builder->insert(array_merge($where, $insert));
        }
        return (bool)$builder->take(1)->update($update);
    }

    /**
     * 处理数据库原生符号
     * @param string $value
     * @return mixed
     */
    public static function DBRAW($value = '')
    {
        if (env('DB_CONNECTION') === 'pgsql') {
            $value = str_replace("`", "\"", $value);
        }
        return $value;
    }

    /**
     * array转xml
     * @param $data
     * @param string $root 根节点
     * @return string
     */
    public static function array2xml($data, $root = '<xml>'){
        $str = "";
        if ($root) $str .= $root;
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $child = self::array2xml($val, false);
                $str .= "<$key>$child</$key>";
            } else {
                $str .= "<$key><![CDATA[$val]]></$key>";
            }
        }
        if ($root) $str .= '</xml>';
        return $str;
    }

    /**
     * xml转json
     * @param string $source 传的是文件，还是xml的string的判断
     * @return string
     */
    public static function xml2json($source)
    {
        if (is_file($source)) {
            $xml_array = @simplexml_load_file($source);
        } else {
            $xml_array = @simplexml_load_string($source, NULL, LIBXML_NOCDATA);
        }
        return json_encode($xml_array);
    }

    /**
     * 返回经stripslashes处理过的字符串或数组
     * @param array|string $string 需要处理的字符串或数组
     * @return mixed
     */
    public static function newStripslashes($string)
    {
        if (is_numeric($string)) {
            return $string;
        }elseif (!is_array($string)) {
            return stripslashes($string);
        }
        foreach ($string as $key => $val) $string[$key] = Base::newStripslashes($val);
        return $string;
    }

    /**
     * 返回经addslashes处理过的字符串或数组
     * @param array|string $string 需要处理的字符串或数组
     * @return mixed
     */
    public static function newAddslashes($string)
    {
        if (is_numeric($string)) {
            return $string;
        }elseif (!is_array($string)) {
            return addslashes($string);
        }
        foreach ($string as $key => $val) $string[$key] = Base::newAddslashes($val);
        return $string;
    }

    /**
     * 返回经trim处理过的字符串或数组
     * @param $string
     * @return array|string
     */
    public static function newTrim($string)
    {
        if (!is_array($string)) return trim($string);
        foreach ($string as $key => $val) $string[$key] = Base::newTrim($val);
        return $string;
    }

    /**
     * 返回经intval处理过的字符串或数组
     * @param $string
     * @return array|string
     */
    public static function newIntval($string)
    {
        if (!is_array($string)) return intval($string);
        foreach ($string as $key => $val) $string[$key] = Base::newIntval($val);
        return $string;
    }

    /**
     * 重MD5加密
     * @param $text
     * @param string $pass
     * @return string
     */
    public static function md52($text, $pass = '')
    {
        $_text = md5($text) . $pass;
        return md5($_text);
    }

    /**
     * 随机字符串
     * @param int $length 随机字符长度
     * @param string $type
     * @return string 1数字、2大小写字母、21小写字母、22大写字母、默认全部;
     */
    public static function generatePassword($length = 8, $type = '')
    {
        // 密码字符集，可任意添加你需要的字符
        switch ($type) {
            case '1':
                $chars = '0123456789';
                break;
            case '2':
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case '21':
                $chars = 'abcdefghijklmnopqrstuvwxyz';
                break;
            case '22':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            default:
                $chars = $type ? $type : 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                break;
        }
        $passwordstr = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $passwordstr .= $chars[mt_rand(0, $max)];
        }
        return $passwordstr;
    }

    /**
     * 同 generate_password 默认获取纯数字
     * @param $length
     * @param string $chars
     * @return string
     */
    public static function strRandom($length, $chars = '0123456789')
    {
        return Base::generatePassword($length, $chars);
    }

    /**
     * 判断两个地址域名是否相同
     * @param string $var1
     * @param string $var2
     * @return bool
     */
    public static function hostContrast($var1, $var2)
    {
        $arr1 = parse_url($var1);
        $arr2 = parse_url($var2);
        //
        $host1 = $var1;
        if (isset($arr1['host'])) {
            $host1 = $arr1['host'];
        }
        //
        $host2 = $var2;
        if (isset($arr2['host'])) {
            $host2 = $arr2['host'];
        }
        return $host1== $host2;
    }

    /**
     * 获取url域名
     * @param string $var
     * @return mixed
     */
    public static function getHost($var = '')
    {
        if (empty($var)) {
            $var = url("/");
        }
        $arr = parse_url($var);
        return $arr['host'];
    }

    /**
     * 相对路径补全
     * @param string|array $str
     * @return string
     */
    public static function fillUrl($str = '')
    {
        if (is_array($str)) {
            foreach ($str AS $key=>$item) {
                $str[$key] = Base::fillUrl($item);
            }
            return $str;
        }
        if (empty($str)) {
            return $str;
        }
        if (substr($str, 0, 2) == "//" ||
            substr($str, 0, 7) == "http://" ||
            substr($str, 0, 8) == "https://" ||
            substr($str, 0, 6) == "ftp://" ||
            substr($str, 0, 1) == "/" ||
            substr(str_replace(' ', '', $str), 0, 11) == "data:image/"
        ) {
            return $str;
        } else {
            return url($str);
        }
    }

    /**
     * 反 fillUrl
     * @param string $str
     * @return array|string
     */
    public static function unFillUrl($str = '')
    {
        if (is_array($str)) {
            foreach ($str AS $key=>$item) {
                $str[$key] = Base::unFillUrl($item);
            }
            return $str;
        }
        return Base::leftDelete($str, url('') . '/');
    }

    /**
     * 格式化内容图片地址
     * @param $content
     * @return mixed
     */
    public static function formatContentImg($content)
    {
        $pattern = '/<img(.*?)src=("|\')(.*?)\2/is';
        if (preg_match($pattern, $content)) {
            preg_match_all($pattern, $content, $matchs);
            foreach ($matchs[3] AS $index => $value) {
                if (!(substr($value, 0, 7) == "http://" ||
                    substr($value, 0, 8) == "https://" ||
                    substr($value, 0, 6) == "ftp://" ||
                    substr(str_replace(' ', '', $value), 0, 11) == "data:image/"
                )) {
                    if (substr($value, 0, 2) == "//") {
                        $value = "http:" . $value;
                    }elseif (substr($value, 0, 1) == "/") {
                        $value = substr($value, 1);
                    }
                    $newValue = "<img" . $matchs[1][$index] . "src=" . $matchs[2][$index] . self::fillUrl($value) . $matchs[2][$index];
                    $content = str_replace($matchs[0][$index], $newValue, $content);
                }
            }
        }
        return $content;
    }

    /**
     * 打散字符串，只留为数字的项
     * @param $delimiter
     * @param $string
     * @return array
     */
    public static function explodeInt($delimiter, $string)
    {
        $array = is_array($string) ? $string : explode($delimiter, $string);
        foreach ($array AS $k => $v) {
            if (!is_numeric($v)) {
                unset($array[$k]);
            }
        }
        return $array;
    }

    /**
     * 检测日期格式
     * @param string $str 需要检测的字符串
     * @return bool
     */
    public static function isDate($str)
    {
        $strArr = explode('-', $str);
        if (empty($strArr) || count($strArr) != 3) {
            return false;
        } else {
            list($year, $month, $day) = $strArr;
            if (checkdate($month, $day, $year)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 检测时间格式
     * @param string $str 需要检测的字符串
     * @return bool
     */
    public static function isTime($str)
    {
        $strArr = explode(':', $str);
        if (empty($strArr) || count($strArr) != 2) {
            return false;
        } else {
            list($hour, $minute) = $strArr;
            if (intval($hour) > 23 || intval($minute) > 59) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * 检测手机号码格式
     * @param string $str   需要检测的字符串
     * @return bool
     */
    public static function isMobile($str)
    {
        if (preg_match("/^1([3456789])\d{9}$/", $str)) {
            return true;
        }else{
            return false;
        }
    }

    /**
     * 检测邮箱格式
     * @param string $str 需要检测的字符串
     * @return int
     */
    public static function isMail($str)
    {
        $RegExp = '/^[a-z0-9][a-z\.0-9-_]+@[a-z0-9_-]+(?:\.[a-z]{0,3}\.[a-z]{0,2}|\.[a-z]{0,3}|\.[a-z]{0,2})$/i';
        return preg_match($RegExp, $str);
    }

    /**
     * 正则判断是否纯数字
     * @param $str
     * @return bool
     */
    public static function isNumber($str)
    {
        if (preg_match("/^\d*$/", $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断身份证是否正确
     * @param $id
     * @return bool
     */
    public static function isIdcard($id)
    {
        $id = strtoupper($id);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = array();
        if(!preg_match($regx, $id)) {
            return FALSE;
        }
        if(15==strlen($id)) {
            //检查15位
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
            @preg_match($regx, $id, $arr_split);
            //检查生日日期是否正确
            $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth)) {
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            //检查18位
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $id, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            //检查生日日期是否正确
            if(!strtotime($dtm_birth)) {
                return FALSE;
            } else {
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                $sign = 0;
                for ( $i = 0; $i < 17; $i++ ) {
                    $b = (int) $id{$i};
                    $w = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $val_num = $arr_ch[$n];
                if ($val_num != substr($id,17, 1)) {
                    return FALSE;
                } else {
                    return TRUE;
                }
            }
        }
    }

    /**
     * 阵列数组
     * @param $keys
     * @param $src
     * @param bool $default
     * @return array
     */
    public static function arrayElements($keys, $src, $default = FALSE)
    {
        $return = [];
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        foreach ($keys as $key) {
            if (isset($src[$key])) {
                $return[$key] = $src[$key];
            } else {
                $return[$key] = $default;
            }
        }
        return $return;
    }

    /**
     * 判断字符串存在(包含)
     * @param string $string
     * @param string $find
     * @return bool
     */
    public static function strExists($string, $find)
    {
        if (!is_string($string) || !is_string($find)) {
            return false;
        }
        return !(strpos($string, $find) === FALSE);
    }

    /**
     * 判断字符串开头包含
     * @param string $string //原字符串
     * @param string $find //判断字符串
     * @param bool|false $lower //是否不区分大小写
     * @return int
     */
    public static function leftExists($string, $find, $lower = false)
    {
        if (!is_string($string) || !is_string($find)) {
            return false;
        }
        if ($lower) {
            $string = strtolower($string);
            $find = strtolower($find);
        }
        return (substr($string, 0, strlen($find)) == $find);
    }

    /**
     * 判断字符串结尾包含
     * @param string $string //原字符串
     * @param string $find //判断字符串
     * @param bool|false $lower //是否不区分大小写
     * @return int
     */
    public static function rightExists($string, $find, $lower = false)
    {
        if (!is_string($string) || !is_string($find)) {
            return false;
        }
        if ($lower) {
            $string = strtolower($string);
            $find = strtolower($find);
        }
        return (substr($string, strlen($find) * -1) == $find);
    }

    /**
     * 删除开头指定字符串
     * @param $string
     * @param $find
     * @param bool $lower
     * @return string
     */
    public static function leftDelete($string, $find, $lower = false)
    {
        if (Base::leftExists($string, $find, $lower)) {
            $string = substr($string, strlen($find));
        }
        return $string ? $string : '';
    }

    /**
     * 删除结尾指定字符串
     * @param $string
     * @param $find
     * @param bool $lower
     * @return string
     */
    public static function rightDelete($string, $find, $lower = false)
    {
        if (Base::rightExists($string, $find, $lower)) {
            $string = substr($string, 0, strlen($find) * -1);
        }
        return $string;
    }

    /**
     * 截取指定字符串
     * @param $str
     * @param string $ta
     * @param string $tb
     * @return string
     */
    public static function getMiddle($str, $ta = '', $tb = ''){
        if ($ta && strpos($str, $ta) !== false){
            $str = substr($str, strpos($str, $ta) + strlen($ta));
        }
        if ($tb && strpos($str, $tb) !== false){
            $str = substr($str, 0, strpos($str, $tb));
        }
        return $str;
    }

    /**
     * 获取或设置
     * @param $setname          //配置名称
     * @param bool $array       //保存内容
     * @param bool $isCache     //读取缓存
     * @return array
     */
    public static function setting($setname, $array = false, $isCache = false)
    {
        global $_A;
        if (empty($setname)) {
            return [];
        }
        if ($array === false && isset($_A["__static_setting_" . $setname])) {
            return $_A["__static_setting_" . $setname];
        }
        $setting = [];
        $setrow = DBCache::table('setting')->where('title', $setname)->cache($isCache)->first();
        if (!empty($setrow)) {
            $setting = Base::string2array($setrow['setting']);
        } else {
            DB::table('setting')->insert(['title' => $setname]);
        }
        if ($array !== false) {
            $setting = $array;
            DB::table('setting')->where('title', $setname)->update(['setting' => (is_array($array) ? Base::array2string($array) : $array)]);
            DBCache::table('setting')->where('title', $setname)->removeCache()->first();
        }
        $_A["__static_setting_" . $setname] = $setting;
        return $setting;
    }

    /**
     * 获取设置值
     * @param $setname
     * @param $keyname
     * @param $defaultVal
     * @return mixed
     */
    public static function settingFind($setname, $keyname, $defaultVal = '')
    {
        $array = Base::setting($setname);
        return isset($array[$keyname]) ? $array[$keyname] : $defaultVal;
    }

    /**
     * 秒 （转） 年、天、时、分、秒
     * @param $time
     * @return array|bool
     */
    public static function sec2time($time)
    {
        if (is_numeric($time)) {
            $value = array(
                "years" => 0, "days" => 0, "hours" => 0,
                "minutes" => 0, "seconds" => 0,
            );
            /*
            if($time >= 31536000){
                $value["years"] = floor($time/31536000);
                $time = ($time%31536000);
            }
            */
            if ($time >= 86400) {
                $value["days"] = floor($time / 86400);
                $time = ($time % 86400);
            }
            if ($time >= 3600) {
                $value["hours"] = floor($time / 3600);
                $time = ($time % 3600);
            }
            if ($time >= 60) {
                $value["minutes"] = floor($time / 60);
                $time = ($time % 60);
            }
            $value["seconds"] = floor($time);
            return (array)$value;
        } else {
            return (bool)FALSE;
        }
    }

    /**
     * 年、天、时、分、秒 （转） 秒
     * @param $value
     * @return int
     */
    public static function time2sec($value)
    {
        $time = intval($value["seconds"]);
        $time += intval($value["minutes"] * 60);
        $time += intval($value["hours"] * 3600);
        $time += intval($value["days"] * 86400);
        $time += intval($value["years"] * 31536000);
        return $time;
    }

    /**
     * 阿拉伯数字转化为中文
     * @param $num
     * @return string
     */
    public static function chinaNum($num)
    {
        $china = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
        $arr = str_split($num);
        $txt = '';
        for ($i = 0; $i < count($arr); $i++) {
            $txt .= $china[$arr[$i]];
        }
        return $txt;
    }

    /**
     * 阿拉伯数字转化为中文（用于星期，七改成日）
     * @param $num
     * @return string
     */
    public static function chinaNumZ($num)
    {
        return str_replace("七", "日", Base::chinaNum($num));
    }

    /**
     * 获取(时间戳转)今天是星期几，只返回（几）
     * @param string|number $unixTime
     * @return string
     */
    public static function getTimeWeek($unixTime = '')
    {
        $unixTime = is_numeric($unixTime) ? $unixTime : Base::time();
        $weekarray = ['日', '一', '二', '三', '四', '五', '六'];
        return $weekarray[date('w', $unixTime)];
    }

    /**
     * 获取(时间戳转)现在时间段：深夜、凌晨、早晨、上午.....
     * @param string|number $unixTime
     * @return string
     */
    public static function getTimeDayeSegment($unixTime = '')
    {
        $unixTime = is_numeric($unixTime) ? $unixTime : Base::time();
        $H = date('H', $unixTime);
        if ($H >= 19) {
            return '晚上';
        } elseif ($H >= 18) {
            return '傍晚';
        } elseif ($H >= 13) {
            return '下午';
        } elseif ($H >= 12) {
            return '中午';
        } elseif ($H >= 8) {
            return '上午';
        } elseif ($H >= 5) {
            return '早晨';
        } elseif ($H >= 1) {
            return '凌晨';
        } elseif ($H >= 0) {
            return '深夜';
        } else {
            return '';
        }
    }

    /**
     * JSON返回
     * @param $param
     * @return string
     */
    public static function jsonEcho($param)
    {
        global $_GPC;
        //
        $json = json_encode($param);
        $callback = $_GPC['callback'];
        if ($callback) {
            return $callback . '(' . $json . ')';
        } else {
            return $json;
        }
    }

    /**
     * 数组返回 正确
     * @param $msg
     * @param array $data
     * @param int $ret
     * @return array
     */
    public static function retSuccess($msg, $data = [], $ret = 1)
    {
        return array(
            'ret' => $ret,
            'msg' => $msg,
            'data' => $data
        );
    }

    /**
     * 数组返回 错误
     * @param $msg
     * @param array $data
     * @param int $ret
     * @return array
     */
    public static function retError($msg, $data = [], $ret = 0)
    {
        return array(
            'ret' => $ret,
            'msg' => $msg,
            'data' => $data
        );
    }

    /**
     * Ajax 错误返回
     * @param $msg
     * @param array $data
     * @param int $ret
     * @return array|void
     */
    public static function ajaxError($msg, $data = [], $ret = 0)
    {
        if (Request::input('__Access-Control-Allow-Origin') || Request::header('Content-Type') === 'application/json' || Request::header('platform')) {
            return Base::retError($msg, $data, $ret);
        }else{
            return abort(404);
        }
    }

    /**
     * JSON返回 正确
     * @param $msg
     * @param array $data
     * @param int $ret
     * @return string
     */
    public static function jsonSuccess($msg, $data = [], $ret = 1)
    {
        return Base::jsonEcho(Base::retSuccess($msg, $data, $ret));
    }

    /**
     * JSON返回 错误
     * @param $msg
     * @param array $data
     * @param int $ret
     * @return string
     */
    public static function jsonError($msg, $data = [], $ret = 0)
    {
        return Base::jsonEcho(Base::retError($msg, $data, $ret));
    }

    /**
     * 是否错误
     * @param $param
     * @return bool
     */
    public static function isError($param)
    {
        return intval($param['ret']) <= 0;
    }

    /**
     * 异常模板
     * @param $msg
     * @param bool $catch
     * @return bool
     */
    public static function Exce($msg, $catch = false)
    {
        if ($catch === false) {
            return "{{ExceMsg:".$msg.":ExceMsg}}";
        }else{
            if (Base::strExists($msg . "", "{{ExceMsg:") && Base::strExists($msg . "", ":ExceMsg}}")) {
                $msg = Base::getMiddle($msg, "{{ExceMsg:", ":ExceMsg}}");
                $catch = $msg?$msg:$catch;
            }
            return $catch;
        }
    }

    /**
     * 获取数组的第几个值
     * @param $arr
     * @param int $i
     * @return array
     */
    public static function getArray($arr, $i = 1)
    {
        $array = [];
        $j = 1;
        foreach ($arr AS $item) {
            $array[] = $item;
            if ($i >= $j) {
                break;
            }
            $j++;
        }
        return $array;
    }

    /**
     * 小时转天/小时
     * @param $hour
     * @return string
     */
    public static function forumHourDay($hour)
    {
        $hour = intval($hour);
        if ($hour > 24) {
            $day = floor($hour / 24);
            $hour-= $day * 24;
            return $day.'天'.$hour.'小时';
        }
        return $hour.'小时';
    }

    /**
     * 时间格式化
     * @param $date
     * @return false|string
     */
    public static function forumDate($date)
    {
        $dur = time() - $date;
        if ($date > strtotime(date("Y-m-d"))) {
            //今天
            if ($dur < 60) {
                return max($dur, 1) . '秒前';
            } elseif ($dur < 3600) {
                return floor($dur / 60) . '分钟前';
            } elseif ($dur < 86400) {
                return floor($dur / 3600) . '小时前';
            } else {
                return date("H:i", $date);
            }
        } elseif ($date > strtotime(date("Y-m-d", strtotime("-1 day")))) {
            //昨天
            return '昨天';
        } elseif ($date > strtotime(date("Y-m-d", strtotime("-2 day")))) {
            //前天
            return '前天';
        } elseif ($dur > 86400) {
            //x天前
            return floor($dur / 86400) . '天前';
        }
        return date("Y-m-d", $date);
    }

    /**
     * 获取时间戳今天的第一秒时间戳
     * @param $time
     * @return false|int
     */
    public static function dayTimeF($time)
    {
        return strtotime(date("Y-m-d 00:00:00", self::isNumber($time) ? $time : strtotime($time)));
    }

    /**
     * 获取时间戳今天的最后一秒时间戳
     * @param $time
     * @return false|int
     */
    public static function dayTimeE($time)
    {
        return strtotime(date("Y-m-d 23:59:59", self::isNumber($time) ? $time : strtotime($time)));
    }

    /**
     * 用户名、邮箱、手机账号、银行卡号中间字符串以*隐藏
     * @param $str
     * @return mixed|string
     */
    public static function cardFormat($str)
    {
        if (strpos($str, '@')) {
            $email_array = explode("@", $str);
            $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($str, 0, 3); //邮箱前缀
            $count = 0;
            $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $str, -1, $count);
            return $prevfix . $str;
        }
        if (Base::isMobile($str)) {
            return substr($str, 0, 3) . "****" . substr($str, -4);
        }
        $pattern = '/([\d]{4})([\d]{4})([\d]{4})([\d]{4})([\d]{0,})?/i';
        if (preg_match($pattern, $str)) {
            return preg_replace($pattern, '$1 **** **** **** $5', $str);
        }
        $pattern = '/([\d]{4})([\d]{4})([\d]{4})([\d]{0,})?/i';
        if (preg_match($pattern, $str)) {
            return preg_replace($pattern, '$1 **** **** $4', $str);
        }
        $pattern = '/([\d]{4})([\d]{4})([\d]{0,})?/i';
        if (preg_match($pattern, $str)) {
            return preg_replace($pattern, '$1 **** $3', $str);
        }
        return substr($str, 0, 3) . "***" . substr($str, -1);
    }

    /**
     * 数字每4位加一空格
     * @param $str
     * @param string $interval
     * @return string
     */
    public static function fourFormat($str, $interval = " ")
    {
        if (!is_numeric($str)) return $str;
        //
        $text = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $text .= $str[$i];
            if ($i % 4 == 3) {
                $text .= $interval;
            }
        }
        return $text;
    }

    /**
     * 保留两位小数点
     * @param $str
     * @param bool $float
     * @return float
     */
    public static function twoFloat($str, $float = false)
    {
        $str = sprintf("%.2f", floatval($str));
        if ($float === true) {
            $str = floatval($str);
        }
        return $str;
    }

    /**
     * 获取时间戳
     * @param bool $refresh
     * @return int
     */
    public static function time($refresh = false)
    {
        global $_A;
        if (!isset($_A["__static_time"]) || $refresh === true) {
            $_A["__static_time"] = time();
        }
        return $_A["__static_time"];
    }

    /**
     * 获取毫秒时间戳
     * @return float
     */
    public static function msecTime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $time = explode(".", $sec . ($msec * 1000));
        return $time[0];
    }

    /**
     * 时间差(不够1个小时算一个小时)
     * @param int $s    开始时间戳
     * @param int $e    结束时间戳
     * @return string
     */
    public static function timeDiff($s, $e)
    {
        $d = $e - $s;
        if ($d > 86400) {
            $day = floor($d / 86400);
            $hour = ceil(($d - ($day * 86400)) / 3600);
            if ($hour > 0) {
                return $day . '天' . $hour . '小时';
            } else {
                return $day . '天';
            }
        } elseif ($d > 3600) {
            return ceil($d / 3600) . '小时';
        } elseif ($d > 60) {
            return ceil($d / 60) . '分钟';
        } elseif ($d > 1) {
            return '1分钟内';
        } else {
            return '0秒';
        }
    }

    /**
     * 获取IP地址
     * @return string
     */
    public static function getIp()
    {
        global $_A;
        if (!isset($_A["__static_ip"])) {
            if (getenv('HTTP_CLIENT_IP') and strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
                $onlineip = getenv('HTTP_CLIENT_IP');
            } elseif (getenv('HTTP_X_FORWARDED_FOR') and strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
                $onlineip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('REMOTE_ADDR') and strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
                $onlineip = getenv('REMOTE_ADDR');
            } elseif (isset($_SERVER['REMOTE_ADDR']) and $_SERVER['REMOTE_ADDR'] and strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
                $onlineip = $_SERVER['REMOTE_ADDR'];
            } elseif (Request::header('X-Real-IP-Swoole-Y3MTXN')) {
                $onlineip = Request::header('X-Real-IP-Swoole-Y3MTXN');
            } else {
                $onlineip = '0,0,0,0';
            }
            preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $onlineip, $match);
            $_A["__static_ip"] = $match[0] ? $match[0] : 'unknown';
        }
        return $_A["__static_ip"];
    }

    /**
     * 获取IP地址
     * @param string $ip
     * @return array|mixed
     */
    public static function getIpInfo($ip = '')
    {
        if (empty($ip)) {
            $ip = self::getIp();
        }
        $cacheKey = "getIpInfo::" . md5($ip);
        $result = Cache::rememberForever($cacheKey, function() use ($ip) {
            return Ihttp::ihttp_request("http://ip.taobao.com/service/getIpInfo.php?ip=" . $ip, [], [], 8);
        });
        if (Base::isError($result)) {
            return $result;
        }
        $data = json_decode($result['data'], true);
        if (!is_array($data) || intval($data['code']) != 0) {
            Cache::forget($cacheKey);
            return Base::retError("error ip: -1");
        }
        $data = $data['data'];
        if (!is_array($data) || !isset($data['country'])) {
            return Base::retError("error ip: -2");
        }
        $data['text'] = $data['country'];
        $data['textSmall'] = $data['country'];
        if ($data['region'] && $data['region'] != $data['country'] && $data['region'] != "XX") {
            $data['text'].= " " . $data['region'];
            $data['textSmall'] = $data['region'];
        }
        if ($data['city'] && $data['city'] != $data['region'] && $data['city'] != "XX") {
            $data['text'].= " " . $data['city'];
            $data['textSmall'].= " " . $data['city'];
        }
        if ($data['county'] && $data['county'] != $data['city'] && $data['county'] != "XX") {
            $data['text'].= " " . $data['county'];
            $data['textSmall'].= " " . $data['county'];
        }
        return Base::retSuccess("success", $data);
    }

    /**
     * 是否是中国IP：-1错误、1是、0否
     * @param string $ip
     * @return int
     */
    public static function isCnIp($ip = '')
    {
        if (empty($ip)) {
            $ip = self::getIp();
        }
        $cacheKey = "isCnIp::" . md5($ip);
        //
        $result = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($ip) {
            $file = dirname(__FILE__) . '/ip/all_cn.txt';
            if (!file_exists($file)) {
                return -1;
            }
            $in = false;
            $myFile = fopen($file, "r");
            $i = 0;
            while (!feof($myFile)) {
                $i++;
                $cidr = trim(fgets($myFile));
                if (Base::ipInRange($ip, $cidr)) {
                    $in = true;
                    break;
                }
            }
            fclose($myFile);
            return $in ? 1 : 0;
        });
        if ($result === -1) {
            Cache::forget($cacheKey);
        }
        //
        return intval($result);
    }

    /**
     * 验证IP地址范围
     * $range 支持多种写法
     *  - Wildcard： 1.2.3.*
     *  - CIRD：1.2.3/24 或者 1.2.3.4/255.255.255.0
     *  - Start-End: 1.2.3.0-1.2.3.255
     * @param $ip
     * @param $range
     * @return bool
     */
    public static function ipInRange($ip, $range)
    {
        if (substr_count($ip, '.') == 3 && $ip == $range) {
            return true;
        }
        if (strpos($range, '/') !== false) {
            list($range, $netmask) = explode('/', $range, 2);
            if (strpos($netmask, '.') !== false) {
                $netmask = str_replace('*', '0', $netmask);
                $netmask_dec = ip2long($netmask);
                return ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec));
            } else {
                $x = explode('.', $range);
                while (count($x) < 4) {
                    $x[] = '0';
                }
                list($a, $b, $c, $d) = $x;
                $range = sprintf("%u.%u.%u.%u", empty($a) ? '0' : $a, empty($b) ? '0' : $b, empty($c) ? '0' : $c, empty($d) ? '0' : $d);
                $range_dec = ip2long($range);
                $ip_dec = ip2long($ip);
                $wildcard_dec = pow(2, (32 - $netmask)) - 1;
                $netmask_dec = ~$wildcard_dec;
                return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
            }
        } else {
            if (strpos($range, '*') !== false) {
                $lower = str_replace('*', '0', $range);
                $upper = str_replace('*', '255', $range);
                $range = "$lower-$upper";
            }
            if (strpos($range, '-') !== false) {
                list($lower, $upper) = explode('-', $range, 2);
                $lower_dec = (float)sprintf("%u", ip2long($lower));
                $upper_dec = (float)sprintf("%u", ip2long($upper));
                $ip_dec = (float)sprintf("%u", ip2long($ip));
                return (($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec));
            }
            return false;
        }
    }

    /**
     * php://input 字符串解析到变量并获取指定值
     * @param $key
     * @return array
     */
    public static function getContentsParse($key)
    {
        parse_str(Request::getContent(), $input);
        if ($key) {
            $input = isset($input[$key])?$input[$key]:array();
        }
        return is_array($input)?$input:array($input);
    }

    /**
     * 多维 array_values
     * @param $array
     * @param string $keyName
     * @param string $valName
     * @return array
     */
    public static function array_values_recursive($array, $keyName = 'key', $valName = 'item') {
        if (is_array($array) && count($array) > 0) {
            $temp = [];
            foreach ($array as $key => $value) {
                $continue = false;
                if (is_array($value) && count($value) > 0) {
                    $continue = true;
                    foreach ($value AS $item) {
                        if (!is_array($item)) {
                            $continue = false;
                            break;
                        }
                    }
                }
                $temp[] = [
                    $keyName => $key,
                    $valName => $continue ? self::array_values_recursive($value, $keyName, $valName) : $value,
                ];
            }
            return $temp;
        }
        return $array;
    }

    /**
     * 获取tonken
     * @return string
     */
    public static function getToken()
    {
        global $_A;
        if (!isset($_A["__static_token"])) {
            $_A["__static_token"] = Base::nullShow(Request::header('token'), Request::input('token'));
        }
        return $_A["__static_token"];
    }

    /**
     * 设置tonken
     * @param $token
     */
    public static function setToken($token)
    {
        global $_A;
        $_A["__static_token"] = $token;
    }

    /**
     * 是否微信
     * @return bool
     */
    public static function isWechat()
    {
        return strpos(Request::server('HTTP_USER_AGENT'), 'MicroMessenger') !== false;
    }

    /**
     * 获取浏览器类型
     * @return string
     */
    public static function browser()
    {
        $user_agent = Request::server('HTTP_USER_AGENT');
        if (strpos($user_agent, 'AlipayClient') !== false) {
            return 'alipay';
        }elseif (strpos($user_agent, 'MicroMessenger') !== false) {
            return 'weixin';
        }else{
            return 'none';
        }
    }

    /**
     * 返回根据距离sql排序语句
     * @param $lat
     * @param $lng
     * @param string $latName
     * @param string $lngName
     * @return string
     */
    public static function acos($lat , $lng, $latName = 'lat', $lngName = 'lng') {
        $lat = floatval($lat);
        $lng = floatval($lng);
        return 'ACOS(
		SIN(('.$lat.' * 3.1415) / 180) * SIN(('.$latName.' * 3.1415) / 180) + COS(('.$lat.' * 3.1415) / 180) * COS(('.$latName.' * 3.1415) / 180) * COS(
			('.$lng.' * 3.1415) / 180 - ('.$lngName.' * 3.1415) / 180
		)
	) * 6380';
    }

    /**
     * 获取分页详细信息
     * @param LengthAwarePaginator $lists
     * @param bool $getTotal
     * @return array
     */
    public static function getPageInfo(LengthAwarePaginator $lists, $getTotal = true)
    {
        return [
            "currentPage" => $lists->currentPage(),
            "firstItem" => $lists->firstItem(),
            "hasMorePages" => $lists->hasMorePages(),
            "lastItem" => $lists->lastItem(),
            "lastPage" => $lists->lastPage(),
            "nextPageUrl" => $lists->nextPageUrl(),
            "previousPageUrl" => $lists->previousPageUrl(),
            "perPage" => $lists->perPage(),
            "total" => $getTotal === true ? $lists->total() : -1,
        ];
    }

    /**
     * 获取分页数据
     * @param $lists
     * @param bool $getTotal
     * @return array
     */
    public static function getPageList($lists, $getTotal = true)
    {
        $data = Base::getPageInfo($lists, $getTotal);
        $data['lists'] = Base::coll2array($lists);
        return $data;
    }

    /**
     * 上传文件
     * @param array $param [ type=[文件类型], file=>Request::file, path=>文件路径, fileName=>文件名称, scale=>[压缩原图宽,高, 压缩方式], size=>限制大小KB, autoThumb=>false不要自动生成缩略图 ]
     * @return array [name=>原文件名, size=>文件大小(单位KB),file=>绝对地址, path=>相对地址, url=>全路径地址, ext=>文件后缀名]
     */
    public static function upload($param)
    {
        $file = $param['file'];
        if (empty($file)) {
            return Base::retError("您没有选择要上传的文件!");
        }
        if($file->isValid()){
            Base::makeDir(public_path($param['path']));
            //
            switch ($param['type']) {
                case 'png':
                    $type = ['png'];
                    break;
                case 'png+jpg':
                    $type = ['jpg', 'jpeg', 'png'];
                    break;
                case 'image':
                    $type = ['jpg', 'jpeg', 'gif', 'png'];
                    break;
                case 'video':
                    $type = ['rm', 'rmvb', 'wmv', 'avi', 'mpg', 'mpeg', 'mp4'];
                    break;
                case 'audio':
                    $type = ['mp3', 'wma', 'wav', 'amr'];
                    break;
                case 'excel':
                    $type = ['xls', 'xlsx'];
                    break;
                case 'app':
                    $type = ['apk'];
                    break;
                case 'zip':
                    $type = ['zip'];
                    break;
                case 'file':
                    $type = ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'esp'];
                    break;
                default:
                    return Base::retError('错误的类型参数');
            }
            $extension = strtolower($file->getClientOriginalExtension());
            if ($type && is_array($type) && !in_array($extension, $type)) {
                return Base::retError('文件格式错误，限制类型：'.implode(",", $type));
            }
            try {
                $fileSize = $file->getSize();
                if ($param['size'] > 0 && $fileSize > $param['size'] * 1024) {
                    return Base::retError('文件大小超限，最大限制：' . $param['size'] . 'kB');
                }
            } catch (Exception $e) {
                $fileSize = 0;
            }
            $scaleName = "";
            if ($param['fileName']) {
                $fileName = $param['fileName'];
            }else{
                if ($param['scale'] && is_array($param['scale'])) {
                    list($width, $height) = $param['scale'];
                    if ($width > 0 || $height > 0) {
                        $scaleName = "_{WIDTH}x{HEIGHT}";
                        if (isset($param['scale'][2])) {
                            $scaleName.= $param['scale'][2];
                        }
                    }
                }
                $fileName = md5_file($file) . '.' . $extension;
                $scaleName = md5_file($file) . $scaleName . '.' . $extension;
            }
            //
            $file->move($param['path'], $fileName);
            //
            $array = [
                "name" => $file->getClientOriginalName(),               //原文件名
                "size" => Base::twoFloat($fileSize / 1024, true),       //大小KB
                "file" => public_path($param['path'].$fileName),        //目录的完整路径                "D:\www....KzZ.jpg"
                "path" => $param['path'].$fileName,                     //相对路径                     "uploads/pic....KzZ.jpg"
                "url" => Base::fillUrl($param['path'].$fileName),       //完整的URL                    "https://.....hhsKzZ.jpg"
                "thumb" => '',                                          //缩略图（预览图）               "https://.....hhsKzZ.jpg_thumb.jpg"
                "width" => -1,                                          //图片宽度
                "height" => -1,                                         //图片高度
                "ext" => $extension,                                    //文件后缀名
            ];
            if (!is_file($array['file'])) {
                return Base::retError('上传失败!');
            }
            //iOS照片颠倒处理
            if (in_array($extension, ['jpg', 'jpeg']) && function_exists('exif_read_data')) {
                $data = imagecreatefromstring(file_get_contents($array['file']));
                $exif = @exif_read_data($array['file']);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 8:
                            $data = imagerotate($data, 90, 0);
                            break;
                        case 3:
                            $data = imagerotate($data, 180, 0);
                            break;
                        case 6:
                            $data = imagerotate($data, -90, 0);
                            break;
                        default:
                            $data = null;
                            break;
                    }
                    if ($data !== null) {
                        imagejpeg($data, $array['file']);
                    }
                }
            }
            //
            if (in_array($param['type'], ['png', 'png+jpg', 'image'])) {
                //图片尺寸
                $paramet = getimagesize($array['file']);
                $array['width'] = $paramet[0];
                $array['height'] = $paramet[1];
                //原图压缩
                if ($param['scale'] && is_array($param['scale'])) {
                    list($width, $height) = $param['scale'];
                    if (($width > 0 && $array['width'] > $width) || ($height > 0 && $array['height'] > $height)) {
                        $cut = ($width > 0 && $height > 0) ? 1 : -1;
                        $cut = $param['scale'][2] ?? $cut;
                        //图片压缩
                        $tmpFile = $array['file'] . '_tmp.jpg';
                        if (Base::imgThumb($array['file'], $tmpFile, $width, $height, $cut)) {
                            $tmpSize = filesize($tmpFile);
                            if ($tmpSize > $fileSize) {
                                @unlink($tmpFile);
                            }else{
                                @unlink($array['file']);
                                rename($tmpFile, $array['file']);
                            }
                        }
                        //图片尺寸
                        $paramet = getimagesize($array['file']);
                        $array['width'] = $paramet[0];
                        $array['height'] = $paramet[1];
                        //重命名
                        if ($scaleName) {
                            $scaleName = str_replace(['{WIDTH}', '{HEIGHT}'], [$array['width'], $array['height']], $scaleName);
                            if (rename($array['file'], Base::rightDelete($array['file'], $fileName) . $scaleName)) {
                                $array['file'] = Base::rightDelete($array['file'], $fileName) . $scaleName;
                                $array['path'] = Base::rightDelete($array['path'], $fileName) . $scaleName;
                                $array['url'] = Base::rightDelete($array['url'], $fileName) . $scaleName;
                            }
                        }
                    }
                }
                //生成缩略图
                $array['thumb'] = $array['path'];
                if ($param['autoThumb'] === "false") $param['autoThumb'] = false;
                if ($param['autoThumb'] !== false) {
                    if (Base::imgThumb($array['file'], $array['file']."_thumb.jpg", 180, 0)) {
                        $array['thumb'].= "_thumb.jpg";
                    }
                }
                $array['thumb'] = Base::fillUrl($array['thumb']);
            }
            //
            return Base::retSuccess('success', $array);
        }else{
            return Base::retError($file->getErrorMessage());
        }
    }

    /**
     * 生成缩略图
     * @param string $src_img 源图绝对完整地址{带文件名及后缀名}
     * @param string $dst_img 目标图绝对完整地址{带文件名及后缀名}
     * @param int $width 缩略图宽{0:此时目标高度不能为0，目标宽度为源图宽*(目标高度/源图高)}
     * @param int $height 缩略图高{0:此时目标宽度不能为0，目标高度为源图高*(目标宽度/源图宽)}
     * @param int $cut 是否裁切{宽,高必须非0}：1是、0否、-1或'auto'保持等比
     * @param int $proportion 缩放{0:不缩放, 0<this<1:缩放到相应比例(此时宽高限制和裁切均失效)}
     * @return bool
     */
    public static function imgThumb($src_img, $dst_img, $width = 75, $height = 75, $cut = 0, $proportion = 0)
    {
        if (!is_file($src_img)) {
            return false;
        }
        if (empty($dst_img)) {
            $dst_img = $src_img;
        }
        $st = pathinfo($src_img, PATHINFO_EXTENSION);
        if (!in_array(strtolower($st), array('jpg', 'jpeg', 'png', 'gif', 'bmp'))) {
            return false;
        }
        $ot = pathinfo($dst_img, PATHINFO_EXTENSION);
        $otfunc = 'image' . ($ot == 'jpg' ? 'jpeg' : $ot);
        $srcinfo = getimagesize($src_img);
        $src_w = $srcinfo[0];
        $src_h = $srcinfo[1];
        $type = strtolower(substr(image_type_to_extension($srcinfo[2]), 1));
        if (empty($type)) {
            return false;
        }
        $createfun = 'imagecreatefrom' . ($type == 'jpg' ? 'jpeg' : $type);

        $dst_h = $height;
        $dst_w = $width;
        $x = $y = 0;

        /**
         * 缩略图不超过源图尺寸（前提是宽或高只有一个）
         */
        if (($width > $src_w && $height > $src_h) || ($height > $src_h && $width == 0) || ($width > $src_w && $height == 0)) {
            $proportion = 1;
        }
        if ($width > $src_w) {
            $dst_w = $width = $src_w;
        }
        if ($height > $src_h) {
            $dst_h = $height = $src_h;
        }

        if (!$width && !$height && !$proportion) {
            return false;
        }
        if (!$proportion) {
            if ($cut == 'auto' || $cut == -1) {
                if ($dst_w && $dst_h) {
                    $wB = $dst_w / $src_w;
                    $hB = $dst_h / $src_h;
                    if ($wB > $hB) {
                        $dst_w = 0;
                    }else{
                        $dst_h = 0;
                    }
                }
                $cut = 0;
            }
            if ($cut == 0) {

                if ($dst_w && $dst_h) {
                    if ($dst_w / $src_w > $dst_h / $src_h) {
                        $dst_w = $src_w * ($dst_h / $src_h);
                        $x = 0 - ($dst_w - $width) / 2;
                    } else {
                        $dst_h = $src_h * ($dst_w / $src_w);
                        $y = 0 - ($dst_h - $height) / 2;
                    }
                } else if ($dst_w xor $dst_h) {
                    if ($dst_w && !$dst_h)  //有宽无高
                    {
                        $propor = $dst_w / $src_w;
                        $height = $dst_h = $src_h * $propor;
                    } else if (!$dst_w && $dst_h)  //有高无宽
                    {
                        $propor = $dst_h / $src_h;
                        $width = $dst_w = $src_w * $propor;
                    }
                }
            } else {
                if (!$dst_h)  //裁剪时无高
                {
                    $height = $dst_h = $dst_w;
                }
                if (!$dst_w)  //裁剪时无宽
                {
                    $width = $dst_w = $dst_h;
                }
                $propor = min(max($dst_w / $src_w, $dst_h / $src_h), 1);
                $dst_w = (int)round($src_w * $propor);
                $dst_h = (int)round($src_h * $propor);
                $x = ($width - $dst_w) / 2;
                $y = ($height - $dst_h) / 2;
            }
        } else {
            $proportion = min($proportion, 1);
            $height = $dst_h = $src_h * $proportion;
            $width = $dst_w = $src_w * $proportion;
        }

        if (!function_exists($createfun)) {
            return false;
        }

        $src = $createfun($src_img);
        $dst = imagecreatetruecolor($width ? $width : $dst_w, $height ? $height : $dst_h);
        try {
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
        } catch (Exception $e) {

        }
        if (function_exists('imagecopyresampled')) {
            imagecopyresampled($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        } else {
            imagecopyresized($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        }
        $otfunc($dst, $dst_img);
        imagedestroy($dst);
        imagedestroy($src);
        return true;
    }

    /**
     * 排列组合（无重复）
     * @param $arr
     * @param $m
     * @return array
     */
    public static function getCombinationToString($arr, $m)
    {
        $result = [];
        if ($m == 1) {
            return $arr;
        }

        if ($m == count($arr)) {
            $result[] = implode(',', $arr);
            return $result;
        }

        $temp_firstelement = $arr[0];
        unset($arr[0]);
        $arr = array_values($arr);
        $temp_list1 = self::getCombinationToString($arr, ($m - 1));

        foreach ($temp_list1 as $s) {
            $s = $temp_firstelement . ',' . $s;
            $result[] = $s;
        }
        unset($temp_list1);

        $temp_list2 = self::getCombinationToString($arr, $m);
        foreach ($temp_list2 as $s) {
            $result[] = $s;
        }
        unset($temp_list2);

        return $result;
    }

    /**
     * 不同元素交叉组合（多个数组）
     * @return array
     */
    public static function getNewArray()
    {
        $args = func_get_args();
        $pailie = function ($arr1, $arr2) {
            $arr = [];
            $k = 0;
            foreach ($arr1 as $k1 => $v1) {
                foreach ($arr2 as $k2 => $v2) {
                    $arr[$k] = $v1 . "," . $v2;
                    $k++;
                }
            }
            return $arr;
        };
        $arr = [];
        foreach ($args as $k => $v) {
            if (isset($args[$k + 1]) && $args[$k + 1]) {
                switch ($k) {
                    case 0:
                        $arr[$k] = $pailie($v, $args[$k + 1]);
                        break;
                    default:
                        $arr[$k] = $pailie($arr[$k - 1], $args[$k + 1]);
                        break;
                }
            }
        }
        $key = count($arr) - 1;
        return array_values($arr[$key]);
    }

    /**
     * 获取当前是本月第几个星期
     * @return false|float
     */
    public static function getMonthWeek()
    {
        $time = strtotime(date("Y-m-01"));
        $w = date('w', $time);
        $j = date("j");
        return ceil(($j + $w) / 7);
    }

    /**
     * 缓存数据
     * @param $title
     * @param null $value
     * @return mixed|null
     */
    public static function cacheData($title, $value = null)
    {
        $title = "cacheData::" . $title;
        $tmp = DB::table('tmp')->where('title', $title)->select('value')->first();
        if ($value !== null ) {
            if (empty($tmp)) {
                DB::table('tmp')->insert(['title'=>$title, 'value'=>$value]);
            }else{
                DB::table('tmp')->where('title', $title)->update(['value'=>$value]);
            }
            return $value;
        }else{
            return $tmp->value;
        }
    }
}
