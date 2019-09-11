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
  
  // grafica por comerciale
  /*
  $select = 'SELECT count(*) AS count, users.`name` '.
            'FROM visitas '. 
            'JOIN users ON visitas.`users_id` = users.id '.
            'JOIN promociones ON visitas.`promociones_id_1` = promociones.id '.  
            'WHERE visitas.`deleted` = 0 AND promociones.home = 1 ' . join($where, '') . ' GROUP BY visitas.`users_id`';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $comerciales = array_map(function ($result) {
    return [$result['name'], $result['count']];
  }, $sth->fetchAll());
  */

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
  if (!is_null($request->getQueryParam('promocionId', null))) {
    $select = 'SELECT COUNT(*) AS `reservadas`, '.
                '(SELECT SUM(`cantidad`) FROM `promociones_tipos_inmuebles` WHERE `promociones_id` = :promocionId) AS `totales`, '.
                '(SELECT COUNT(*) FROM `ventas` WHERE `deleted` = 0 AND `promociones_id` = :promocionId) AS `vendidas` '.
              'FROM `visitas` '.
              'WHERE `status` = "reserva" AND `promociones_id_1` = :promocionId ';
  } else {
    $select = 'SELECT COUNT(*) AS `reservadas`, '.
	              '(SELECT SUM(`cantidad`) FROM `promociones_tipos_inmuebles`) AS `totales`, '.
	              '(SELECT COUNT(*) FROM `ventas` WHERE `deleted` = 0) AS `vendidas` '.
              'FROM `visitas` '.
              'WHERE `status` = "reserva"';
  }
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $count = (array) $sth->fetchObject();
  $counts = [];
  $counts = [
    ['reservadas', (int) $count['reservadas']],
    //['totales', (int) $count['totales']],
    ['vendidas', (int) $count['vendidas']],
    ['libres', (int) $count['totales'] - (int) $count['reservadas'] -(int) $count['vendidas']],
  ];

  // gráfica ventas
  /*
  $select = 'SELECT p.id AS pid, p.name AS pname, pti.`tipos_inmuebles_id`, pti.`cantidad`, ti.`name` AS tiname, COUNT(*) AS vendidas '.
            'FROM ventas AS v '.
            'LEFT JOIN promociones_tipos_inmuebles AS pti ON ( pti.promociones_id = v.promociones_id AND pti.tipos_inmuebles_id = v.tipos_inmuebles_id ) '.
            'LEFT JOIN promociones AS p ON p.id = pti.`promociones_id` '.
            'LEFT JOIN tipos_inmuebles AS ti ON ti.id = pti.tipos_inmuebles_id '.
            'WHERE p.`active` = 1 AND p.`home` = 1 AND v.`deleted` = 0 GROUP BY pti.promociones_id, pti.tipos_inmuebles_id';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $ventas = [];
  foreach($sth->fetchAll() as $row) {
    if (!isset($ventas[$row['pid']])) {
      $ventas[$row['pid']] = [
        'id' => (int) $row['pid'],
        'pname' => $row['pname'],
      ];
    }
    $ventas[$row['pid']]['inmuebles'][$row['tipos_inmuebles_id']] = [
      'id' => (int) $row['tipos_inmuebles_id'],
      'cantidad' => (int) $row['cantidad'],
      'vendidas' => (int) $row['vendidas'],
      'name' => $row['tiname'],
    ];   
  }
  $ventas = array_map(function ($venta) {
    $venta['inmuebles'] = array_values($venta['inmuebles']);
    return $venta;
  }, $ventas);
  $ventas = array_values($ventas);
  */

  return $this->response->withJson([
    'error' => false,
    'data' => [
      //'comerciales' => $comerciales,
      'counts' => $counts,
      'conociste' => $conociste,
      'promociones' => $promociones,
      //'ventas' => $ventas,
    ],
  ]);
};