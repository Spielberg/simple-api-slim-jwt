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
    'name',
    'apellido_1',
    'apellido_2',
    'email',
    'telefono',
    'promociones_id_1',
    'promociones_id_2',
    'tipos_inmuebles_1',
    'tipos_inmuebles_2',
    'fecha_visita',
    'conociste',
    'status',
    'publicidad',
    'users_id',
  ];

  // default promocion 2
  $arrInput = [
    'id' => $input['id'],
    'promociones_id_2' => 
      isset($input['promociones_id_2'])
      && $input['promociones_id_2'] !== ''
      && ctype_digit((string)$input['promociones_id_2'])
        ? $input['promociones_id_2']
        : null,
    'tipos_inmuebles_1' => 
      isset($input['tipos_inmuebles_1'])
      && $input['tipos_inmuebles_1'] !== ''
        ? $input['tipos_inmuebles_1']
        : [],
    'tipos_inmuebles_2' => 
      isset($input['tipos_inmuebles_2'])
      && $input['tipos_inmuebles_2'] !== ''
        ? $input['tipos_inmuebles_2']
        : []
  ];
  $arrValue = [];
  foreach($allowed as $key){
    if (isset($input[$key]) && $input[$key] !== '') {
      $arrInput[$key] = $key === 'tipos_inmuebles_1' || $key === 'tipos_inmuebles_2'
        ? serialize($input[$key])
        : $input[$key];
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

  // now save observaciones
  if ($input['observacion'] !== '') {
    $sql = 'INSERT INTO observaciones (visitas_id, text) '.
          'VALUES (:visitas_id, :text)';
    $sth = $this->db->prepare($sql);
    $sth->bindParam('visitas_id', $input['id']);
    $sth->bindParam('text', $input['observacion']);
    try {
      $sth->execute();
    } catch(Exception $e) {
      return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
    }
  }
        
  return $this->response->withJson([
    'error' => false,
    'data' => [
      'id' => $input['id'], 
    ],
  ]);
};