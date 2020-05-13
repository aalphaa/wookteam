<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\DBCache;
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
     * 搜索会员列表
     */
    public function searchinfo()
    {
        $keys = Request::input('where');
        $whereArr = [];
        $whereRaw = null;
        if ($keys['usernameequal'])     $whereArr[] = ['username', '=', $keys['usernameequal']];
        if ($keys['identity'])          $whereArr[] = ['identity', 'like', '%,' . $keys['identity'] . ',%'];
        if ($keys['noidentity'])        $whereArr[] = ['identity', 'not like', '%,' . $keys['noidentity'] . ',%'];
        if ($keys['username']) {
            $whereRaw.= $whereRaw ? ' AND ' : '';
            $whereRaw.= "(`username` LIKE '%" . $keys['username'] . "%' OR `nickname` LIKE '%" . $keys['username'] . "%')";
        }
        if (intval($keys['projectid']) > 0) {
            $whereRaw.= $whereRaw ? ' AND ' : '';
            $whereRaw.= "`username` IN (SELECT username FROM `" . env('DB_PREFIX') . "project_users` WHERE `type`='成员' AND `projectid`=" . intval($keys['projectid']) .")";
        }
        //
        $lists = DBCache::table('users')->select(['id', 'username', 'nickname', 'userimg', 'profession'])
            ->where($whereArr)
            ->whereRaw($whereRaw)
            ->orderBy('id')
            ->cacheMinutes(now()->addSeconds(10))
            ->take(Min(Max(Base::nullShow(Request::input('take'), 10), 1), 100))
            ->get();
        foreach ($lists AS $key => $item) {
            $lists[$key]['userimg'] = Base::fillUrl($item['userimg']);
            $lists[$key]['identitys'] = explode(",", trim($item['identity'], ","));
            $lists[$key]['setting'] = Base::string2array($item['setting']);
        }
        return Base::retSuccess('success', $lists);
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
        } elseif (strlen($newpass) > 32) {
            return Base::retError('密码最多只能设置32位数！');
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

    /**
     * 团队列表
     */
    public function team__lists()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $lists = DB::table('users')->select(['id', 'username', 'nickname', 'userimg', 'profession', 'regdate'])->orderByDesc('id')->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的团队成员');
        }
        foreach ($lists['lists'] AS $key => $item) {
            $lists['lists'][$key]['userimg'] = Users::userimg($item['userimg']);
        }
        return Base::retSuccess('success', $lists);
    }

    /**
     * 添加团队成员
     */
    public function team__add()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        if (Base::isError(Users::identity('admin'))) {
            return Base::retError('身份权限不足！', [], -1);
        }
        //头像
        $userimg = Request::input('userimg');
        if ($userimg) {
            $userimg = is_array($userimg) ? $userimg[0]['path'] : $userimg;
        }
        //昵称
        $nickname = trim(Request::input('nickname'));
        if ($nickname) {
            if (mb_strlen($nickname) < 2) {
                return Base::retError('昵称不可以少于2个字！');
            } elseif (mb_strlen($nickname) > 8) {
                return Base::retError('昵称最多只能设置8个字！');
            }
        }
        //职位/职称
        $profession = trim(Request::input('profession'));
        if ($profession) {
            if (mb_strlen($profession) < 2) {
                return Base::retError('昵称不可以少于2个字！');
            } elseif (mb_strlen($profession) > 20) {
                return Base::retError('昵称最多只能设置20个字！');
            }
        }
        //用户名
        $username = trim(Request::input('username'));
        if (strlen($username) < 2) {
            return Base::retError('用户名不可以少于2个字符！');
        } elseif (strlen($username) > 12) {
            return Base::retError('用户名最多只能设置12个字符！');
        }
        if (Users::username2id($username) > 0) {
            return Base::retError('用户名已存在！');
        }
        //密码
        $userpass = trim(Request::input('userpass'));
        if (strlen($userpass) < 6) {
            return Base::retError('密码设置不能小于6位数！');
        } elseif (strlen($userpass) > 32) {
            return Base::retError('密码最多只能设置32位数！');
        }
        //
        if (DB::table('users')->insert([
            'userimg' => $userimg ?: '',
            'nickname' => $nickname ?: '',
            'profession' => $profession ?: '',
            'username' => $username,
            'userpass' => Base::md52($userpass),
            'regip' => Base::getIp(),
            'regdate' => Base::time()
        ])) {
            return Base::retSuccess('添加成功！');
        } else {
            return Base::retError('添加失败！');
        }
    }

    /**
     * 删除团队成员
     */
    public function team__delete()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        if (Base::isError(Users::identity('admin'))) {
            return Base::retError('身份权限不足！', [], -1);
        }
        $id = intval(Request::input('id'));
        if ($user['id'] == $id) {
            return Base::retError('不能删除自己！');
        }
        //
        if (DB::table('users')->where('id', $id)->delete()) {
            return Base::retSuccess('删除成功！');
        } else {
            return Base::retError('删除失败！');
        }
    }
}
