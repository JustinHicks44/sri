<?php
require_once __DIR__ . '/../Php_sri/Lib/auth.php';
auth_signout();
header('Location: ../index.php');
exit;
