<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
 
  $token = $request->getAttribute('decoded_token_data');

  // el id del user es obligatorio.
  $input = $request->getParsedBody();
  if (!isset($args['id']) || $args['id'] === '' || !ctype_digit((string)$args['id'])) {
    return $this->response->withJson(['error' => true, 'message' => 'Falta alguno de los parámetros obligatorios: id.']);
  }

  // verify is superuser.
  if(!$token['superuser']) {
    return $this->response->withJson(['error' => true, 'message' => 'No tienes suficientes permisos para hacer esta llamada.']);  
    }

  // build quey.
  $sql = 'UPDATE ventas SET reserva = 0 WHERE id = :id LIMIT 1';
  $sth = $this->db->prepare($sql);
  try {
    $sth->execute([
      'id' => $args['id'],
    ]);
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }

  // update visita para ponerla en stats de reserva
  if ($input['visita_id']) {
    $sql = 'UPDATE visitas SET status = "compra" WHERE id = :visita_id';
    $sth = $this->db->prepare($sql);
    try {
      $sth->execute([
        'visita_id' => $input['visita_id'],
      ]);
    } catch(Exception $e) {
      return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
    }
  }
      
  return $this->response->withJson([
    'error' => false,
  ]);
};