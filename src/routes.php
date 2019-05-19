<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;

return function (App $app) {
    $container = $app->getContainer();

    $app->post('/login', function (Request $request, Response $response, array $args) {
 
      // get user detaills
      $input = $request->getParsedBody();
      $sql = "SELECT * FROM users WHERE email= :email";
      $sth = $this->db->prepare($sql);
      $sth->bindParam("email", $input['email']);
      $sth->execute();
      $user = $sth->fetchObject();

      // verify email address.
      if(!$user) {
          return $this->response->withJson(['error' => true, 'message' => 'These credentials do not match our records.']);  
      }
   
      // verify password.
      if (!password_verify($input['password'],$user->password)) {
          return $this->response->withJson(['error' => true, 'message' => 'These credentials do not match our records.']);  
      }
   
      // update last_login
      $sql = "UPDATE users SET last_login = NOW() WHERE id= :id";
      $sth = $this->db->prepare($sql);
      $sth->bindParam("id", $user->id);
      $sth->execute();

      $settings = $this->get('settings'); // get settings array.
      
      $d = new DateTime();
      $token = JWT::encode([
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'iat' => $d->getTimestamp(), 
        'exp' => $d->getTimestamp() + ($settings['jwt']['exp'] * 3600),
      ], $settings['jwt']['secret'], "HS256");
   
      return $this->response->withJson(['token' => $token]);
  });

  // api
  $app->group('/api', function(\Slim\App $app) {
 
    $app->get('/user',function(Request $request, Response $response, array $args) {
      return $this->response->withJson($request->getAttribute('decoded_token_data'));
    });
   
  });
};
