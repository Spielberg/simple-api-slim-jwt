<?php
$dotenv = Dotenv\Dotenv::create(__DIR__.'/../');
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'JWT_SECRET']);

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Database
        'db' => [            
          'host' => getenv('DB_HOST'),             
          'dbname' => getenv('DB_NAME'),             
          'user' => getenv('DB_USER'),            
          'pass' => getenv('DB_PASS')        
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
        ],

        // pagination
        'pagination' => [
          'limit' => 10,
        ],
    ],
];