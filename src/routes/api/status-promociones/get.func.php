<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once __DIR__ . '/functions.php';

return function (Request $request, Response $response, array $args) {
   
  $status = return_status_promociones($request, $response, $args, $this);

  if (!$status) {
    return $this->response->withJson(['error' => true, 'message' => 'Status return no result']);  
  }

  return $this->response->withJson($status);
};