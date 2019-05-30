<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
 
  $token = $request->getAttribute('decoded_token_data');

  // get settings array.
  $settings = $this->get('settings');  

  // get params or set default.
  $limit = (int) $request->getQueryParam('limit', $settings['pagination']['limit']);
  $offset = (int) $request->getQueryParam('offset', 0);
  $id = $request->getQueryParam('id', null);
  $query = $request->getQueryParam('query', null);
  $telefono = $request->getQueryParam('telefono', null);
  $promocion = (int) $request->getQueryParam('promocion', 0);

  // get visitas detaills
  $params = [
    [ 'key' => 'limit', 'var' => $limit, 'code' => PDO::PARAM_INT ],
    [ 'key' => 'offset', 'var' => (int)$offset * (int)$limit, 'code' => PDO::PARAM_INT ],
  ];
  $select = 'SELECT visitas.*, users.name AS comercial, promo1.name AS promo1, promo2.name AS promo2 '.
            'FROM visitas '.
            'JOIN users ON visitas.users_id = users.id '.
            'JOIN promociones AS promo1 ON visitas.promociones_id_1 = promo1.id '.
            'LEFT JOIN promociones AS promo2 ON visitas.promociones_id_2 = promo2.id '.
            'WHERE visitas.deleted = 0 ';
  $count = 'SELECT count(*) FROM visitas WHERE deleted = 0 ';
  if ($id !== null && $id !== '') {
    $select .= "AND visitas.id = :id ";
    $count  .= 'AND visitas.id = ' . $id;
    $params[] = [ 'key' => 'id', 'var' => $id, 'code' => PDO::PARAM_INT ];
  }
  if ($query !== null && $query !== '') {
    $select .= 'AND ( visitas.name LIKE :query OR visitas.email LIKE :query OR visitas.telefono LIKE :query) ';
    $count  .= 'AND ( visitas.name LIKE "%' . $query . '%" OR visitas.email LIKE "%' . $query . '%" OR visitas.telefono LIKE "%' . $query . '%") ';
    $params[] = [ 'key' => 'query', 'var' => '%' . $query . '%', 'code' => PDO::PARAM_STR ];
  }
  if ($telefono !== null && $telefono !== '') {
    $select .= 'AND visitas.telefono LIKE :telefono ';
    $count  .= 'AND visitas.telefono LIKE "%' . $telefono . '%" ';
    $params[] = [ 'key' => 'telefono', 'var' => '%' . $telefono . '%', 'code' => PDO::PARAM_STR ];
  }
  if ($promocion !== 0) {
    $select .= "AND (visitas.promociones_id_1 = :promocion OR visitas.promociones_id_2 = :promocion) ";
    $count  .= 'AND (visitas.promociones_id_1 = ' . $promocion . ' OR visitas.promociones_id_2 = ' . $promocion . ') ';
    $params[] = [ 'key' => 'promocion', 'var' => $promocion, 'code' => PDO::PARAM_INT ];
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
    $result['id'] = (int) $result['id'];
    foreach(['name', 'promo1', 'promo1'] as $w) {
      $result[$w] = utf8_encode($result[$w]);
    }
    return $result;
  }, $sth->fetchAll());

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