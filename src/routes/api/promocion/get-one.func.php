<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
 
  $token = $request->getAttribute('decoded_token_data');

  // get settings array.
  $settings = $this->get('settings');  

  // get params or set default.
  $select = 'SELECT `id`, `name`, `zona`, `created_at`, `active` '.
            'FROM promociones '.
            'WHERE promociones.deleted = 0 AND promociones.id = :id LIMIT 1';
  $sth = $this->db->prepare($select);
  $sth->bindParam('id', $args['id'], PDO::PARAM_INT);
  try {
    $sth->execute();
    $data = $sth->fetch();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }

  foreach(['name', 'zona'] as $k) {
    $data[$k] = utf8_encode($data[$k]);
  }

  // prepare data after send
  foreach(['active'] as $k) {
    $data[$k] = $data[$k] === 1;
  };
  
  // get observaciones
  $select = 'SELECT p.id AS id, tipos_inmuebles.name AS name, tipos_inmuebles.id AS tipoId '.
            'FROM promociones AS p '.
            'JOIN promociones_tipos_inmuebles ON promociones_tipos_inmuebles.promociones_id = p.id '.
            'JOIN tipos_inmuebles ON promociones_tipos_inmuebles.tipos_inmuebles_id = tipos_inmuebles.id '.
            'WHERE p.id = :id';
  $sth = $this->db->prepare($select);
  $sth->bindParam('id', $args['id'], PDO::PARAM_INT);
  $sth->execute();
  $inmuebles = [];
  foreach($sth->fetchAll() as $inmueble) {
    $data['inmuebles'][] = ['id' => (int) $inmueble['tipoId'], 'name' => utf8_encode($inmueble['name'])];
  }

  return $this->response->withJson([
    'error' => false,
    'data' => $data, 
  ]);
};