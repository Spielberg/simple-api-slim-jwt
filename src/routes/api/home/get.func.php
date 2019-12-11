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

  // gràfica resumen de ventas
  $selectParams = [];
  if (!is_null($request->getQueryParam('promocionId', null))) {
    $select = 'SELECT '.
                '(SELECT SUM(`cantidad`) FROM `promociones_tipos_inmuebles` WHERE `promociones_id` = :promocionId) AS `totales`, '.
                '( '.
	                '(SELECT COUNT(*) FROM `ventas` WHERE `deleted` = 0 AND `promociones_id` = :promocionId AND reserva = 1) + '.
	                '(SELECT IFNULL(SUM(`cantidad`), 0) FROM `promociones_historico` WHERE `type` = "reserva" AND `promociones_id` = :promocionId) '.
                ') AS `reservadas`, '.
                '( '.
	                '(SELECT COUNT(*) FROM `ventas` WHERE `deleted` = 0 AND `promociones_id` = :promocionId AND reserva = 0) + '.
	                '(SELECT IFNULL(SUM(`cantidad`), 0) FROM `promociones_historico` WHERE `type` = "venta" AND `promociones_id` = :promocionId) '.
                ') AS `vendidas` '.
                'FROM `visitas` WHERE `promociones_id_1` = :promocionId  GROUP BY `totales`';
    $selectParams['promocionId'] = $request->getQueryParam('promocionId', null);
  } else {
    $select = 'SELECT '.
                '(SELECT IFNULL(SUM(`cantidad`), 0) FROM `promociones_tipos_inmuebles`) AS `totales`, '.
                '( '.
                  '(SELECT COUNT(*) FROM `ventas` WHERE `deleted` = 0 AND `reserva` = 1) + '.
                  '(SELECT IFNULL(SUM(`cantidad`), 0) FROM `promociones_historico` WHERE `type` = "reserva") '.
                ') AS `reservadas`, '.
                '( '.
                  '(SELECT COUNT(*) FROM `ventas` WHERE `deleted` = 0 AND `reserva` = 0) + '.
                  '(SELECT IFNULL(SUM(`cantidad`), 0) FROM `promociones_historico` WHERE `type` = "venta") '.
                ') AS `vendidas` '.
                'FROM `visitas` GROUP BY `totales`';
  }
  $sth = $this->db->prepare($select);
  $sth->execute($selectParams);
  $count = (array) $sth->fetchObject();
  $counts = [];
  $counts = [
    ['reservadas', (int) $count['reservadas']],
    ['vendidas', (int) $count['vendidas']],
    ['libres', (int) $count['totales'] - (int) $count['reservadas'] -(int) $count['vendidas']],
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