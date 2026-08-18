<?php
/**
 * Get a system setting value by key
 * 
 * @param string $key The setting key
 * @param mixed $default The default value if setting not found
 * @return mixed The setting value or default
 */
function getSystemSetting($key, $default = null) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Format currency with the system currency symbol
 * 
 * @param float $amount The amount to format
 * @return string Formatted currency string
 */
function formatCurrency($amount) {
    $currency = getSystemSetting('currency', 'TZS');
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Get the system language
 * 
 * @return string The language code (en or sw)
 */
function getSystemLanguage() {
    return getSystemSetting('language', 'en');
}
?>