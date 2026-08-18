<?php
// Only set session configuration if no session is active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/pangisha');
    ini_set('session.save_path', __DIR__.'/../tmp');
    session_name('PANGISHA_SESS');
    session_start();
}

error_log("Session ID: ".session_id());