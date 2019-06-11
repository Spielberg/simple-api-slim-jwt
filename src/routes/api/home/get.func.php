<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
   
  $where  = [];
  $params = [];
  if (!is_null($request->getQueryParam('since', null))) {
    $where[]  = "AND visitas.created_at >= :since";
    $params['since'] = $request->getQueryParam('since', null);
  }
  if (!is_null($request->getQueryParam('until', null))) {
    $where[] = "AND visitas.created_at <= :until";
    $params['until'] = $request->getQueryParam('until', null);
  }

  // grafica por promociones
  $select = 'SELECT count(*) AS count, promociones.`name` '.
            'FROM visitas '. 
            'JOIN promociones ON visitas.`promociones_id_1` = promociones.id '. 
            'WHERE visitas.`deleted` = 0 ' . join($where, '') . ' GROUP BY visitas.`promociones_id_1`';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $promociones = array_map(function ($result) {
    return [$result['name'], $result['count']];
  }, $sth->fetchAll());
  
  // grafica por usuarios
  $select = 'SELECT count(*) AS count, users.`name` '.
            'FROM visitas '. 
            'JOIN users ON visitas.`users_id` = users.id '. 
            'WHERE visitas.`deleted` = 0 ' . join($where, '') . ' GROUP BY visitas.`users_id`';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $comerciales = array_map(function ($result) {
    return [$result['name'], $result['count']];
  }, $sth->fetchAll());

  // grafica por como nos conociste
  $select = 'SELECT count(*) AS count, conociste '.
  'FROM visitas '. 
  'WHERE visitas.`deleted` = 0 ' . join($where, '') . ' AND conociste <> "" GROUP BY visitas.`conociste`';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $conociste = array_map(function ($result) {
  return [$result['conociste'], $result['count']];
  }, $sth->fetchAll());

  return $this->response->withJson([
    'error' => false,
    'data' => [
      'comerciales' => $comerciales,
      'conociste' => $conociste,
      'promociones' => $promociones,
    ],
  ]);
};