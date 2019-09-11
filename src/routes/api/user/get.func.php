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
  $select = 'SELECT id, name, email, created_at, last_login, active, superuser FROM users WHERE deleted = 0 ';
  $count = 'SELECT count(*) FROM users WHERE deleted = 0 ';
  if ($id !== null && $id !== '') {
    $select .= "AND id = :id ";
    $count  .= 'AND id = ' . $id;
    $params[] = [ 'key' => 'id', 'var' => $id, 'code' => PDO::PARAM_INT ];
  }
  if ($query !== null && $query !== '') {
    $select .= 'AND ( name LIKE :query OR email LIKE :query) ';
    $count  .= 'AND ( name LIKE "%' . $query . '%" OR email LIKE "%' . $query . '%") ';
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
  $results = array_map(function ($result) {
    $result['active'] = (bool) $result['active'] == 1;
    $result['superuser'] = (bool) $result['superuser'] == 1;
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