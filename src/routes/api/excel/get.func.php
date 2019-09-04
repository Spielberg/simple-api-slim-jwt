<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

return function (Request $request, Response $response, array $args) {
  $token = $request->getAttribute('decoded_token_data');

  // get settings array.
  $settings = $this->get('settings');  

  // get params or set default.
  $id = $request->getQueryParam('id', null);
  $query = $request->getQueryParam('query', null);
  $telefono = $request->getQueryParam('telefono', null);
  $promocion = (int) $request->getQueryParam('promocion', 0);
  $status = $request->getQueryParam('status', null);

  // get tipos de inmuebles
  $select = 'SELECT id, name FROM tipos_inmuebles';
  $sth = $this->db->prepare($select);
  $sth->execute();
  $tipos_inmuebles = [];
  foreach($sth->fetchAll() as $column) {
    $tipos_inmuebles[$column['id']] = $column['name'];
  }

  // get visitas detaills
  $params = [];
  $select = 'SELECT visitas.id AS id, '.
                  'visitas.name AS nombre, '.
                  'visitas.apellido_1 AS `primer apellido`, '.
                  'visitas.apellido_2 AS `segundo apellido`, '.
                  'visitas.email, '.
                  'visitas.telefono AS `teléfono`, '.
                  'visitas.created_at AS `fecha de creación`, '.
                  'visitas.updated_at AS `fecha última modificación`, '.
                  'visitas.fecha_visita AS `fecha visita`, '.
                  'MONTH(visitas.fecha_visita) AS `mes visita`, '.
                  'YEAR(visitas.fecha_visita) AS `año visita`, '.
                  'visitas.conociste AS `cómo nos conociste`, '.
                  'visitas.status AS `estado`, '.
                  'visitas.publicidad AS `desea recibir publicidad`, '.
                  'visitas.tipos_inmuebles_1 AS `tipos de inmuebles I`, '.
                  'visitas.tipos_inmuebles_2 AS `tipos de inmuebles II`, '.
                  'users.name AS comercial, '.
                  'promo1.name AS `promoción I`, '.
                  'promo2.name AS `promoción II` '.
            'FROM visitas '.
            'JOIN users ON visitas.users_id = users.id '.
            'JOIN promociones AS promo1 ON visitas.promociones_id_1 = promo1.id '.
            'LEFT JOIN promociones AS promo2 ON visitas.promociones_id_2 = promo2.id '.
            'WHERE visitas.deleted = 0 ';
  if ($id !== null && $id !== '') {
    $select .= "AND visitas.id = :id ";
    $params[] = [ 'key' => 'id', 'var' => $id, 'code' => PDO::PARAM_INT ];
  }
  if ($query !== null && $query !== '') {
    $select .= 'AND ( visitas.name LIKE :query OR visitas.apellido_1 LIKE :query OR visitas.apellido_2 LIKE :query OR visitas.email LIKE :query OR visitas.telefono LIKE :query) ';
    $params[] = [ 'key' => 'query', 'var' => '%' . $query . '%', 'code' => PDO::PARAM_STR ];
  }
  if ($telefono !== null && $telefono !== '') {
    $select .= 'AND visitas.telefono LIKE :telefono ';
    $params[] = [ 'key' => 'telefono', 'var' => '%' . $telefono . '%', 'code' => PDO::PARAM_STR ];
  }
  if ($promocion !== 0) {
    $select .= "AND (visitas.promociones_id_1 = :promocion OR visitas.promociones_id_2 = :promocion) ";
    $params[] = [ 'key' => 'promocion', 'var' => $promocion, 'code' => PDO::PARAM_INT ];
  }
  if ($status !== null && $status !== '') {
    $select .= "AND visitas.status = :status ";
    $params[] = [ 'key' => 'status', 'var' => $status, 'code' => PDO::PARAM_STR ];
  }
  $select .= 'ORDER BY visitas.created_at DESC';
  $sth = $this->db->prepare($select);
  foreach($params as $obj) {
    $sth->bindParam($obj['key'], $obj['var'], $obj['code']);
  }
  try {
    $sth->execute();
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }
  
  $results = [];
  foreach($sth->fetchAll() as $key => $result) {
    $result['id'] = (int) $result['id'];
    foreach(['tipos de inmuebles I', 'tipos de inmuebles II'] as $w) {
      $arr_tipos = [];
      $unserialize = unserialize($result[$w]);
      if (count($unserialize) > 0) {
        foreach($unserialize as $tipo) {
          $arr_tipos[] = $tipos_inmuebles[$tipo];
        }
        $result[$w] = implode(', ', $arr_tipos);
      } else {
        $result[$w] = '';
      }
    }
    $results[] = $result;
  }

  $spreadsheet = new Spreadsheet();
  $sheet = $spreadsheet->getActiveSheet();
  $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
  $i = 2; // filas
  foreach($results as $result) {
    $j = 0; // columnas
    foreach ($result as $key => $value) {
      if ($i === 2) {
        $sheet->setCellValue("$columns[$j]1", $key);
        $sheet->setCellValue("$columns[$j]2", $value);
      } else {
        $sheet->setCellValue($columns[$j] . $i, $value);
      }
      $j++;
    }
    $i++;
  }
  
  $writer = new Xlsx($spreadsheet);
  
  header('Content-Type: application/vnd.ms-excel');
  header('Content-Disposition: attachment; filename="visitas.xlsx"');
  header('Accept: application/vnd.ms-excel');
  $writer->save("php://output");
};
