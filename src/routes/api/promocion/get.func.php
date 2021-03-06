<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
   
  // get settings array.
  $settings = $this->get('settings');  

  // get params or set default.
  $limit = (int) $request->getQueryParam('limit', $settings['pagination']['limit']);
  $offset = (int) $request->getQueryParam('offset', 0);
  $home = (int) $request->getQueryParam('home', 0);
  $id = $request->getQueryParam('id', null);
  $query = $request->getQueryParam('query', null);
  $telefono = $request->getQueryParam('telefono', null);
  $active = (int) $request->getQueryParam('active', 0);

  // get promociones detaills
  $params = [
    [ 'key' => 'limit', 'var' => $limit, 'code' => PDO::PARAM_INT ],
    [ 'key' => 'offset', 'var' => $offset * $limit, 'code' => PDO::PARAM_INT ],
  ];
  $select = 'SELECT id, name, zona, created_at, active, home FROM promociones WHERE deleted = 0 ';
  $count = 'SELECT count(*) FROM promociones WHERE deleted = 0 ';
  if ($id !== null && $id !== '') {
    $select .= "AND id = :id ";
    $count  .= 'AND id = ' . $id;
    $params[] = [ 'key' => 'id', 'var' => $id, 'code' => PDO::PARAM_INT ];
  }
  if ($query !== null && $query !== '') {
    $select .= 'AND ( name LIKE :query OR zona LIKE :query) ';
    $count  .= 'AND ( name LIKE "%' . $query . '%" OR zona LIKE "%' . $query . '%") ';
    $params[] = [ 'key' => 'query', 'var' => '%' . $query . '%', 'code' => PDO::PARAM_STR ];
  }
  if ($telefono !== null && $telefono !== '') {
    $select .= 'AND telefono LIKE :telefono ';
    $count  .= 'AND telefono LIKE "%' . $telefono . '%") ';
    $params[] = [ 'key' => 'telefono', 'var' => '%' . $telefono . '%', 'code' => PDO::PARAM_STR ];
  }
  if ($home !== 0) {
    $select .= 'AND home = 1 ';
    $count  .= 'AND home = 1 ';
  }
  if ($active !== 0) {
    $select .= 'AND active = 1 ';
    $count  .= 'AND active = 1 ';
  }
  $select .= 'ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
  $sth = $this->db->prepare($select);
  foreach($params as $obj) {
    $sth->bindParam($obj['key'], $obj['var'], $obj['code']);
  }
  try {
    $sth->execute();
    $total = (int) $this->db->query($count)->fetchColumn();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
  $results = array_map(function ($result) {
    $result['active'] = (bool) $result['active'] == 1;
    $result['home'] = (bool) $result['home'] == 1;
    $result['id'] = (int) $result['id'];
    return $result;
  }, $sth->fetchAll());

  // obtener los inmuebles asociados
  if (count($results) !== 0) {
    $ids = array_map(function ($result) {
      return $result['id'];
    }, $results);  
    $sql = 'SELECT p.id AS id, tipos_inmuebles.name AS name, promociones_tipos_inmuebles.cantidad AS cantidad, tipos_inmuebles.id AS tipoId '.
          'FROM promociones AS p '.
          'JOIN promociones_tipos_inmuebles ON promociones_tipos_inmuebles.promociones_id = p.id '.
          'JOIN tipos_inmuebles ON promociones_tipos_inmuebles.tipos_inmuebles_id = tipos_inmuebles.id '.
          'WHERE p.id IN ('. implode(', ', $ids) .')';
    $inmuebles = [];
    foreach($this->db->query($sql)->fetchAll() as $inmueble) {
      $inmuebles[$inmueble['id']][] = ['id' => (int) $inmueble['tipoId'], 'name' => $inmueble['name'], 'cantidad' => (int) $inmueble['cantidad']];
    }
    foreach($results as $key => $result) {
      $results[$key]['inmuebles'] = !is_null($inmuebles[$result['id']])
        ? $inmuebles[$result['id']]
        : [];
    }
  }
  
  // obtener los inmuebles asociados
  if (count($results) !== 0) {
    $ids = array_map(function ($result) {
      return $result['id'];
    }, $results);  
    $sql = 'SELECT p.id AS id, tipos_inmuebles.name AS name, promociones_historico.type AS type, promociones_historico.cantidad AS cantidad, tipos_inmuebles.id AS tipoId '.
          'FROM promociones AS p '.
          'JOIN promociones_historico ON promociones_historico.promociones_id = p.id '.
          'JOIN tipos_inmuebles ON promociones_historico.tipos_inmuebles_id = tipos_inmuebles.id '.
          'WHERE p.id IN ('. implode(', ', $ids) .')';
    $historico = [];
    foreach($this->db->query($sql)->fetchAll() as $inmueble) {
      if (!isset($historico[$inmueble['id']][$inmueble['type']])) {
        $historico[$inmueble['id']][$inmueble['type']] = [];
      }
      $historico[$inmueble['id']][$inmueble['type']][] = ['id' => (int) $inmueble['tipoId'], 'name' => $inmueble['name'], 'cantidad' => (int) $inmueble['cantidad']];
    }
    foreach($results as $key => $result) {
      $results[$key]['historico'] = !is_null($historico[$result['id']])
        ? $historico[$result['id']]
        : [];
    }
  }

  return $this->response->withJson([
    'error' => false,
    'data' => [
      'results' => $results, 
      'pagination' => [ 
        'total' => $total, 
        'limit' => $limit, 
        'offset' => $offset,
      ],
    ],
  ]);
};