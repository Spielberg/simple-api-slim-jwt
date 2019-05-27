<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
 
  $token = $request->getAttribute('decoded_token_data');

  // get settings array.
  $settings = $this->get('settings');  

  // validation.
  $input = $request->getParsedBody();
  foreach([
    'name',
    'email',
    'telefono',
    'promociones_id',
    'fecha_visita',
    'users_id',
  ] as $key){
    if (!isset($input[$key]) || $input[$key] === '') {
      return $this->response->withJson(['error' => true, 'message' => "Falta alguno de los parámetros obligatorios: $key."]);  
    }
  }

  $sql = 'INSERT INTO visitas (name, email, telefono, promociones_id, observaciones, fecha_visita, conociste, status, users_id) '.
         'VALUES (:name, :email, :telefono, :promociones_id, :observaciones, :fecha_visita, :conociste, :status, :users_id)';
  $sth = $this->db->prepare($sql);
  $sth->bindParam('name', $input['name']);
  $sth->bindParam('email', $input['email']);
  $sth->bindParam('telefono', $input['telefono']);
  $sth->bindParam('promociones_id', $input['promociones_id'], PDO::PARAM_INT);
  $sth->bindParam('observaciones', $input['observaciones']);
  $sth->bindParam('fecha_visita', $input['fecha_visita']);
  $sth->bindParam('conociste', $input['conociste']);
  $sth->bindParam('status', $input['status']);
  $sth->bindParam('publicidad', $input['publicidad'], PDO::PARAM_INT);
  $sth->bindParam('users_id', $input['users_id'], PDO::PARAM_INT);
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