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
 * @apiDefine report
 *
 * 汇报
 */
class ReportController extends Controller
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
     * 获取内容
     *
     * @apiParam {Number} id           数据ID
     */
    public function content()
    {
        $row = Base::DBC2A(DB::table('report_content')->select(['rid', 'content'])->where('rid', intval(Request::input('id')))->first());
        if (empty($row)) {
            return Base::retError('内容不存在或已被删除！');
        }
        return Base::retSuccess('success', $row);
    }

    /**
     * 获取模板、保存、发送、删除
     *
     * @apiParam {String} type           类型
     * - 日报
     * - 周报
     * @apiParam {Number} [id]          数据ID
     * @apiParam {String} [act]         请求方式
     * - submit: 保存
     * - send: 仅发送
     * - delete: 删除汇报
     * - else: 获取
     * @apiParam {String} [send]        是否发送（1:是），仅act=submit且未发送过的有效
     * @apiParam {Object} [D]           Request Payload 提交
     * - title: 标题
     * - ccuser: 抄送人
     * - content: 内容
     */
    public function template()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $id = intval(Request::input('id'));
        $act = trim(Request::input('act'));
        $type = trim(Request::input('type'));
        if (!in_array($type, ['日报', '周报'])) {
            return Base::retError('参数错误！');
        }
        $dateTitle = "";
        //
        $whereArray = [];
        $whereArray[] = ['username', '=', $user['username']];
        if ($id > 0) {
            $whereArray[] = ['id', '=', $id];
        } else {
            switch ($type) {
                case "日报":
                    $whereArray[] = ['type', '=', '日报'];
                    $whereArray[] = ['date', '=', date("Ymd")];
                    $dateTitle = date("Y-m-d");
                    break;
                case "周报":
                    $whereArray[] = ['type', '=', '周报'];
                    $whereArray[] = ['date', '=', date("W")];
                    $dateTitle = date("Y年m月") . "第" . Base::getMonthWeek() . "周";
                    break;
            }
        }
        //
        $reportDetail = Base::DBC2A(DB::table('report_lists')->where($whereArray)->first());
        if ($id > 0 && empty($reportDetail)) {
            return Base::retError('没有相关的数据！');
        }
        if ($act == 'send') {
            if (empty($reportDetail)) {
                return Base::retError('没有相关的数据或已被删除！');
            }
            $ccuser = Base::string2array($reportDetail['ccuser']);
            DB::table('report_ccuser')->where(['rid' => $reportDetail['id']])->update(['cc' => 0]);
            foreach ($ccuser AS $ck => $cuser) {
                if (!$cuser) {
                    unset($ccuser[$ck]);
                    continue;
                }
                DB::table('report_ccuser')->updateOrInsert(['rid' => $reportDetail['id'], 'username' => $cuser], ['cc' => 1]);
            }
            DB::table('report_lists')->where('id', $reportDetail['id'])->update([
                'status' => '已发送',
                'ccuser' => Base::array2string($ccuser)
            ]);
            return Base::retSuccess('发送成功！');
        } elseif ($act == 'delete') {
            if (empty($reportDetail)) {
                return Base::retError('没有相关的数据或已被删除！');
            }
            if ($reportDetail['status'] == '已发送') {
                return Base::retError('汇报已发送，无法删除！');
            }
            DB::table('report_lists')->where('id', $reportDetail['id'])->delete();
            DB::table('report_ccuser')->where('rid', $reportDetail['id'])->delete();
            DB::table('report_content')->where('rid', $reportDetail['id'])->delete();
            return Base::retSuccess('删除成功！');
        } elseif ($act == 'submit') {
            $D = Base::getContentsParse('D');
            if (empty($reportDetail)) {
                DB::table('report_lists')->insert([
                    'username' => $user['username'],
                    'title' => $D['title'],
                    'type' => $type,
                    'status' => '未发送',
                    'date' => $type=='日报'?date("Ymd"):date("W"),
                    'indate' => Base::time(),
                ]);
                $reportDetail = Base::DBC2A(DB::table('report_lists')->where($whereArray)->first());
            }
            if (empty($reportDetail)) {
                return Base::retError('系统繁忙，请稍后再试！');
            }
            //
            $D['ccuser'] = explode(",", $D['ccuser']);
            $send = $reportDetail['status'] == '已发送' ? 1 : intval(Request::input('send'));
            if ($send) {
                DB::table('report_ccuser')->where(['rid' => $reportDetail['id']])->update(['cc' => 0]);
                foreach ($D['ccuser'] AS $ck => $cuser) {
                    if (!$cuser) {
                        unset($D['ccuser'][$ck]);
                        continue;
                    }
                    DB::table('report_ccuser')->updateOrInsert(['rid' => $reportDetail['id'], 'username' => $cuser], ['cc' => 1]);
                }
                $reportDetail['status'] = '已发送';
            }
            //
            DB::table('report_lists')->where('id', $reportDetail['id'])->update([
                'title' => $D['title'],
                'status' => $send ? '已发送' : '未发送',
                'ccuser' => Base::array2string($D['ccuser'])
            ]);
            DB::table('report_content')->updateOrInsert(['rid' => $reportDetail['id']], ['content' => $D['content']]);
            //
            $reportDetail = array_merge($reportDetail, [
                'ccuser' => $D['ccuser'],
                'title' => $D['title'],
                'content' => $D['content'],
            ]);
        }
        if (empty($reportDetail)) {
            //已完成
            $completeContent = '';
            $startTime = $type == '日报' ? strtotime(date('Y-m-d 00:00:00')) : strtotime(date('Y-m-d 00:00:00', strtotime('this week')));
            $lists = Base::DBC2A(DB::table('project_task')
                ->select(['title', 'completedate'])
                ->where('username', $user['username'])
                ->where('complete', 1)
                ->where('delete', 0)
                ->whereBetween('completedate', [$startTime, time()])
                ->orderBy('completedate')
                ->orderBy('id')
                ->get());
            foreach ($lists as $item) {
                $pre = $type == '周报' ? ('<span>[周' . ['日', '一', '二', '三', '四', '五', '六'][date('w')] . ']</span>&nbsp;') : '';
                $completeContent .= '<li>' . $pre . $item['title'] . '</li>';
            }
            if (empty($completeContent)) {
                $completeContent = '<li>&nbsp;</li>';
            }
            //未完成
            $unfinishedContent = '';
            $finishTime = $type == '日报' ? strtotime(date('Y-m-d 23:59:59')) : strtotime(date('Y-m-d 23:59:59', strtotime('last day next week')));
            $lists = Base::DBC2A(DB::table('project_task')
                ->select(['title', 'enddate'])
                ->where('username', $user['username'])
                ->where('complete', 0)
                ->where('delete', 0)
                ->where('startdate', '>', 0)
                ->where('enddate', '<', $finishTime)
                ->orderBy('id')
                ->get());
            foreach ($lists as $item) {
                $pre = $item['enddate'] > 0 && $item['enddate'] < time() ? '<span style="color:#ff0000;">[超期]</span>&nbsp;' : '';
                $unfinishedContent .= '<li>' . $pre . $item['title'] . '</li>';
            }
            if (empty($unfinishedContent)) {
                $unfinishedContent = '<li>&nbsp;</li>';
            }
            //
            $reportDetail['title'] = ($user['nickname'] ?: $user['username']) . '的' . $type . '[' . $dateTitle . ']';
            $reportDetail['ccuser'] = '';
            $reportDetail['content'] = '<h2>已完成工作</h2><ol>' . $completeContent . '</ol><h2>未完成的工作</h2><ol>' . $unfinishedContent . '</ol>';
            $reportDetail['status'] = '未保存';
        } else {
            $reportDetail['ccuser'] = implode(',' ,Base::string2array($reportDetail['ccuser']));
            if (!isset($reportDetail['content'])) {
                $reportDetail['content'] = DB::table('report_content')->select(['content'])->where('rid', $reportDetail['id'])->value('content');
            }
        }
        return Base::retSuccess($act == 'submit' ? '保存成功' : 'success', $reportDetail);
    }

    /**
     * 我的汇报
     *
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:20，最大:100
     */
    public function my()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $whereArray = [];
        $whereArray[] = ['username', '=', $user['username']];
        if (trim(Request::input('username'))) {
            $whereArray[] = ['username', '=', trim(Request::input('username'))];
        }
        if (in_array(trim(Request::input('type')), ['日报', '周报'])) {
            $whereArray[] = ['type', '=', trim(Request::input('type'))];
        }
        $indate = Request::input('indate');
        if (is_array($indate)) {
            if ($indate[0] > 0) $whereArray[] = ['indate', '>=', Base::dayTimeF($indate[0])];
            if ($indate[1] > 0) $whereArray[] = ['indate', '<=', Base::dayTimeE($indate[1])];
        }
        //
        $orderBy = '`id` DESC';
        $sorts = Base::json2array(Request::input('sorts'));
        if (in_array($sorts['order'], ['asc', 'desc'])) {
            switch ($sorts['key']) {
                case 'indate':
                    $orderBy = '`' . $sorts['key'] . '` ' . $sorts['order'] . ',`id` DESC';
                    break;
            }
        }
        //
        $lists = DB::table('report_lists')
            ->where($whereArray)
            ->orderByRaw($orderBy)
            ->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的汇报', $lists);
        }
        foreach ($lists['lists'] AS $key => $item) {
            $lists['lists'][$key]['ccuser'] = Base::string2array($item['ccuser']);
        }
        return Base::retSuccess('success', $lists);
    }

    /**
     * 我的汇报
     *
     * @apiParam {String} [username]            汇报者用户名
     * @apiParam {Array} [indate]               汇报时间
     * @apiParam {String} [type]                类型
     * - 日报
     * - 周报
     * @apiParam {Object} [sorts]               排序方式，格式：{key:'', order:''}
     * - key: username|indate
     * - order: asc|desc
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:20，最大:100
     */
    public function receive()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $whereArray = [];
        $whereArray[] = ['report_ccuser.username', '=', $user['username']];
        $whereArray[] = ['report_ccuser.cc', '=', 1];
        if (trim(Request::input('username'))) {
            $whereArray[] = ['report_lists.username', '=', trim(Request::input('username'))];
        }
        if (in_array(trim(Request::input('type')), ['日报', '周报'])) {
            $whereArray[] = ['report_lists.type', '=', trim(Request::input('type'))];
        }
        $indate = Request::input('indate');
        if (is_array($indate)) {
            if ($indate[0] > 0) $whereArray[] = ['report_lists.indate', '>=', Base::dayTimeF($indate[0])];
            if ($indate[1] > 0) $whereArray[] = ['report_lists.indate', '<=', Base::dayTimeE($indate[1])];
        }
        //
        $orderBy = '`id` DESC';
        $sorts = Base::json2array(Request::input('sorts'));
        if (in_array($sorts['order'], ['asc', 'desc'])) {
            switch ($sorts['key']) {
                case 'username':
                case 'indate':
                    $orderBy = '`' . $sorts['key'] . '` ' . $sorts['order'] . ',`id` DESC';
                    break;
            }
        }
        //
        $lists = DB::table('report_lists')
            ->join('report_ccuser', 'report_lists.id', '=', 'report_ccuser.rid')
            ->select(['report_lists.*'])
            ->where($whereArray)
            ->orderByRaw($orderBy)
            ->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的汇报', $lists);
        }
        foreach ($lists['lists'] AS $key => $item) {
            $lists['lists'][$key]['ccuser'] = Base::string2array($item['ccuser']);
        }
        return Base::retSuccess('success', $lists);
    }
}
