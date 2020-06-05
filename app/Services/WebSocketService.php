<?php

namespace App\Services;

use App\Model\DBCache;
use App\Module\Base;
use App\Module\Chat;
use App\Module\Users;
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
        global $_A;
        $_A = [
            '__static_langdata' => [],
        ];
        //
        $to = $request->fd;
        if (!isset($request->get['token'])) {
            $server->push($to, $this->formatMsgSend([
                'messageType' => 'error',
                'type' => 'user',
                'content' => [
                    'error' => '参数错误'
                ],
            ]));
            $server->close($to);
            $this->forgetUser($to);
            return;
        }
        //
        $token = $request->get['token'];
        $cacheKey = "ws::token:" . md5($token);
        $username = Cache::remember($cacheKey, now()->addSeconds(1), function () use ($token) {
            list($id, $username, $encrypt, $timestamp) = explode("@", base64_decode($token) . "@@@@");
            if (intval($id) > 0 && intval($timestamp) + 2592000 > time()) {
                if (DB::table('users')->where(['id' => $id, 'username' => $username, 'encrypt' => $encrypt])->exists()) {
                    return $username;
                }
            }
            return null;
        });
        if (empty($username)) {
            Cache::forget($cacheKey);
            $server->push($to, $this->formatMsgSend([
                'messageType' => 'error',
                'type' => 'user',
                'content' => [
                    'error' => '会员不存在',
                ],
            ]));
            $server->close($to);
            $this->forgetUser($to);
            return;
        }
        //
        $wsid = $this->name2fd($username);
        if ($wsid > 0) {
            $server->push($wsid, $this->formatMsgSend([
                'messageType' => 'forced',
                'type' => 'user',
                'content' => [
                    'ip' => Base::getIp(),
                    'time' => time(),
                    'new_wsid' => $to,
                    'old_wsid' => $wsid,
                ],
            ]));
        }
        $this->saveUser($to, $username);
        $server->push($to, $this->formatMsgSend([
            'messageType' => 'open',
            'type' => 'user',
            'content' => [
                'swid' => $to,
            ],
        ]));
        //
        $lastMsg = Base::DBC2A(DB::table('chat_msg')->where('receive', $username)->orderByDesc('indate')->first());
        if ($lastMsg && $lastMsg['roger'] === 0) {
            $content = Base::string2array($lastMsg['message']);
            $content['resend'] = 1;
            $content['id'] = $lastMsg['id'];
            $content['username'] = $lastMsg['username'];
            $content['userimg'] = Users::userimg($lastMsg['username']);
            $content['indate'] = $lastMsg['indate'];
            $server->push($to, $this->formatMsgSend([
                'messageType' => 'send',
                'contentId' => $lastMsg['id'],
                'type' => 'user',
                'sender' => $lastMsg['username'],
                'target' => $lastMsg['receive'],
                'content' => $content,
                'time' => $lastMsg['indate'],
            ]));
        }
    }

    /**
     * 收到消息时触发
     * @param Server $server
     * @param Frame $frame
     */
    public function onMessage(Server $server, Frame $frame)
    {
        global $_A;
        $_A = [
            '__static_langdata' => [],
        ];
        //
        $data = $this->formatMsgReceive($frame->data);
        $feedback = [
            'status' => 1,
            'message' => '',
        ];
        switch ($data['type']) {
            /**
             * 刷新
             */
            case 'refresh':
                DB::table('users')->where('username', $this->fd2name($frame->fd))->update(['wsdate' => time()]);
                break;

            /**
             * 未读消息总数
             */
            case 'unread':
                $from = $this->fd2name($frame->fd);
                if ($from) {
                    $num = intval(DB::table('chat_dialog')->where('user1', $from)->sum('unread1'));
                    $num+= intval(DB::table('chat_dialog')->where('user2', $from)->sum('unread2'));
                    $feedback['message'] = $num;
                } else {
                    $feedback['message'] = 0;
                }
                break;

            /**
             * 已读会员消息
             */
            case 'read':
                $to = $this->name2fd($data['target']);
                if ($to) {
                    $dialog = Chat::openDialog($this->fd2name($frame->fd), $data['target']);
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
             * 收到信息回执
             */
            case 'roger':
                if ($data['contentId'] > 0) {
                    DB::table('chat_msg')->where([
                        'id' => $data['contentId'],
                        'receive' => $this->fd2name($frame->fd),
                    ])->update([
                        'roger' => 1,
                    ]);
                }
                break;

            /**
             * 发给用户
             */
            case 'user':
                $to = $this->name2fd($data['target']);
                $res = Chat::saveMessage($this->fd2name($frame->fd), $data['target'], $data['content']);
                if (Base::isError($res)) {
                    $feedback = [
                        'status' => 0,
                        'message' => $res['msg'],
                    ];
                } else {
                    $contentId = $res['data']['id'];
                    $data['contentId'] = $contentId;
                    $data['content']['id'] = $contentId;
                    $feedback['message'] = $contentId;
                    if ($to) {
                        $server->push($to, $this->formatMsgSend($data));
                    }
                }
                break;

            /**
             * 发给整个团队
             */
            case 'team':
                if (Base::val($data['content'], 'type') === 'taskA') {
                    $taskId = intval(Base::val($data['content'], 'taskDetail.id'));
                    if ($taskId > 0) {
                        $userLists = $this->getTaskUsers($taskId);
                    } else {
                        $userLists = $this->getTeamUsers();
                    }
                    foreach ($userLists as $user) {
                        $data['target'] = $user['username'];
                        $server->push($user['wsid'], $this->formatMsgSend($data));
                    }
                }
                break;
        }
        if ($data['messageId']) {
            $server->push($frame->fd, $this->formatMsgSend([
                'messageType' => 'feedback',
                'messageId' => $data['messageId'],
                'type' => 'user',
                'content' => $feedback,
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
        $this->forgetUser($fd);
    }

    /** ****************************************************************************** */
    /** ****************************************************************************** */
    /** ****************************************************************************** */

    /**
     * 格式化信息（来自接收）
     * @param $data
     * @return array
     */
    private function formatMsgReceive($data) {
        return $this->formatMsgData(Base::json2array($data));
    }

    /**
     * 格式化信息（用于发送）
     * @param $array
     * @return string
     */
    private function formatMsgSend($array) {
        return Base::array2json($this->formatMsgData($array));
    }

    /**
     * 格式化信息
     * @param array $array
     * @return array
     */
    private function formatMsgData($array = []) {
        if (!is_array($array)) {
            $array = [];
        }
        if (!isset($array['messageType'])) $array['messageType'] = '';
        if (!isset($array['messageId'])) $array['messageId'] = '';
        if (!isset($array['contentId'])) $array['contentId'] = 0;
        if (!isset($array['type'])) $array['type'] = '';
        if (!isset($array['sender'])) $array['sender'] = null;
        if (!isset($array['target'])) $array['target'] = null;
        if (!isset($array['content'])) $array['content'] = [];
        if (!isset($array['time'])) $array['time'] = time();
        if (!is_array($array['content'])) $array['content'] = [];
        $array['contentId'] = intval($array['contentId']);
        return $array;
    }

    /**
     * 保存用户wsid
     * @param $fd
     * @param $username
     */
    private function saveUser($fd, $username)
    {
        $this->forgetUser($fd);
        $this->forgetName($username);
        DB::table('users')->where('username', $username)->update(['wsid' => $fd, 'wsdate' => time()]);
    }

    /**
     * 清除用户wsid
     * @param $fd
     */
    private function forgetUser($fd)
    {
        $this->forgetFd($fd);
        DB::table('users')->where('wsid', $fd)->update(['wsid' => 0]);
    }

    /**
     * 根据wsid清除缓存
     * @param $fd
     */
    private function forgetFd($fd) {
        Cache::forget('ws::name:' . $this->fd2name($fd));
        Cache::forget('ws::fd:' . $fd);
    }

    /**
     * 根据username清除缓存
     * @param $username
     */
    private function forgetName($username) {
        Cache::forget('ws::fd:' . $this->name2fd($username));
        Cache::forget('ws::name:' . $username);
    }

    /**
     * 获取团队用户
     * @return array|string
     */
    private function getTeamUsers()
    {
        return Base::DBC2A(DB::table('users')->select(['wsid', 'username'])->where([
            ['wsid', '>', 0],
            ['wsdate', '>', time() - 600],
        ])->get());
    }

    /**
     * 获取跟任务有关系的用户（关注的、在项目里的、负责人、创建者）
     * @param $taskId
     * @return array
     */
    private function getTaskUsers($taskId)
    {
        $taskDeatil = Base::DBC2A(DB::table('project_task')->select(['follower', 'createuser', 'username', 'projectid'])->where('id', $taskId)->first());
        if (empty($taskDeatil)) {
            return [];
        }
        //关注的用户
        $userArray = Base::string2array($taskDeatil['follower']);
        //创建者
        $userArray[] = $taskDeatil['createuser'];
        //负责人
        $userArray[] = $taskDeatil['username'];
        //在项目里的用户
        if ($taskDeatil['projectid'] > 0) {
            $tempLists = Base::DBC2A(DB::table('project_users')->select(['username'])->where(['projectid' => $taskDeatil['projectid'], 'type' => '成员' ])->get());
            foreach ($tempLists AS $item) {
                $userArray[] = $item['username'];
            }
        }
        //
        return Base::DBC2A(DB::table('users')->select(['wsid', 'username'])->where([
            ['wsid', '>', 0],
            ['wsdate', '>', time() - 600],
        ])->whereIn('username', array_values(array_unique($userArray)))->get());
    }

    /**
     * wsid获取用户名
     * @param $fd
     * @return mixed
     */
    private function fd2name($fd)
    {
        return DBCache::table('users')->cacheKeyname('ws::fd:' . $fd)->select(['username'])->where('wsid', $fd)->value('username');
    }

    /**
     * 用户名获取wsid
     * @param $username
     * @return mixed
     */
    private function name2fd($username)
    {
        return DBCache::table('users')->cacheKeyname('ws::name:' . $username)->select(['wsid'])->where('username', $username)->value('wsid');
    }
}
