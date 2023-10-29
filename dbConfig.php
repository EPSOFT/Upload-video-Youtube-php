<?php
// Include Configuration file
require_once 'config.php';

// Create Database connection
$db = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($db->connect_error){
    die("Connection failed: " . $db->connect_error);
}
