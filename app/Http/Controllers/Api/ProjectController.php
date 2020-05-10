<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Module\Base;
use App\Module\Users;
use DB;
use Request;
use Session;

/**
 * @apiDefine project
 *
 * 项目
 */
class ProjectController extends Controller
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
     * 项目列表
     *
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:20，最大:100
     */
    public function lists()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $lists = DB::table('project_lists')
            ->join('project_users', 'project_lists.id', '=', 'project_users.projectid')
            ->select(['project_lists.*', 'project_users.isowner'])
            ->where([
                ['project_lists.delete', 0],
                ['project_users.type', '成员'],
                ['project_users.username', $user['username']]
            ])
            ->orderByDesc('project_lists.id')->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的项目');
        }
        return Base::retSuccess('success', $lists);
    }

    /**
     * 添加项目
     *
     * @apiParam {String} title     项目名称
     * @apiParam {Array} labels     流程，格式[流程1, 流程2]
     */
    public function add()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //项目名称
        $title = trim(Request::input('title'));
        if (mb_strlen($title) < 2) {
            return Base::retError('项目名称不可以少于2个字！');
        } elseif (mb_strlen($title) > 32) {
            return Base::retError('项目名称最多只能设置32个字！');
        }
        //流程
        $labels = Request::input('labels');
        if (!is_array($labels)) $labels = [];
        $insertLabels = [];
        $inorder = 0;
        foreach ($labels AS $label) {
            $label = trim($label);
            if ($label) {
                $insertLabels[] = [
                    'title' => $label,
                    'inorder' => $inorder++,
                ];
            }
        }
        if (empty($insertLabels)) {
            $insertLabels[] = [
                'title' => '默认',
                'inorder' => 0,
            ];
        }
        //开始创建
        $projectid = DB::table('project_lists')->insertGetId([
            'title' => $title,
            'username' => $user['username'],
            'createuser' => $user['username'],
            'indate' => Base::time()
        ]);
        if ($projectid) {
            DB::table('project_label')->insert($insertLabels);
            DB::table('project_log')->insert([
                'type' => '日志',
                'projectid' => $projectid,
                'username' => $user['username'],
                'detail' => '创建项目',
                'indate' => Base::time()
            ]);
            DB::table('project_users')->insert([
                'type' => '成员',
                'projectid' => $projectid,
                'isowner' => 1,
                'username' => $user['username'],
                'indate' => Base::time()
            ]);
            return Base::retSuccess('添加成功！');
        } else {
            return Base::retError('添加失败！');
        }
    }

    /**
     * 收藏项目
     *
     * @apiParam {String} act
     * - cancel: 取消收藏
     * - else: 添加收藏
     * @apiParam {Number} projectid     项目ID
     *
     * @throws \Throwable
     */
    public function favor()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = trim(Request::input('projectid'));
        $item = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->first());
        if (empty($item)) {
            return Base::retError('项目不存在或已被删除！');
        }
        return DB::transaction(function () use ($item, $user) {
            switch (Request::input('act')) {
                case 'cancel': {
                    if (DB::table('project_users')->where([
                        'type' => '收藏',
                        'projectid' => $item['id'],
                        'username' => $user['username'],
                    ])->delete()) {
                        DB::table('project_log')->insert([
                            'type' => '日志',
                            'projectid' => $item['id'],
                            'username' => $user['username'],
                            'detail' => '取消收藏',
                            'indate' => Base::time()
                        ]);
                        return Base::retSuccess('取消成功');
                    }
                    return Base::retSuccess('已取消');
                }
                default: {
                    $row = Base::DBC2A(DB::table('project_users')->where([
                        'type' => '收藏',
                        'projectid' => $item['id'],
                        'username' => $user['username'],
                    ])->lockForUpdate()->first());
                    if (empty($row)) {
                        DB::table('project_users')->insert([
                            'type' => '收藏',
                            'projectid' => $item['id'],
                            'isowner' => $item['username'] == $user['username'] ? 1 : 0,
                            'username' => $user['username'],
                            'indate' => Base::time()
                        ]);
                        DB::table('project_log')->insert([
                            'type' => '日志',
                            'projectid' => $item['id'],
                            'username' => $user['username'],
                            'detail' => '收藏项目',
                            'indate' => Base::time()
                        ]);
                        return Base::retSuccess('收藏成功');
                    }
                    return Base::retSuccess('已收藏');
                }
            }
        });
    }

    /**
     * 重命名项目
     *
     * @apiParam {Number} projectid     项目ID
     * @apiParam {String} title         项目新名称
     */
    public function rename()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = trim(Request::input('projectid'));
        $item = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->first());
        if (empty($item)) {
            return Base::retError('项目不存在或已被删除！');
        }
        if ($item['username'] != $user['username']) {
            return Base::retError('你不是项目负责人！');
        }
        //
        $title = trim(Request::input('title'));
        if (mb_strlen($title) < 2) {
            return Base::retError('项目名称不可以少于2个字！');
        } elseif (mb_strlen($title) > 32) {
            return Base::retError('项目名称最多只能设置32个字！');
        }
        //
        DB::table('project_lists')->where('id', $item['id'])->update([
            'title' => $title
        ]);
        DB::table('project_log')->insert([
            'type' => '日志',
            'projectid' => $item['id'],
            'username' => $user['username'],
            'detail' => '【' . $item['title'] . '】重命名【' . $title . '】',
            'indate' => Base::time()
        ]);
        //
        return Base::retSuccess('修改成功');
    }

    /**
     * 移交项目
     *
     * @apiParam {Number} projectid     项目ID
     * @apiParam {String} username      项目新负责人用户名
     *
     * @throws \Throwable
     */
    public function transfer()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = trim(Request::input('projectid'));
        $item = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->first());
        if (empty($item)) {
            return Base::retError('项目不存在或已被删除！');
        }
        if ($item['username'] != $user['username']) {
            return Base::retError('你不是项目负责人！');
        }
        //
        $username = trim(Request::input('username'));
        if ($username == $item['username']) {
            return Base::retError('你已是项目负责人！');
        }
        $count = DB::table('users')->where('username', $username)->count();
        if ($count <= 0) {
            return Base::retError('成员用户名(' . $username . ')不存在！');
        }
        //判断是否已在项目
        $count = DB::table('project_users')->where([
            'type' => '成员',
            'projectid' => $item['id'],
            'username' => $username,
        ])->count();
        if ($count <= 0) {
            DB::table('project_users')->insert([
                'type' => '成员',
                'projectid' => $item['id'],
                'isowner' => 0,
                'username' => $username,
                'indate' => Base::time()
            ]);
            DB::table('project_log')->insert([
                'type' => '日志',
                'projectid' => $item['id'],
                'username' => $username,
                'detail' => '移交项目，自动加入项目',
                'indate' => Base::time()
            ]);
        }
        //开始移交
        return DB::transaction(function () use ($user, $username, $item) {
            DB::table('project_lists')->where('id', $item['id'])->update([
                'username' => $username
            ]);
            DB::table('project_log')->insert([
                'type' => '日志',
                'projectid' => $item['id'],
                'username' => $user['username'],
                'detail' => '【' . $item['username'] . '】移交给【' . $username . '】',
                'indate' => Base::time()
            ]);
            DB::table('project_users')->where([
                'projectid' => $item['id'],
                'username' => $item['username'],
            ])->update([
                'isowner' => 0
            ]);
            DB::table('project_users')->where([
                'projectid' => $item['id'],
                'username' => $username,
            ])->update([
                'isowner' => 1
            ]);
            return Base::retSuccess('移交成功');
        });
    }

    /**
     * 删除项目
     *
     * @apiParam {Number} projectid     项目ID
     */
    public function delete()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = trim(Request::input('projectid'));
        $item = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->first());
        if (empty($item)) {
            return Base::retError('项目不存在或已被删除！');
        }
        if ($item['username'] != $user['username']) {
            return Base::retError('你不是项目负责人！');
        }
        //
        DB::table('project_lists')->where('id', $item['id'])->update([
            'delete' => 1,
            'deletedate' => Base::time()
        ]);
        DB::table('project_log')->insert([
            'type' => '日志',
            'projectid' => $item['id'],
            'username' => $user['username'],
            'detail' => '删除项目',
            'indate' => Base::time()
        ]);
        //
        return Base::retSuccess('删除成功');
    }

    /**
     * 退出项目
     *
     * @apiParam {Number} projectid     项目ID
     */
    public function out()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = trim(Request::input('projectid'));
        $item = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->first());
        if (empty($item)) {
            return Base::retError('项目不存在或已被删除！');
        }
        if ($item['username'] == $user['username']) {
            return Base::retError('你是项目负责人，不可退出项目！');
        }
        $count = DB::table('project_users')->where([
            'type' => '成员',
            'projectid' => $projectid,
            'username' => $user['username'],
        ])->count();
        if ($count <= 0) {
            return Base::retError('你不在项目成员内！');
        }
        //
        DB::table('project_users')->where([
            'type' => '成员',
            'projectid' => $item['id'],
            'username' => $user['username'],
        ])->delete();
        DB::table('project_log')->insert([
            'type' => '日志',
            'projectid' => $item['id'],
            'username' => $user['username'],
            'detail' => '退出项目',
            'indate' => Base::time()
        ]);
        //
        return Base::retSuccess('退出项目成功');
    }

    /**
     * 项目成员-列表
     *
     * @apiParam {Number} projectid             项目ID
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:20，最大:100
     */
    public function users__lists()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = intval(Request::input('projectid'));
        $count = DB::table('project_users')->where([
            'type' => '成员',
            'projectid' => $projectid,
            'username' => $user['username'],
        ])->count();
        if ($count <= 0) {
            return Base::retError('你不在项目成员内！');
        }
        //
        $lists = DB::table('project_lists')
            ->join('project_users', 'project_lists.id', '=', 'project_users.projectid')
            ->select(['project_lists.title', 'project_users.*'])
            ->where([
                ['project_lists.id', $projectid],
                ['project_lists.delete', 0],
                ['project_users.type', '成员'],
            ])
            ->orderByDesc('project_lists.id')->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的成员');
        }
        foreach ($lists['lists'] AS $key => $item) {
            $userInfo = Users::username2basic($item['username']);
            $lists['lists'][$key]['userimg'] = $userInfo['userimg'];
            $lists['lists'][$key]['nickname'] = $userInfo['nickname'];
            $lists['lists'][$key]['profession'] = $userInfo['profession'];
        }
        return Base::retSuccess('success', $lists);
    }

    /**
     * 项目成员-添加、删除
     *
     * @apiParam {String} act
     * - delete: 删除成员
     * - else: 添加成员
     * @apiParam {Number} projectid             项目ID
     * @apiParam {Array|String} username        用户名（或用户名组）
     */
    public function users__join()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = trim(Request::input('projectid'));
        $item = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->first());
        if (empty($item)) {
            return Base::retError('项目不存在或已被删除！');
        }
        if ($item['username'] != $user['username']) {
            return Base::retError('你是不是项目负责人！');
        }
        $usernames = Request::input('username');
        if (empty($usernames)) {
            return Base::retError('参数错误！');
        }
        if (!is_array($usernames)) {
            if (Base::strExists($usernames, ',')) {
                $usernames = explode(',', $usernames);
            } else {
                $usernames = [$usernames];
            }
        }
        //
        $logArray = [];
        foreach ($usernames AS $username) {
            $count = DB::table('project_users')->where([
                'type' => '成员',
                'projectid' => $projectid,
                'username' => $username,
            ])->count();
            switch (Request::input('act')) {
                case 'delete': {
                    if ($count > 0 && $item['username'] != $username) {
                        DB::table('project_users')->where([
                            'type' => '成员',
                            'projectid' => $projectid,
                            'username' => $username,
                        ])->delete();
                        $logArray[] = [
                            'type' => '日志',
                            'projectid' => $item['id'],
                            'username' => $user['username'],
                            'detail' => '将成员【' . $username . '】移出项目',
                            'indate' => Base::time()
                        ];
                    }
                    break;
                }
                default: {
                    if ($count == 0) {
                        DB::table('project_users')->insert([
                            'type' => '成员',
                            'projectid' => $projectid,
                            'isowner' => 0,
                            'username' => $username,
                            'indate' => Base::time()
                        ]);
                        $logArray[] = [
                            'type' => '日志',
                            'projectid' => $item['id'],
                            'username' => $username,
                            'detail' => '将成员【' . $username . '】加入项目',
                            'indate' => Base::time()
                        ];
                    }
                    break;
                }
            }
        }
        return Base::retSuccess('操作完成');
    }

    /**
     * 项目任务-列表
     *
     * @apiParam {Number} projectid             项目ID
     * @apiParam {Number} [archived]            是否归档
     * - 0: 未归档
     * - 1: 已归档
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:20，最大:100
     */
    public function task__lists()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = intval(Request::input('projectid'));
        $count = DB::table('project_users')->where([
            'type' => '成员',
            'projectid' => $projectid,
            'username' => $user['username'],
        ])->count();
        if ($count <= 0) {
            return Base::retError('你不在项目成员内！');
        }
        //
        $whereArray = [];
        $whereArray[] = ['project_lists.id', '=', $projectid];
        $whereArray[] = ['project_lists.delete', '=', 0];
        if (in_array(intval(Request::input('archived')), [0, 1])) {
            $whereArray[] = ['project_task.archived', '=', Request::input('archived')];
        }
        //
        $orderBy = 'project_task.id';
        if (intval(Request::input('archived')) === 1) {
            $orderBy = 'project_task.archiveddate';
        }
        //
        $lists = DB::table('project_lists')
            ->join('project_task', 'project_lists.id', '=', 'project_task.projectid')
            ->select(['project_task.*'])
            ->where($whereArray)
            ->orderByDesc($orderBy)->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的任务');
        }
        return Base::retSuccess('success', $lists);
    }

    /**
     * 项目任务-归档、取消归档
     *
     * @apiParam {String} act
     * - cancel: 取消归档
     * - else: 加入归档
     * @apiParam {Number} taskid             任务ID
     */
    public function task__archived()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $taskid = intval(Request::input('taskid'));
        $task = Base::DBC2A(DB::table('project_lists')
            ->join('project_task', 'project_lists.id', '=', 'project_task.projectid')
            ->select(['project_task.projectid', 'project_task.title', 'project_task.archived'])
            ->where([
                ['project_lists.delete', '=', 0],
                ['project_task.id', '=', $taskid],
            ])
            ->first());
        if (empty($task)) {
            return Base::retError('任务不存在！');
        }
        $count = DB::table('project_users')->where([
            'type' => '成员',
            'projectid' => $task['projectid'],
            'username' => $user['username'],
        ])->count();
        if ($count <= 0) {
            return Base::retError('你不在项目成员内！');
        }
        //
        switch (Request::input('act')) {
            case 'cancel': {
                if ($task['archived'] == 0) {
                    return Base::retError('任务未归档！');
                }
                DB::table('project_task')->where('id', $taskid)->update([
                    'archived' => 1,
                    'archiveddate' => Base::time()
                ]);
                DB::table('project_log')->insert([
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $taskid,
                    'username' => $user['username'],
                    'detail' => '取消归档【' . $task['title'] . '】',
                    'indate' => Base::time()
                ]);
                return Base::retSuccess('取消归档成功');
            }
            default: {
                if ($task['archived'] == 1) {
                    return Base::retError('任务已归档！');
                }
                DB::table('project_task')->where('id', $taskid)->update([
                    'archived' => 0,
                ]);
                DB::table('project_log')->insert([
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $taskid,
                    'username' => $user['username'],
                    'detail' => '归档【' . $task['title'] . '】',
                    'indate' => Base::time()
                ]);
                return Base::retSuccess('加入归档成功');
            }
        }
    }
}
