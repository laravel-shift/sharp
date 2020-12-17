<?php

// API routes
use Code16\Sharp\Http\Api\FormUploadController;
use Code16\Sharp\Http\DashboardController;
use Code16\Sharp\Http\HomeController;
use Code16\Sharp\Http\ListController;
use Code16\Sharp\Http\LoginController;
use Code16\Sharp\Http\SingleShowController;
use Code16\Sharp\Http\WebDispatchController;

Route::group([
    'prefix' => '/' . sharp_base_url_segment() . '/api',
    'middleware' => ['sharp_web', 'sharp_api_errors', 'sharp_api_context', 'sharp_api_validation', 'sharp_locale'],
    'namespace' => 'Code16\Sharp\Http\Api'
], function() {

    Route::get("/dashboard/{dashboardKey}")
        ->name("code16.sharp.api.dashboard")
        ->middleware(['sharp_api_append_breadcrumb'])
        ->uses('DashboardController@show');

    Route::get("/dashboard/{dashboardKey}/command/{commandKey}/data")
        ->name("code16.sharp.api.dashboard.command.data")
        ->uses('Commands\DashboardCommandController@show');

    Route::post("/dashboard/{dashboardKey}/command/{commandKey}")
        ->name("code16.sharp.api.dashboard.command")
        ->uses('Commands\DashboardCommandController@update');

    Route::get("/list/{entityKey}")
        ->name("code16.sharp.api.list")
        ->middleware(['sharp_api_append_list_authorizations', 'sharp_api_append_list_multiform', 'sharp_api_append_notifications', 'sharp_api_append_breadcrumb'])
        ->uses('EntityListController@show');

    Route::post("/list/{entityKey}/reorder")
        ->name("code16.sharp.api.list.reorder")
        ->uses('EntityListController@update');

    Route::post("/list/{entityKey}/state/{instanceId}")
        ->name("code16.sharp.api.list.state")
        ->uses('Commands\EntityListInstanceStateController@update');

    Route::post("/list/{entityKey}/command/{commandKey}")
        ->name("code16.sharp.api.list.command.entity")
        ->uses('Commands\EntityCommandController@update');

    Route::get("/list/{entityKey}/command/{commandKey}/data")
        ->name("code16.sharp.api.list.command.entity.data")
        ->uses('Commands\EntityCommandController@show');

    Route::post("/list/{entityKey}/command/{commandKey}/{instanceId}")
        ->name("code16.sharp.api.list.command.instance")
        ->uses('Commands\EntityListInstanceCommandController@update');

    Route::get("/list/{entityKey}/command/{commandKey}/{instanceId}/data")
        ->name("code16.sharp.api.list.command.instance.data")
        ->uses('Commands\EntityListInstanceCommandController@show');

    Route::get("/show/{entityKey}/{instanceId?}")
        ->name("code16.sharp.api.show.show")
        ->middleware(['sharp_api_append_form_authorizations', 'sharp_api_append_breadcrumb'])
        ->uses('ShowController@show');

    Route::get("/show/download/{fieldKey}/{entityKey}/{instanceId?}")
        ->name("code16.sharp.api.show.download")
        ->uses('DownloadController@show');

    Route::post("/show/{entityKey}/command/{commandKey}/{instanceId?}")
        ->name("code16.sharp.api.show.command.instance")
        ->uses('Commands\ShowInstanceCommandController@update');

    Route::get("/show/{entityKey}/command/{commandKey}/{instanceId}/data")
        ->name("code16.sharp.api.show.command.instance.data")
        ->uses('Commands\ShowInstanceCommandController@show');

    // Specific route for single shows, because /show/{entityKey}/command/{commandKey}/{instanceId?}/data
    // does not work since instanceId is optional but not the last segment.
    Route::get("/show/{entityKey}/command/{commandKey}/data")
        ->name("code16.sharp.api.show.command.singleInstance.data")
        ->uses('Commands\ShowInstanceCommandController@show');

    Route::post("/show/{entityKey}/state/{instanceId?}")
        ->name("code16.sharp.api.show.state")
        ->uses('Commands\ShowInstanceStateController@update');

    Route::get("/form/{entityKey}")
        ->name("code16.sharp.api.form.create")
        ->middleware(['sharp_api_append_form_authorizations', 'sharp_api_append_breadcrumb'])
        ->uses('FormController@create');

    Route::post("/form/{entityKey}")
        ->name("code16.sharp.api.form.store")
        ->uses('FormController@store');

    Route::get("/form/{entityKey}/{instanceId?}")
        ->name("code16.sharp.api.form.edit")
        ->middleware(['sharp_api_append_form_authorizations', 'sharp_api_append_breadcrumb'])
        ->uses('FormController@edit');

    Route::post("/form/{entityKey}/{instanceId?}")
        ->name("code16.sharp.api.form.update")
        ->uses('FormController@update');

    Route::delete("/form/{entityKey}/{instanceId?}")
        ->name("code16.sharp.api.form.delete")
        ->uses('FormController@delete');

    Route::get("/form/download/{fieldKey}/{entityKey}/{instanceId?}")
        ->name("code16.sharp.api.form.download")
        ->uses('DownloadController@show');

    Route::get("/filters")
        ->name("code16.sharp.api.filter.index")
        ->uses('GlobalFilterController@index');

    Route::post("/filters/{filterKey}")
        ->name("code16.sharp.api.filter.update")
        ->uses('GlobalFilterController@update');
});

// Web routes
Route::group([
    'prefix' => '/' . sharp_base_url_segment(),
    'middleware' => ['sharp_web', 'sharp_invalidate_cache'],
    'namespace' => 'Code16\Sharp\Http'
], function() {

    Route::get('/login', [LoginController::class, "create"])
        ->name("code16.sharp.login");

    Route::post('/login', [LoginController::class, "store"])
        ->name("code16.sharp.login.post");

    Route::get('/', [HomeController::class, "index"])
        ->name("code16.sharp.home");

    Route::get('/logout', [LoginController::class, "destroy"])
        ->name("code16.sharp.logout");

    Route::get('/s-list/{entityKey}', [ListController::class, "show"])
        ->name("code16.sharp.list");

    Route::get('/s-show/{entityKey}', [SingleShowController::class, "show"])
        ->name("code16.sharp.single-show");

    Route::get('/s-list/{entityKey}/{uri}', [WebDispatchController::class, "index"])
        ->where("uri", ".*");

    Route::get('/s-show/{entityKey}/{uri}', [WebDispatchController::class, "index"])
        ->where("uri", ".*");

    Route::get('/s-dashboard/{dashboardKey}', [DashboardController::class, "show"])
        ->name("code16.sharp.dashboard");

    Route::post('/api/upload', [FormUploadController::class, "store"])
        ->name("code16.sharp.api.form.upload");
});

// Localization
Route::get('/vendor/sharp/lang.js')
    ->name('code16.sharp.assets.lang')
    ->uses('Code16\Sharp\Http\LangController@index');
