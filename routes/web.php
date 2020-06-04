<?php

use App\Http\Middleware\ApiMiddleware;


/**
 * 接口
 */
Route::prefix('api')->middleware(ApiMiddleware::class)->group(function () {
    //会员
    Route::any('users/{method}',                    'Api\UsersController');
    Route::any('users/{method}/{action}',           'Api\UsersController');
    //项目
    Route::any('project/{method}',                  'Api\ProjectController');
    Route::any('project/{method}/{action}',         'Api\ProjectController');
    //汇报
    Route::any('report/{method}',                   'Api\ReportController');
    Route::any('report/{method}/{action}',          'Api\ReportController');
    //知识库
    Route::any('docs/{method}',                     'Api\DocsController');
    Route::any('docs/{method}/{action}',            'Api\DocsController');
    //聊天
    Route::any('chat/{method}',                     'Api\ChatController');
    Route::any('chat/{method}/{action}',            'Api\ChatController');
    //系统
    Route::any('system/{method}',                   'Api\SystemController');
    Route::any('system/{method}/{action}',          'Api\SystemController');
});


/**
 * 页面
 */
Route::middleware(ApiMiddleware::class)->group(function () {
    Route::any('/',                                 'IndexController');
    Route::any('/{method}',                         'IndexController');
    Route::any('/{method}/{action}',                'IndexController');
    Route::any('/{method}/{action}/{child}',        'IndexController');
});

