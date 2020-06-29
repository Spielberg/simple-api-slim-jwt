<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (Request $request, Response $response, array $args) {
 
  $token = $request->getAttribute('decoded_token_data');

  // el id del user es obligatorio.
  $input = $request->getParsedBody();
  if (!isset($input['id']) || $input['id'] === '' || !ctype_digit((string)$input['id'])) {
    return $this->response->withJson(['error' => true, 'message' => 'Falta alguno de los parámetros obligatorios: id.']);
  }

  // verify is superuser.
  if(!$token['superuser']) {
    return $this->response->withJson(['error' => true, 'message' => 'No tienes suficientes permisos para hacer esta llamada.']);  
    }

  // allowed.
  $allowed = [
    'active' => PDO::PARAM_BOOL,
    'deleted' => PDO::PARAM_BOOL,
    'home' => PDO::PARAM_BOOL,
    'name' => PDO::PARAM_STR,
    'zona' => PDO::PARAM_STR,
  ];

  $arrInput = [
    'id' => $input['id'],
  ];
  $arrValue = [];
  foreach($allowed as $key => $value){
    if (isset($input[$key]) && $input[$key] !== '') {
      $arrInput[$key] = $input[$key];
      $arrValue[] = "$key = :$key";
    }
  }
  if(count($arrInput) === 0) {
    return $this->response->withJson(['error' => true, 'message' => 'Ningún parametro para actualizar.']);  
    }

  // build quey.
  $sql = 'UPDATE promociones SET ' . implode($arrValue, ', ') . ' WHERE id = :id LIMIT 1';
  $sth = $this->db->prepare($sql);
  try {
    $sth->execute($arrInput);
  } catch(Exception $e) {
    return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
  }

  // guardamos ahora los inmuebles
  if ($input['inmuebles']) {
    $sql = 'DELETE FROM `promociones_tipos_inmuebles` '.
               'WHERE `promociones_id` = ' . (int) $input['id'];
    try {
      $this->db->query($sql)->execute();
    } catch(Exception $e) {
      return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
    }
    foreach($input['inmuebles'] as $inmuebleId => $cantidad) {
      $sql = 'INSERT INTO promociones_tipos_inmuebles ' .
            '(promociones_id, tipos_inmuebles_id, cantidad) VALUES (:id, :tipos_inmuebles_id, :cantidad) '.
            ' ON DUPLICATE KEY UPDATE cantidad = :cantidad';
      $sth = $this->db->prepare($sql);
      $sth->bindParam('id', $input['id'], PDO::PARAM_INT);
      $sth->bindParam('tipos_inmuebles_id', $inmuebleId, PDO::PARAM_INT);
      $sth->bindParam('cantidad', $cantidad, PDO::PARAM_INT);
      try {
        $sth->execute();
      } catch(Exception $e) {
        return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
      }
    }
  }

  if ($input['historico']) {
    foreach(['reserva', 'venta'] as $type) {
      foreach($input['historico'][$type] as $inmuebleId => $cantidad) {
        if ($cantidad < 0) {
          $sql = 'DELETE FROM `promociones_historico` '.
                'WHERE `promociones_id` = ' . (int) $input['id'] . ' '.
                'AND `type` = "' . $type . '" '.
                'AND tipos_inmuebles_id = ' . (int) $inmuebleId . ' LIMIT 1';
          try {
            $this->db->query($sql)->execute();
          } catch(Exception $e) {
            return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
          }
        } else {
          $sql = 'INSERT INTO promociones_historico ' .
              '(promociones_id, tipos_inmuebles_id, cantidad, type) VALUES (:id, :tipos_inmuebles_id, :cantidad, :type) '.
              ' ON DUPLICATE KEY UPDATE cantidad = :cantidad';
          $sth = $this->db->prepare($sql);
          $sth->bindParam('id', $input['id'], PDO::PARAM_INT);
          $sth->bindParam('tipos_inmuebles_id', $inmuebleId, PDO::PARAM_INT);
          $sth->bindParam('cantidad', $cantidad, PDO::PARAM_INT);
          $sth->bindParam('type', $type);
          try {
            $sth->execute();
          } catch(Exception $e) {
            return $this->response->withJson(['error' => true, 'message' => $e->getMessage()]);  
          }
        }
      }
    }
  }  

  return $this->response->withJson([
    'error' => false,
  ]);
};