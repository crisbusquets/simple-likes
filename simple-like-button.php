<?php
/**
 * Plugin Name: Simple Like Button
 * Description: Adds a lightweight "Like" button to posts and pages with AJAX functionality.
 * Version: 1.0.0
 * Author: Cris Busquets
 * Text Domain: simple-like-button
 */

defined( 'ABSPATH' ) || exit;

define( 'SLB_VERSION', '1.0.0' );
define( 'SLB_PATH', plugin_dir_path( __FILE__ ) );
define( 'SLB_URL', plugin_dir_url( __FILE__ ) );

require_once SLB_PATH . 'includes/functions.php';
require_once SLB_PATH . 'includes/admin-actions.php';

if ( is_admin() ) {
    require_once SLB_PATH . 'admin/admin-page.php';
}