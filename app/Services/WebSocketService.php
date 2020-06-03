<?php

namespace App\Services;

use App\Module\Base;
use App\Module\Chat;
use Cache;
use DB;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * @see https://wiki.swoole.com/#/start/start_ws_server
 */
class WebSocketService implements WebSocketHandlerInterface
{
    /**
     * 声明没有参数的构造函数
     * WebSocketService constructor.
     */
    public function __construct()
    {

    }

    /**
     * 连接建立时触发
     * @param Server $server
     * @param Request $request
     */
    public function onOpen(Server $server, Request $request)
    {
        $to = $request->fd;
        if (!isset($request->get['token'])) {
            $server->push($to, Base::array2json([
                'messageType' => 'error',
                'type' => 'user',
                'sender' => null,
                'target' => null,
                'content' => [
                    'error' => '参数错误'
                ],
                'time' => Base::time()
            ]));
            $server->close($to);
            self::forgetUser($to);
            return;
        }
        //
        $token = $request->get['token'];
        $cacheKey = "ws-token:" . md5($token);
        $username = Cache::remember($cacheKey, now()->addSeconds(1), function () use ($token) {
            list($id, $username, $encrypt, $timestamp) = explode("@", base64_decode($token) . "@@@@");
            if (intval($id) > 0 && intval($timestamp) + 2592000 > Base::time()) {
                if (DB::table('users')->where(['id' => $id, 'username' => $username, 'encrypt' => $encrypt])->exists()) {
                    return $username;
                }
            }
            return null;
        });
        if (empty($username)) {
            Cache::forget($cacheKey);
            $server->push($to, Base::array2json([
                'messageType' => 'error',
                'type' => 'user',
                'sender' => null,
                'target' => null,
                'content' => [
                    'error' => '会员不存在',
                ],
                'time' => Base::time()
            ]));
            $server->close($to);
            self::forgetUser($to);
            return;
        }
        //
        self::saveUser($to, $username);
        $server->push($to, Base::array2json([
            'messageType' => 'open',
            'type' => 'user',
            'sender' => null,
            'target' => null,
            'content' => [
                'swid' => $to,
            ],
            'time' => Base::time()
        ]));
    }

    /**
     * 收到消息时触发
     * @param Server $server
     * @param Frame $frame
     */
    public function onMessage(Server $server, Frame $frame)
    {
        $data = Base::json2array($frame->data);
        $feedback = [
            'status' => 1,
            'message' => '',
        ];
        switch ($data['type']) {
            /**
             * 未读消息总数
             */
            case 'unread':
                $from = self::fd2name($frame->fd);
                if ($from) {
                    $num = intval(DB::table('chat_dialog')->where('user1', $from)->sum('unread1'));
                    $num+= intval(DB::table('chat_dialog')->where('user2', $from)->sum('unread2'));
                    $feedback['message'] = $num;
                } else {
                    $feedback['message'] = 0;
                }
                break;

            /**
             * 已读消息
             */
            case 'read':
                $to = self::name2fd($data['target']);
                if ($to) {
                    $dialog = Chat::openDialog(self::fd2name($frame->fd), $data['target']);
                    if (!Base::isError($dialog)) {
                        $dialog = $dialog['data'];
                        $upArray = [];
                        if ($dialog['user1'] == $dialog['user2']) {
                            $upArray['unread1'] = 0;
                            $upArray['unread2'] = 0;
                        } else {
                            $upArray[($dialog['recField'] == 1 ? 'unread2' : 'unread1')] = 0;
                        }
                        DB::table('chat_dialog')->where('id', $dialog['id'])->update($upArray);
                    }
                }
                break;

            /**
             * 发给用户
             */
            case 'user':
                $to = self::name2fd($data['target']);
                $res = Chat::saveMessage(self::fd2name($frame->fd), $data['target'], $data['content']);
                if (Base::isError($res)) {
                    $feedback = [
                        'status' => 0,
                        'message' => $res['msg'],
                    ];
                } else {
                    $data['content']['id'] = $res['data']['id'];
                    $feedback['message'] = $res['data']['id'];
                    if ($to) {
                        $server->push($to, Base::array2json($data));
                    }
                }
                break;

            /**
             * 发给整个团队
             */
            case 'team':
                foreach (self::getUsersAll() as $user) {
                    $data['target'] = $user['username'];
                    $server->push($user['wsid'], Base::array2json($data));
                }
                break;
        }
        if ($data['messageId']) {
            $server->push($frame->fd, Base::array2json([
                'messageType' => 'feedback',
                'messageId' => $data['messageId'],
                'type' => 'user',
                'sender' => null,
                'target' => null,
                'content' => $feedback,
                'time' => Base::time()
            ]));
        }
    }

    /**
     * 关闭连接时触发
     * @param Server $server
     * @param $fd
     * @param $reactorId
     */
    public function onClose(Server $server, $fd, $reactorId)
    {
        self::forgetUser($fd);
    }

    /** ****************************************************************************** */
    /** ****************************************************************************** */
    /** ****************************************************************************** */

    /**
     * 缓存用户信息
     * @param $fd
     * @param $username
     */
    public static function saveUser($fd, $username)
    {
        DB::table('users')->where('wsid', $fd)->update(['wsid' => 0]);
        DB::table('users')->where('username', $username)->update(['wsid' => $fd]);
    }

    /**
     * 清除用户缓存
     * @param $fd
     */
    public static function forgetUser($fd)
    {
        DB::table('users')->where('wsid', $fd)->update(['wsid' => 0]);
    }

    /**
     * 获取当前用户
     * @return array|string
     */
    public static function getUsersAll()
    {
        return Base::DBC2A(DB::table('users')->select(['wsid', 'username'])->where('wsid', '>', 0)->get());
    }

    /**
     * @param $fd
     * @return mixed
     */
    public static function fd2name($fd)
    {
        return DB::table('users')->select(['username'])->where('wsid', $fd)->value('username');
    }

    /**
     * @param $username
     * @return mixed
     */
    public static function name2fd($username)
    {
        return DB::table('users')->select(['wsid'])->where('username', $username)->value('wsid');
    }
}
