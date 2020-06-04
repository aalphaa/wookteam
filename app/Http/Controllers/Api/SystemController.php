<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Module\Base;
use App\Module\Users;
use Request;

/**
 * @apiDefine system
 *
 * 系统
 */
class SystemController extends Controller
{
    public function __invoke($method, $action = '')
    {
        $app = $method ? $method : 'main';
        if ($action) {
            $app .= "__" . $action;
        }
        return (method_exists($this, $app)) ? $this->$app() : Base::ajaxError("404 not found (" . str_replace("__", "/", $app) . ").");
    }

    /**
     * 获取设置、保存设置
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（支持：reg）
     */
    public function setting()
    {
        $type = trim(Request::input('type'));
        if ($type == 'save') {
            $user = Users::authE();
            if (Base::isError($user)) {
                return $user;
            } else {
                $user = $user['data'];
            }
            if ($user['id'] != 1) {
                return Base::retError('权限不足！');
            }
            $all = Request::input();
            foreach ($all AS $key => $value) {
                if (!in_array($key, ['reg'])) {
                    unset($all[$key]);
                }
            }
            $setting = Base::setting('system', Base::newTrim($all));
        } else {
            $setting = Base::setting('system');
        }
        return Base::retSuccess('success', $setting ? $setting : json_decode('{}'));
    }
}
