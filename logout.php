<?php 
// Include configuration file 
require_once 'config.php'; 
 
// Revoke token & destroy session 
$client->revokeToken(); 
session_destroy(); 
 
// Redirect to the homepage 
header("Location: index.php"); 
exit; 
?>