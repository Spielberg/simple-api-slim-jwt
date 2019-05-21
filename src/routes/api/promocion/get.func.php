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

  // get users detaills
  $sql = 'SELECT id, name, zona, created_at, active FROM promociones WHERE deleted = 0 LIMIT :limit OFFSET :offset';
  $sth = $this->db->prepare($sql);
  $sth->bindParam('limit', $limit, PDO::PARAM_INT);
  $sth->bindParam('offset', $offset, PDO::PARAM_INT);
  try {
    $sth->execute();
    $total = (int) $this->db->query('SELECT count(*) FROM promociones WHERE deleted = 0')->fetchColumn();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
  $results = array_map(function ($result) {
    $result['active'] = (bool) $result['active'] == 1;
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