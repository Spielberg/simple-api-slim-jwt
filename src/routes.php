<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
  $container = $app->getContainer();

  // login
  $app->post('/login', require __DIR__ . '/routes/login.func.php');

  // api
  $app->group('/api', function(\Slim\App $app) {
 
    // users
    $app->get('/users', require __DIR__ . '/routes/api/user/get.func.php');
    $app->post('/users', require __DIR__ . '/routes/api/user/post.func.php');
    $app->put('/users', require __DIR__ . '/routes/api/user/put.func.php');

    // promociones
    $app->get('/promociones/zonas', require __DIR__ . '/routes/api/promocion/get-zonas.func.php');
    $app->get('/promociones', require __DIR__ . '/routes/api/promocion/get.func.php');
    $app->post('/promociones', require __DIR__ . '/routes/api/promocion/post.func.php');
    $app->put('/promociones', require __DIR__ . '/routes/api/promocion/put.func.php');

    // tipos de inmuebles
    $app->get('/tipos-inmuebles/nombre', require __DIR__ . '/routes/api/tipos-inmuebles/get-nombres.func.php');
    $app->get('/tipos-inmuebles', require __DIR__ . '/routes/api/tipos-inmuebles/get.func.php');
    $app->post('/tipos-inmuebles', require __DIR__ . '/routes/api/tipos-inmuebles/post.func.php');
    $app->put('/tipos-inmuebles', require __DIR__ . '/routes/api/tipos-inmuebles/put.func.php');
     

  });
};
