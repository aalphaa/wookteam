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
     * 注册会员
     * @param $username
     * @param $userpass
     * @param array $other
     * @return array
     */
    public static function reg($username, $userpass, $other = [])
    {
        //用户名
        if (strlen($username) < 2) {
            return Base::retError('用户名不可以少于2个字符！');
        } elseif (strlen($username) > 16) {
            return Base::retError('用户名最多只能设置16个字符！');
        }
        if (!preg_match('/^[A-Za-z0-9_\x{4e00}-\x{9fa5}]+$/u', $username)) {
            return Base::retError('用户名由2-16位数字或字母、汉字、下划线组成！');
        }
        if (Users::username2id($username) > 0) {
            return Base::retError('用户名已存在！');
        }
        //密码
        if (strlen($userpass) < 6) {
            return Base::retError('密码设置不能小于6位数！');
        } elseif (strlen($userpass) > 32) {
            return Base::retError('密码最多只能设置32位数！');
        }
        //开始注册
        $inArray = [
            'username' => $username,
            'userpass' => Base::md52($userpass),
            'regip' => Base::getIp(),
            'regdate' => Base::time()
        ];
        if ($other) {
            $inArray = array_merge($inArray, $other);
        }
        DB::table('users')->insert($inArray);
        $user = Base::DBC2A(DB::table('users')->where('username', $username)->first());
        if (empty($user)) {
            return Base::retError('注册失败，请稍后再试。');
        }
        return Base::retSuccess('success', $user);
    }

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
            list($id, $username, $encrypt, $timestamp) = explode("@", base64_decode($authorization) . "@@@@");
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
            list($id, $username, $encrypt, $timestamp) = explode("@", base64_decode($authorization) . "@@@@");
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
            list($id, $username, $encrypt, $timestamp) = explode("@", base64_decode($authorization) . "@@@@");
            if (intval($id) > 0 && intval($timestamp) + 2592000 > Base::time()) {
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
        $userinfo['userimg'] = self::userimg($userinfo['userimg']);
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
     * username 获取 基本信息
     * @param string $username           用户名
     * @param bool $clearCache           清理缓存
     * @return array
     */
    public static function username2basic($username, $clearCache = false)
    {
        if (empty($username)) {
            return [];
        }
        $fields = ['username', 'nickname', 'userimg', 'profession'];
        $builder = DBCache::table('users')->where('username', $username)->select($fields)->cacheMinutes(1);
        if ($clearCache) {
            $builder->removeCache()->first();
            return [];
        } else {
            $userInfo = $builder->first();
            if ($userInfo) {
                $userInfo['userimg'] = Users::userimg($userInfo['userimg']);
            }
            return $userInfo ?: [];
        }
    }

    /**
     * 用户头像，不存在时返回默认
     * @param string $var 头像地址 或 会员用户名
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public static function userimg($var) {
        if (!Base::strExists($var, '.')) {
            if (empty($var)) {
                $var = "";
            } else {
                $userInfo = self::username2basic($var);
                $var = $userInfo['userimg'];
            }
        }
        return $var ? Base::fillUrl($var) : url('images/other/avatar.png');
    }
}
