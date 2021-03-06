<?php
/**
 * Generic CRUD using options storage
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\crud;


abstract class options_crud extends crud {

	/**
	 * Get a collection of items
	 *
	 * @since 0.0.5
	 *
	 * @param array $params {
	 *  $ids array Optional. Array of ids to get
	 *  $limit int Optional. Limit results, default is -1 which gets all. Ignored if $ids is used.
	 *  $page int Optional. Page of results, used with $limit. Default is 1. Ignored if $ids is used.
	 * }
	 *
	 * @return array
	 */
	public static function get_items( $params ) {
		if( ! empty( $params[ 'ids' ] ) ) {
			return self::select_by_ids( $params[ 'ids' ] );
		}

		$limit = $page = 1;
		$args = wp_parse_args(
			$params,
			array(
				'ids' => array(),
				'limit' => -1,
				'page' => 1,
			)
		);

		if ( -1 == $args[ 'limit' ] ) {
			//@todo better hack?
			$args[ 'limit' ] = 90000001;
		}

		extract( $args );
		return self::get_all( $limit, $page );

	}

	/**
	 * Get an item
	 *
	 * @param int $id Item ID
	 *
	 * @return array Item config array.
	 */
	public static function read( $id ) {

		/**
		 * Runs before an object is read.
		 *
		 * @since 0.0.6
		 *
		 * @param int $id Item ID
		 * @param string $what Object name
		 */
		do_action( 'ingot_crud_pre_read', $id, static::what() );
		$item = get_option( self::key_name( $id ), array() );

		if( empty( $item ) ) {
			return false;
		}

		$item = static::fill_in( $item );
		if( ! empty( $item ) && ! isset( $item[ 'ID' ] ) ) {
			$item[ 'ID' ] = $id;
		}

		/**
		 * Runs before an object is returned from DB
		 *
		 * @since 0.0.6
		 *
		 * @param array $item Data to be returned
		 * @param string $what Object name
		 */
		$item = apply_filters( 'ingot_crud_read', $item, static::what() );
		return $item;

	}

	/**
	 * Delete an item or all items
	 *
	 * @since 0.0.1
	 *
	 * @param int|string $id Item id or "all" to delete all
	 *
	 * @return bool
	 */
	public static function delete( $id ) {
		/**
		 * Runs before an object is deleted.
		 *
		 * @since 0.0.6

		 * @param int $id Item ID
		 * @param string $what Object name
		 */
		do_action( 'ingot_crud_pre_delete', $id, static::what() );
		if ( is_numeric( $id ) ) {
			return delete_option( self::key_name( $id ) );
		}

		if ( 'all' == $id ){
			return self::delete_all();
		}

	}

	/**
	 * Generic save for read/update
	 *
	 * @since 0.0.4
	 *
	 * @param array $data Item con
	 * @param int $id Optional. Item ID. Not used or needed if using to create.
	 * @param bool|false $bypass_cap
	 *
	 * @return int|bool||WP_Error Item ID if created,or false if not created, or error if not allowed to create.
	 */
	protected static function save( $data, $id = null, $bypass_cap = false  ) {
		$data = self::prepare_data( $data );
		if( is_wp_error( $data ) ) {
			return $data;

		}

		$can = self::can( $id, $bypass_cap );

		if ( $can ) {
			if ( ! $id ) {
				$id = self::increment_id();
			}

			$key = self::key_name( $id );

			if ( ! isset( $data[ 'ID' ] ) || $data[ 'ID' ] != $id ) {
				$data[ 'ID' ] = $id;

			}

			$saved = update_option( $key, $data, false  );
			if ( $saved ) {
				do_action( 'ingot_config_saved', $id, $data, static::what() );
				return (int) $id;

			}else{
				return new \WP_Error( 'ingot-can-not-save-config' );

			}
		}else{
			return new \WP_Error( 'ingot-save-config-not-allowed' );

		}

	}

	/**
	 * Query all, possibly with limit and offset
	 *
	 * @since 0.0.5
	 *
	 * @param int $limit How many results to return
	 * @param int $page Page
	 *
	 * @return array
	 */
	protected static function get_all( $limit, $page = 1) {
		$offset = self::calculate_offset( $limit, $page );

		global $wpdb;
		$like = self::like();
		$sql = sprintf( 'SELECT * FROM `%1s` WHERE `option_name` LIKE "%2s" ORDER BY `option_id` DESC LIMIT %d OFFSET %d', $wpdb->options, $like, $limit, $offset );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return self::format_results_from_sql_query( $results );
	}

	/**
	 * Select by IDs
	 *
	 * @param array $ids An array of IDs to select
	 *
	 * @return array
	 */
	protected static function select_by_ids( $ids ) {
		global $wpdb;
		$what = static::what();
		$like_pattern = "'ingot_%s_%d'";

		if ( is_array( $ids ) ) {
			foreach ( $ids as $id ) {
				$in[] = sprintf( $like_pattern, $what, $id );
			}
		}else{
			return new \WP_Error( __METHOD__ );
		}

		$in = implode( ',', $in );

		$sql = sprintf( 'SELECT * FROM `%1s` WHERE `option_name` IN (%2s)', $wpdb->options, $in );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return self::format_results_from_sql_query( $results );
	}

	/**
	 * Delete all items
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @return array|null|object
	 */
	protected static function delete_all() {
		global $wpdb;
		$what = static::what();
		$like = "%ingot_{$what}_%";
		$sql = sprintf( 'DELETE FROM `%s` WHERE `option_name` LIKE "%s"', $wpdb->options, $like  );

		$results = $wpdb->get_results( $sql );
		self::increment_id( true );
		return $results;
	}

	/**
	 * Get an incriemnted ID and increase counter
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @param bool $reset Optional. If true, will reset counter to 1. Default is false.
	 *
	 * @return int
	 */
	protected static function increment_id( $reset = false ) {

		$key = 'ingot_id_increment_' . static::what();
		if ( false == $reset ) {
			$id = get_option( $key, 1 );
			update_option( $key, $id + 1 );
			return $id;

		}else{
			update_option( $key, 1 );
			return 1;
			
		}

	}


	/**
	 * Create an option key name
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @param int $id Item ID
	 *
	 * @return string
	 */
	private static function key_name( $id ) {
		if( is_numeric( $id ) ) {
			$what = static::what();
			return "ingot_{$what}_{$id}";

		}


	}

	/**
	 * Format results from $wpdb->results()
	 *
	 * @since 0.0.7
	 *
	 * @access protected
	 *
	 * @param $results
	 *
	 *
	 * @return array
	 */
	protected static function format_results_from_sql_query( $results ) {
		$all = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$all[] = maybe_unserialize( $result['option_value'] );
			}
		}


		return $all;
	}

	/**
	 * Prepare WHERE for a like
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 *
	 * @return string
	 */
	protected static function like() {
		$what = static::what();
		$like = "%ingot_{$what}_%";

		return $like;
	}

	/**
	 * Get total number of items
	 *
	 * @since 0.2.0
	 *
	 * @return int
	 */
	public static function total(){
		global $wpdb;
		$what = static::what();
		$like = "%ingot_{$what}%";
		$sql = sprintf( 'SELECT `option_id` FROM %s WHERE `option_name` LIKE "%s"', $wpdb->options, $like );
		$wpdb->get_results( $sql, ARRAY_A );
		if ( is_numeric(  $wpdb->num_rows ) ) {
			return $wpdb->num_rows;
		}

	}


}
