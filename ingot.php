<?php
/**
 Plugin Name: Ingot
Version: 0.2.0.1
 */


define( 'INGOT_VER', '0.2.0.1' );
define( 'INGOT_URL', plugin_dir_url( __FILE__ ) );
define( 'INGOT_DIR', dirname( __FILE__ ) );
define( 'INGOT_UI_PARTIALS_DIR', dirname( __FILE__ ) . '/classes/ui/admin/partials/' );

add_action( 'plugins_loaded', 'ingot_maybe_load', 0 );
function ingot_maybe_load() {
	$fail = false;
	if ( ! version_compare( PHP_VERSION, '5.5.0', '>=' ) ) {


		if ( is_admin() ) {
			include_once( dirname( __FILE__ ) . '/vendor/calderawp/dismissible-notice/src/functions.php' );
			$message = __( sprintf( 'Ingot requires PHP version %1s or later. Current version is %2s.', '5.5.0', PHP_VERSION ), 'ingot' );

			echo caldera_warnings_dismissible_notice( $message, true, 'activate_plugins' );
			$fail = true;
		}

	}

	global $wp_version;
	if ( ! version_compare( $wp_version, '4.3.1', '>=' ) ) {


		if ( is_admin() ) {
			include_once( dirname( __FILE__ ) . 'vendor/calderawp/dismissible-notice/src/functions.php' );
			$message = __( sprintf( 'Ingot requires WordPress version %1s or later. Current version is %2s.', '5.5.0', '4.3.1', $wp_version ), 'ingot' );

			echo caldera_warnings_dismissible_notice( $message, true, 'activate_plugins' );
			$fail = true;
		}

	}


	if( false == $fail ){
		include_once( dirname(__FILE__ ) . '/ingot_bootstrap.php' );
		add_action( 'plugins_loaded', array( 'ingot_bootstrap', 'maybe_load' ) );
	}


}

/**
 * EDD Licensing
 */
define( 'INGOT_SL_STORE_URL', 'http://ingothq.com' );
define( 'INGOT_SL_ITEM_NAME', 'Ingot Plugin: The Automatic A/B Tester' );
add_action( 'admin_init', 'ingot_sl_plugin_updater', 0 );
add_action( 'admin_init', 'ingot_sl_register_option');
if( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	include( dirname( __FILE__ ) . '/includes/EDD_SL_Plugin_Updater.php' );
}
