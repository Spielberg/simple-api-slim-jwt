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

  // verify is superuser.
  if(!$token['superuser'] && $token['id'] !== $input['id']) {
    return $this->response->withJson(['error' => true, 'message' => 'No tienes suficientes permisos para hacer esta llamada.']);  
    }

  // allowed.
  $allowed = [
    'name' => PDO::PARAM_STR,
    'email' => PDO::PARAM_STR,
    'password' => PDO::PARAM_STR,
    'active' => PDO::PARAM_BOOL,
    'deleted' => PDO::PARAM_BOOL,
  ];
  $arrQuery = [];
  $arrValue = [];
  foreach($allowed as $key => $value){
    if (isset($input[$key]) && $input[$key] !== '') {
      $arrQuery[] = $key;
      $arrValue[] = "$key = :$key";
    }
  }
  if(count($arrQuery) === 0) {
    return $this->response->withJson(['error' => true, 'message' => 'Ningún parametro para actualizar.']);  
    }

  // build quey.
  $sql = 'UPDATE users SET ' . implode($arrValue, ', ') . ' WHERE id = :id LIMIT 1';
  $sth = $this->db->prepare($sql);
  $sth->bindParam('id', $input['id']);
  foreach($arrQuery as $key) {
    if ($key !== 'password') {
      $sth->bindParam($key, $input[$key], $allowed[$key]);
    } else {
      $sth->bindParam('password', password_hash($input['password'], PASSWORD_DEFAULT));
    }
  };
  
  try {
    $sth->execute();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
      
  return $this->response->withJson([
    'error' => false,
  ]);
};