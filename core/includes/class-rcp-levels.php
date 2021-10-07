<?php
/**
 * RCP Membership Levels class
 *
 * This class handles querying, inserting, updating, and removing membership levels
 * Also includes other membership level helper functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Membership Levels
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

/**
 * Class RCP_Levels
 *
 * @deprecated 3.4
 */
class RCP_Levels {

	/**
	 * Holds the name of our levels database table
	 *
	 * @access  public
	 * @since   1.5
	*/
	public $db_name;

	/**
	 * Holds the name of our level meta database table
	 *
	 * @access  public
	 * @since   2.6
	*/
	public $meta_db_name;


	/**
	 * Holds the version number of our levels database table
	 *
	 * @access  public
	 * @since   1.5
	*/
	public $db_version;


	/**
	 * Get things started
	 *
	 * @since   1.5
	 * @return  void
	 */
	function __construct() {

		$this->db_name      = rcp_get_levels_db_name();
		$this->meta_db_name = rcp_get_level_meta_db_name();
		$this->db_version   = '1.6';

	}


	/**
	 * Retrieve a specific membership level from the database
	 *
	 * @deprecated 3.4 In favour of `rcp_get_membership_level()`
	 * @see        rcp_get_membership_level()
	 *
	 * @param int $level_id ID of the level to retrieve.
	 *
	 * @access     public
	 * @since      1.5
	 * @return  object
	 */
	public function get_level( $level_id = 0 ) {

		_deprecated_function( __METHOD__, '3.4', 'rcp_get_membership_level' );

		return rcp_get_membership_level( $level_id );

	}

	/**
	 * Retrieve a specific membership level from the database
	 *
	 * @deprecated 3.4 In favour of `rcp_get_membership_level_by()`
	 * @see        rcp_get_membership_level_by()
	 *
	 * @param string $field Name of the field to check against.
	 * @param mixed  $value Value of the field.
	 *
	 * @access     public
	 * @since      1.8.2
	 * @return  object|null
	 */
	public function get_level_by( $field = 'name', $value = '' ) {

		_deprecated_function( __METHOD__, '3.4', 'rcp_get_membership_level_by' );

		return rcp_get_membership_level_by( $field, $value );

	}


	/**
	 * Retrieve all membership levels from the database
	 *
	 * @deprecated 3.4 In favour of `rcp_get_membership_levels()`
	 * @see        rcp_get_membership_levels()
	 *
	 * @param array $args Query arguments to override the defaults.
	 *
	 * @access     public
	 * @since      1.5
	 * @return  array|false Array of level objects or false if none are found.
	 */
	public function get_levels( $args = array() ) {

		_deprecated_function( __METHOD__, '3.4', 'rcp_get_membership_levels' );

		$args = wp_parse_args( $args, array(
			'status'  => 'all',
			'limit'   => null,
			'offset'  => 0,
			'orderby' => 'list_order'
		) );

		if ( 'all' === $args['status'] ) {
			unset( $args['status'] ); //  It's "all" by default.
		}

		if ( ! empty( $args['limit'] ) ) {
			$args['number'] = absint( $args['limit'] );
		}
		unset( $args['limit'] );

		return rcp_get_membership_levels( $args );
	}

	/**
	 * Count the total number of membership levels in the database
	 *
	 * @deprecated 3.4 In favour of `rcp_count_membership_levels()`
	 * @see        rcp_count_membership_levels()
	 *
	 * @param array $args Query arguments to override the defaults.
	 *
	 * @access     public
	 * @return int
	 */
	public function count( $args = array() ) {

		_deprecated_function( __METHOD__, '3.4', 'rcp_count_membership_levels' );

		$args = wp_parse_args( $args, array(
			'status' => 'all'
		) );

		if ( 'all' === $args['status'] ) {
			unset( $args['status'] ); // It's "all" by default.
		}

		return rcp_count_membership_levels( $args );

	}


	/**
	 * Retrieve a field for a membership level
	 *
	 * @deprecated 3.4
	 *
	 * @param   int    $level_id ID of the level.
	 * @param   string $field    Name of the field to retrieve the value for.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  mixed
	 */
	public function get_level_field( $level_id = 0, $field = '' ) {

		_deprecated_function( __METHOD__, '3.4' );

		$level = rcp_get_membership_level( $level_id );

		if ( ! method_exists( $level, 'get_' . $field ) ) {
			return false;
		}

		$value = call_user_func( array( $level, 'get_' . $field ) );

		/**
		 * Filters the level field.
		 *
		 * @deprecated 3.4
		 */
		return apply_filters_deprecated( 'rcp_get_level_field', array( $value, $level_id, $field ), '3.4' );

	}


	/**
	 * Insert a membership level into the database
	 *
	 * @deprecated 3.4 In favour of `rcp_add_membership_level()`
	 * @see        rcp_add_membership_level()
	 *
	 * @param array $args Arguments to override the defaults.
	 *
	 * @access     public
	 * @since      1.5
	 * @return  int|WP_Error ID of the newly created level or WP_Error on failure.
	 */
	public function insert( $args = array() ) {

		_deprecated_function( __METHOD__, '3.4', 'rcp_add_membership_level' );

		return rcp_add_membership_level( $args );

	}


	/**
	 * Update an existing membership level
	 *
	 * @deprecated 3.4 In favour of `rcp_update_membership_level()`
	 * @see        rcp_update_membership_level()
	 *
	 * @param int   $level_id ID of the level to update.
	 * @param array $args     Fields and values to update.
	 *
	 * @access     public
	 * @since      1.5
	 * @return  true|WP_Error True if the update was successful, WP_Error on failure.
	 */
	public function update( $level_id = 0, $args = array() ) {

		_deprecated_function( __METHOD__, '3.4', 'rcp_update_membership_level' );

		return rcp_update_membership_level( $level_id, $args );

	}


	/**
	 * Delete a membership level
	 *
	 * @deprecated 3.4 In favour of `rcp_delete_membership_level()`
	 * @see        rcp_delete_membership_level()
	 *
	 * @param int $level_id ID of the level to delete.
	 *
	 * @access     public
	 * @since      1.5
	 * @return  void
	 */
	public function remove( $level_id = 0 ) {

		_deprecated_function( __METHOD__, '3.4', 'rcp_delete_membership_level' );

		rcp_delete_membership_level( $level_id );

	}

	/**
	 * Retrieve level meta field for a membership level.
	 *
	 * @param   int    $level_id      Membership level ID.
	 * @param   string $meta_key      The meta key to retrieve.
	 * @param   bool   $single        Whether to return a single value.
	 *
	 * @access  public
	 * @since   2.6
	 * @return  mixed  Single metadata value, or array of values
	 */
	public function get_meta( $level_id = 0, $meta_key = '', $single = false ) {
		return get_metadata( 'level', $level_id, $meta_key, $single );
	}

	/**
	 * Add meta data field to a membership level.
	 *
	 * @param   int    $level_id      Membership level ID.
	 * @param   string $meta_key      Metadata name.
	 * @param   mixed  $meta_value    Metadata value.
	 * @param   bool   $unique        Optional, default is false. Whether the same key should not be added.
	 *
	 * @access  public
	 * @since   2.6
	 * @since   3.5.10                Removing default values for $level_id and $meta_key
	 *
	 * @return  int|false             The meta ID on success, false on failure.
	 */
	public function add_meta( $level_id, $meta_key, $meta_value, $unique = false ) {
		return add_metadata( 'level', $level_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update level meta field based on membership level ID.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with the
	 * same key and membership level ID.
	 *
	 * If the meta field for the membership level does not exist, it will be added.
	 *
	 * @param   int    $level_id      Membership level ID.
	 * @param   string $meta_key      Metadata key.
	 * @param   mixed  $meta_value    Metadata value.
	 * @param   mixed  $prev_value    Optional. Previous value to check before removing.
	 *
	 * @access  public
	 * @since   2.6
	 * @since   3.5.10                Removing default values for $level_id and $meta_key
	 *
	 * @return  int|bool              Meta ID if the key didn't exist, true on successful update, false on failure.
	 */
	public function update_meta( $level_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_metadata( 'level', $level_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Remove metadata matching criteria from a membership level.
	 *
	 * You can match based on the key, or key and value. Removing based on key and
	 * value, will keep from removing duplicate metadata with the same key. It also
	 * allows removing all metadata matching key, if needed.
	 *
	 * @param   int    $level_id      Membership level ID.
	 * @param   string $meta_key      Metadata name.
	 * @param   mixed  $meta_value    Optional. Metadata value.
	 *
	 * @access  public
	 * @since   2.6
	 * @return  bool                  True on successful delete, false on failure.
	 */
	public function delete_meta( $level_id = 0, $meta_key = '', $meta_value = '' ) {
		return delete_metadata( 'level', $level_id, $meta_key, $meta_value );
	}

	/**
	 * Removes all metadata for the specified membership level.
	 *
	 * @since 2.6.6
	 * @uses wpdb::query()
	 * @uses wpdb::prepare()
	 *
	 * @param  int $level_id membership level ID.
	 * @return int|false Number of rows affected/selected or false on error.
	 */
	public function remove_all_meta_for_level_id( $level_id = 0 ) {

		global $wpdb;

		if ( empty( $level_id ) ) {
			return;
		}

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->levelmeta} WHERE level_id = %d", absint( $level_id ) ) );
	}

	/**
	 * Validates that the amount is a valid format.
	 *
	 * Private for now until we finish validation for all fields.
	 *
	 * @since 2.7
	 * @access private
	 * @return boolean true if valid, false if not.
	 */
	private function valid_amount( $amount ) {
		return filter_var( $amount, FILTER_VALIDATE_FLOAT );
	}

	/**
	 * Determines if the specified membership level has a trial option.
	 *
	 * @deprecated 3.4 In favour of `RCP\Membership_Level::has_trial()`
	 * @see        RCP\Membership_Level::has_trial()
	 *
	 * @access     public
	 * @since      2.7
	 *
	 * @param int $level_id The membership level ID.
	 *
	 * @return boolean true if the level has a trial option, false if not.
	 */
	public function has_trial( $level_id = 0 ) {

		_deprecated_function( __METHOD__, '3.4', 'RCP\Membership_Level::has_trial' );

		$level = rcp_get_membership_level( $level_id );

		if ( ! $level instanceof RCP\Membership_Level ) {
			return false;
		}

		return $level->has_trial();
	}

	/**
	 * Retrieves the trial duration for the specified membership level.
	 *
	 * @deprecated 3.4 In favour of `RCP\Membership_Level::get_trial_duration()`
	 * @see        RCP\Membership_Level::get_trial_duration()
	 *
	 * @access     public
	 * @since      2.7
	 *
	 * @param int $level_id The membership level ID.
	 *
	 * @return int The duration of the trial. 0 if there is no trial.
	 */
	public function trial_duration( $level_id = 0 ) {

		_deprecated_function( __METHOD__, '3.4', 'RCP\Membership_Level::get_trial_duration' );

		$level = rcp_get_membership_level( $level_id );

		if ( ! $level instanceof RCP\Membership_Level ) {
			return 0;
		}

		return $level->get_trial_duration();

	}

	/**
	 * Retrieves the trial duration unit for the specified membership level.
	 *
	 * @deprecated 3.4 In favour of `RCP\Membership_Level::get_trial_duration_unit()`
	 * @see        RCP\Membership_Level::get_trial_duration_unit()
	 *
	 * @access     public
	 * @since      2.7
	 *
	 * @param int $level_id The membership level ID.
	 *
	 * @return string The duration unit of the trial.
	 */
	public function trial_duration_unit( $level_id = 0 ) {

		_deprecated_function( __METHOD__, '3.4', 'RCP\Membership_Level::get_trial_duration_unit' );

		$level = rcp_get_membership_level( $level_id );

		if ( ! $level instanceof RCP\Membership_Level ) {
			return 'day';
		}

		return $level->get_trial_duration_unit();
	}

}
