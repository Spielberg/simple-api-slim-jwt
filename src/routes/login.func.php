<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;

return function (Request $request, Response $response, array $args) {
 
 // get user detaills
 $input = $request->getParsedBody();
 $sql = "SELECT * FROM users WHERE email= :email";
 $sth = $this->db->prepare($sql);
 $sth->bindParam("email", $input['email']);
 $sth->execute();
 $user = $sth->fetchObject();

 // verify email address.
 if(!$user) {
     return $this->response->withJson(['error' => true, 'message' => 'El usuario y/o la contraseña son incorrectas.']);  
 }

 // verify password.
 if (!password_verify($input['password'],$user->password)) {
     return $this->response->withJson(['error' => true, 'message' => 'El usuario y/o la contraseña son incorrectas.']);  
 }

 // update last_login.
 $sql = "UPDATE users SET last_login = NOW() WHERE id= :id";
 $sth = $this->db->prepare($sql);
 $sth->bindParam("id", $user->id);
 $sth->execute();

 // get settings array.
 $settings = $this->get('settings');
 
 $d = new DateTime();
 $token = JWT::encode([
   'id' => (int) $user->id,
   'email' => $user->email,
   'exp' => $d->getTimestamp() + ($settings['jwt']['exp'] * 3600),
   'iat' => $d->getTimestamp(), 
   'name' => $user->name,
   'superuser' => (int) $user->superuser === 1, 
 ], $settings['jwt']['secret'], 'HS256');

 return $this->response->withJson(['error' => false, 'data' => ['token' => $token]]);
};