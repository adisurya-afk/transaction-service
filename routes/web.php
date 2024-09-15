<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->group(['prefix' => 'items'], function () use ($router) {
        $router->get('', 'ItemController@index');
        $router->get('/detail/{id}', 'ItemController@show');
        $router->get('/total', 'ItemController@countTotalItems');
        $router->post('', 'ItemController@create');
        $router->put('/{id}', 'ItemController@update');
        $router->delete('/{id}', 'ItemController@delete');
    });

    $router->group(['prefix' => 'transactions'], function () use ($router) {
        $router->get('', 'TransactionController@index');
        $router->get('/detail/{id}', 'TransactionController@show');
        $router->post('', 'TransactionController@create');
        $router->delete('/{id}', 'TransactionController@delete');
        $router->get('/graphic/{year}', 'TransactionController@GetTrxGraphic');
        $router->get('/graphic-specific/{year}/{type}', 'TransactionController@GetTrxGraphicSpecific');
        $router->get('/monthly-report/{month}/{year}', 'TransactionController@GetReportMonthly');
    });
});