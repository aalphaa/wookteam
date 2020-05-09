<?php

use App\Http\Middleware\ApiMiddleware;


/**
 * 接口
 */
Route::prefix('api')->middleware(ApiMiddleware::class)->group(function () {

});


/**
 * 页面
 */
Route::any('/',                                     'IndexController');
Route::any('/{method}',                             'IndexController');
Route::any('/{method}/{action}',                    'IndexController');
Route::any('/{method}/{action}/{child}',            'IndexController');
Route::any('/{method}/{action}/{child}/{n}',        'IndexController');
Route::any('/{method}/{action}/{child}/{n}/{c}',    'IndexController');
