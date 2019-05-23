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

  // get users detaills
  $sql = 'SELECT id, name, email, created_at, last_login, active, superuser FROM users WHERE deleted = 0 LIMIT :limit OFFSET :offset';
  $sth = $this->db->prepare($sql);
  $sth->bindParam('limit', $limit, PDO::PARAM_INT);
  $sth->bindParam('offset', $offset, PDO::PARAM_INT);
  try {
    $sth->execute();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
  $total = (int) $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();
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