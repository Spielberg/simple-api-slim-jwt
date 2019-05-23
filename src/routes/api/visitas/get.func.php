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

  // get visitas detaills
  $params = [
    [ 'key' => 'limit', 'var' => $limit, 'code' => PDO::PARAM_INT ],
    [ 'key' => 'offset', 'var' => $offset, 'code' => PDO::PARAM_INT ],
  ];
  $select = 'SELECT visitas.*, users.name AS comercial, promociones.name AS promocion '.
            'FROM visitas '.
            'JOIN users ON visitas.users_id = users.id '.
            'JOIN promociones ON visitas.promociones_id = promociones.id '.
            'WHERE visitas.deleted = 0 ';
  $count = 'SELECT count(*) FROM visitas WHERE deleted = 0 ';
  if ($id !== null && $id !== '') {
    $select .= "AND id = :id ";
    $count  .= 'AND id = ' . $id;
    $params[] = [ 'key' => 'id', 'var' => $id, 'code' => PDO::PARAM_INT ];
  }
  if ($query !== null && $query !== '') {
    $select .= 'AND ( visitas.name LIKE :query OR visitas.email LIKE :query OR visitas.telefono LIKE :query OR visitas.observaciones LIKE :query) ';
    $count  .= 'AND ( visitas.name LIKE "%' . $query . '%" OR visitas.email LIKE "%' . $query . '%" OR visitas.telefono LIKE "%' . $query . '%" OR visitas.observaciones LIKE "%' . $query . '%") ';
    $params[] = [ 'key' => 'query', 'var' => '%' . $query . '%', 'code' => PDO::PARAM_STR ];
  }
  $select .= 'LIMIT :limit OFFSET :offset';
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
  
  return $this->response->withJson([
    'error' => false,
    'data' => [
      'results' => $sth->fetchAll(), 
      'pagination' => [ 
        'total' => $total, 
        'limit' => $limit, 
        'offset' => $offset,
      ],
    ],
  ]);
};