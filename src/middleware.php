<?php

use Slim\App;

return function (App $app) {
    $container = $app->getContainer();
    $jwt = $container->get('settings')['jwt'];

    // e.g: $app->add(new \Slim\Csrf\Guard);
    $app->add(new \Tuupola\Middleware\JwtAuthentication([
      'path' => '/api', /* or ['/api', '/admin'] */
      'attribute' => 'decoded_token_data',
      'secure' => false,
      'secret' => $jwt['secret'],
      'algorithm' => ['HS256'],
      'error' => function ($response, $arguments) {
          $data['status'] = 'error';
          $data['message'] = $arguments['message'];
          return $response
              ->withHeader('Content-Type', 'application/json')
              ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
      }
  ]));
};
