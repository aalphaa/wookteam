<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Module\Base;
use App\Module\Chat;
use App\Module\Users;
use DB;
use Request;

/**
 * @apiDefine chat
 *
 * 聊天
 */
class ChatController extends Controller
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
     * 对话列表
     */
    public function dialog__lists()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $lists = Base::DBC2A(DB::table('chat_dialog')
            ->where(function ($query) use ($user) {
                return $query->where('user1', $user['username'])->orWhere('user2', $user['username']);
            })
            ->orderByDesc('lastdate')
            ->take(200)
            ->get());
        if (count($lists) <= 0) {
            return Base::retError('暂无对话记录');
        }
        foreach ($lists AS $key => $item) {
            $lists[$key] = array_merge($item, Users::username2basic($item['user1'] == $user['username'] ? $item['user2'] : $item['user1']));
            $lists[$key]['lastdate'] = $item['lastdate'] ?: $item['indate'];
        }
        return Base::retSuccess('success', $lists);
    }


    /**
     * 添加/创建对话
     *
     * @apiParam {String} username             用户名
     */
    public function dialog__add()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $target = Users::username2basic(trim(Request::input('username')));
        if (empty($target)) {
            return Base::retError('用户不存在');
        }
        return Chat::openDialog($user['username'], $target['username']);
    }

    /**
     * 消息列表
     *
     * @apiParam {String} username             用户名
     */
    public function message__lists()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $res = Chat::openDialog($user['username'], trim(Request::input('username')));
        if (Base::isError($res)) {
            return $res;
        }
        $lists = DB::table('chat_msg')
            ->where('did', $res['data']['id'])
            ->orderByDesc('indate')
            ->orderByDesc('id')
            ->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists, false);
        //
        foreach ($lists['lists'] AS $key => $item) {
            $lists['lists'][$key]['userimg'] = Users::userimg($item['username']);
            $lists['lists'][$key]['message'] = Base::string2array($item['message']);
        }
        //
        return Base::retSuccess('success', $lists);
    }
}
