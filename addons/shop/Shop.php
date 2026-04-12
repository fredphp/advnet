<?php

namespace addons\shop;

use addons\shop\library\FulltextSearch;
use addons\shop\library\Service;
use app\common\library\Menu;
use think\Addons;
use think\Config;
use think\Db;
use think\Request;
use think\Loader;

/**
 * Shop插件
 */
class Shop extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        $menu = include ADDON_PATH . 'shop' . DS . 'data' . DS . 'menu.php';
        Menu::create($menu);

        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        Menu::delete('shop');
        return true;
    }

    /**
     * 插件启用方法
     */
    public function enable()
    {
        $menu = include ADDON_PATH . 'shop' . DS . 'data' . DS . 'menu.php';
        Menu::upgrade('shop', $menu);
        Menu::enable('shop');
    }

    /**
     * 插件禁用方法
     */
    public function disable()
    {
        Menu::disable('shop');
    }

    /**
     * 插件升级方法
     */
    public function upgrade()
    {
        $menu = include ADDON_PATH . 'shop' . DS . 'data' . DS . 'menu.php';
        Menu::upgrade('shop', $menu);
    }

    /**
     * 应用初始化
     */
    public function appInit()
    {
        //添加命名空间
        if (!class_exists('\Hashids\Hashids')) {
            Loader::addNamespace('Hashids', ADDON_PATH . 'shop' . DS . 'library' . DS . 'hashids' . DS);
        }
        // 自定义路由变量规则
        // \think\Route::pattern([
        //     'diyname' => "/[a-zA-Z0-9\-_\x{4e00}-\x{9fa5}]+/u",
        //     'id'      => "\d+",
        // ]);
        $config = get_addon_config('shop');
        $taglib = Config::get('template.taglib_pre_load');
        Config::set('template.taglib_pre_load', ($taglib ? $taglib . ',' : '') . 'addons\\shop\\taglib\\Shop');
        Config::set('shop', $config);
    }

    /**
     * 脚本替换
     */
    public function viewFilter(&$content)
    {
        $request = \think\Request::instance();
        $dispatch = $request->dispatch();
        if (!$dispatch) {
            return;
        }

        if ($request->module() || !isset($dispatch['method'][0]) || $dispatch['method'][0] != '\think\addons\Route') {
            return;
        }
        $addon = $dispatch['var']['addon'] ?? $request->param('addon');
        if ($addon != 'shop') {
            return;
        }
        $style = '';
        $script = '';
        $result = preg_replace_callback("/<(script|style)\s(data\-render=\"(script|style)\")([\s\S]*?)>([\s\S]*?)<\/(script|style)>/i", function ($match) use (&$style, &$script) {
            if (isset($match[1]) && in_array($match[1], ['style', 'script'])) {
                ${$match[1]} .= str_replace($match[2], '', $match[0]);
            }
            return '';
        }, $content);
        $content = preg_replace_callback('/^\s+(\{__STYLE__\}|\{__SCRIPT__\})\s+$/m', function ($matches) use ($style, $script) {
            return $matches[1] == '{__STYLE__}' ? $style : $script;
        }, $result ?: $content);
    }

    /**
     * 会员中心边栏后
     * @return mixed
     * @throws \Exception
     */
    public function userSidenavAfter()
    {
        $request = Request::instance();
        $controllername = strtolower($request->controller());
        $actionname = strtolower($request->action());
        $config = get_addon_config('shop');
        $usersidebar = explode(',', $config['usersidenav']);
        if (!$usersidebar) {
            return '';
        }
        $data = [
            'controllername' => $controllername,
            'actionname'     => $actionname,
            'usersidebar'    => $usersidebar
        ];

        return $this->fetch('view/hook/user_sidenav_after', $data);
    }


}
