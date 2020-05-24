<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Module\Base;
use App\Module\Users;
use DB;
use Request;

/**
 * @apiDefine docs
 *
 * 知识库
 */
class DocsController extends Controller
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
     * 知识库列表
     *
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:20，最大:100
     */
    public function book__lists()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $lists = DB::table('docs_book')
            ->orderByDesc('id')
            ->paginate(Min(Max(Base::nullShow(Request::input('pagesize'), 10), 1), 100));
        $lists = Base::getPageList($lists);
        if ($lists['total'] == 0) {
            return Base::retError('暂无知识库', $lists);
        }
        return Base::retSuccess('success', $lists);
    }

    /**
     * 添加/修改知识库
     *
     * @apiParam {String} title             知识库名称
     */
    public function book__add()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $id = intval(Request::input('id'));
        $title = trim(Request::input('title'));
        if (mb_strlen($title) < 2 || mb_strlen($title) > 100) {
            return Base::retError('标题限制2-100个字！');
        }
        if ($id > 0) {
            // 修改
            $row = Base::DBC2A(DB::table('docs_book')->where('id', $id)->first());
            if (empty($row)) {
                return Base::retError('知识库不存在或已被删除！');
            }
            if ($row['username'] != $user['username']) {
                return Base::retError('此操作仅限知识库负责人！');
            }
            $data = [
                'title' => $title,
            ];
            DB::table('docs_book')->where('id', $id)->update($data);
            return Base::retSuccess('修改成功！', $data);
        } else {
            // 添加
            $data = [
                'username' => $user['username'],
                'title' => $title,
                'indate' => Base::time(),
            ];
            $id = DB::table('docs_book')->insertGetId($data);
            if (empty($id)) {
                return Base::retError('系统繁忙，请稍后再试！');
            }
            $data['id'] = $id;
            return Base::retSuccess('添加成功！', $data);
        }
    }

    /**
     * 删除知识库
     *
     * @apiParam {Number} id                知识库数据ID
     */
    public function book__delete()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $id = intval(Request::input('id'));
        $row = Base::DBC2A(DB::table('docs_book')->where('id', $id)->first());
        if (empty($row)) {
            return Base::retError('知识库不存在或已被删除！');
        }
        if ($row['username'] != $user['username']) {
            return Base::retError('此操作仅限知识库负责人！');
        }
        DB::table('docs_book')->where('id', $id)->delete();
        DB::table('docs_section')->where('bookid', $id)->delete();
        //未完成，应该还要删除章节
        return Base::retSuccess('删除成功！');
    }

    /**
     * 章节列表
     *
     * @apiParam {Number} bookid                知识库数据ID
     */
    public function section__lists()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $lists = Base::DBC2A(DB::table('docs_section')
            ->where('bookid', intval(Request::input('bookid')))
            ->orderByDesc('inorder')
            ->orderByDesc('id')
            ->take(300)
            ->get());
        if (empty($lists)) {
            return Base::retError('暂无章节');
        }
        foreach ($lists AS $key => $item) {
            $lists[$key]['icon'] = Base::fillUrl('images/files/' . $item['type'] . '.png');
        }
        return Base::retSuccess('success', Base::list2Tree($lists, 'id', 'parentid'));
    }

    /**
     * 添加/修改章节
     *
     * @apiParam {Number} bookid                知识库数据ID
     * @apiParam {String} title                 章节名称
     * @apiParam {String} type                  章节类型
     */
    public function section__add()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $bookid = intval(Request::input('bookid'));
        $bookRow = Base::DBC2A(DB::table('docs_book')->where('id', $bookid)->first());
        if (empty($bookRow)) {
            return Base::retError('知识库不存在或已被删除！');
        }
        $count = DB::table('docs_section')->where('bookid', $bookid)->count();
        if ($count >= 300) {
            return Base::retError('知识库章节已经超过最大限制（300）！');
        }
        //
        $id = intval(Request::input('id'));
        $title = trim(Request::input('title'));
        $type = trim(Request::input('type'));
        if (mb_strlen($title) < 2 || mb_strlen($title) > 100) {
            return Base::retError('标题限制2-100个字！');
        }
        if ($id > 0) {
            // 修改
            $row = Base::DBC2A(DB::table('docs_section')->where('id', $id)->first());
            if (empty($row)) {
                return Base::retError('知识库不存在或已被删除！');
            }
            $data = [
                'title' => $title,
            ];
            DB::table('docs_section')->where('id', $id)->update($data);
            return Base::retSuccess('修改成功！', $data);
        } else {
            // 添加
            if (!in_array($type, ['document', 'mind', 'sheet', 'chart', 'folder'])) {
                return Base::retError('参数错误！');
            }
            $parentid = 0;
            if ($id < 0) {
                $count = Base::DBC2A(DB::table('docs_section')->where('id', abs($id))->where('bookid', $bookid)->count());
                if ($count > 0) {
                    $parentid = abs($id);
                }
            }
            $data = [
                'bookid' => $bookid,
                'parentid' => $parentid,
                'username' => $user['username'],
                'title' => $title,
                'type' => $type,
                'inorder' => intval(DB::table('docs_section')->select(['inorder'])->where('bookid', $bookid)->orderByDesc('inorder')->value('inorder')) + 1,
                'indate' => Base::time(),
            ];
            $id = DB::table('docs_section')->insertGetId($data);
            if (empty($id)) {
                return Base::retError('系统繁忙，请稍后再试！');
            }
            $data['id'] = $id;
            return Base::retSuccess('添加成功！', $data);
        }
    }

    /**
     * 排序任务
     *
     * @apiParam {Number} bookid                知识库数据ID
     * @apiParam {String} oldsort               旧排序数据
     * @apiParam {String} newsort               新排序数据
     */
    public function section__sort()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $bookid = intval(Request::input('bookid'));
        $bookRow = Base::DBC2A(DB::table('docs_book')->where('id', $bookid)->first());
        if (empty($bookRow)) {
            return Base::retError('知识库不存在或已被删除！');
        }
        //
        $newSort = explode(";", Request::input('newsort'));
        if (count($newSort) == 0) {
            return Base::retError('参数错误！');
        }
        //
        $count = count($newSort);
        foreach ($newSort AS $sort => $item) {
            list($newId, $newParentid) = explode(':', $item);
            DB::table('docs_section')->where([
                'id' => $newId,
                'bookid' => $bookid
            ])->update([
                'inorder' => $count - intval($sort),
                'parentid' => $newParentid
            ]);
        }
        return Base::retSuccess('保存成功！');
    }

    /**
     * 删除章节
     *
     * @apiParam {Number} id                章节数据ID
     */
    public function section__delete()
    {
        $user = Users::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = $user['data'];
        }
        //
        $id = intval(Request::input('id'));
        $row = Base::DBC2A(DB::table('docs_section')->where('id', $id)->first());
        if (empty($row)) {
            return Base::retError('文档不存在或已被删除！');
        }
        DB::table('docs_section')->where('parentid', $id)->update([ 'parentid' => $row['parentid'] ]);
        DB::table('docs_section')->where('id', $id)->delete();
        //未完成，应该还要删除章节
        return Base::retSuccess('删除成功！');
    }

    /**
     * 获取章节内容
     *
     * @apiParam {Number} id                章节数据ID
     */
    public function section__content()
    {
        $id = intval(Request::input('id'));
        $row = Base::DBC2A(DB::table('docs_section')->where('id', $id)->first());
        if (empty($row)) {
            return Base::retError('文档不存在或已被删除！');
        }
        $cRow = Base::DBC2A(DB::table('docs_content')->select(['content'])->where('sid', $id)->first());
        if (empty($cRow)) {
            $cRow = [ 'content' => '' ];
        }
        return Base::retSuccess('success', array_merge($row, $cRow));
    }

    /**
     * 获取章节内容
     *
     * @apiParam {Number} id                章节数据ID
     * @apiParam {Object} [D]               Request Payload 提交
     * - content: 内容
     */
    public function section__save()
    {
        $id = intval(Request::input('id'));
        $row = Base::DBC2A(DB::table('docs_section')->where('id', $id)->first());
        if (empty($row)) {
            return Base::retError('文档不存在或已被删除！');
        }
        $D = Base::getContentsParse('D');
        DB::table('docs_content')->updateOrInsert(['sid' => $id], ['content' => $D['content']]);
        return Base::retSuccess('保存成功！');
    }
}
