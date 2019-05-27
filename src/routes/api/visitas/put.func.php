<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
 
  $token = $request->getAttribute('decoded_token_data');

  // el id del user es obligatorio.
  $input = $request->getParsedBody();
  if (!isset($input['id']) || $input['id'] === '' || !ctype_digit((string)$input['id'])) {
    return $this->response->withJson(['error' => true, 'message' => 'Falta alguno de los parámetros obligatorios: id.']);
  }

  // allowed.
  $allowed = [
    'conociste',
    'email',
    'fecha_visita',
    'name',
    'observaciones',
    'promociones_id',
    'publicidad',
    'status',
    'telefono',
    'users_id',
  ];

  $arrInput = [
    'id' => $input['id'],
  ];
  $arrValue = [];
  foreach($allowed as $key){
    if (isset($input[$key]) && $input[$key] !== '') {
      $arrInput[$key] = $input[$key];
      $arrValue[] = "$key = :$key";
    }
  }
  if(count($arrInput) === 0) {
    return $this->response->withJson(['error' => true, 'message' => 'Ningún parametro para actualizar.']);  
    }

  // build quey.
  $sql = 'UPDATE visitas SET ' . implode($arrValue, ', ') . ' WHERE id = :id LIMIT 1';
  $sth = $this->db->prepare($sql);
  try {
    $sth->execute($arrInput);
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
      
  return $this->response->withJson([
    'error' => false,
  ]);
};