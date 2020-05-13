<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Module\Base;
use App\Module\Project;
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
     * @apiParam {String} act           类型
     * - join: 加入的项目（默认）
     * - favor: 收藏的项目
     * - manage: 管理的项目
     * @apiParam {Number} [page]        当前页，默认:1
     * @apiParam {Number} [pagesize]    每页显示数量，默认:20，最大:100
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
        $whereArray = [];
        $whereArray[] = ['project_lists.delete', '=', 0];
        $whereArray[] = ['project_users.username', '=', $user['username']];
        switch (Request::input('act')) {
            case "favor": {
                $whereArray[] = ['project_users.type', '=', '收藏'];
                break;
            }
            case "manage": {
                $whereArray[] = ['project_users.type', '=', '成员'];
                $whereArray[] = ['project_users.isowner', '=', 1];
                break;
            }
            default: {
                $whereArray[] = ['project_users.type', '=', '成员'];
                break;
            }
        }
        $lists = DB::table('project_lists')
            ->join('project_users', 'project_lists.id', '=', 'project_users.projectid')
            ->select(['project_lists.*', 'project_users.isowner', 'project_users.indate as uindate'])
            ->where($whereArray)
            ->orderByDesc('project_lists.id')->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的项目');
        }
        return Base::retSuccess('success', $lists);
    }

    /**
     * 项目详情
     *
     * @apiParam {Number} projectid     项目ID
     */
    public function detail()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = trim(Request::input('projectid'));
        $projectDetail = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->where('delete', 0)->first());
        if (empty($projectDetail)) {
            return Base::retError('项目不存在或已被删除！');
        }
        $inRes = Project::inThe($projectid, $user['username']);
        if (Base::isError($inRes)) {
            return $inRes;
        }
        //子分类
        $label = Base::DBC2A(DB::table('project_label')->where('projectid', $projectid)->orderBy('inorder')->orderBy('id')->get());
        $simpleLabel = [];
        //任务
        $task = Base::DBC2A(DB::table('project_task')->where([ 'projectid' => $projectid, 'delete' => 0, 'complete' => 0 ])->orderByDesc('inorder')->orderByDesc('id')->get());
        //任务归类
        foreach ($label AS $index => $temp) {
            $taskLists = [];
            foreach ($task AS $info) {
                if ($temp['id'] == $info['labelid']) {
                    $info['overdue'] = Project::taskIsOverdue($info);
                    $taskLists[] = array_merge($info, Users::username2basic($info['username']));
                }
            }
            $label[$index]['taskLists'] = $taskLists;
            $simpleLabel[] = ['id' => $temp['id'], 'title' => $temp['title']];
        }
        //
        return Base::retSuccess('success', [
            'project' => $projectDetail,
            'label' => $label,
            'simpleLabel' => $simpleLabel,
        ]);
    }

    /**
     * 添加项目
     *
     * @apiParam {String} title         项目名称
     * @apiParam {Array} labels         流程，格式[流程1, 流程2]
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
            foreach ($insertLabels AS $key => $label) {
                $insertLabels[$key]['projectid'] = $projectid;
            }
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
     * @apiParam {String} act           类型
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
        $projectDetail = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->where('delete', 0)->first());
        if (empty($projectDetail)) {
            return Base::retError('项目不存在或已被删除！');
        }
        return DB::transaction(function () use ($projectDetail, $user) {
            switch (Request::input('act')) {
                case 'cancel': {
                    if (DB::table('project_users')->where([
                        'type' => '收藏',
                        'projectid' => $projectDetail['id'],
                        'username' => $user['username'],
                    ])->delete()) {
                        DB::table('project_log')->insert([
                            'type' => '日志',
                            'projectid' => $projectDetail['id'],
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
                        'projectid' => $projectDetail['id'],
                        'username' => $user['username'],
                    ])->lockForUpdate()->first());
                    if (empty($row)) {
                        DB::table('project_users')->insert([
                            'type' => '收藏',
                            'projectid' => $projectDetail['id'],
                            'isowner' => $projectDetail['username'] == $user['username'] ? 1 : 0,
                            'username' => $user['username'],
                            'indate' => Base::time()
                        ]);
                        DB::table('project_log')->insert([
                            'type' => '日志',
                            'projectid' => $projectDetail['id'],
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
        $projectDetail = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->where('delete', 0)->first());
        if (empty($projectDetail)) {
            return Base::retError('项目不存在或已被删除！');
        }
        if ($projectDetail['username'] != $user['username']) {
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
        DB::table('project_lists')->where('id', $projectDetail['id'])->update([
            'title' => $title
        ]);
        DB::table('project_log')->insert([
            'type' => '日志',
            'projectid' => $projectDetail['id'],
            'username' => $user['username'],
            'detail' => '【' . $projectDetail['title'] . '】重命名【' . $title . '】',
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
        $projectDetail = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->where('delete', 0)->first());
        if (empty($projectDetail)) {
            return Base::retError('项目不存在或已被删除！');
        }
        if ($projectDetail['username'] != $user['username']) {
            return Base::retError('你不是项目负责人！');
        }
        //
        $username = trim(Request::input('username'));
        if ($username == $projectDetail['username']) {
            return Base::retError('你已是项目负责人！');
        }
        $count = DB::table('users')->where('username', $username)->count();
        if ($count <= 0) {
            return Base::retError('成员用户名(' . $username . ')不存在！');
        }
        //判断是否已在项目成员内
        $inRes = Project::inThe($projectDetail['id'], $username);
        if (Base::isError($inRes)) {
            DB::table('project_users')->insert([
                'type' => '成员',
                'projectid' => $projectDetail['id'],
                'isowner' => 0,
                'username' => $username,
                'indate' => Base::time()
            ]);
            DB::table('project_log')->insert([
                'type' => '日志',
                'projectid' => $projectDetail['id'],
                'username' => $username,
                'detail' => '移交项目，自动加入项目',
                'indate' => Base::time()
            ]);
        }
        //开始移交
        return DB::transaction(function () use ($user, $username, $projectDetail) {
            DB::table('project_lists')->where('id', $projectDetail['id'])->update([
                'username' => $username
            ]);
            DB::table('project_log')->insert([
                'type' => '日志',
                'projectid' => $projectDetail['id'],
                'username' => $user['username'],
                'detail' => '【' . $projectDetail['username'] . '】移交给【' . $username . '】',
                'indate' => Base::time()
            ]);
            DB::table('project_users')->where([
                'projectid' => $projectDetail['id'],
                'username' => $projectDetail['username'],
            ])->update([
                'isowner' => 0
            ]);
            DB::table('project_users')->where([
                'projectid' => $projectDetail['id'],
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
        $projectDetail = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->where('delete', 0)->first());
        if (empty($projectDetail)) {
            return Base::retError('项目不存在或已被删除！');
        }
        if ($projectDetail['username'] != $user['username']) {
            return Base::retError('你不是项目负责人！');
        }
        //
        DB::table('project_lists')->where('id', $projectDetail['id'])->update([
            'delete' => 1,
            'deletedate' => Base::time()
        ]);
        DB::table('project_log')->insert([
            'type' => '日志',
            'projectid' => $projectDetail['id'],
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
        $projectDetail = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->where('delete', 0)->first());
        if (empty($projectDetail)) {
            return Base::retError('项目不存在或已被删除！');
        }
        if ($projectDetail['username'] == $user['username']) {
            return Base::retError('你是项目负责人，不可退出项目！');
        }
        $inRes = Project::inThe($projectid, $user['username']);
        if (Base::isError($inRes)) {
            return $inRes;
        }
        //
        DB::table('project_users')->where([
            'type' => '成员',
            'projectid' => $projectDetail['id'],
            'username' => $user['username'],
        ])->delete();
        DB::table('project_log')->insert([
            'type' => '日志',
            'projectid' => $projectDetail['id'],
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
     * @apiParam {Number} projectid     项目ID
     * @apiParam {Number} [page]        当前页，默认:1
     * @apiParam {Number} [pagesize]    每页显示数量，默认:20，最大:100
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
        $inRes = Project::inThe($projectid, $user['username']);
        if (Base::isError($inRes)) {
            return $inRes;
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
        foreach ($lists['lists'] AS $key => $projectDetail) {
            $userInfo = Users::username2basic($projectDetail['username']);
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
        $projectDetail = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->where('delete', 0)->first());
        if (empty($projectDetail)) {
            return Base::retError('项目不存在或已被删除！');
        }
        if ($projectDetail['username'] != $user['username']) {
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
            $inRes = Project::inThe($projectid, $username);
            switch (Request::input('act')) {
                case 'delete': {
                    if (!Base::isError($inRes) && $projectDetail['username'] != $username) {
                        DB::table('project_users')->where([
                            'type' => '成员',
                            'projectid' => $projectid,
                            'username' => $username,
                        ])->delete();
                        $logArray[] = [
                            'type' => '日志',
                            'projectid' => $projectDetail['id'],
                            'username' => $user['username'],
                            'detail' => '将成员【' . $username . '】移出项目',
                            'indate' => Base::time()
                        ];
                    }
                    break;
                }
                default: {
                    if (Base::isError($inRes)) {
                        DB::table('project_users')->insert([
                            'type' => '成员',
                            'projectid' => $projectid,
                            'isowner' => 0,
                            'username' => $username,
                            'indate' => Base::time()
                        ]);
                        $logArray[] = [
                            'type' => '日志',
                            'projectid' => $projectDetail['id'],
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
     * 项目子分类-添加分类
     *
     * @apiParam {Number} projectid             项目ID
     * @apiParam {String} title                 分类名称
     */
    public function label__add()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = trim(Request::input('projectid'));
        $inRes = Project::inThe($projectid, $user['username']);
        if (Base::isError($inRes)) {
            return $inRes;
        }
        //
        $title = trim(Request::input('title'));
        if (empty($title)) {
            return Base::retError('列表名称不能为空！');
        } elseif (mb_strlen($title) > 32) {
            return Base::retError('列表名称最多只能设置32个字！');
        }
        //
        $count = DB::table('project_label')->where('projectid', $projectid)->where('title', $title)->count();
        if ($count > 0) {
            return Base::retError('列表名称已存在！');
        }
        //
        $id = DB::table('project_label')->insertGetId([
            'projectid' => $projectid,
            'title' => $title,
            'inorder' => intval(DB::table('project_label')->where('projectid', $projectid)->orderByDesc('inorder')->value('inorder')) + 1,
        ]);
        if (empty($id)) {
            return Base::retError('系统繁忙，请稍后再试！');
        }
        DB::table('project_log')->insert([
            'type' => '日志',
            'projectid' => $projectid,
            'username' => $user['username'],
            'detail' => '添加任务列表【' . $title . '】',
            'indate' => Base::time()
        ]);
        //
        $row = Base::DBC2A(DB::table('project_label')->where('id', $id)->first());
        $row['taskLists'] = [];
        return Base::retSuccess('添加成功', $row);
    }

    /**
     * 项目子分类-重命名分类
     *
     * @apiParam {Number} projectid             项目ID
     * @apiParam {Number} labelid               分类ID
     * @apiParam {String} title                 新分类名称
     */
    public function label__rename()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = trim(Request::input('projectid'));
        $inRes = Project::inThe($projectid, $user['username']);
        if (Base::isError($inRes)) {
            return $inRes;
        }
        //
        $title = trim(Request::input('title'));
        if (empty($title)) {
            return Base::retError('列表名称不能为空！');
        } elseif (mb_strlen($title) > 32) {
            return Base::retError('列表名称最多只能设置32个字！');
        }
        //
        $labelid = intval(Request::input('labelid'));
        $count = DB::table('project_label')->where('id', '!=', $labelid)->where('projectid', $projectid)->where('title', $title)->count();
        if ($count > 0) {
            return Base::retError('列表名称已存在！');
        }
        //
        $labelDetail = Base::DBC2A(DB::table('project_label')->where('id', $labelid)->where('projectid', $projectid)->first());
        if (empty($labelDetail)) {
            return Base::retError('列表不存在或已被删除！');
        }
        //
        if (DB::table('project_label')->where('id', $labelDetail['id'])->update([ 'title' => $title ])) {
            DB::table('project_log')->insert([
                'type' => '日志',
                'projectid' => $projectid,
                'username' => $user['username'],
                'detail' => '任务列表【' . $labelDetail['title'] . '】重命名【' . $title . '】',
                'indate' => Base::time()
            ]);
        }
        //
        return Base::retSuccess('修改成功');
    }

    /**
     * 项目子分类-删除分类
     *
     * @apiParam {Number} projectid             项目ID
     * @apiParam {Number} labelid               分类ID
     *
     * @throws \Throwable
     */
    public function label__delete()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = trim(Request::input('projectid'));
        $inRes = Project::inThe($projectid, $user['username']);
        if (Base::isError($inRes)) {
            return $inRes;
        }
        //
        $labelid = intval(Request::input('labelid'));
        $labelDetail = Base::DBC2A(DB::table('project_label')->where('id', $labelid)->where('projectid', $projectid)->first());
        if (empty($labelDetail)) {
            return Base::retError('列表不存在或已被删除！');
        }
        //
        return DB::transaction(function () use ($user, $projectid, $labelDetail) {
            $taskLists = Base::DBC2A(DB::table('project_task')->where('labelid', $labelDetail['id'])->get());
            $logArray = [];
            foreach ($taskLists AS $task) {
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $projectid,
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '删除列表任务【' . $task['title'] . '】',
                    'indate' => Base::time()
                ];
            }
            $logArray[] = [
                'type' => '日志',
                'projectid' => $projectid,
                'taskid' => 0,
                'username' => $user['username'],
                'detail' => '删除任务列表【' . $labelDetail['title'] . '】',
                'indate' => Base::time()
            ];
            DB::table('project_task')->where('labelid', $labelDetail['id'])->update([
                'delete' => 1,
                'deletedate' => Base::time()
            ]);
            DB::table('project_label')->where('id', $labelDetail['id'])->delete();
            DB::table('project_log')->insert($logArray);
            //
            return Base::retSuccess('删除成功');
        });
    }

    /**
     * 项目任务-列表
     *
     * @apiParam {Number} projectid             项目ID
     * @apiParam {Number} [labelid]             项目子分类ID
     * @apiParam {String} [archived]            是否归档
     * - 未归档 （默认）
     * - 已归档
     * - 全部
     * @apiParam {String} [type]                任务类型
     * - 全部（默认）
     * - 未完成
     * - 已超期
     * - 已完成
     * @apiParam {String} [username]            负责人用户名
     * @apiParam {Number} [statistics]          是否获取统计数据（1:获取）
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
        $inRes = Project::inThe($projectid, $user['username']);
        if (Base::isError($inRes)) {
            return $inRes;
        }
        //
        $orderBy = '`inorder` DESC,`id` DESC';
        $whereArray = [];
        $whereArray[] = ['project_lists.id', '=', $projectid];
        $whereArray[] = ['project_lists.delete', '=', 0];
        $whereArray[] = ['project_task.delete', '=', 0];
        if (intval(Request::input('labelid')) > 0) {
            $whereArray[] = ['project_task.labelid', '=', intval(Request::input('labelid'))];
        }
        if (intval(Request::input('level')) > 0) {
            $whereArray[] = ['project_task.level', '=', intval(Request::input('level'))];
        }
        if (trim(Request::input('username'))) {
            $whereArray[] = ['project_task.username', '=', trim(Request::input('username'))];
        }
        $archived = trim(Request::input('archived'));
        if (empty($archived)) $archived = "未归档";
        switch ($archived) {
            case '已归档':
                $whereArray[] = ['project_task.archived', '=', 1];
                $orderBy = '`archiveddate` DESC';
                break;
            case '未归档':
                $whereArray[] = ['project_task.archived', '=', 0];
                break;
        }
        $type = trim(Request::input('type'));
        switch ($type) {
            case '未完成':
                $whereArray[] = ['project_task.complete', '=', 0];
                break;
            case '已超期':
                $whereArray[] = ['project_task.complete', '=', 0];
                $whereArray[] = ['project_task.enddate', '>', 0];
                $whereArray[] = ['project_task.enddate', '<=', Base::time()];
                break;
            case '已完成':
                $whereArray[] = ['project_task.complete', '=', 1];
                break;
        }
        //
        $lists = DB::table('project_lists')
            ->join('project_task', 'project_lists.id', '=', 'project_task.projectid')
            ->select(['project_task.*'])
            ->where($whereArray)
            ->orderByRaw($orderBy)->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if (intval(Request::input('statistics')) == 1) {
            $lists['statistics_unfinished'] = $type === '未完成' ? $lists['total'] : DB::table('project_task')->where('projectid', $projectid)->where('delete', 0)->where('complete', 0)->count();
            $lists['statistics_overdue'] = $type === '已超期' ? $lists['total'] : DB::table('project_task')->where('projectid', $projectid)->where('delete', 0)->where('complete', 0)->whereBetween('enddate', [1, Base::time()])->count();
            $lists['statistics_complete'] = $type === '已完成' ? $lists['total'] : DB::table('project_task')->where('projectid', $projectid)->where('delete', 0)->where('complete', 1)->count();
        }
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的任务', $lists);
        }
        foreach ($lists['lists'] AS $key => $info) {
            $info['overdue'] = Project::taskIsOverdue($info);
            $lists['lists'][$key] = array_merge($info, Users::username2basic($info['username']));
        }
        return Base::retSuccess('success', $lists);
    }

    /**
     * 项目任务-添加任务
     *
     * @apiParam {Number} projectid             项目ID
     * @apiParam {Number} labelid               项目子分类ID
     * @apiParam {String} title                 任务标题
     * @apiParam {Number} [level]               任务紧急级别（1~4，默认:2）
     * @apiParam {String} [username]            任务负责人用户名
     * - 0: 未归档
     * - 1: 已归档
     *
     * @throws \Throwable
     */
    public function task__add()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = intval(Request::input('projectid'));
        $projectDetail = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->where('delete', 0)->first());
        if (empty($projectDetail)) {
            return Base::retError('项目不存在或已被删除！');
        }
        //
        $labelid = intval(Request::input('labelid'));
        $labelDetail = Base::DBC2A(DB::table('project_label')->where('id', $labelid)->where('projectid', $projectid)->first());
        if (empty($labelDetail)) {
            return Base::retError('项目子分类不存在或已被删除！');
        }
        //
        $inRes = Project::inThe($projectid, $user['username']);
        if (Base::isError($inRes)) {
            return $inRes;
        }
        //
        $username = trim(Request::input('username'));
        if (empty($username)) {
            $username = $user['username'];
        }
        if ($username != $user['username']) {
            $inRes = Project::inThe($projectid, $username);
            if (Base::isError($inRes)) {
                return Base::retError('负责人不在项目成员内！');
            }
        }
        //
        $title = trim(Request::input('title'));
        if (empty($title)) {
            return Base::retError('任务标题不能为空！');
        } elseif (mb_strlen($title) > 255) {
            return Base::retError('任务标题最多只能设置255个字！');
        }
        //
        $inArray = [
            'projectid' => $projectid,
            'labelid' => $labelid,
            'createuser' => $user['username'],
            'username' => $username,
            'title' => $title,
            'level' => max(1, min(4, intval(Request::input('level')))),
            'indate' => Base::time(),
            'subtask' => Base::array2string([]),
            'files' => Base::array2string([]),
            'follower' => Base::array2string([]),
        ];
        return DB::transaction(function () use ($inArray) {
            $taskid = DB::table('project_task')->insertGetId($inArray);
            if (empty($taskid)) {
                return Base::retError('系统繁忙，请稍后再试！');
            }
            DB::table('project_log')->insert([
                'type' => '日志',
                'projectid' => $inArray['projectid'],
                'taskid' => $taskid,
                'username' => $inArray['createuser'],
                'detail' => '添加任务【' . $inArray['title'] . '】',
                'indate' => Base::time()
            ]);
            Project::updateNum($inArray['projectid']);
            //
            $task = Base::DBC2A(DB::table('project_task')->where('id', $taskid)->first());
            $task['overdue'] = Project::taskIsOverdue($task);
            $task = array_merge($task, Users::username2basic($task['username']));
            return Base::retSuccess('添加成功！', $task);
        });
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
                ['project_task.delete', '=', 0],
                ['project_task.id', '=', $taskid],
            ])
            ->first());
        if (empty($task)) {
            return Base::retError('任务不存在！');
        }
        $inRes = Project::inThe($task['projectid'], $user['username']);
        if (Base::isError($inRes)) {
            return $inRes;
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
