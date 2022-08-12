<?php

$router->group(['prefix' => 'user'], function () use ($router) {
    $router->post('login', 'UserAuthController@login');
    $router->post('logout', 'UserAuthController@logout');
});

$router->group(['middleware' => 'jwt-auth'], function () use ($router) {
    $router->post('products', 'ProductController@create');
    $router->get('products', 'ProductController@list');
    $router->get('products/{id}', 'ProductController@view');
    $router->put('products/{id}', 'ProductController@update');
    $router->delete('products/{id}', 'ProductController@delete');

    $router->post('product/groups', 'ProductGroupController@create');
    $router->get('product/groups', 'ProductGroupController@list');
    $router->put('product/groups/{id}', 'ProductGroupController@update');
    $router->delete('product/groups/{id}', 'ProductGroupController@delete');

    $router->post('orders', 'OrderController@create');
    $router->get('orders', 'OrderController@list');
    $router->get('orders/{id}', 'OrderController@view');
    $router->put('orders/{id}', 'OrderController@update');
    $router->delete('orders/{id}', 'OrderController@delete');

    $router->get('transactions', 'TransactionController@list');
    $router->get('transactions/{id}', 'TransactionController@view');
    $router->put('transactions/{id}', 'TransactionController@update');
    $router->delete('transactions/{id}', 'TransactionController@delete');

    $router->post('users', 'UserController@create');
    $router->get('users', 'UserController@list');
    $router->get('users/{id}', 'UserController@view');
    $router->put('users/{id}', 'UserController@update');
    $router->delete('users/{id}', 'UserController@delete');
});
