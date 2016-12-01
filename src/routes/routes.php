<?php

use Illuminate\Support\Facades\Route;

Route::group([
                 'namespace' => 'Zigo928\Mypackage\App\Http\Controllers',
                 'prefix'    => 'zigo928/mypackage',
             ], function () {
    Route::match([
                     'get',
                     'post',
                 ], '/interest', 'InterestController@index');
});