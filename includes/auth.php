<?php
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    function isAdmin() {
        return isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }
    
    function isLandlord() {
        return isLoggedIn() && $_SESSION['user_role'] === 'landlord';
    }
    
    function isAgent() {
        return isLoggedIn() && $_SESSION['user_role'] === 'agent';
    }
    
    function isTenant() {
        return isLoggedIn() && $_SESSION['user_role'] === 'tenant';
    }
    
    function isTechnician() {
        return isLoggedIn() && $_SESSION['user_role'] === 'technician';
    }
}