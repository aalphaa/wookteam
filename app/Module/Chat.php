<?php

namespace App\Module;

use Cache;
use DB;

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
        if (!$username || !$receive) {
            return Base::retError('参数错误！');
        }
        $cacheKey = $username . "@" . $receive;
        $result = Cache::remember($cacheKey, now()->addMinutes(10), function() use ($receive, $username) {
            $row = Base::DBC2A(DB::table('chat_dialog')->where([
                'user1' => $username,
                'user2' => $receive,
            ])->first());
            if ($row) {
                $row['recField'] = 2;   //接受者的字段位置
                return Base::retSuccess('already1', $row);
            }
            $row = Base::DBC2A(DB::table('chat_dialog')->where([
                'user1' => $receive,
                'user2' => $username,
            ])->first());
            if ($row) {
                $row['recField'] = 1;
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
                $row['recField'] = 2;
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
     * 删除对话缓存
     * @param $username
     * @param $receive
     * @return bool
     */
    public static function forgetDialog($username, $receive)
    {
        if (!$username || !$receive) {
            return false;
        }
        Cache::forget($username . "@" . $receive);
        Cache::forget($receive . "@" . $username);
        return true;
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
        $indate = abs($message['indate'] - time()) > 60 ? time() : $message['indate'];
        if (isset($message['id'])) unset($message['id']);
        if (isset($message['username'])) unset($message['username']);
        if (isset($message['userimg'])) unset($message['userimg']);
        if (isset($message['indate'])) unset($message['indate']);
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
            case 'taskB':
                $lastText = $message['text'] . " [来自关注任务]";
                break;
            case 'report':
                $lastText = $message['text'] . " [来自工作报告]";
                break;
            default:
                $lastText = '[未知类型]';
                break;
        }
        if ($lastText) {
            $upArray = Base::DBUP([
                ($dialog['recField'] == 1 ? 'unread1' : 'unread2') => 1,
            ]);
            $upArray['lasttext'] = $lastText;
            $upArray['lastdate'] = $indate;
            if ($dialog['del1']) {
                $upArray['del1'] = 0;
            }
            if ($dialog['del2']) {
                $upArray['del2'] = 0;
            }
            DB::table('chat_dialog')->where('id', $dialog['id'])->update($upArray);
            if ($dialog['del1'] || $dialog['del2']) {
                Chat::forgetDialog($dialog['user1'], $dialog['user2']);
            }
        }
        $inArray['id'] = DB::table('chat_msg')->insertGetId($inArray);
        $inArray['message'] = $message;
        //
        return Base::retSuccess('success', $inArray);
    }
}
