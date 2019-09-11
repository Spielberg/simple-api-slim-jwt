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
  $id = $request->getQueryParam('id', null);
  $query = $request->getQueryParam('query', null);

  // get users detaills
  $params = [
    [ 'key' => 'limit', 'var' => $limit, 'code' => PDO::PARAM_INT ],
    [ 'key' => 'offset', 'var' => $offset * $limit, 'code' => PDO::PARAM_INT ],
  ];
  $select = 'SELECT id, name, created_at, active FROM tipos_inmuebles WHERE deleted = 0 ';
  $count = 'SELECT count(*) FROM tipos_inmuebles WHERE deleted = 0 ';
  if ($id !== null && $id !== '') {
    $select .= "AND id = :id ";
    $count  .= 'AND id = ' . $id;
    $params[] = [ 'key' => 'id', 'var' => $id, 'code' => PDO::PARAM_INT ];
  }
  if ($query !== null && $query !== '') {
    $select .= 'AND name LIKE :query ';
    $count  .= 'AND name LIKE "%' . $query . '%" ';
    $params[] = [ 'key' => 'query', 'var' => '%' . $query . '%', 'code' => PDO::PARAM_STR ];
  }
  $select .= 'ORDER BY sort LIMIT :limit OFFSET :offset';
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
    foreach(['name'] as $w) {
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