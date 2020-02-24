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

  $id = $args['id']
    ? (int) $args['id']
    : null;

  // default selet query
  $select = 'SELECT id, name, created_at, status FROM screenshots WHERE deleted = 0 ';

  // only if id is present
  if (!is_null($id) && ctype_digit((string)$id)) {
    $select .= "AND id = $id ";
  }

  // order
  $select .= 'ORDER BY created_at DESC';
  $sth = $this->db->prepare($select);
  $sth->execute();

  $results = array_map(function ($result) {
    $result['id'] = (int) $result['id'];
    $result['status'] = unserialize($result['status']);
    return $result;
  }, $sth->fetchAll());

  return $this->response->withJson([
    'error' => false,
    'data' => $results,
  ]);
};