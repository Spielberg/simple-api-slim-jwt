<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
   
  // get settings array.
  $settings = $this->get('settings');  

  // get params or set default.
  $limit = (int) $request->getQueryParam('limit', $settings['pagination']['limit']);
  $query = $request->getQueryParam('query', '');
  $query = "%$query%";

  // get users detaills
  $sql = 'SELECT name FROM tipos_inmuebles WHERE deleted = 0 AND name LIKE :query GROUP BY name ORDER BY name DESC LIMIT 0,:limit';
  $sth = $this->db->prepare($sql);
  $sth->bindParam('query', $query, PDO::PARAM_STR);
  $sth->bindParam('limit', $limit, PDO::PARAM_INT);
  try {
    $sth->execute();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
  $results = array_map(function ($result) {
    return $result['name'];
  }, $sth->fetchAll());
    
  return $this->response->withJson([
    'error' => false,
    'data' => $results,
  ]);
};