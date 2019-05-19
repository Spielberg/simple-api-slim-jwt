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
   
  });
};
