<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
   
  $where  = [];
  $params = [];
  if (!is_null($request->getQueryParam('since', null))) {
    $where[]  = "AND visitas.created_at >= :since ";
    $params['since'] = $request->getQueryParam('since', null);
  }
  if (!is_null($request->getQueryParam('until', null))) {
    $where[] = "AND visitas.created_at <= :until ";
    $params['until'] = $request->getQueryParam('until', null);
  }
  if (!is_null($request->getQueryParam('promocionId', null))) {
    $where[]  = "AND visitas.promociones_id_1 = :promocionId ";
    $params['promocionId'] = $request->getQueryParam('promocionId', null);
  }

  // grafica por promociones
  $select = 'SELECT count(*) AS count, promociones.`name` '.
            'FROM visitas '. 
            'JOIN promociones ON visitas.`promociones_id_1` = promociones.id '. 
            'WHERE visitas.`deleted` = 0 AND promociones.home = 1 ' . join($where, '') . ' GROUP BY visitas.`promociones_id_1`';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $promociones = array_map(function ($result) {
    return [$result['name'], $result['count']];
  }, $sth->fetchAll());
  
  // grafica por como nos conociste
  $select = 'SELECT count(*) AS count, conociste '.
            'FROM visitas '. 
            'JOIN promociones ON visitas.`promociones_id_1` = promociones.id '.  
            'WHERE visitas.`deleted` = 0 AND promociones.home = 1 ' . join($where, '') . ' AND conociste <> "" GROUP BY visitas.`conociste`';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $conociste = array_map(function ($result) {
  return [$result['conociste'], $result['count']];
  }, $sth->fetchAll());

  // grafica resumen de ventas
  if (!is_null($params['promocionId'])) {
    $select = 'SELECT '.
              '(SELECT IFNULL(SUM(`cantidad`), 0) '.
              'FROM `promociones_tipos_inmuebles`) AS `totales`, '.
              '(SELECT COUNT(*) '.
              'FROM `ventas` '.
              'WHERE `deleted` = 0 '.
                'AND `reserva` = 0 '.
                'AND `promociones_id` = :promocionId '.
                'AND `updated_at` >= :since '.
                'AND `updated_at` <= :until ) AS `vendidas`, '.
              '(SELECT COUNT(*) '.
              'FROM `ventas` '.
              'WHERE `deleted` = 0 '.
                'AND `reserva` = 1 '.
                'AND `promociones_id` = :promocionId ) AS `reservadas`, '.  
              '(SELECT IFNULL(SUM(`cantidad`), 0) '.
              'FROM `promociones_historico` '.
              'WHERE `type` = "venta") AS `historico` '.
            'FROM `visitas` '.
            'WHERE `promociones_id_1` = :promocionId '.
            'GROUP BY `totales`';
  } else {
    $select = 'SELECT '.
              '(SELECT IFNULL(SUM(`cantidad`), 0) '.
              'FROM `promociones_tipos_inmuebles`) AS `totales`, '.
              '(SELECT COUNT(*) '.
              'FROM `ventas` '.
              'WHERE `deleted` = 0 '.
                'AND `reserva` = 0 '.
                'AND `updated_at` >= :since '.
                'AND `updated_at` <= :until ) AS `vendidas`, '.
              '(SELECT COUNT(*) '.
              'FROM `ventas` '.
              'WHERE `deleted` = 0 '.
                'AND `reserva` = 1 ) AS `reservadas`, '.  
              '(SELECT IFNULL(SUM(`cantidad`), 0) '.
              'FROM `promociones_historico` '.
              'WHERE `type` = "venta") AS `historico` '.
            'FROM `visitas` '.
            'GROUP BY `totales`';
  }
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $count = (array) $sth->fetchObject();
  $counts = [];
  $counts = (object)[
    'vendidas' => (int) $count['vendidas'],
    'reservadas' => (int) $count['reservadas'],
    'historico' => (int) $count['historico'],
    'totales' => (int) $count['totales'],
  ];

  return $this->response->withJson([
    'error' => false,
    'data' => [
      'counts' => $counts,
      'conociste' => $conociste,
      'promociones' => $promociones,
    ],
  ]);
};