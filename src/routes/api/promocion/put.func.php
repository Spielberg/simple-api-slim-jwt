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
  if(!$token['superuser']) {
    return $this->response->withJson(['error' => true, 'message' => 'No tienes suficientes permisos para hacer esta llamada.']);  
    }

  // allowed.
  $allowed = [
    'name' => PDO::PARAM_STR,
    'zona' => PDO::PARAM_STR,
    'active' => PDO::PARAM_BOOL,
    'deleted' => PDO::PARAM_BOOL,
  ];

  $arrInput = [
    'id' => $input['id'],
  ];
  $arrValue = [];
  foreach($allowed as $key => $value){
    if (isset($input[$key]) && $input[$key] !== '') {
      $arrInput[$key] = $input[$key];
      $arrValue[] = "$key = :$key";
    }
  }
  if(count($arrInput) === 0) {
    return $this->response->withJson(['error' => true, 'message' => 'Ningún parametro para actualizar.']);  
    }

  // build quey.
  $sql = 'UPDATE promociones SET ' . implode($arrValue, ', ') . ' WHERE id = :id LIMIT 1';
  $sth = $this->db->prepare($sql);
  try {
    $sth->execute($arrInput);
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }

  // guardamos ahora los inmuebles
  $sql = 'DELETE FROM `promociones_tipos_inmuebles` WHERE `promociones_id` = ' . (int) $input['id'];
  try {
    $this->db->query($sql)->execute();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
  
  if ($input['inmuebles']) {
    foreach($input['inmuebles'] as $inmuebleId => $cantidad) {
      $sth = $this->db->prepare('INSERT INTO promociones_tipos_inmuebles (promociones_id, tipos_inmuebles_id, cantidad) VALUES (:id, :tipos_inmuebles_id, :cantidad)');
      $sth->bindParam('id', $input['id'], PDO::PARAM_INT);
      $sth->bindParam('tipos_inmuebles_id', $inmuebleId, PDO::PARAM_INT);
      $sth->bindParam('cantidad', $cantidad, PDO::PARAM_INT);
      try {
        $sth->execute();
      } catch(Exception $e) {
        return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
      }
    }
  }
      
  return $this->response->withJson([
    'error' => false,
  ]);
};