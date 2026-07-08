<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

$local = require __DIR__ . '/local.php';

return $local['google'];
