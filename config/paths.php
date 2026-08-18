<?php
// Check if constants are already defined
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__.'/..');
    define('PUBLIC_PATH', ROOT_PATH.'/public');
    define('CONFIG_PATH', ROOT_PATH.'/config');
    define('VIEWS_PATH', ROOT_PATH.'/views');
    define('BASE_URL', '/pangisha/public/');
    define('ASSETS_URL', BASE_URL.'assets/');
}