<?php
/**
 * Membership Level Command
 *
 * @package restrict-content-pro
 * @copyright  Copyright (c) 2021, iThemes
 * @license GPL2+
 */

namespace RCP;

use WP_CLI\Formatter;
use function WP_CLI\Utils\get_flag_value;

class RCP_Membership_Level_Command {

	/** @var string[] */
	private $default_fields;

	/**
	 * RCP_Membership_Level_Command constructor
	 *
	 */
	public function __construct() {
		$this->default_fields = [
			'id',
			'name',
			'duration',
			'trial_duration',
			'price',
			'status',
			'role'
		];
	}

	/**
	 * List Membership Levels
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole membership level, returns the value of a single field
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 *
	 * @subcommand list
	 *
	 * ## EXAMPLES
	 *
	 * wp rcp membership-level list
	 * wp rcp membership-level list --format='count'
	 * wp rcp membership-level list --format='ids'
	 * wp rcp membership-level list --field=name
	 * wp rcp membership-level list --fields=id,name,description
	 */
	public function list_( $args, $assoc_args ) {

		$format = get_flag_value( $assoc_args, 'format', 'table' );

		if ( 'count' === $format ) {
			$membership_levels = rcp_get_membership_levels( [ 'count' => true ] );
			\WP_CLI::log( count( $membership_levels ) );
		} elseif ( 'ids' === $format ) {
			$membership_levels = rcp_get_membership_levels( [ 'fields' => 'ids' ] );
			\WP_CLI::log( implode( ' ', array_map( static function ( $membership_level) { return $membership_level; }, $membership_levels ) ) );
		} else {
			$fields = explode( ',', get_flag_value( $assoc_args, 'fields') );
			$membership_levels = rcp_get_membership_levels( $fields );
			$formatter = new Formatter( $assoc_args, $this->default_fields );
			$formatter->display_items( array_map( [ $this, 'format_membership_level' ], $membership_levels ) );
		}
	}

	/**
	 * Get a membership level
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The id of the membership level
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole membership level, returns the value of a single field
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *
	 * ## EXAMPLES
	 *
	 * wp rcp membership-level get 1
	 * wp rcp membership-level get 1 --field=name
	 */
	public function get( $args, $assoc_args ) {
		list ( $id ) = $args;

		$membership_level = rcp_get_membership_level( $id );

		if ( $membership_level ) {
			$formatter = new Formatter( $assoc_args, $this->default_fields );
			$formatter->display_item( $this->format_membership_level( $membership_level ) );
		} else {
			\WP_CLI::error( sprintf( 'Membership level with id: %d was not found.', $id ) );
		}
	}

	/**
	 * Delete a Membership Level
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more of the membership level ids.
	 *
	 * ## EXAMPLES
	 * wp rcp membership-level delete 1
	 * wp rcp membership-level delete 1 2 3
	 */
	public function delete( $args, $assoc_args ) {
		$status = 0;

		foreach ( $args as $id ) {
			$deleted = rcp_delete_membership_level( $id );
			if ( $deleted )  {
				\WP_CLI::log( sprintf( 'Membership Level: %d deleted', $id ) );
			} else {
				\WP_CLI::warning( sprintf( 'Could not delete Membership Level: %d', $id ) );
			}
			$status = 1;
		}

		\WP_CLI::halt( $status );
	}

	/**
	 * Create a Membership Level
	 *
	 * ## OPTIONS
	 *
	 * --name=<name>
	 * : The name of the Membership Level
	 *
	 * [--description=<description>]
	 * : The description of the Membership Level
	 *
	 * [--duration=<duration>]
	 * : The number referring to the membership level's duration
	 *
	 * [--duration_unit=<duration_unit>]
	 * : The type of duration for the membership level
	 *
	 * [--trial_duration=<trial_duration>]
	 * : The number referring to the membership level's trial duration
	 *
	 * [--trial_duration_unit=<trial_duration_unit>]
	 * : The type of trial duration for the membership level
	 *
	 * [--price=<price>]
	 * : The price of the membership level
	 *
	 * [--fee=<fee>]
	 * : The fee of the membership level
	 *
	 * [--maximum_renewals=<maximum_renewals>]
	 * : The maximum renewals for a membership level
	 *
	 * [--list_order=<list_order>]
	 * : The list order of the membership level
	 *
	 * [--status=<status>]
	 * : The status of the membership level
	 *
	 * [--role=<role>]
	 * : The role of the membership level
	 *
	 * [--porcelain]
	 * : Output just the membership level id.
	 *
	 * ## EXAMPLES
	 * wp rcp membership-level create --name='example'
	 * wp rcp membership-level create --name='example 2' --status='active'
	 * wp rcp membership-level create --name='example 3' --porcelain
	 */
	public function create( $args, $assoc_args ) {
		$membership_level = rcp_add_membership_level( $assoc_args );

		if ( is_wp_error( $membership_level ) ) {
			\WP_CLI::error( $membership_level );
		} else {
			if ( get_flag_value( $assoc_args, 'porcelain' ) ) {
				\WP_CLI::log( $membership_level );
			} else {
				\WP_CLI::success( 'Membership Level created: ' . $membership_level );
			}
		}
	}

	/**
	 * Update a Membership Level
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The membership level's id.
	 *
	 * [--name=<name>]
	 * : The name of the membership level
	 *
	 * [--description=<description>]
	 * : The description of the Membership Level
	 *
	 * [--duration=<duration>]
	 * : The number referring to the membership level's duration
	 *
	 * [--duration_unit=<duration_unit>]
	 * : The type of duration for the membership level
	 *
	 * [--trial_duration=<trial_duration>]
	 * : The number referring to the membership level's trial duration
	 *
	 * [--trial_duration_unit=<trial_duration_unit>]
	 * : The type of trial duration for the membership level
	 *
	 * [--price=<price>]
	 * : The price of the membership level
	 *
	 * [--fee=<fee>]
	 * : The fee of the membership level
	 *
	 * [--maximum_renewals=<maximum_renewals>]
	 * : The maximum renewals for a membership level
	 *
	 * [--list_order=<list_order>]
	 * : The list order of the membership level
	 *
	 * [--status=<status>]
	 * : The status of the membership level
	 *
	 * [--role=<role>]
	 * : The role of the membership level
	 *
	 * ## EXAMPLES
	 * wp rcp membership-level update 1 --name='1 Month Recurring (Updated)'
	 * wp rcp membership-level update 1 2 --price=20
	 */
	public function update( $args, $assoc_args ) {

		$formatter = new Formatter( $assoc_args, $this->default_fields );

		foreach ( $args as $id ) {
			$updated = rcp_update_membership_level( $id, $assoc_args);
			if ( $updated ) {
				\WP_CLI::log( sprintf( 'Membership Level: %d updated', $id ) );
				$membership_level = rcp_get_membership_level( $id );
				$formatter->display_item( $this->format_membership_level( $membership_level ) );
			} else {
				\WP_CLI::error( sprintf( 'Membership Level: %d update failed', $id ) );
			}
		}
	}

	/**
	 * @param Membership_Level $membership_level
	 *
	 * @return array
	 */
	private function format_membership_level( Membership_Level $membership_level ) {
		return [
			'id'                    => $membership_level->get_id(),
			'name'                  => $membership_level->get_name(),
			'description'           => $membership_level->get_description(),
			'duration'              => $membership_level->get_duration() . ' ' . $membership_level->get_duration_unit(),
			'trial_duration'        => $membership_level->get_trial_duration() . ' ' . $membership_level->get_trial_duration_unit(),
			'price'                 => $membership_level->get_price(),
			'fee'                   => $membership_level->get_fee(),
			'maximum_renewals'      => $membership_level->get_maximum_renewals(),
			'after_final_payment'   => $membership_level->get_after_final_payment(),
			'list_order'            => $membership_level->get_list_order(),
			'level'                 => $membership_level->get_access_level(),
			'status'                => $membership_level->get_status(),
			'role'                  => $membership_level->get_role()
		];
	}
}

\WP_CLI::add_command( 'rcp membership-level', new RCP_Membership_Level_Command() );
