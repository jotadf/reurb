<?php
require __DIR__ . '/lib/bootstrap.php';
encerrar_sessao();
redirecionar(crud_url(cfg('pagina_login', 'login.php')));
