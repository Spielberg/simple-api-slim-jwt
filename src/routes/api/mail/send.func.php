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
    'to',
    'template',
  ] as $key){
    if (!isset($input[$key]) || $input[$key] === '') {
      return $this->response->withJson(['error' => true, 'message' => "Falta alguno de los parámetros obligatorios: $key."]);  
    }
  }  

  $body = [
    'Messages' => [
        [
            'From' => [
                'Email' => 'construcciones.andia.noreply@gmail.com',
                'Name' => 'Construcciones Andia',
            ],
            'To' => [
                [
                  'Email' => 'javier.sanchezostiz@gmail.com',
                ]
            ],
            'TemplateID' => (int) $input['template'],
            'TemplateLanguage' => true,
        ]
    ]
  ];
  foreach($input['to'] as $mail){
    //$body['Messages'][0]['To'][] = ['Email' => $mail];
  }

  $rtn = $this->mjv31->post(Resources::$Email, ['body' => $body]);
  return $this->response->withJson([
    'error' => !$rtn->success(),
  ]);
};