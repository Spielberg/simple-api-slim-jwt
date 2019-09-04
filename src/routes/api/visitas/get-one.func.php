<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
 
  $token = $request->getAttribute('decoded_token_data');

  // get settings array.
  $settings = $this->get('settings');  

  // get params or set default.
  $select = 'SELECT visitas.*, users.name AS comercial, promo1.name AS promo1, promo2.name AS promo2, '.
            'v.id as vID, v.promociones_id AS vPromoId, v.tipos_inmuebles_id AS vTipo '.
            'FROM visitas '.
            'JOIN users ON visitas.users_id = users.id '.
            'LEFT JOIN promociones AS promo1 ON visitas.promociones_id_1 = promo1.id '.
            'LEFT JOIN promociones AS promo2 ON visitas.promociones_id_2 = promo2.id '.
            'LEFT JOIN ventas AS v ON (v.visitas_id = visitas.id AND v.deleted = 0) '.
            'WHERE visitas.deleted = 0 AND visitas.id = :id LIMIT 1';
  $sth = $this->db->prepare($select);
  $sth->bindParam('id', $args['id'], PDO::PARAM_INT);
  try {
    $sth->execute();
    $data = $sth->fetch();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }

  foreach(['tipos_inmuebles_1', 'tipos_inmuebles_2'] as $w) {
    $data[$w] = unserialize($data[$w]);
  }

  // publicidad
  $data['publicidad'] = $data['contactado'] !== 'no';

  // prepare data after send
  foreach(['deleted'] as $k) {
    $data[$k] = $data[$k] === 1;
  };
  foreach(['id', 'promociones_id_1', 'promociones_id_2', 'users_id'] as $k) {
    $data[$k] = (int) $data[$k];
  };

  // ventas
  $data['venta'] = new stdClass();
  if ($data['vID']) {
    $data['venta'] = (object) [
      'id' => $data['vID'],
      'promocion' => $data['vPromoId'],
      'tipo' => $data['vTipo'],
      'deleted' => false,
    ];
  }
  unset($data['vID'], $data['vPromoId'], $data['vTipo']);
  
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