<?php
/**
 * Plugin Name: Forge12 Secure Login
 * Plugin URI: https://www.forge12.com
 * Description: Extended login settings for WordPress like password length, minimum strength and expire date.
 * Version: v2.0
 * Author: Forge12 Interactive GmbH
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( plugin_dir_path( __FILE__ ) . "core/class.application.php" );

if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . "core/class.admin.php" );
	require_once( plugin_dir_path( __FILE__ ) . "core/class.user.php" );

	new \f12_secure_login\core\admin();
	new \f12_secure_login\core\user();
}

new \f12_secure_login\core\application();