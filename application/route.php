<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    //别名配置,别名只能是映射到控制器且访问时必须加上请求的方法
    '__alias__'   => [
    ],
    //变量规则
    '__pattern__' => [
    ],

    // 签到系统路由映射
    // 前端调用 /addons/signin/api.index/xxx 映射到 api/signin.Index/xxx
    'addons/signin/api.index/index'     => 'api/signin.Index/index',
    'addons/signin/api.index/monthSign' => 'api/signin.Index/monthSign',
    'addons/signin/api.index/dosign'    => 'api/signin.Index/dosign',
    'addons/signin/api.index/fillup'    => 'api/signin.Index/fillup',
    'addons/signin/api.index/rank'      => 'api/signin.Index/rank',
    'addons/signin/api.index/signLog'   => 'api/signin.Index/signLog',
//        域名绑定到模块
//        '__domain__'  => [
//            'admin' => 'admin',
//            'api'   => 'api',
//        ],
];
