<?php

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', $config['env'] === 'local' ? '1' : '0');

date_default_timezone_set($config['timezone']);

session_save_path(__DIR__ . '/../storage/sessions');
session_start();
