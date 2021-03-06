<?php
/**
 * Load up the Ingot
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */
class ingot_bootstrap {

	/**
	 * Loads ingot if not already loaded.
	 *
	 * @since 0.0.5
	 */
	public static function maybe_load() {
		if ( did_action( 'ingot_loaded' ) ) {
			return;
		}

		if ( ! defined( 'INGOT_DEV_MODE' ) ){
			/**
			 * Puts Ingot into dev mode
			 *
			 * Don't use on a live site -- makes API totally open
			 *
			 * @since 0.0.5
			 */
			define( 'INGOT_DEV_MODE', false );
		}

		$load = true;
		if ( ! version_compare( PHP_VERSION, '5.5.0', '>=' ) ) {
			$load = false;
		}

		$autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
		if ( ! file_exists( $autoloader ) ) {
			$load = false;
		}

		if ( $load ) {
			add_filter( 'ingot_force_update_table', '__return_true' );
			if (  ! did_action( 'ingot_loaded') ) {
				include_once( $autoloader );

				self::maybe_add_sequence_table();
				self::maybe_add_tracking_table();
				self::maybe_add_price_group_table();
				if( ! self::table_exists( \ingot\testing\crud\sequence::get_table_name() ) || ! self::table_exists( \ingot\testing\crud\tracking::get_table_name() ) ) {
					if( is_admin() ) {
						printf( '<div class="error"><p>%s</p></div>', __( 'Ingot Not Loaded', 'ingot' ) );
					}

					return;
				}


				ingot\testing\ingot::instance();
				new ingot\ui\make();
				self::maybe_load_api();

				add_action( 'init', array( __CLASS__, 'init_cookies' ), 25 );
				add_action( 'ingot_cookies_set', array( __CLASS__, 'init_price_tests' ) );

				/**
				 * Runs when Ingot has loaded.
				 *
				 * @since 0.0.5
				 *
				 */
				do_action( 'ingot_loaded' );
			}

		}


	}

	/**
	 * If not installed as a plugin include our bundled REST API
	 *
	 * @since 0.0.6
	 */
	protected static function maybe_load_api() {
		if( ! defined( 'REST_API_VERSION' ) ) {
			include_once( INGOT_DIR . '/wp-api/plugin.php' );
		}

	}

	/**
	 * If needed, add the custom table for sequences
	 *
	 * @since 0.0.7
	 */
	public static function maybe_add_sequence_table( $drop_first = false ) {
		global $wpdb;

		$table_name = \ingot\testing\crud\sequence::get_table_name();

		if( $drop_first  ) {
			if( self::table_exists( $table_name )  ) {
				$wpdb->query( "DROP TABLE $table_name" );
			}

		}

		if(  self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		  ID mediumint(9) NOT NULL AUTO_INCREMENT,
		  a_id mediumint(9) NOT NULL,
		  b_id mediumint(9) NOT NULL,
	   	  test_type VARCHAR( 255 ) NOT NULL,
		  a_win mediumint(9) NOT NULL,
		  b_win mediumint(9) NOT NULL,
		  a_total mediumint(9) NOT NULL,
		  b_total mediumint(9) NOT NULL,
		  initial mediumint(9) NOT NULL,
		  threshold mediumint(9) NOT NULL,
		  created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  modified  datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  completed  tinyint(1) NOT NULL,
		  group_ID mediumint(9) NOT NULL,
		  UNIQUE KEY id (id)
		) $charset_collate;";



		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

	}

	/**
	 * If needed, add the custom table for sequences
	 *
	 * @since 0.0.7
	 *
	 * @param bool $drop_first Optional. If true, DROP TABLE statemnt is made first. Default is false
	 */
	public static function maybe_add_tracking_table( $drop_first = false ) {
		global $wpdb;

		$table_name = \ingot\testing\crud\tracking::get_table_name();

		if( $drop_first  ) {
			if( self::table_exists( $table_name )  ) {
				$wpdb->query( "DROP TABLE $table_name" );
			}

		}

		if(  self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		  ID bigint(9) NOT NULL AUTO_INCREMENT,
		  group_ID mediumint(9) NOT NULL,
		  sequence_ID mediumint(9) NOT NULL,
		  test_ID mediumint(9) NOT NULL,
		  IP VARCHAR(255) NOT NULL,
		  UTM LONGTEXT NOT NULL,
		  referrer VARCHAR(255) NOT NULL,
		  browser VARCHAR(255) NOT NULL,
		  meta LONGTEXT NOT NULL,
		  user_agent LONGTEXT NOT NULL,
		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  UNIQUE KEY ID (ID)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

	}

	/**
	 * If needed, add the custom table for price groups
	 *
	 * @since 0.0.9
	 *
	 * @param bool $drop_first Optional. If true, DROP TABLE statemnt is made first. Default is false
	 */
	public static function maybe_add_price_group_table( $drop_first = false ) {
		global $wpdb;

		$table_name = \ingot\testing\crud\price_group::get_table_name();

		if( $drop_first  ) {
			if( self::table_exists( $table_name )  ) {
				$wpdb->query( "DROP TABLE $table_name" );
			}

		}

		if(  self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		  ID bigint(9) NOT NULL AUTO_INCREMENT,
		  plugin VARCHAR(255) NOT NULL,
		  group_name VARCHAR(255) NOT NULL,
		  sequences LONGTEXT NOT NULL,
		  test_order LONGTEXT NOT NULL,
		  initial mediumint(9) NOT NULL,
		  threshold mediumint(9) NOT NULL,
		  product_ID BIGINT NOT NULL,
		  type VARCHAR(255) NOT NULL,
		  current_sequence mediumint(9) NOT NULL,
		  created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  modified  datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  UNIQUE KEY ID (ID)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

	}

	/**
	 * Check if table exists
	 *
	 * @since 0.0.7
	 *
	 * @param string $table_name
	 *
	 * @return bool
	 */
	protected static function table_exists( $table_name ) {
		global $wpdb;
		if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name  ) {
			return true;

		}else{
			return false;

		}

	}

	/**
	 * Setup our cookies
	 *
	 * @uses "init"
	 *
	 * @since 0.0.9
	 */
	public static function init_cookies() {
		if( false == ingot_is_front_end() ) {
			return false;
		}

		$cookies = array();
		if( isset( $_COOKIE ) && is_array( $_COOKIE ) ) {
			$cookies = $_COOKIE;
		}

		$cookies = new ingot\testing\cookies\init( $cookies );
		$ingot_cookies = $cookies->get_ingot_cookie();
		if( ! empty( $ingot_cookies ) ){
			$cookie_time = 15 * DAY_IN_SECONDS;
			setcookie($cookies->get_cookie_name(), $ingot_cookies, $cookie_time, COOKIEPATH, COOKIE_DOMAIN, false );


		}

		/**
		 * Fires after Ingot Cookies Are Set
		 *
		 * Note: will fire if they were set empty
		 * Should happen at init:25
		 *
		 * @since 0.0.9
		 */
		do_action( 'ingot_cookies_set' );

	}

	/**
	 * Initialize price testing
	 *
	 * @uses "ingot_cookies_set"
	 *
	 * @since 0.0.9
	 */
	public static function init_price_tests() {
		$tests = \ingot\testing\cookies\cache::instance()->get( 'price' );
		if( ! empty( $tests ) ){
			new \ingot\testing\tests\price\init( $tests );
			if ( ingot_is_edd_active() ) {
				self::track_edd();
			}
		}
	}

	/**
	 * Hook into edd sales for tracking
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 */
	protected static function track_edd() {

		add_action( 'edd_complete_purchase', array(
			"\\ingot\\testing\\tests\\price\\track",
			'track_edd_sale'
		) );

	}


}
