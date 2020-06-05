<?php

namespace App\Http\Controllers;

use App\Module\Base;
use App\Module\Users;
use Redirect;
use Request;


/**
 * 页面
 * Class IndexController
 * @package App\Http\Controllers
 */
class IndexController extends Controller
{

    private $version = '100000';

    public function __invoke($method, $action = '', $child = '')
    {
        $app = $method ? $method : 'main';
        if ($action) {
            $app .= "__" . $action;
        }
        return (method_exists($this, $app)) ? $this->$app($child) : Base::ajaxError("404 not found (" . str_replace("__", "/", $app) . ").");
    }

    /**
     * 获取终端详细信息
     * @return array
     */
    public function get()
    {
        if (Request::input("key") !== env('APP_KEY')) {
            return [];
        }
        return Base::retSuccess('success', [
            'ip' => Base::getIp(),
            'ip-info' => Base::getIpInfo(Base::getIp()),
            'ip-iscn' => Base::isCnIp(Base::getIp()),
            'header' => Request::header(),
            'token' => Base::getToken(),
            'url' => url('') . Base::getUrl(),
        ]);
    }

    /**
     * 获取IP地址
     * @return array|mixed
     */
    public function get__ip() {
        return Base::getIp();
    }

    /**
     * 是否中国IP地址
     * @return array|mixed
     */
    public function get__cnip() {
        return Base::isCnIp(Request::input('ip'));
    }

    /**
     * 获取IP地址详细信息
     * @return array|mixed
     */
    public function get__ipinfo() {
        return Base::getIpInfo(Request::input("ip"));
    }

    /**
     * 首页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function main()
    {
        return view('main', ['version' => $this->version]);
    }

    /**
     * 接口文档
     * @return \Illuminate\Http\RedirectResponse
     */
    public function api()
    {
        return Redirect::to(Base::fillUrl('docs'), 301);
    }

    /**
     * 上传图片
     * @return array
     */
    public function api__imgupload()
    {
        if (Users::token2userid() === 0) {
            return Base::retError('身份失效，等重新登录！');
        }
        $scale = [intval(Request::input('width')), intval(Request::input('height'))];
        if (!$scale[0] && !$scale[1]) {
            $scale = [2160, 4160, -1];
        }
        $path = "uploads/picture/" . Users::token2userid() . "/" . date("Ym") . "/";
        if (Request::input('from') == 'chat') {
            $path = "uploads/chat/" . Users::token2userid() . "/" . date("Ym") . "/";
        }
        $data = Base::upload([
            "file" => Request::file('image'),
            "type" => 'image',
            "path" => $path,
            "scale" => $scale
        ]);
        if (Base::isError($data)) {
            return Base::retError($data['msg']);
        } else {
            return Base::retSuccess('success', $data['data']);
        }
    }

    /**
     * 浏览图片空间
     * @return array
     */
    public function api__imgview()
    {
        if (Users::token2userid() === 0) {
            return Base::retError('身份失效，等重新登录！');
        }
        $publicPath = "uploads/picture/" . Users::token2userid() . "/";
        $dirPath = public_path($publicPath);
        $dirs = $files = [];
        //
        $path = Request::input('path');
        if ($path && is_string($path)) {
            $path = str_replace(array('||', '|'), '/', $path);
            $path = trim($path, '/');
            $path = str_replace('..', '', $path);
            $path = Base::leftDelete($path, $publicPath);
            if ($path) {
                $path = $path . '/';
                $dirPath .= $path;
                //
                $dirs[] = [
                    'type' => 'dir',
                    'title' => '...',
                    'path' => substr(substr($path, 0, -1), 0, strripos(substr($path, 0, -1), '/')),
                    'url' => '',
                    'thumb' => Base::fillUrl('images/other/dir.png'),
                    'inode' => 0,
                ];
            }
        } else {
            $path = '';
        }
        $list = glob($dirPath . '*', GLOB_BRACE);
        foreach ($list as $v) {
            $filename = basename($v);
            $pathTemp = $publicPath . $path . $filename;
            if (is_dir($v)) {
                $dirs[] = [
                    'type' => 'dir',
                    'title' => $filename,
                    'path' => $pathTemp,
                    'url' => Base::fillUrl($pathTemp),
                    'thumb' => Base::fillUrl('images/other/dir.png'),
                    'inode' => fileatime($v),
                ];
            } elseif (substr($filename, -10) != "_thumb.jpg") {
                $array = [
                    'type' => 'file',
                    'title' => $filename,
                    'path' => $pathTemp,
                    'url' => Base::fillUrl($pathTemp),
                    'thumb' => $pathTemp,
                    'inode' => fileatime($v),
                ];
                //
                $extension = pathinfo($dirPath . $filename, PATHINFO_EXTENSION);
                if (in_array($extension, array('gif', 'jpg', 'jpeg', 'png', 'bmp'))) {
                    if (file_exists($dirPath . $filename . '_thumb.jpg')) {
                        $array['thumb'] .= '_thumb.jpg';
                    }
                    $array['thumb'] = Base::fillUrl($array['thumb']);
                    $files[] = $array;
                }
            }
        }
        if ($dirs) {
            $inOrder = [];
            foreach ($dirs as $key => $item) {
                $inOrder[$key] = $item['title'];
            }
            array_multisort($inOrder, SORT_DESC, $dirs);
        }
        if ($files) {
            $inOrder = [];
            foreach ($files as $key => $item) {
                $inOrder[$key] = $item['inode'];
            }
            array_multisort($inOrder, SORT_DESC, $files);
        }
        //
        return Base::retSuccess('success', ['dirs' => $dirs, 'files' => $files]);
    }

    /**
     * 获取国际化列表
     */
    public function language__lists()
    {
        $lists = Base::readDir(resource_path('assets/js'));
        $array = [];
        foreach ($lists AS $file) {
            $content = file_get_contents($file);
            preg_match_all('/\$L\(([\'"])(.*?)\\1/', $content, $matchs);
            foreach ($matchs[2] AS $key=>$text) {
                if (Base::strExists($text, "',")) {
                    $text = Base::getMiddle($text, null, "',");
                }
                if (!isset($array[$text])) {
                    $array[$text] = null;
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 清理opcache数据
     * @return int
     */
    public function opcache__reset()
    {
        opcache_reset();
        return Base::time();
    }
}
