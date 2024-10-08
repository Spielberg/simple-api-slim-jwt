<?php

use Slim\App;

return function (App $app) {
    $container = $app->getContainer();

    // view renderer
    $container['renderer'] = function ($c) {
        $settings = $c->get('settings')['renderer'];
        return new \Slim\Views\PhpRenderer($settings['template_path']);
    };

    // monolog
    $container['logger'] = function ($c) {
        $settings = $c->get('settings')['logger'];
        $logger = new \Monolog\Logger($settings['name']);
        $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        return $logger;
    };

    // PDO database library 
    $container['db'] = function ($c) {
      $settings = $c->get('settings')['db'];
      $pdo = new PDO("mysql:host=" . $settings['host'] . ";port=" . $settings['port'] . ";dbname=" . $settings['dbname'],
          $settings['user'], $settings['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      return $pdo;
    };

    // mailjet  
    $container['mjv3'] = function ($c) {
      $settings = $c->get('settings')['mailjet'];
      return new \Mailjet\Client($settings['public_key'], $settings['private_key'], true, ['version' => 'v3'] );
    };

    $container['mjv31'] = function ($c) {
      $settings = $c->get('settings')['mailjet'];
      return new \Mailjet\Client($settings['public_key'], $settings['private_key'], true, ['version' => 'v3.1'] );
    };

    $container['view'] = new \Slim\Views\PhpRenderer(__DIR__.'/../templates/');
};
