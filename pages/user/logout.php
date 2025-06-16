<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
session_start();
require $db_connect_url;

session_unset();
session_destroy();
header('Location: ' . $index_url);
exit;
?>