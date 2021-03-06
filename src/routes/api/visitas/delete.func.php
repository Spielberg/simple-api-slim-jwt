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

  // build quey.
  $sql = 'UPDATE visitas SET deleted = 1 WHERE id = :id LIMIT 1';
  $sth = $this->db->prepare($sql);
  try {
    $sth->execute(['id' => $args['id']]);
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
        
  return $this->response->withJson([
    'error' => false,
  ]);
};