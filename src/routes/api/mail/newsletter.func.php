<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use \Mailjet\Resources;

return function (Request $request, Response $response, array $args) {
  $token = $request->getAttribute('decoded_token_data');

  // verify is superuser.
  if(!$token['superuser']) {
    return $this->response->withJson(['error' => true, 'message' => 'No tienes suficientes permisos para hacer esta llamada.']);  
    } 
  
  // validation.
  $input = $request->getParsedBody();
  foreach([
    'template',
  ] as $key){
    if (!isset($input[$key]) || $input[$key] === '') {
      return $this->response->withJson(['error' => true, 'message' => "Falta alguno de los parámetros obligatorios: $key."]);  
    }
  }  

  // get all emails
  $select = 'SELECT ANY_VALUE(CONCAT(`name`, " ", `apellido_1`, " ",  `apellido_2`)) AS `Name`, LOWER(email) AS Email '.
            'FROM visitas '. 
            'WHERE deleted = 0 AND publicidad = 1 AND email <> "notiene@gmail.com" '.
            'GROUP BY email';
  $sth = $this->db->prepare($select);
  $sth->execute();
  $to = $sth->fetchAll();

  // verify is $to is empty
  if(count($to)=== 0) {
    return $this->response->withJson(['error' => true, 'message' => 'La base de datos está vacía.']);  
    } 

  $body = [
    'Messages' => [
        [
            'From' => [
                'Email' => 'construcciones.andia.noreply@gmail.com',
                'Name' => 'Construcciones Andia',
            ],
            'To' => $to,
            'TemplateID' => (int) $input['template'],
            'TemplateLanguage' => true,
        ]
    ]
  ];

  if (true) {
    $rtn = $this->mjv31->post(Resources::$Email, ['body' => $body]);
    return $this->response->withJson([
      'error' => !$rtn->success(),
    ]);
  } else {
    return $this->response->withJson([
      'error' => false,
    ]);
  }
};