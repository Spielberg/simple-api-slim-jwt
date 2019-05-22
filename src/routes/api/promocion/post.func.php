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
  
  // get settings array.
  $settings = $this->get('settings');  

  // validation.
  $input = $request->getParsedBody();
  foreach([
    'name',
    'zona',
  ] as $key){
    if (!isset($input[$key]) || $input[$key] === '') {
      return $this->response->withJson(['error' => true, 'message' => "Falta alguno de los parámetros obligatorios: $key."]);  
    }
  }

  $sql = 'INSERT INTO promociones (name, zona) VALUES (:name, :zona)';
  $sth = $this->db->prepare($sql);
  $sth->bindParam('name', $input['name']);
  $sth->bindParam('zona', $input['zona']);
  try {
    $sth->execute();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
  
  $id = (int) $this->db->query('SELECT LAST_INSERT_ID()')->fetchColumn();
    
  // guardamos ahora los inmuebles
  foreach($input['inmuebles'] as $inmuebleId) {
    $sql = 'INSERT INTO promociones_tipos_inmuebles (promociones_id, tipos_inmuebles_id) VALUES (:promociones_id, :tipos_inmuebles_id)';
    $sth = $this->db->prepare($sql);
    $sth->bindParam('promociones_id', $id);
    $sth->bindParam('tipos_inmuebles_id', $inmuebleId);
    try {
      $sth->execute();
    } catch(Exception $e) {
      return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
    }
  }

  return $this->response->withJson([
    'error' => false,
    'data' => [
      'id' => $id, 
    ],
  ]);
};