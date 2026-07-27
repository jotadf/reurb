<?php
//-------------------------------------------------------------------------------------------
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR'
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {

            $ips = explode(',', $_SERVER[$header]);

            foreach ($ips as $ip) {
                $ip = trim($ip);
                echo $ip . "<br>";
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    echo " Validado: " . $ip . "<br>";
                }
            }
        }
    }