<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
 
  $token = $request->getAttribute('decoded_token_data');

  // verify is superuser.
  if(!$token['superuser']) {
    return $this->response->withJson(['error' => true, 'message' => 'No tienes suficientes permisos para hacer esta llamada.']);  
  }

  // name must be included
  $input = $request->getParsedBody();
  if (!isset($input['name']) || $input['name'] === '') {
    return $this->response->withJson(['error' => true, 'message' => 'Falta alguno de los parámetros obligatorios: id.']);
  }

  return $this->response->withJson([
    'error' => false,
  ]);
};