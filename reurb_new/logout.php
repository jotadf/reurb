<?php
require __DIR__ . '/lib/bootstrap.php';
$_SESSION = [];
session_destroy();
redirecionar(cfg('pagina_login', 'login.php'));
