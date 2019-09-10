<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
   
  $where  = [];
  $params = [];

  $orderBy = $request->getQueryParam('orderBy', 'createdAt');
  $desc = $request->getQueryParam('desc', 1) === 1
    ? 'DESC'
    : 'ASC';
  $year = $request->getQueryParam('year', null);

  if (!is_null($year)) {
    $where[]  = "YEAR(ventas.created_at) = :year ";
    $params['year'] = $year;
  }

  // detalles de las ventas
  $select = 'SELECT COUNT(*) AS `ventas`, MONTH(`ventas`.`created_at`) as `month`, '.
            'YEAR(`ventas`.`created_at`) as `year`, `ventas`.`tipos_inmuebles_id`, `promociones`.`id` AS `promocion_id` '.
            'FROM `ventas` '.
            'LEFT JOIN `promociones` ON `promociones`.`id` = `ventas`.`promociones_id` '.
            'WHERE `ventas`.`deleted` = 0 AND ' . ( count($where) ? join($where, ' AND ') : '1' ) . ' '.
            'AND `promociones`.`deleted` = 0 AND `promociones`.`home` = 1 '.
            'GROUP BY `promociones`.`id`, `month`, `year`, `ventas`.`tipos_inmuebles_id`';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $ventasByMonth = [];
  foreach($sth->fetchAll() as $row) {
    if (!isset($ventasByMonth[$row['promocion_id']])) {
      $ventasByMonth[$row['promocion_id']] = [];
    }
    $ventasByMonth[$row['promocion_id']][$row['year']][$row['month']][$row['tipos_inmuebles_id']] = (int) $row['ventas'];
  }

  // detalles ventas
  $select = 'SELECT COUNT(*) AS ventas, `promociones_id`, `tipos_inmuebles_id` '.
            'FROM `ventas` '.
            'WHERE `deleted` = 0 AND ' . ( count($where) ? join($where, ' AND ') : '1' ) . ' '.
            'GROUP BY `promociones_id`, `tipos_inmuebles_id`';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $ventas = [];
  foreach($sth->fetchAll() as $row) {
    if (!isset($ventas[$row['promociones_id']])) {
      $ventas[$row['promociones_id']] = [];
    }
    $ventas[$row['promociones_id']][$row['tipos_inmuebles_id']] = (int)$row['ventas'];
  }

  // detalles de las visitas
  $select = 'SELECT MONTH(`visitas`.`created_at`) as `month`, YEAR(`visitas`.`created_at`) as `year`, '.
            '`visitas`.`tipos_inmuebles_1` AS `tipos_inmuebles`, `visitas`.`promociones_id_1` AS `promocion_id` '.
            'FROM `visitas` '.
            'LEFT JOIN `promociones` ON `promociones`.`id` = `visitas`.`promociones_id_1` '.
            'WHERE `visitas`.`deleted` = 0 AND `promociones`.`deleted` = 0 AND `promociones`.`home` = 1';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $visitas = [];
  foreach($sth->fetchAll() as $result) {
    $year = $result['year'];
    $month = $result['month'];
    $promocion = $result['promocion_id'];
    $arr = $visitas[$promocion][$year][$month];
    if (!isset($arr)) {
      $arr = [];
    }
    foreach(unserialize($result['tipos_inmuebles']) as $k) {
      if (!isset($arr[$k])) {
        $arr[$k] = 0;
      }
      $arr[$k] += 1;
    }
    $visitas[$promocion][$year][$month] = $arr;
  }

  // detalles de promociones
  $select = 'SELECT id, name FROM promociones WHERE deleted = 0 AND home = 1';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $promociones = array_map(function ($result) {
    foreach(['id'] as $k) {
      $result[$k] = (int)$result[$k];
    }
    return $result;
  }, $sth->fetchAll());
  if (count($promociones) !== 0) {
    $ids = array_map(function ($result) {
      return $result['id'];
    }, $promociones);  
    $sql = 'SELECT p.id AS id, promociones_tipos_inmuebles.cantidad AS cantidad, tipos_inmuebles.id AS tipoId '.
          'FROM promociones AS p '.
          'JOIN promociones_tipos_inmuebles ON promociones_tipos_inmuebles.promociones_id = p.id '.
          'JOIN tipos_inmuebles ON promociones_tipos_inmuebles.tipos_inmuebles_id = tipos_inmuebles.id '.
          'WHERE p.id IN ('. implode(', ', $ids) .')';
    $inmuebles = [];
    foreach($this->db->query($sql)->fetchAll() as $inmueble) {
      $inmuebles[$inmueble['id']][$inmueble['tipoId']] = (int) $inmueble['cantidad'];
    }
    foreach($promociones as $key => $result) {
      $promociones[$key]['inmuebles'] = $inmuebles[$result['id']]
        ? $inmuebles[$result['id']]
        : (object) [];
    }
  }

  // detalles de tipos de inmueble
  $select = 'SELECT id, name FROM tipos_inmuebles WHERE deleted = 0';
  $sth = $this->db->prepare($select);
  $sth->execute($params);
  $tipos_inmuebles = array_map(function ($result) {
    foreach(['id'] as $k) {
      $result[$k] = (int)$result[$k];
    }
    return $result;
  }, $sth->fetchAll());
  //print_r();
  //die ();

  return $this->response->withJson([
    'error' => false,
    'data' => [
      'promociones' => $promociones,
      'tipos_inmuebles' => $tipos_inmuebles,
      'ventas' => $ventas,
      'ventas_by_month' => $ventasByMonth,
      'visitas' => $visitas,
    ],
  ]);
};