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
    'tipos_inmuebles_id',
    'promociones_id',
    'visitas_id',
  ] as $key){
    if (!isset($input[$key]) || $input[$key] === '') {
      return $this->response->withJson(['error' => true, 'message' => "Falta alguno de los parámetros obligatorios: $key."]);  
    }
  }

  // insert reserva en la tabla de ventas
  $sql = 'INSERT INTO ventas (tipos_inmuebles_id, promociones_id, visitas_id, reserva) VALUES (:tipos_inmuebles_id, :promociones_id, :visitas_id, 1)';
  $sth = $this->db->prepare($sql);
  $sth->bindParam('tipos_inmuebles_id', $input['tipos_inmuebles_id']);
  $sth->bindParam('promociones_id', $input['promociones_id']);
  $sth->bindParam('visitas_id', $input['visitas_id']);
  try {
    $sth->execute();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
  
  $id = (int) $this->db->query('SELECT LAST_INSERT_ID()')->fetchColumn();

  // update visita para ponerla en stats de reserva
  $sql = 'UPDATE visitas SET status = "reserva" WHERE id = :visitas_id';
  $sth = $this->db->prepare($sql);
  $sth->bindParam('visitas_id', $input['visitas_id']);
  try {
    $sth->execute();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }

  return $this->response->withJson([
    'error' => false,
    'data' => [
      'id' => $id, 
    ],
  ]);
};