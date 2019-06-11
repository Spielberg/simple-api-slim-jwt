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
  $promocionId = $request->getQueryParam('promocionId', null);
  $query = $request->getQueryParam('query', null);

  // get users detaills
  $params = [
    [ 'key' => 'limit', 'var' => $limit, 'code' => PDO::PARAM_INT ],
    [ 'key' => 'offset', 'var' => $offset, 'code' => PDO::PARAM_INT ],
  ];
  $select = 'SELECT v.id, v.created_at AS created_at, p.`name` AS promocion, ti.`name` AS inmueble, vi.`name`, vi.`apellido_1`, vi.`apellido_2` '.
            'FROM ventas AS v '.
            'JOIN promociones_tipos_inmuebles AS pti ON pti.`id` = v.`promociones_tipos_inmuebles` '.
            'JOIN promociones AS p ON p.`id` = pti.`promociones_id` '.
            'JOIN `tipos_inmuebles` AS ti ON ti.id = pti.`tipos_inmuebles_id` '.
            'JOIN visitas AS vi ON vi.`id` = v.`visitas_id` '.
            'WHERE v.deleted = 0 ';
  $count = 'SELECT count(*) FROM ventas AS v WHERE v.deleted = 0 ';
  if ($promocionId !== null && $promocionId !== '') {
    $select .= "AND pti.`promociones_id` = :promocionId ";
    $count  .= 'AND pti.`promociones_id` = ' . $promocionId;
    $params[] = [ 'key' => 'promocionId', 'var' => $promocionId, 'code' => PDO::PARAM_INT ];
  }
  if ($query !== null && $query !== '') {
    $select .= 'AND ( p.`name` LIKE :query OR ti.`name` LIKE :query OR vi.`name` LIKE :query) ';
    $count  .= 'AND ( p.`name` LIKE "%' . $query . '%" OR ti.`name` LIKE "%' . $query . '%" OR vi.`name` LIKE "%' . $query .'%") ';
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
    $result['id'] = (int) $result['id'];
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