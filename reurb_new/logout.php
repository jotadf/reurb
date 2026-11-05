<?php
require __DIR__ . '/lib/bootstrap.php';
encerrar_sessao();
redirecionar(cfg('pagina_login', 'login.php'));
