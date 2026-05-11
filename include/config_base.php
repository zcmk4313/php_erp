<?php
/**
 * ERP System - Base Configuration
 * PHP 5.6+ Compatible
 *
 * This file handles:
 * - Error reporting configuration
 * - PHP version compatibility
 * - Session setup and initialization
 * - Database configuration
 */

// ==========================================
// ERROR REPORTING
// ==========================================

// PHP 5.6 compatible error reporting
// Report all errors except notices
error_reporting(E_ALL & ~E_NOTICE);

// ==========================================
// DEFINE BASE CONSTANTS
// ==========================================

// Define main include directory constant
define('VIOOMAINC', dirname(__FILE__));

// ==========================================
// GLOBAL VARIABLE COMPATIBILITY (PHP < 4.1)
// ==========================================

// PHP 5.6 doesn't have this issue, but keeping for compatibility
$ckvs = array('_GET', '_POST', '_COOKIE', '_FILES');
$ckvs4 = array('HTTP_GET_VARS', 'HTTP_POST_VARS', 'HTTP_COOKIE_VARS', 'HTTP_POST_FILES');

// Check for old PHP versions and migrate variables
$phpold = 0;
foreach ($ckvs4 as $_k => $_v) {
    if (!isset(${$_v})) {
        continue;
    }
    if (!isset(${$ckvs[$_k]})) {
        if (isset(${$_v}) && is_array(${$_v})) {
            ${$ckvs[$_k]} = ${$_v};
            unset(${$_v});
            $phpold = 1;
        }
    }
}

// ==========================================
// GLOBAL SECURITY CHECK
// ==========================================

// Global variable safety detection
// Prevent variable injection attacks
foreach ($ckvs as $ckv) {
    if (!isset($$ckv)) {
        continue;
    }
    
    foreach ($$ckv as $_k => $_v) {
        // Check for dangerous variable names that start with _, globals, or cfg_
        if (preg_match("/^(_|globals|cfg_)/i", $_k)) {
            unset(${$ckv}[$_k]);
        }
    }
}

// ==========================================
// LOAD USER CONFIGURATION
// ==========================================

// Load user-defined configuration variables
require_once(VIOOMAINC . "/config_hand.php");

// ==========================================
// PHP 5.1+ TIMEZONE CONFIGURATION
// ==========================================

// PHP 5.6 compatible timezone setup
// Use version_compare() instead of > operator
if (version_compare(PHP_VERSION, '5.1', '>=')) {
    // Build timezone string from config
    $time_offset = isset($cfg_cli_time) ? $cfg_cli_time : 0;
    $timezone = 'Etc/GMT' . ($time_offset > 0 ? '+' : '-') . abs($time_offset);
    
    // Set default timezone
    if (function_exists('date_default_timezone_set')) {
        date_default_timezone_set($timezone);
    }
}

// ==========================================
// SESSION CONFIGURATION
// ==========================================

// Define session save path
$sessSavePath = VIOOMAINC . "/../data/sessions/";

// Create sessions directory if it doesn't exist
if (!is_dir($sessSavePath)) {
    mkdir($sessSavePath, 0755, true);
}

// Fix permissions if needed
if (is_dir($sessSavePath)) {
    chmod($sessSavePath, 0755);
}

// Check if session path is writable and readable
if (is_writeable($sessSavePath) && is_readable($sessSavePath)) {
    // Set custom session save path
    session_save_path($sessSavePath);
} else {
    // Log warning if session directory is not properly configured
    error_log("WARNING: Session directory not properly configured: " . $sessSavePath);
}

// ==========================================
// START SESSION
// ==========================================

// Initialize PHP session
// Must be called before any cookie operations
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// DATABASE CONFIGURATION
// ==========================================

// Database connection settings
$cfg_dbhost = 'localhost';
$cfg_dbname = '';
$cfg_dbuser = '';
$cfg_dbpwd = '';
$cfg_dbprefix = 'sy_';
$cfg_db_language = 'utf8';

// ==========================================
// SOFTWARE INFORMATION
// ==========================================

// Software metadata - IMPORTANT: Do not remove
// System requires this to receive security updates
$cfg_softname = "ERP SYSTEM";
$cfg_soft_enname = "SUYANG2019版";
$cfg_soft_devteam = "suyang";
$cfg_version = 'v2013';
$cfg_ver_lang = 'utf8';  // CRITICAL: Do not manually modify

// ==========================================
// INCLUDE REQUIRED LIBRARIES
// ==========================================

// Load core functions (must be first)
require_once(VIOOMAINC . '/inc_functions.php');

// Load database classes
require_once(VIOOMAINC . '/config_passport.php');

// Load main configuration
require_once(VIOOMAINC . '/config.php');

// Conditionally load database class
if (!isset($__ONLYCONFIG) || !$__ONLYCONFIG) {
    require_once(VIOOMAINC . '/pub_db_mysql.php');
}

// Conditionally load additional functions
if (!isset($__ONLYDB) || !$__ONLYDB) {
    require_once(VIOOMAINC . '/inc_functions.php');
}

?>
