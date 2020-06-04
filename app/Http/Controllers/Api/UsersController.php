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
     * 登陆、注册
     *
     * @apiParam {String} type           类型
     * - login:登录（默认）
     * - reg:注册
     * @apiParam {String} username       用户名
     * @apiParam {String} userpass       密码
     */
    public function login()
    {
        $type = trim(Request::input('type'));
        $username = trim(Request::input('username'));
        $userpass = trim(Request::input('userpass'));
        if ($type == 'reg') {
            $setting = Base::setting('system');
            if ($setting['reg'] == 'close') {
                return Base::retError('未开放注册。');
            }
            $user = Users::reg($username, $userpass);
            if (Base::isError($user)) {
                return $user;
            } else {
                $user = $user['data'];
            }
        } else {
            $user = Base::DBC2A(DB::table('users')->where('username', $username)->first());
            if (empty($user)) {
                return Base::retError('账号或密码错误。');
            }
            if ($user['userpass'] != Base::md52($userpass)) {
                return Base::retError('账号或密码错误！');
            }
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
        return Base::retSuccess($type == 'reg' ? "注册成功！" : "登陆成功！", Users::retInfo($user));
    }

    /**
     * 获取我的信息
     *
     * @apiParam {String} [callback]           jsonp返回字段
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
     * 获取指定会员基本信息
     *
     * @apiParam {String} username           会员用户名
     */
    public function basic()
    {
        return Base::retSuccess('success', Users::username2basic(trim(Request::input('username'))));
    }

    /**
     * 搜索会员列表
     *
     * @apiParam {Object} where            搜索条件
     * - where.usernameequal
     * - where.username
     * - where.nousername
     * - where.identity
     * - where.noidentity
     * - where.projectid
     * - where.noprojectid
     * @apiParam {Number} [take]           获取数量，10-100
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
        if ($keys['nousername']) {
            $nousername = [];
            foreach (explode(",", $keys['nousername']) AS $name) {
                $name = trim($name);
                if ($name && !in_array($name, $nousername)) {
                    $nousername[] = $name;
                }
            }
            if ($nousername) {
                $whereRaw.= $whereRaw ? ' AND ' : '';
                $whereRaw.= "(`username` NOT IN ('" . implode("','", $nousername) . "'))";
            }
        }
        if (intval($keys['noprojectid']) > 0) {
            $whereRaw.= $whereRaw ? ' AND ' : '';
            $whereRaw.= "`username` NOT IN (SELECT username FROM `" . env('DB_PREFIX') . "project_users` WHERE `type`='成员' AND `projectid`=" . intval($keys['noprojectid']) .")";
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
            $lists[$key]['userimg'] = Users::userimg($item['userimg']);
            $lists[$key]['identitys'] = explode(",", trim($item['identity'], ","));
            $lists[$key]['setting'] = Base::string2array($item['setting']);
        }
        return Base::retSuccess('success', $lists);
    }

    /**
     * 修改资料
     *
     * @apiParam {Object} [userimg]             会员头像
     * @apiParam {String} [nickname]            昵称
     * @apiParam {String} [profession]          职位/职称
     * @apiParam {String} [bgid]                背景编号
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
        //背景
        $bgid = intval(Request::input('bgid'));
        if ($bgid > 0) {
            $array['bgid'] = $bgid;
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
     *
     * @apiParam {String} oldpass           旧密码
     * @apiParam {String} newpass           新密码
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
     *
     * @apiParam {Object} [sorts]               排序方式，格式：{key:'', order:''}
     * - key: username|id(默认)
     * - order: asc|desc
     * @apiParam {Number} [firstchart]          是否获取首字母，1:获取
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:10，最大:100
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
        $orderBy = '`id` DESC';
        $sorts = Base::json2array(Request::input('sorts'));
        if (in_array($sorts['order'], ['asc', 'desc'])) {
            switch ($sorts['key']) {
                case 'username':
                    $orderBy = '`' . $sorts['key'] . '` ' . $sorts['order'] . ',`id` DESC';
                    break;
            }
        }
        //
        $lists = DB::table('users')->select(['id', 'username', 'nickname', 'userimg', 'profession', 'regdate'])->orderByRaw($orderBy)->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的团队成员');
        }
        foreach ($lists['lists'] AS $key => $item) {
            $lists['lists'][$key]['userimg'] = Users::userimg($item['userimg']);
            $lists['lists'][$key]['firstchart'] = Base::getFirstCharter($item['username']);
        }
        return Base::retSuccess('success', $lists);
    }

    /**
     * 添加团队成员
     *
     * @apiParam {String} username              用户名
     * @apiParam {String} userpass              密码
     * @apiParam {Object} [userimg]             会员头像
     * @apiParam {String} [nickname]            昵称
     * @apiParam {String} [profession]          职位/职称
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
        //开始注册
        $user = Users::reg(trim(Request::input('username')), trim(Request::input('userpass')), [
            'userimg' => $userimg ?: '',
            'nickname' => $nickname ?: '',
            'profession' => $profession ?: '',
        ]);
        if (Base::isError($user)) {
            return $user;
        } else {
            return Base::retSuccess('添加成功！');
        }
    }

    /**
     * 删除团队成员
     *
     * @apiParam {String} username           用户名
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
        $username = intval(Request::input('username'));
        if ($user['username'] == $username) {
            return Base::retError('不能删除自己！');
        }
        //
        if (DB::table('users')->where('username', $username)->delete()) {
            return Base::retSuccess('删除成功！');
        } else {
            return Base::retError('删除失败！');
        }
    }
}
