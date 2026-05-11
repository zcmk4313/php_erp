<?php
/**
 * ERP System - Login Check/Authentication Guard
 * PHP 5.6+ Compatible
 * 
 * This script is included at the top of protected pages
 * It verifies user authentication and redirects to login if needed
 */

// Load configuration
require_once(dirname(__FILE__) . '/config_base.php');

// ==========================================
// VERIFY AUTHENTICATION
// ==========================================

/**
 * Check if user is properly authenticated
 * Verifies both SESSION and COOKIE for consistency
 */

// Get authentication data from cookie
$auth_cookie = GetCookie('VioomaUserID');

// Get authentication data from session
$auth_session = GetSession('VioomaUserID', '');

// ==========================================
// AUTHENTICATION LOGIC
// ==========================================

$is_authenticated = false;
$authentication_valid = false;

// Check if both session and cookie exist
if (!empty($auth_session) && !empty($auth_cookie)) {
    // Verify they match (security check for tampering)
    if ($auth_session === $auth_cookie) {
        $is_authenticated = true;
        $authentication_valid = true;
    } else {
        // Mismatch detected - potential tampering
        $message = "Authentication mismatch detected - possible session tampering. User: " . 
                   substr($auth_session, 0, 20) . " | IP: " . GetIP();
        WriteNote($message, GetDateTimeMk(time()), GetIP(), 'Unknown');
        $is_authenticated = false;
    }
}
// Check if session exists but cookie doesn't (might happen after cookie expires)
elseif (!empty($auth_session) && empty($auth_cookie)) {
    // Try to restore cookie from session
    $rank = GetSession('rank', '');
    if (!empty($rank)) {
        // Restore the cookie
        $username = str_replace(GetSession('cfg_cookie_encode', ''), '', $auth_session);
        PutCookie('VioomaUserID', $auth_session, 2);
        PutCookie('rank', $rank, 2);
        $is_authenticated = true;
        $authentication_valid = true;
    }
}
// No authentication data found
else {
    $is_authenticated = false;
}

// ==========================================
// REDIRECT IF NOT AUTHENTICATED
// ==========================================

if (!$is_authenticated) {
    // Clear any partial authentication data
    DeleteCookie('VioomaUserID');
    DeleteCookie('rank');
    DeleteSession('VioomaUserID');
    DeleteSession('rank');
    
    // Redirect to login page using JavaScript
    echo "<script language='javascript'>";
    echo "parent.window.location.href='login.php';";
    echo "</script>";
    exit();
}

// ==========================================
// LOAD OPTIONAL SECURITY MODULES
// ==========================================

// Conditionally load encryption module if available
if (file_exists(dirname(__FILE__) . '/cryption.php')) {
    require_once(dirname(__FILE__) . '/cryption.php');
}

// Conditionally load authentication code module if available
if (file_exists(dirname(__FILE__) . '/a_code.php')) {
    require_once(dirname(__FILE__) . '/a_code.php');
    
    // Execute security check if function exists
    if (function_exists('check_key')) {
        check_key(false);
    }
}

// Authentication check complete - user is authorized to access this page

?>
