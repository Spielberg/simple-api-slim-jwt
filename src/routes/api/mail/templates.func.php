<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use \Mailjet\Resources;

return function (Request $request, Response $response, array $args) {
  $filters = [
    'OwnerType' => 'user',
    'Limit' => '100'
  ];
  $rtn = $this->mjv3->get(Resources::$Template, ['filters' => $filters]);
  return $this->response->withJson([
    'error' => !$rtn->success(),
    'data' => $rtn->success()
      ? array_map(function ($result) {
          return ['id' => $result['ID'], 'value' => $result['Name']];
        }, $rtn->getData())
      : [],
  ]);
};