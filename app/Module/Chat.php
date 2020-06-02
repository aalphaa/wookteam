<?php

namespace App\Module;

use App\Model\DBCache;
use Cache;
use DB;
use Request;
use Session;

/**
 * Class Chat
 * @package App\Module
 */
class Chat
{
    /**
     * 打开对话（创建对话）
     * @param string $username      发送者用户名
     * @param string $receive       接受者用户名
     * @return mixed
     */
    public static function openDialog($username, $receive)
    {
        $cacheKey = $username . "@" . $receive;
        $result = Cache::remember($cacheKey, now()->addMinutes(10), function() use ($receive, $username) {
            $row = Base::DBC2A(DB::table('chat_dialog')->where([
                'user1' => $username,
                'user2' => $receive,
            ])->first());
            if ($row) {
                return Base::retSuccess('already1', $row);
            }
            $row = Base::DBC2A(DB::table('chat_dialog')->where([
                'user1' => $receive,
                'user2' => $username,
            ])->first());
            if ($row) {
                return Base::retSuccess('already2', $row);
            }
            //
            DB::table('chat_dialog')->insert([
                'user1' => $username,
                'user2' => $receive,
                'indate' => Base::time()
            ]);
            $row = Base::DBC2A(DB::table('chat_dialog')->where([
                'user1' => $username,
                'user2' => $receive,
            ])->first());
            if ($row) {
                return Base::retSuccess('success', $row);
            }
            //
            return Base::retError('系统繁忙，请稍后再试！');
        });
        if (Base::isError($result)) {
            Cache::forget($cacheKey);
        }
        return $result;
    }

    /**
     * 保存对话消息
     * @param string $username      发送者用户名
     * @param string $receive       接受者用户名
     * @param array $message
     * @return mixed
     */
    public static function saveMessage($username, $receive, $message)
    {
        $dialog = self::openDialog($username, $receive);
        if (Base::isError($dialog)) {
            return $dialog;
        } else {
            $dialog = $dialog['data'];
        }
        //
        $indate = abs($message['indate'] - time()) > 30 ? time() : $message['indate'];
        $inArray = [
            'did' => $dialog['id'],
            'username' => $username,
            'receive' => $receive,
            'message' => Base::array2string($message),
            'indate' => $indate
        ];
        //
        switch ($message['type']) {
            case 'text':
                $lastText = $message['text'];
                break;
            case 'image':
                $lastText = '[图片]';
                break;
            default:
                $lastText = '[未知类型]';
                break;
        }
        if ($lastText) {
            DB::table('chat_dialog')->where('id', $dialog['id'])->update([
                'lasttext' => $lastText,
                'lastdate' => $indate,
            ]);
        }
        $inArray['id'] = DB::table('chat_msg')->insertGetId($inArray);
        $inArray['message'] = $message;
        //
        return Base::retSuccess('success', $inArray);
    }
}
