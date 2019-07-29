<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
  $container = $app->getContainer();

  $app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
  });

  $app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
  });

  // login
  $app->post('/login', require __DIR__ . '/routes/login.func.php');

  // api
  $app->group('/api', function(\Slim\App $app) {

    // home
    $app->get('/home', require __DIR__ . '/routes/api/home/get.func.php');
    
    // excel
    $app->get('/excel', require __DIR__ . '/routes/api/excel/get.func.php');

    // users
    $app->get('/users', require __DIR__ . '/routes/api/user/get.func.php');
    $app->post('/users', require __DIR__ . '/routes/api/user/post.func.php');
    $app->put('/users', require __DIR__ . '/routes/api/user/put.func.php');

    // promociones
    $app->get('/promociones/zonas', require __DIR__ . '/routes/api/promocion/get-zonas.func.php');
    $app->get('/promociones/{id}', require __DIR__ . '/routes/api/promocion/get-one.func.php');
    $app->get('/promociones', require __DIR__ . '/routes/api/promocion/get.func.php');
    $app->post('/promociones', require __DIR__ . '/routes/api/promocion/post.func.php');
    $app->put('/promociones', require __DIR__ . '/routes/api/promocion/put.func.php');

    // tipos de inmuebles
    $app->get('/tipos-inmuebles/nombre', require __DIR__ . '/routes/api/tipos-inmuebles/get-nombres.func.php');
    $app->get('/tipos-inmuebles', require __DIR__ . '/routes/api/tipos-inmuebles/get.func.php');
    $app->post('/tipos-inmuebles', require __DIR__ . '/routes/api/tipos-inmuebles/post.func.php');
    $app->put('/tipos-inmuebles', require __DIR__ . '/routes/api/tipos-inmuebles/put.func.php');
     
    // visitas
    $app->get('/visitas/{id}', require __DIR__ . '/routes/api/visitas/get-one.func.php');
    $app->get('/visitas', require __DIR__ . '/routes/api/visitas/get.func.php');
    $app->post('/visitas', require __DIR__ . '/routes/api/visitas/post.func.php');
    $app->delete('/visitas/{id}', require __DIR__ . '/routes/api/visitas/delete.func.php');
    $app->put('/visitas', require __DIR__ . '/routes/api/visitas/put.func.php');

    // ventas
    $app->get('/ventas', require __DIR__ . '/routes/api/ventas/get.func.php');
    $app->delete('/ventas/{id}', require __DIR__ . '/routes/api/ventas/delete.func.php');
    $app->post('/ventas', require __DIR__ . '/routes/api/ventas/post.func.php');

    // mailjet
    $app->get('/mail/templates', require __DIR__ . '/routes/api/mail/templates.func.php');
    $app->post('/mail/newsletter', require __DIR__ . '/routes/api/mail/newsletter.func.php');
    $app->post('/mail/send', require __DIR__ . '/routes/api/mail/send.func.php');

  });

    // homepage
  $app->get('/', function ($request, $response, $args) {
    return $this->view->render($response, 'index.html');
  });

};
