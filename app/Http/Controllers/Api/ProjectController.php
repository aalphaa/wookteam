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
                    $info['subtask'] = Base::string2array($info['subtask']);
                    $info['follower'] = Base::string2array($info['follower']);
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
     * 获取项目负责人
     *
     * @apiParam {Number} projectid     项目ID
     */
    public function leader()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = trim(Request::input('projectid'));
        $projectDetail = Base::DBC2A(DB::table('project_lists')->select(['username'])->where('id', $projectid)->where('delete', 0)->first());
        if (empty($projectDetail)) {
            return Base::retError('项目不存在或已被删除！');
        }
        return Base::retSuccess('success', [
            'username' => $projectDetail['username'],
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
                        return Base::retSuccess('取消成功！');
                    }
                    return Base::retSuccess('已取消！');
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
                        return Base::retSuccess('收藏成功！');
                    }
                    return Base::retSuccess('已收藏！');
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
        return Base::retSuccess('修改成功！');
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
                'detail' => '自动加入项目',
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
            return Base::retSuccess('移交成功！');
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
        return Base::retSuccess('删除成功！');
    }

    /**
     * 排序任务
     *
     * @apiParam {Number} projectid     项目ID
     * @apiParam {String} oldsort       旧排序数据
     * @apiParam {String} newsort       新排序数据
     * @apiParam {Number} label         赋值表示排序分类，否则排序任务(调整任务所属分类)
     */
    public function sort()
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
        $oldSort = explode(";", Request::input('oldsort'));
        $newSort = explode(";", Request::input('newsort'));
        if (count($oldSort) != count($newSort)) {
            return Base::retError('参数错误！');
        }
        if (intval(Request::input('label'))) {
            //排序分类
            foreach ($newSort AS $sort => $item) {
                list($newLabelid, $newTask) = explode(':', $item);
                list($oldLabelid, $oldTask) = explode(':', $oldSort[$sort]);
                if ($newLabelid != $oldLabelid) {
                    DB::table('project_label')->where([
                        'id' => $newLabelid,
                        'projectid' => $projectid
                    ])->update([
                        'inorder' => intval($sort)
                    ]);
                }
            }
            $detail = '调整任务列表排序';
            $sortType = 'label';
        } else {
            //排序任务（调整任务归类）
            foreach ($newSort AS $sort => $item) {
                list($newLabelid, $newTask) = explode(':', $item);
                list($oldLabelid, $oldTask) = explode(':', $oldSort[$sort]);
                if ($newTask != $oldTask) {
                    $newTask = explode('-', $newTask);
                    $inorder = count($newTask);
                    foreach ($newTask AS $taskid) {
                        DB::table('project_task')->where([
                            'id' => $taskid,
                            'projectid' => $projectid
                        ])->update([
                            'labelid' => $newLabelid,
                            'inorder' => $inorder
                        ]);
                        $inorder--;
                    }
                }
            }
            $detail = '调整任务排序';
            $sortType = 'task';
        }
        //
        $row = Base::DBC2A(DB::table('project_log')->where([ 'type' => '日志', 'projectid' => $projectid ])->orderByDesc('id')->first());
        $continue = 1;
        if ($row && $row['username'] == $user['username'] && $row['indate'] + 300 > Base::time()) {
            $other = Base::string2array($row['other']);
            if ($other['sortType'] == $sortType) {
                $continue = intval($other['continue']) + 1;
                if ($continue <= 100) {
                    DB::table('project_log')->where('id', $row['id'])->update([
                        'detail' => $detail . '(' . $continue . '次)',
                        'other' => Base::array2string([
                            'sortType' => $sortType,
                            'continue' => $continue,
                            'times' => $other['times'] . '|' . Base::time(),
                        ])
                    ]);
                }
            }
        }
        if ($continue == 1) {
            DB::table('project_log')->insert([
                'type' => '日志',
                'projectid' => $projectid,
                'username' => $user['username'],
                'detail' => $detail,
                'indate' => Base::time(),
                'other' => Base::array2string([
                    'sortType' => $sortType,
                    'continue' => $continue,
                    'times' => Base::time(),
                ])
            ]);
        }
        return Base::retSuccess('保存成功！');
    }

    /**
     * 排序任务（todo待办）
     *
     * @apiParam {String} oldsort       旧排序数据
     * @apiParam {String} newsort       新排序数据
     */
    public function sort__todo()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $oldSort = explode(";", Request::input('oldsort'));
        $newSort = explode(";", Request::input('newsort'));
        if (count($oldSort) != count($newSort)) {
            return Base::retError('参数错误！');
        }
        //
        $levels = [];
        $logArray = [];
        $taskLevel = [];
        foreach ($newSort AS $sort => $item) {
            list($newLevel, $newTask) = explode(':', $item);
            list($oldLevel, $oldTask) = explode(':', $oldSort[$sort]);
            if ($newTask != $oldTask) {
                $newTask = explode('-', $newTask);
                $oldTask = explode('-', $oldTask);
                $userorder = intval(DB::table('project_task')->select('userorder')->where([
                    'delete' => 0,
                    'archived' => 0,
                    'level' => $newLevel,
                    'username' => $user['username'],
                ])->orderByDesc('userorder')->value('userorder'));
                if (count($newTask) < count($oldTask)) {
                    $userorder--;
                } else {
                    $userorder++;
                }
                foreach ($newTask AS $taskid) {
                    $task = Base::DBC2A(DB::table('project_task')->select(['id', 'title', 'projectid', 'level', 'userorder'])->where([
                        'id' => $taskid,
                        'username' => $user['username']
                    ])->first());
                    $upArray = [];
                    if ($task) {
                        if ($task['level'] != $newLevel) {
                            $upArray['level'] = $newLevel;
                            $logArray[] = [
                                'type' => '日志',
                                'projectid' => $task['projectid'],
                                'taskid' => $task['id'],
                                'username' => $user['username'],
                                'detail' => '调整任务等级为【P' . $newLevel . '】',
                                'indate' => Base::time(),
                                'other' => Base::array2string([
                                    'type' => 'task',
                                    'id' => $task['id'],
                                    'title' => $task['title'],
                                ])
                            ];
                            $taskLevel[] = [
                                'id' => $task['id'],
                                'level' => $newLevel,
                            ];
                        }
                        if ($task['userorder'] != $userorder) {
                            $upArray['userorder'] = $userorder;
                        }
                    }
                    if ($upArray) {
                        DB::table('project_task')->where('id', $taskid)->update($upArray);
                    }
                    $userorder--;
                }
                $levels[] = $newLevel;
            }
        }
        if ($logArray) {
            DB::table('project_log')->insert($logArray);
        }
        //
        return Base::retSuccess('保存成功！', [
            'levels' => $levels,
            'taskLevel' => $taskLevel,
        ]);
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
        return Base::retSuccess('退出项目成功！');
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
            ->orderByDesc('project_users.isowner')->orderByDesc('project_users.id')->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
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
        return Base::retSuccess('操作完成！');
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
        return Base::retSuccess('添加成功！', $row);
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
        return Base::retSuccess('修改成功！');
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
                    'detail' => '删除列表任务',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                    ])
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
            return Base::retSuccess('删除成功！');
        });
    }

    /**
     * 项目任务-列表
     *
     * @apiParam {Number} [projectid]           项目ID
     * @apiParam {Number} [taskid]              任务ID (1、填写此参数时projectid强制为此任务的projectid；2、赋值返回详细数据，不返回列表数据)
     * @apiParam {Number} [labelid]             项目子分类ID
     * @apiParam {String} [username]            负责人用户名（如果项目ID为空时此参数无效只获取自己的任务）
     * @apiParam {Number} [level]               任务等级（1~4）
     * @apiParam {String} [archived]            任务是否归档
     * - 未归档 （默认）
     * - 已归档
     * - 全部
     * @apiParam {String} [type]                任务类型
     * - 全部（默认）
     * - 未完成
     * - 已超期
     * - 已完成
     * @apiParam {Number} [createuser]          是否仅获取自己创建的项目（1:是；赋值时projectid和username不强制）
     * @apiParam {Number} [attention]           是否仅获取关注数据（1:是）
     * @apiParam {Number} [statistics]          是否获取统计数据（1:获取）
     * @apiParam {String} [startdate]           任务开始时间，格式：YYYY-MM-DD
     * @apiParam {String} [enddate]             任务结束时间，格式：YYYY-MM-DD
     *
     * @apiParam {Object} [sorts]               排序方式，格式：{key:'', order:''}
     * - key: title|labelid|enddate|username|level|indate|type|inorder(默认)|userorder
     * - order: asc|desc
     * - 【archived=已归档】或【startdate和enddate赋值】时无效
     *
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
        $taskid = intval(Request::input('taskid'));
        if ($taskid > 0) {
            $projectid = intval(DB::table('project_task')->where('id', $taskid)->value('projectid'));
        } else {
            $projectid = intval(Request::input('projectid'));
        }
        //
        if ($projectid > 0) {
            $inRes = Project::inThe($projectid, $user['username']);
            if (Base::isError($inRes)) {
                return $inRes;
            }
        }
        //
        $orderBy = '`inorder` DESC,`id` DESC';
        $sorts = Base::json2array(Request::input('sorts'));
        if (in_array($sorts['order'], ['asc', 'desc'])) {
            switch ($sorts['key']) {
                case 'title':
                case 'labelid':
                case 'enddate':
                case 'username':
                case 'level':
                case 'indate':
                case 'inorder':
                case 'userorder':
                    $orderBy = '`' . $sorts['key'] . '` ' . $sorts['order'] . ',`id` DESC';
                    break;
                case 'type':
                    $orderBy = 'CASE WHEN `complete`= 0 AND `enddate` BETWEEN 1 AND ' . Base::time() . ' THEN 0 ELSE 1 END ' . $sorts['order'] . ', `complete` ' . $sorts['order'] . ',`id` DESC';
                    break;
            }
        }
        //
        $selectArray = ['project_task.*'];
        $whereRaw = null;
        $whereFunc = null;
        $whereArray = [];
        $whereArray[] = ['project_task.delete', '=', 0];
        if (intval(Request::input('createuser')) === 1) {
            $whereArray[] = ['project_task.createuser', '=', $user['username']];
            if ($projectid > 0) {
                $whereArray[] = ['project_lists.id', '=', $projectid];
                $whereArray[] = ['project_lists.delete', '=', 0];
            }
            if (trim(Request::input('username'))) {
                $whereArray[] = ['project_task.username', '=', trim(Request::input('username'))];
            }
        } else {
            if ($projectid > 0) {
                $whereArray[] = ['project_lists.id', '=', $projectid];
                $whereArray[] = ['project_lists.delete', '=', 0];
                if (trim(Request::input('username'))) {
                    $whereArray[] = ['project_task.username', '=', trim(Request::input('username'))];
                }
            } else {
                $whereArray[] = ['project_task.username', '=', $user['username']];
            }
        }
        if ($taskid > 0) {
            $whereArray[] = ['project_task.id', '=', intval(Request::input('taskid'))];
        }
        if (intval(Request::input('labelid')) > 0) {
            $whereArray[] = ['project_task.labelid', '=', intval(Request::input('labelid'))];
        }
        if (intval(Request::input('level')) > 0) {
            $whereArray[] = ['project_task.level', '=', intval(Request::input('level'))];
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
        $startdate = trim(Request::input('startdate'));
        $enddate = trim(Request::input('enddate'));
        if (Base::isDate($startdate) || Base::isDate($enddate)) {
            $startdate = strtotime($startdate . ' 00:00:00');
            $enddate = strtotime($enddate . ' 23:59:59');
            $whereRaw.= $whereRaw ? ' AND ' : '';
            $whereRaw.= "((`startdate` >= " . $startdate . " OR `startdate` = 0) AND (`enddate` <= " . $enddate . " OR `enddate` = 0))";
            $orderBy = '`startdate` DESC';
        }
        //
        $builder = DB::table('project_task');
        if ($projectid > 0) {
            $builder->join('project_lists', 'project_lists.id', '=', 'project_task.projectid');
        }
        if (intval(Request::input('attention')) === 1) {
            $builder->join('project_users', 'project_users.taskid', '=', 'project_task.id');
            $builder->where([
                ['project_users.type', '=', '关注'],
                ['project_users.username', '=', $user['username']],
            ]);
            $selectArray[] = 'project_users.indate AS attentiondate';
        }
        if ($whereRaw) {
            $builder->whereRaw($whereRaw);
        }
        $lists = $builder->select($selectArray)
            ->where($whereArray)
            ->orderByRaw($orderBy)
            ->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists, $taskid > 0 ? false : true);
        if (intval(Request::input('statistics')) == 1) {
            $lists['statistics_unfinished'] = $type === '未完成' ? $lists['total'] : DB::table('project_task')->where('projectid', $projectid)->where('delete', 0)->where('archived', 0)->where('complete', 0)->count();
            $lists['statistics_overdue'] = $type === '已超期' ? $lists['total'] : DB::table('project_task')->where('projectid', $projectid)->where('delete', 0)->where('archived', 0)->where('complete', 0)->whereBetween('enddate', [1, Base::time()])->count();
            $lists['statistics_complete'] = $type === '已完成' ? $lists['total'] : DB::table('project_task')->where('projectid', $projectid)->where('delete', 0)->where('archived', 0)->where('complete', 1)->count();
        }
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的任务！', $lists);
        }
        foreach ($lists['lists'] AS $key => $info) {
            $info['overdue'] = Project::taskIsOverdue($info);
            $info['subtask'] = Base::string2array($info['subtask']);
            $info['follower'] = Base::string2array($info['follower']);
            $lists['lists'][$key] = array_merge($info, Users::username2basic($info['username']));
        }
        if ($taskid > 0) {
            if (count($lists['lists']) == 0) {
                return Base::retError('未能找到此任务或无法管理此任务！');
            }
            return Base::retSuccess('success', $lists['lists'][0]);
        } else {
            return Base::retSuccess('success', $lists);
        }
    }

    /**
     * 项目任务-添加任务
     *
     * @apiParam {String} title                 任务标题
     * @apiParam {Number} [projectid]           项目ID
     * @apiParam {Number} [labelid]             项目子分类ID
     * @apiParam {Number} [level]               任务紧急级别（1~4，默认:2）
     * @apiParam {String} [username]            任务负责人用户名（如果项目ID为空时此参数无效，负责人为自己）
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
        $labelid = intval(Request::input('labelid'));
        if ($projectid > 0) {
            $projectDetail = Base::DBC2A(DB::table('project_lists')->where('id', $projectid)->where('delete', 0)->first());
            if (empty($projectDetail)) {
                return Base::retError('项目不存在或已被删除！');
            }
            //
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
        } else {
            $username = $user['username'];
        }
        //
        $title = trim(Request::input('title'));
        if (empty($title)) {
            return Base::retError('任务标题不能为空！');
        } elseif (mb_strlen($title) > 255) {
            return Base::retError('任务标题最多只能设置255个字！');
        }
        //
        $level = max(1, min(4, intval(Request::input('level'))));
        $inArray = [
            'projectid' => $projectid,
            'labelid' => $labelid,
            'createuser' => $user['username'],
            'username' => $username,
            'title' => $title,
            'level' => $level,
            'inorder' => empty($projectid) ? 0 : intval(DB::table('project_task')->where('projectid', $projectid)->orderByDesc('inorder')->value('inorder')) + 1,
            'userorder' => intval(DB::table('project_task')->where('username', $user['username'])->where('level', $level)->orderByDesc('userorder')->value('userorder')) + 1,
            'indate' => Base::time(),
            'startdate' => Base::time(),
            'subtask' => Base::array2string([]),
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
                'detail' => '添加任务',
                'indate' => Base::time(),
                'other' => Base::array2string([
                    'type' => 'task',
                    'id' => $taskid,
                    'title' => $inArray['title'],
                ])
            ]);
            Project::updateNum($inArray['projectid']);
            //
            $task = Base::DBC2A(DB::table('project_task')->where('id', $taskid)->first());
            $task['overdue'] = Project::taskIsOverdue($task);
            $task['subtask'] = Base::string2array($task['subtask']);
            $task['follower'] = Base::string2array($task['follower']);
            $task = array_merge($task, Users::username2basic($task['username']));
            return Base::retSuccess('添加成功！', $task);
        });
    }

    /**
     * 项目任务-修改
     *
     * @apiParam {Number} taskid            任务ID
     * @apiParam {String} act               修改字段|操作类型
     * - title: 标题
     * - desc: 描述
     * - level: 优先级
     * - username: 负责人
     * - plannedtime: 设置计划时间
     * - unplannedtime: 取消计划时间
     * - complete: 标记完成
     * - unfinished: 标记未完成
     * - archived: 归档
     * - unarchived: 取消归档
     * - delete: 删除任务
     * - comment: 评论
     * - attention: 添加关注
     * @apiParam {String} [content]         内容数据
     *
     * @throws \Throwable
     */
    public function task__edit()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $taskid = intval(Request::input('taskid'));
        $task = Base::DBC2A(DB::table('project_task')
            ->where([
                ['delete', '=', 0],
                ['id', '=', $taskid],
            ])
            ->first());
        if (empty($task)) {
            return Base::retError('任务不存在！');
        }
        if ($task['projectid'] > 0) {
            $inRes = Project::inThe($task['projectid'], $user['username']);
            if (Base::isError($inRes)) {
                return $inRes;
            }
            if (!$inRes['data']['isowner'] && $task['username'] != $user['username']) {
                return Base::retError('此操作只允许项目管理员或者任务负责人！');
            }
        } else {
            if ($task['username'] != $user['username']) {
                return Base::retError('此操作只允许任务负责人！');
            }
        }
        //
        $act = trim(Request::input('act'));
        $content = trim(Request::input('content'));
        $message = "";
        $upArray = [];
        $logArray = [];
        switch ($act) {
            /**
             * 修改标题
             */
            case 'title': {
                if ($content == $task['title']) {
                    return Base::retError('标题未做改变！');
                }
                $upArray['title'] = $content;
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '修改任务标题',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $content,
                        'old_title' => $task['title'],
                    ])
                ];
                break;
            }

            /**
             * 修改描述
             */
            case 'desc': {
                if ($content == $task['desc']) {
                    return Base::retError('描述未做改变！');
                }
                $upArray['desc'] = $content;
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '修改任务描述',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                        'old_desc' => $task['desc'],
                    ])
                ];
                break;
            }

            /**
             * 调整任务等级
             */
            case 'level': {
                $content = intval($content);
                if ($content == $task['level']) {
                    return Base::retError('优先级未做改变！');
                }
                if ($content > 4 || $content < 1) {
                    return Base::retError('优先级参数错误！');
                }
                $upArray['level'] = $content;
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '调整任务等级为【P' . $content . '】',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                        'old_level' => $task['level'],
                    ])
                ];
                break;
            }

            /**
             * 修改任务负责人
             */
            case 'username': {
                if ($content == $task['username']) {
                    return Base::retError('负责人未做改变！');
                }
                if ($task['projectid'] > 0) {
                    $inRes = Project::inThe($task['projectid'], $content);
                    if (Base::isError($inRes)) {
                        return Base::retError($content . '不在成员列表内！');
                    }
                }
                $upArray['username'] = $content;
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '修改负责人',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                        'old_username' => $task['username'],
                    ])
                ];
                break;
            }

            /**
             * 修改计划时间
             */
            case 'plannedtime': {
                list($startdate, $enddate) = explode(",", $content);
                if (!Base::isDate($startdate) || !Base::isDate($enddate)) {
                    return Base::retError('计划时间参数错误！');
                }
                $startdate = strtotime($startdate);
                $enddate = strtotime($enddate);
                if ($startdate == $task['startdate'] && $enddate == $task['enddate']) {
                    return Base::retError('与原计划时间一致！');
                }
                $upArray['startdate'] = $startdate;
                $upArray['enddate'] = $enddate;
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '设置计划时间',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                        'old_startdate' => $task['startdate'],
                        'old_enddate' => $task['enddate'],
                    ])
                ];
                break;
            }

            /**
             * 取消计划时间
             */
            case 'unplannedtime': {
                $startdate = 0;
                $enddate = 0;
                if ($startdate == $task['startdate'] && $enddate == $task['enddate']) {
                    return Base::retError('与原计划时间一致！');
                }
                $upArray['startdate'] = $startdate;
                $upArray['enddate'] = $enddate;
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '取消计划时间',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                        'old_startdate' => $task['startdate'],
                        'old_enddate' => $task['enddate'],
                    ])
                ];
                break;
            }

            /**
             * 标记完成
             */
            case 'complete': {
                if ($task['complete'] == 1) {
                    return Base::retError('任务已标记完成，请勿重复操作！');
                }
                $upArray['complete'] = 1;
                $upArray['completedate'] = Base::time();
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '标记已完成',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                    ])
                ];
                break;
            }

            /**
             * 标记未完成
             */
            case 'unfinished': {
                if ($task['complete'] == 0) {
                    return Base::retError('任务未完成，无法标记未完成！');
                }
                $upArray['complete'] = 0;
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '标记未完成',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                    ])
                ];
                break;
            }

            /**
             * 归档
             */
            case 'archived': {
                if ($task['archived'] == 1) {
                    return Base::retError('任务已经归档，请勿重复操作！');
                }
                $upArray['archived'] = 1;
                $upArray['archiveddate'] = Base::time();
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '任务归档',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                    ])
                ];
                break;
            }

            /**
             * 取消归档
             */
            case 'unarchived': {
                if ($task['archived'] == 0) {
                    return Base::retError('任务未归档，无法取消归档操作！');
                }
                $upArray['archived'] = 0;
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '取消归档',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                    ])
                ];
                break;
            }

            /**
             * 删除任务
             */
            case 'delete': {
                if ($task['delete'] == 1) {
                    return Base::retError('任务已删除，请勿重复操作！');
                }
                $upArray['delete'] = 1;
                $upArray['deletedate'] = Base::time();
                $logArray[] = [
                    'type' => '日志',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => '删除任务',
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                    ])
                ];
                $message = "删除成功！";
                break;
            }

            /**
             * 评论任务
             */
            case 'comment': {
                if (mb_strlen($content) < 2) {
                    return Base::retError('评论内容至少2个字！');
                }
                $logArray[] = [
                    'type' => '评论',
                    'projectid' => $task['projectid'],
                    'taskid' => $task['id'],
                    'username' => $user['username'],
                    'detail' => $content,
                    'indate' => Base::time(),
                    'other' => Base::array2string([
                        'type' => 'task',
                        'id' => $task['id'],
                        'title' => $task['title'],
                    ])
                ];
                $message = "评论成功！";
                break;
            }

            /**
             * 添加关注
             */
            case 'attention': {
                $userArray  = explode(",", $content);
                DB::transaction(function () use ($user, $task, $userArray) {
                    foreach ($userArray AS $uname) {
                        $uid = Users::username2id($uname);
                        if (empty($uid)) {
                            continue;
                        }
                        $row = Base::DBC2A(DB::table('project_users')->where([
                            'type' => '关注',
                            'taskid' => $task['id'],
                            'username' => $uname,
                        ])->lockForUpdate()->first());
                        if (empty($row)) {
                            DB::table('project_users')->insert([
                                'type' => '关注',
                                'projectid' => $task['projectid'],
                                'taskid' => $task['id'],
                                'isowner' => $task['username'] == $uname ? 1 : 0,
                                'username' => $uname,
                                'indate' => Base::time()
                            ]);
                            DB::table('project_log')->insert([
                                'type' => '日志',
                                'projectid' => $task['projectid'],
                                'taskid' => $task['id'],
                                'username' => $uname,
                                'detail' => $uname == $user['username'] ? '关注任务' : '加入关注',
                                'indate' => Base::time(),
                                'other' => Base::array2string([
                                    'type' => 'task',
                                    'id' => $task['id'],
                                    'title' => $task['title'],
                                    'operator' => $user['username'],
                                ])
                            ]);
                        }
                    }
                });
                $tempRow = Base::DBC2A(DB::table('project_users')->select(['username'])->where([ 'type' => '关注', 'taskid' => $task['id'] ])->get()->pluck('username'));
                $upArray['follower'] = Base::array2string($tempRow);
                $message = "保存成功！";
                break;
            }

            /**
             * 取消关注
             */
            case 'unattention': {
                $userArray  = explode(",", $content);
                DB::transaction(function () use ($user, $task, $userArray) {
                    foreach ($userArray AS $uname) {
                        if (DB::table('project_users')->where([
                            'type' => '关注',
                            'taskid' => $task['id'],
                            'username' => $uname,
                        ])->delete()) {
                            DB::table('project_log')->insert([
                                'type' => '日志',
                                'projectid' => $task['projectid'],
                                'taskid' => $task['id'],
                                'username' => $uname,
                                'detail' => $uname == $user['username'] ? '取消关注' : '移出关注',
                                'indate' => Base::time(),
                                'other' => Base::array2string([
                                    'type' => 'task',
                                    'id' => $task['id'],
                                    'title' => $task['title'],
                                    'operator' => $user['username'],
                                ])
                            ]);
                        }
                    }
                });
                $tempRow = Base::DBC2A(DB::table('project_users')->select(['username'])->where([ 'type' => '关注', 'taskid' => $task['id'] ])->get()->pluck('username'));
                $upArray['follower'] = Base::array2string($tempRow);
                $message = "保存成功！";
                break;
            }

            default: {
                return Base::retError('参数错误！');
                break;
            }
        }
        //
        if ($upArray) {
            DB::table('project_task')->where('id', $taskid)->update($upArray);
        }
        if ($logArray) {
            DB::table('project_log')->insert($logArray);
        }
        //
        if (in_array($act, ['complete', 'unfinished'])) {
            Project::updateNum($task['projectid']);
        }
        //
        $task = array_merge($task, $upArray);
        $task['overdue'] = Project::taskIsOverdue($task);
        $task['subtask'] = Base::string2array($task['subtask']);
        $task['follower'] = Base::string2array($task['follower']);
        $task = array_merge($task, Users::username2basic($task['username']));
        return Base::retSuccess($message ?: '修改成功！', $task);
    }

    /**
     * 项目文件-列表
     *
     * @apiParam {Number} [projectid]           项目ID
     * @apiParam {Number} [taskid]              任务ID（如果项目ID为空时此参必须赋值且任务必须是自己负责人或在任务所在的项目里）
     * @apiParam {String} [name]                文件名称
     * @apiParam {String} [username]            上传者用户名
     * @apiParam {Object} [sorts]               排序方式，格式：{key:'', order:''}
     * - key: name|size|username|indate
     * - order: asc|desc
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:20，最大:100
     */
    public function files__lists()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $taskid = intval(Request::input('taskid'));
        if ($taskid > 0) {
            $projectid = intval(DB::table('project_task')->select(['projectid'])->where([ 'id' => $taskid])->value('projectid'));
        } else {
            $projectid = intval(Request::input('projectid'));
        }
        if ($projectid > 0) {
            $inRes = Project::inThe($projectid, $user['username']);
            if (Base::isError($inRes)) {
                return $inRes;
            }
        }
        //
        $orderBy = '`id` DESC';
        $sorts = Base::json2array(Request::input('sorts'));
        if (in_array($sorts['order'], ['asc', 'desc'])) {
            switch ($sorts['key']) {
                case 'name':
                case 'size':
                case 'download':
                case 'username':
                case 'indate':
                    $orderBy = '`' . $sorts['key'] . '` ' . $sorts['order'] . ',`id` DESC';
                    break;
            }
        }
        //
        $whereArray = [];
        if ($projectid > 0) {
            $whereArray[] = ['projectid', '=', $projectid];
            if ($taskid > 0) {
                $whereArray[] = ['taskid', '=', $taskid];
            }
        } else {
            if ($taskid <= 0) {
                return Base::retError('参数错误！');
            }
            $count = DB::table('project_task')->where([ 'id' => $taskid, 'username' => $user['username']])->count();
            if ($count <= 0) {
                return Base::retError('你不是任务负责人！');
            }
            $whereArray[] = ['taskid', '=', $taskid];
        }
        $whereArray[] = ['delete', '=', 0];
        if (intval(Request::input('taskid')) > 0) {
            $whereArray[] = ['taskid', '=', intval(Request::input('taskid'))];
        }
        if (trim(Request::input('name'))) {
            $whereArray[] = ['name', 'like', '%' . trim(Request::input('name')) . '%'];
        }
        if (trim(Request::input('username'))) {
            $whereArray[] = ['username', '=', trim(Request::input('username'))];
        }
        //
        $lists = DB::table('project_files')
            ->where($whereArray)
            ->orderByRaw($orderBy)->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的文件', $lists);
        }
        foreach ($lists['lists'] AS $key => $item) {
            $lists['lists'][$key]['path'] = Base::fillUrl($item['path']);
            $lists['lists'][$key]['thumb'] = Base::fillUrl($item['thumb']);
            $lists['lists'][$key]['yetdown'] = intval(Session::get('filesDownload:' . $item['id']));
        }
        return Base::retSuccess('success', $lists);
    }

    /**
     * 项目文件-上传
     *
     * @apiParam {Number} [projectid]           项目ID
     * @apiParam {Number} [taskid]              任务ID（如果项目ID为空时此参必须赋值且任务必须是自己负责人）
     */
    public function files__upload()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = intval(Request::input('projectid'));
        $taskid = intval(Request::input('taskid'));
        if ($projectid > 0) {
            $inRes = Project::inThe($projectid, $user['username']);
            if (Base::isError($inRes)) {
                return $inRes;
            }
        } else {
            if ($taskid <= 0) {
                return Base::retError('参数错误！');
            }
            $row = Base::DBC2A(DB::table('project_task')->select(['projectid'])->where([ 'id' => $taskid, 'username' => $user['username']])->first());
            if (empty($row)) {
                return Base::retError('你不是任务负责人！');
            }
            $projectid = $row['projectid'];
        }
        //
        $data = Base::upload([
            "file" => Request::file('files'),
            "type" => 'file',
            "path" => "uploads/projects/" . $projectid . "/",
        ]);
        if (Base::isError($data)) {
            return Base::retError($data['msg']);
        } else {
            $fileData = $data['data'];
            $thumb = 'images/files/file.png';
            switch ($fileData['ext']) {
                case "docx":
                    $thumb = 'images/files/doc.png';
                    break;
                case "xlsx":
                    $thumb = 'images/files/xls.png';
                    break;
                case "pptx":
                    $thumb = 'images/files/ppt.png';
                    break;
                case "doc":
                case "xls":
                case "ppt":
                case "txt":
                case "esp":
                case "gif":
                    $thumb = 'images/files/' . $fileData['ext'] . '.png';
                    break;
                case "jpg":
                case "jpeg":
                case "png":
                    if (Base::imgThumb($fileData['file'], $fileData['file'] . "_thumb.jpg", 64, 0)) {
                        $thumb = $fileData['path'] . "_thumb.jpg";
                    }
                    break;
            }
            $array = [
                'projectid' => $projectid,
                'taskid' => $taskid,
                'name' => $fileData['name'],
                'size' => $fileData['size'] * 1024,
                'ext' => $fileData['ext'],
                'path' => $fileData['path'],
                'thumb' => $thumb,
                'username' => $user['username'],
                'indate' => Base::time(),
            ];
            $id = DB::table('project_files')->insertGetId($array);
            $array['id'] = $id;
            $array['path'] = Base::fillUrl($array['path']);
            $array['thumb'] = Base::fillUrl($array['thumb']);
            $array['download'] = 0;
            $array['yetdown'] = 0;
            DB::table('project_log')->insert([
                'type' => '日志',
                'projectid' => $projectid,
                'taskid' => $taskid,
                'username' => $user['username'],
                'detail' => '上传文件',
                'indate' => Base::time(),
                'other' => Base::array2string([
                    'type' => 'file',
                    'id' => $id,
                    'name' => $fileData['name'],
                ])
            ]);
            if ($taskid > 0) {
                DB::table('project_task')->where('id', $taskid)->increment('filenum');
            }
            return Base::retSuccess('success', $array);
        }
    }

    /**
     * 项目文件-下载
     *
     * @apiParam {Number} fileid                文件ID
     */
    public function files__download()
    {
        $fileDetail = Base::DBC2A(DB::table('project_files')->where('id', intval(Request::input('fileid')))->where('delete', 0)->first());
        if (empty($fileDetail)) {
            return abort(404, '文件不存在或已被删除！');
        }
        $filePath = public_path($fileDetail['path']);
        if (!file_exists($filePath)) {
            return abort(404, '文件不存在或已被删除。');
        }
        if (intval(Session::get('filesDownload:' . $fileDetail['id'])) !== 1) {
            Session::put('filesDownload:' . $fileDetail['id'], 1);
            DB::table('project_files')->where('id', $fileDetail['id'])->increment('download');
        }
        return response()->download($filePath, $fileDetail['name']);
    }

    /**
     * 项目文件-重命名
     *
     * @apiParam {Number} fileid                文件ID
     * @apiParam {String} name                  新文件名称
     */
    public function files__rename()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $fileDetail = Base::DBC2A(DB::table('project_files')->where('id', intval(Request::input('fileid')))->where('delete', 0)->first());
        if (empty($fileDetail)) {
            return Base::retError('文件不存在或已被删除！');
        }
        if ($fileDetail['username'] != $user['username']) {
            $inRes = Project::inThe($fileDetail['projectid'], $user['username'], true);
            if (Base::isError($inRes)) {
                return Base::retError('此操作仅支持管理员或上传者！');
            }
        }
        //
        $name = Base::rightDelete(trim(Request::input('name')), '.' . $fileDetail['ext']);
        if (empty($name)) {
            return Base::retError('文件名称不能为空！');
        } elseif (mb_strlen($name) > 32) {
            return Base::retError('文件名称最多只能设置32个字！');
        }
        //
        $name .= '.' . $fileDetail['ext'];
        if (DB::table('project_files')->where('id', $fileDetail['id'])->update([ 'name' => $name ])) {
            DB::table('project_log')->insert([
                'type' => '日志',
                'projectid' => $fileDetail['projectid'],
                'taskid' => $fileDetail['taskid'],
                'username' => $user['username'],
                'detail' => '文件【' . $fileDetail['name'] . '】重命名',
                'indate' => Base::time(),
                'other' => Base::array2string([
                    'type' => 'file',
                    'id' => $fileDetail['id'],
                    'name' => $name,
                ])
            ]);
        }
        //
        return Base::retSuccess('修改成功！', [
            'name' => $name,
        ]);
    }

    /**
     * 项目文件-删除
     *
     * @apiParam {Number} fileid                文件ID
     */
    public function files__delete()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $fileDetail = Base::DBC2A(DB::table('project_files')->where('id', intval(Request::input('fileid')))->where('delete', 0)->first());
        if (empty($fileDetail)) {
            return Base::retError('文件不存在或已被删除！');
        }
        if ($fileDetail['username'] != $user['username']) {
            $inRes = Project::inThe($fileDetail['projectid'], $user['username'], true);
            if (Base::isError($inRes)) {
                return Base::retError('此操作仅支持管理员或上传者！');
            }
        }
        //
        DB::table('project_files')->where('id', $fileDetail['id'])->update([
            'delete' => 1,
            'deletedate' => Base::time()
        ]);
        DB::table('project_log')->insert([
            'type' => '日志',
            'projectid' => $fileDetail['projectid'],
            'taskid' => $fileDetail['taskid'],
            'username' => $user['username'],
            'detail' => '删除文件',
            'indate' => Base::time(),
            'other' => Base::array2string([
                'type' => 'file',
                'id' => $fileDetail['id'],
                'name' => $fileDetail['name'],
            ])
        ]);
        if ($fileDetail['taskid'] > 0) {
            DB::table('project_task')->where('id', $fileDetail['taskid'])->decrement('filenum');
        }
        //
        return Base::retSuccess('删除成功！');
    }

    /**
     * 项目动态-列表
     *
     * @apiParam {Number} [projectid]           项目ID
     * @apiParam {Number} [taskid]              任务ID（如果项目ID为空时此参必须赋值且任务必须是自己负责人）
     * @apiParam {String} [type]                类型
     * - 全部: 日志+评论（默认）
     * - 日志
     * - 评论
     * @apiParam {String} [username]            用户名
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:20，最大:100
     */
    public function log__lists()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $projectid = intval(Request::input('projectid'));
        if ($projectid > 0) {
            $inRes = Project::inThe($projectid, $user['username']);
            if (Base::isError($inRes)) {
                return $inRes;
            }
        }
        //
        $taskid = intval(Request::input('taskid'));
        $whereArray = [];
        $whereFunc = null;
        if ($projectid > 0) {
            $whereArray[] = ['projectid', '=', $projectid];
            if ($taskid > 0) {
                $whereArray[] = ['taskid', '=', $taskid];
            }
        } else {
            if ($taskid < 0) {
                return Base::retError('参数错误！');
            }
            $count = DB::table('project_task')->where([ 'id' => $taskid, 'username' => $user['username']])->count();
            if ($count <= 0) {
                return Base::retError('你不是任务负责人！');
            }
            $whereArray[] = ['taskid', '=', $taskid];
        }
        if (trim(Request::input('username'))) {
            $whereArray[] = ['username', '=', trim(Request::input('username'))];
        }
        switch (trim(Request::input('type'))) {
            case '日志': {
                $whereArray[] = ['type', '=', '日志'];
                break;
            }
            case '评论': {
                $whereArray[] = ['type', '=', '评论'];
                break;
            }
            default: {
                $whereFunc = function ($query) {
                    $query->whereIn('type', ['日志', '评论']);
                };
                break;
            }
        }
        //
        $lists = DB::table('project_log')
            ->where($whereArray)
            ->where($whereFunc)
            ->orderByDesc('indate')->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if ($lists['total'] == 0) {
            return Base::retError('未找到任何相关的记录', $lists);
        }
        foreach ($lists['lists'] AS $key => $item) {
            $item = array_merge($item, Users::username2basic($item['username']));
            $item['timeData'] = [
                'ymd' => date(date("Y", $item['indate']) == date("Y", Base::time()) ? "m-d" : "Y-m-d", $item['indate']),
                'hi' => date("h:i", $item['indate']) ,
                'week' => "周" . Base::getTimeWeek($item['indate']),
                'segment' => Base::getTimeDayeSegment($item['indate']),
            ];
            $item['other'] = Base::string2array($item['other'], ['type' => '']);
            $lists['lists'][$key] = $item;
        }
        return Base::retSuccess('success', $lists);
    }
}
