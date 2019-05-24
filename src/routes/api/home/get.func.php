<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
   
  // grafica por promociones
  $select = 'SELECT count(*) AS count, promociones.`name` '.
            'FROM visitas '. 
            'JOIN promociones ON visitas.`promociones_id` = promociones.id '. 
            'WHERE visitas.`deleted` = 0 GROUP BY visitas.`promociones_id`';
  $sth = $this->db->prepare($select);
  $sth->execute();
  $promociones = array_map(function ($result) {
    return [$result['name'], $result['count']];
  }, $sth->fetchAll());
  
    // grafica por usuarios
  $select = 'SELECT count(*) AS count, users.`name` '.
            'FROM visitas '. 
            'JOIN users ON visitas.`users_id` = users.id '. 
            'WHERE visitas.`deleted` = 0 GROUP BY visitas.`users_id`';
  $sth = $this->db->prepare($select);
  $sth->execute();
  $comerciales = array_map(function ($result) {
    return [$result['name'], $result['count']];
  }, $sth->fetchAll());
  

  return $this->response->withJson([
    'error' => false,
    'data' => [
      'comerciales' => $comerciales,
      'promociones' => $promociones,
    ],
  ]);
};