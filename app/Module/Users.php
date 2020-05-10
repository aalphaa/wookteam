<?php

namespace App\Module;

use App\Model\DBCache;
use DB;
use Request;
use Session;

/**
 * Class Users
 * @package App\Module
 */
class Users
{
    /**
     * 临时身份标识
     * @return mixed|string
     */
    public static function tmpID()
    {
        if (strlen(Request::input("tmpid")) == 16) {
            return Request::input("tmpid");
        }
        $tmpID = Session::get('user::tmpID');
        if (strlen($tmpID) != 16) {
            $tmpID = Base::generatePassword(16);
            Session::put('user::tmpID', $tmpID);
        }
        return $tmpID;
    }

    /**
     * id获取用户名
     * @param $id
     * @return mixed
     */
    public static function id2username($id) {
        return DB::table('users')->where('id', intval($id))->value('username');
    }

    /**
     * 用户名获取id
     * @param $username
     * @return mixed
     */
    public static function username2id($username) {
        return intval(DB::table('users')->where('username', $username)->value('id'));
    }

    /**
     * token获取会员ID
     * @return int
     */
    public static function token2userid()
    {
        $authorization = Base::getToken();
        $id = 0;
        if ($authorization) {
            list($id, $username, $encrypt, $timestamp) = explode("@", base64_decode($authorization));
        }
        return intval($id);
    }

    /**
     * token获取会员手机号
     * @return int
     */
    public static function token2username()
    {
        $authorization = Base::getToken();
        $username = '';
        if ($authorization) {
            list($id, $username, $encrypt, $timestamp) = explode("@", base64_decode($authorization));
        }
        return Base::isMobile($username) ? $username : '';
    }

    /**
     * 用户身份认证（获取用户信息）
     * @return array|mixed
     */
    public static function auth()
    {
        global $_A;
        if (isset($_A["__static_auth"])) {
            return $_A["__static_auth"];
        }
        $authorization = Base::getToken();
        if ($authorization) {
            list($id, $username, $encrypt, $timestamp) = explode("@", base64_decode($authorization));
            if ($id > 0 && $timestamp + 2592000 > Base::time()) {
                $userinfo = DB::table('users')->where(['id' => $id, 'username' => $username, 'encrypt' => $encrypt])->first();
                Base::coll2array($userinfo);
                if ($userinfo['token']) {
                    $upArray = [];
                    if (Base::getIp() && $userinfo['lineip'] != Base::getIp()) {
                        $upArray['lineip'] = Base::getIp();
                    }
                    if ($userinfo['linedate'] + 30 < Base::time()) {
                        $upArray['linedate'] = Base::time();
                    }
                    if ($upArray) {
                        DB::table('users')->where('id', $userinfo['id'])->update($upArray);
                    }
                    return $_A["__static_auth"] = Users::retInfo($userinfo);
                }
            }
        }
        return $_A["__static_auth"] = false;
    }

    /**
     * 用户身份认证, 身份丢失时exit输出（获取用户信息）
     * @return array|mixed
     */
    public static function authE()
    {
        $user = Users::auth();
        if (!$user) {
            $authorization = Base::getToken();
            if ($authorization) {
                return Base::retError('身份已失效,请重新登录！', [], -1);
            } else {
                return Base::retError('请登录后继续...', [], -1);
            }
        }
        return Base::retSuccess("auth", $user);
    }

    /**
     * 生成token
     * @param $userinfo
     * @return bool|string
     */
    public static function token($userinfo)
    {
        if (strlen($userinfo['encrypt']) < 6) {
            $userinfo['encrypt'] = Base::generatePassword(6);
            DB::table('users')->where('id', $userinfo['id'])->update(['encrypt' => $userinfo['encrypt']]);
        }
        return base64_encode($userinfo['id'] . '@' . $userinfo['username'] . '@' . $userinfo['encrypt'] . '@' . Base::time() . '@' . Base::generatePassword(6));
    }

    /**
     * 判断用户权限（身份）
     * @param $identity
     * @return array
     */
    public static function identity($identity)
    {
        $user = Users::auth();
        if (is_array($user['identity'])
            && in_array($identity, $user['identity'])) {
            return Base::retSuccess("权限通过");
        }
        return Base::retError("权限不足");
    }

    /**
     * 筛选用户信息
     * @param $userinfo
     * @return mixed
     */
    public static function retInfo($userinfo)
    {
        //是否设置密码
        if (!isset($userinfo['setpass'])) {
            $userinfo['setpass'] = $userinfo['userpass'] ? 1 : 0;
        }
        //
        $userinfo['setting'] = Base::string2array($userinfo['setting']);
        $userinfo['userimg'] = $userinfo['userimg'] ? Base::fillUrl($userinfo['userimg']) : url('images/avatar.png');
        $userinfo['identity'] = is_array($userinfo['identity']) ? $userinfo['identity'] : explode(",", trim($userinfo['identity'], ","));
        unset($userinfo['encrypt']);
        unset($userinfo['userpass']);
        return $userinfo;
    }

    /**
     * userid 获取 基本信息
     * @param int $userid           会员ID
     * @return array
     */
    public static function userid2basic($userid)
    {
        if (empty($userid)) {
            return [];
        }
        $fields = ['username', 'nickname', 'userimg', 'profession'];
        $userInfo = DBCache::table('users')->where('id', $userid)->select($fields)->cacheMinutes(1)->first();
        if ($userInfo) {
            $userInfo['userimg'] = Users::userimg($userInfo['userimg']);
        }
        return $userInfo ?: [];
    }

    /**
     * 用户头像，不存在时返回默认
     * @param string|int $var 头像地址 或 会员ID
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public static function userimg($var) {
        if (Base::isNumber($var)) {
            if (empty($var)) {
                $var = "";
            }else{
                $userInfo = self::userid2basic($var);
                $var = $userInfo['userimg'];
            }
        }
        return $var ? Base::fillUrl($var) : url('images/other/avatar.png');
    }
}