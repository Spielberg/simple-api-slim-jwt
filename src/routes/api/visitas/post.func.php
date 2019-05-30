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
    'promociones_id_1',
    'fecha_visita',
    'users_id',
  ] as $key){
    if (!isset($input[$key]) || $input[$key] === '') {
      return $this->response->withJson(['error' => true, 'message' => "Falta alguno de los parámetros obligatorios: $key."]);  
    }
  }

  // defaults
  $input['promociones_id_2'] = isset($input['promociones_id_2']) && $input['promociones_id_2'] !== '' && ctype_digit((string)$input['promociones_id_2'])
    ? $input['promociones_id_2']
    : null;
  $input['tipos_inmuebles_1'] = isset($input['tipos_inmuebles_1']) && $input['tipos_inmuebles_1'] !== '' 
    ? $input['tipos_inmuebles_1']
    : [];
  $input['tipos_inmuebles_2'] = isset($input['tipos_inmuebles_2']) && $input['tipos_inmuebles_2'] !== '' 
    ? $input['tipos_inmuebles_2']
    : [];

  $sql = 'INSERT INTO visitas (name, email, telefono, promociones_id_1, promociones_id_2, fecha_visita, conociste, status, publicidad, users_id, tipos_inmuebles_1, tipos_inmuebles_2) '.
         'VALUES (:name, :email, :telefono, :promociones_id_1, :promociones_id_2, :fecha_visita, :conociste, :status, :publicidad, :users_id, :tipos_inmuebles_1, :tipos_inmuebles_2)';
  $sth = $this->db->prepare($sql);
  $sth->bindParam('name', $input['name']);
  $sth->bindParam('email', $input['email']);
  $sth->bindParam('telefono', $input['telefono']);
  $sth->bindParam('promociones_id_1', $input['promociones_id_1'], PDO::PARAM_INT);
  $sth->bindParam('promociones_id_2', $input['promociones_id_2']);
  $sth->bindParam('fecha_visita', $input['fecha_visita']);
  $sth->bindParam('conociste', $input['conociste']);
  $sth->bindParam('status', $input['status']);
  $sth->bindParam('publicidad', $input['publicidad'], PDO::PARAM_INT);
  $sth->bindParam('users_id', $input['users_id'], PDO::PARAM_INT);
  $sth->bindParam('tipos_inmuebles_1', serialize($input['tipos_inmuebles_1']));
  $sth->bindParam('tipos_inmuebles_2', serialize($input['tipos_inmuebles_2']));
  try {
    $sth->execute();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
  
  $id = (int) $this->db->query('SELECT LAST_INSERT_ID()')->fetchColumn();
    
  // now save observaciones
  $sql = 'INSERT INTO observaciones (visitas_id, text) '.
         'VALUES (:visitas_id, :text)';
  $sth = $this->db->prepare($sql);
  $sth->bindParam('visitas_id', $id);
  $sth->bindParam('text', $input['observacion']);
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