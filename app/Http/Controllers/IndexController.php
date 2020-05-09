<?php

namespace App\Http\Controllers;

use App\Module\Base;
use Request;


/**
 * 页面
 * Class IndexController
 * @package App\Http\Controllers
 */
class IndexController extends Controller
{

    private $version = '10161';

    public function __invoke($method, $action = '', $child = '', $name = '')
    {
        $app = $method ? $method : 'main';
        if ($app == 'uploads') {
            $child = $action . '/' . $child . '/' . $name;
            $action = '';
        }
        if ($action) {
            $app .= "__" . $action;
        }
        return (method_exists($this, $app)) ? $this->$app($child) : Base::ajaxError("404 not found (" . str_replace("__", "/", $app) . ").");
    }

    /**
     * 获取IP地址
     * @return array|mixed
     */
    public function get__ip() {
        return Base::getIp();
    }

    /**
     * 是否中国IP地址
     * @return array|mixed
     */
    public function get__cnip() {
        return Base::isCnIp(Request::input('ip'));
    }

    /**
     * 获取IP地址详细信息
     * @return array|mixed
     */
    public function get__ipinfo() {
        return Base::getIpInfo(Request::input("ip"));
    }

    /**
     * 首页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function main()
    {
        return view('main', ['version' => $this->version]);
    }


    /**
     * 清理opcache数据
     * @return int
     */
    public function opcache__reset()
    {
        opcache_reset();
        return Base::time();
    }
}
