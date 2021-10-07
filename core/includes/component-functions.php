<?php
/**
 * Component Functions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   GPL2+
 */

/**
 * Registers a new RCP database component
 *
 * @param string $key  Component key (`customers`, `memberships`, etc).
 * @param array  $args Component arguments.
 *
 * @since 3.4
 * @return void
 */
function rcp_register_component( $key, $args ) {

	$args = wp_parse_args( $args, array(
		'name'   => sanitize_key( $key ),
		'schema' => '\\RCP\\Database\\Schema',
		'table'  => '\\RCP\\Database\\Table',
		'query'  => '\\RCP\Database\\Query',
		'object' => '\\RCP\\Database\\Row',
		'meta'   => false
	) );

	// Set up the component.
	restrict_content_pro()->components[ $args['name'] ] = new RCP\Component( $args );

}

/**
 * Retrieves a component object by its key.
 *
 * @param string $key Component key.
 *
 * @since 3.4
 * @return \RCP\Component|false Component object on success, false on failure.
 */
function rcp_get_component( $key ) {

	$key = sanitize_key( $key );

	return isset( restrict_content_pro()->components[ $key ] ) ? restrict_content_pro()->components[ $key ] : false;

}

/**
 * Sets up all components
 *
 * @since 3.4
 * @return void
 */
function rcp_setup_components() {

	static $setup = false;

	// Ensure components are only registered once.
	if ( false !== $setup ) {
		return;
	}

	rcp_register_component( 'customers', array(
		'schema' => '\\RCP\\Database\\Schemas\\Customers',
		'table'  => '\\RCP\\Database\\Tables\\Customers',
		'query'  => '\\RCP\\Database\\Queries\\Customer',
		'object' => 'RCP_Customer'
	) );

	rcp_register_component( 'discounts', array(
		'schema' => '\\RCP\\Database\\Schemas\\Discounts',
		'table'  => '\\RCP\\Database\\Tables\\Discounts',
		'query'  => '\\RCP\\Database\\Queries\\Discount',
		'object' => 'RCP_Discount'
	) );

	rcp_register_component( 'logs', array(
		'schema' => '\\RCP\\Database\\Schemas\\Logs',
		'table'  => '\\RCP\\Database\\Tables\\Logs',
		'query'  => '\\RCP\\Database\\Queries\\Log',
		'object' => '\\RCP\\Logs\\Log'
	) );

	rcp_register_component( 'membership_counts', array(
		'schema' => '\\RCP\\Database\\Schemas\\Membership_Counts',
		'table'  => '\\RCP\\Database\\Tables\\Membership_Counts',
		'query'  => '\\RCP\\Database\\Queries\\Membership_Count',
		'object' => 'RCP_Membership_Count'
	) );

	rcp_register_component( 'membership_levels', array(
		'schema' => '\\RCP\\Database\\Schemas\\Membership_Levels',
		'table'  => '\\RCP\\Database\\Tables\\Membership_Levels',
		'query'  => '\\RCP\\Database\\Queries\\Membership_Level',
		'object' => '\\RCP\\Membership_Level'
	) );

	rcp_register_component( 'memberships', array(
		'schema' => '\\RCP\\Database\\Schemas\\Memberships',
		'table'  => '\\RCP\\Database\\Tables\\Memberships',
		'query'  => '\\RCP\\Database\\Queries\\Membership',
		'object' => 'RCP_Membership',
		'meta'   => '\\RCP\\Database\\Tables\\Membership_Meta'
	) );

	rcp_register_component( 'queue', array(
		'schema' => '\\RCP\\Database\\Schemas\\Queue',
		'table'  => '\\RCP\\Database\\Tables\\Queue',
		'query'  => '\\RCP\\Database\\Queries\\Queue',
		'object' => '\\RCP\\Utils\\Batch\\Job'
	) );

	$setup = true;

}
