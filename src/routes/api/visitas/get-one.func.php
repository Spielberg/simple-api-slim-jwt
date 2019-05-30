<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
 
  $token = $request->getAttribute('decoded_token_data');

  // get settings array.
  $settings = $this->get('settings');  

  // get params or set default.
  $select = 'SELECT visitas.*, users.name AS comercial, promo1.name AS promo1, promo2.name AS promo2 '.
            'FROM visitas '.
            'JOIN users ON visitas.users_id = users.id '.
            'JOIN promociones AS promo1 ON visitas.promociones_id_1 = promo1.id '.
            'JOIN promociones AS promo2 ON visitas.promociones_id_2 = promo2.id '.
            'WHERE visitas.deleted = 0 AND visitas.id = :id LIMIT 1';
  $sth = $this->db->prepare($select);
  $sth->bindParam('id', $args['id'], PDO::PARAM_INT);
  try {
    $sth->execute();
    $data = $sth->fetch();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }

  foreach(['comercial', 'name', 'promo1', 'promo1'] as $k) {
    $data[$k] = utf8_encode($data[$k]);
  }

  // prepare data after send
  foreach(['deleted', 'publicidad'] as $k) {
    $data[$k] = $data[$k] === 1;
  };
  foreach(['id', 'promociones_id_1', 'promociones_id_2', 'users_id'] as $k) {
    $data[$k] = (int) $data[$k];
  };
  
  // get observaciones
  $select = 'SELECT created_at, text FROM observaciones WHERE deleted = 0 AND visitas_id = :id ORDER BY created_at DESC';
  $sth = $this->db->prepare($select);
  $sth->bindParam('id', $args['id'], PDO::PARAM_INT);
  $sth->execute();
  $data['observaciones'] = $sth->fetchAll();

  return $this->response->withJson([
    'error' => false,
    'data' => $data, 
  ]);
};