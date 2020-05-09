<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Module\Base;
use App\Module\Users;
use DB;
use Request;
use Session;

/**
 * @apiDefine users
 *
 * 会员
 */
class UsersController extends Controller
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
     * 登陆
     * @return array
     */
    public function login()
    {
        $user = Base::DBC2A(DB::table('users')->where('username', trim(Request::input('username')))->first());
        if (empty($user)) {
            return Base::retError('账号或密码错误。');
        }
        if ($user['userpass'] != Base::md52(Request::input('userpass'))) {
            return Base::retError('账号或密码错误！');
        }
        //
        $array = [
            'token' => Users::token($user),
            'loginnum' => $user['loginnum'] + 1,
            'lastip' => Base::getIp(),
            'lastdate' => Base::time(),
            'lineip' => Base::getIp(),
            'linedate' => Base::time(),
        ];
        Base::array_over($user, $array);
        DB::table('users')->where('id', $user['id'])->update($array);
        //
        if (intval(Request::input('onlydata')) !== 1) {
            Session::put('sessionToken', $array['token']);
        }
        return Base::retSuccess("登陆成功", Users::retInfo($user));
    }

    /**
     * 获取会员信息
     * @return array|mixed
     */
    public function info()
    {
        $callback = Request::input('callback');
        //
        $user = Users::authE();
        if (Base::isError($user)) {
            if (strlen($callback) > 3) {
                return $callback . '(' . json_encode($user) . ')';
            }
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        if (strlen($callback) > 3) {
            return $callback . '(' . json_encode(Base::retSuccess('success', Users::retInfo($user))) . ')';
        }
        return Base::retSuccess('success', Users::retInfo($user));
    }

    /**
     * 修改资料
     * @return array|mixed
     */
    public function editdata()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $array = [];
        //头像
        $userimg = Request::input('userimg');
        if ($userimg) {
            $userimg = is_array($userimg) ? $userimg[0]['path'] : $userimg;
            $array['userimg'] = Base::unFillUrl($userimg);
        }
        //昵称
        $nickname = trim(Request::input('nickname'));
        if ($nickname) {
            if (mb_strlen($nickname) < 2) {
                return Base::retError('昵称不可以少于2个字！');
            } elseif (mb_strlen($nickname) > 8) {
                return Base::retError('昵称最多只能设置8个字！');
            } else {
                $array['nickname'] = $nickname;
            }
        }
        //职位/职称
        $profession = trim(Request::input('profession'));
        if ($profession) {
            if (mb_strlen($profession) < 2) {
                return Base::retError('昵称不可以少于2个字！');
            } elseif (mb_strlen($profession) > 20) {
                return Base::retError('昵称最多只能设置20个字！');
            } else {
                $array['profession'] = $profession;
            }
        }
        //
        if ($array) {
            DB::table('users')->where('id', $user['id'])->update($array);
        } else {
            return Base::retError('请设置要修改的内容！');
        }
        return Base::retSuccess('修改成功！');
    }

    /**
     * 修改密码
     * @return array|mixed
     */
    public function editpass()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $oldpass = trim(Request::input('oldpass'));
        $newpass = trim(Request::input('newpass'));
        if (strlen($newpass) < 6) {
            return Base::retError('密码设置不能小于6位数！');
        }
        if ($oldpass == $newpass) {
            return Base::retError('新旧密码一致！');
        }
        //
        if ($user['setpass']) {
            $verify = DB::table('users')->where(['id'=>$user['id'], 'userpass'=>Base::md52($oldpass)])->count();
            if (empty($verify)) {
                return Base::retError('请填写正确的旧密码！');
            }
        }
        DB::table('users')->where('id', $user['id'])->update(['encrypt' => Base::generatePassword(6), 'userpass'=>Base::md52($newpass)]);
        return Base::retSuccess('修改成功');
    }
}
