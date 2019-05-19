<?php
$dotenv = Dotenv\Dotenv::create(__DIR__.'/../');
$dotenv->load();
$dotenv->required(['JWT_SECRET']);

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Database
        'db' => [            
          'host' => '127.0.0.1',             
          'dbname' => '002_api_andia',             
          'user' => 'root',            
          'pass' => 'root'        
        ],

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // jwt settings
        'jwt' => [
          'secret' => getenv('JWT_SECRET'),
          'exp' => 24, // in hours
      ]
    ],
];