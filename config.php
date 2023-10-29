<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'tajlilco_youtube');
define('DB_PASSWORD', 'tajlilco_youtube');
define('DB_NAME', 'tajlilco_youtube');


// Google API Configuration
define('OAUTH_CLIENT_ID', 'here.apps.googleusercontent.com');
define('OAUTH_CLIENT_SECRET', 'client secret');
define('REDIRECT_URL', 'https://youtube.hamidmirzapour.ir/youtube_video_sync.php');

// start session
if(!session_id()) session_start();

// Include Google Client Libraries
require_once 'google-api-php-client/autoload.php';
require_once 'google-api-php-client/Client.php';
require_once 'google-api-php-client/Service/YouTube.php';


// Include Google Client Class
$client = new Google_Client();
$client->setClientId(OAUTH_CLIENT_ID);
$client->setClientSecret(OAUTH_CLIENT_SECRET);
$client->setScopes('https://www.googleapis.com/auth/youtube');
$client->setRedirectUri(REDIRECT_URL);

// Define an object that will be used to make all APIs requests
$youtube = new Google_Service_Youtube($client);
