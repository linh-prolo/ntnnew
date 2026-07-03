<?php
require_once 'config/auth.php';
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
header('Location: /erp/login.php');
exit();
?>