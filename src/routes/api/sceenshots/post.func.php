<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once __DIR__ . '/../status-promociones/functions.php';

return function (Request $request, Response $response, array $args) {
 
  $token = $request->getAttribute('decoded_token_data');

  // verify is superuser.
  if(!$token['superuser']) {
    return $this->response->withJson(['error' => true, 'message' => 'No tienes suficientes permisos para hacer esta llamada.']);  
  }

  // validation.
  $input = $request->getParsedBody();
  foreach([
    'name',
  ] as $key){
    if (!isset($input[$key]) || $input[$key] === '') {
      return $this->response->withJson(['error' => true, 'message' => "Falta alguno de los parámetros obligatorios: $key."]);  
    }
  }

  $status = return_status_promociones($request, $response, $args, $this);

  if (!$status) {
    return $this->response->withJson(['error' => true, 'message' => 'Status return no result']);  
  }

  $status = serialize($status['data']);

  $sql = 'INSERT INTO screenshots (name, status) VALUES (:name, :status)';
  $sth = $this->db->prepare($sql);
  $sth->bindParam('name', $input['name']);
  $sth->bindParam('status', $status);
  try {
    $sth->execute();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
  
  $id = (int) $this->db->query('SELECT LAST_INSERT_ID()')->fetchColumn();

  return $this->response->withJson([
    'error' => false,
    'data' => [
      'id' => $id, 
    ],
  ]);
};