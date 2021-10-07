<?php
/**
 * Batch Functions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

namespace RCP\Utils\Batch;

/**
 *
 * Adds a batch job for later processing.
 *
 * @since 3.0
 *
 * @param array $config      {
 *
 * @type string $name        Job name.
 * @type string $description Job description.
 * @type string $callback    Callback used to process the job.
 * @type array  $data        Any extra data needed for processing.
 * }
 * @return int|\WP_Error Job ID if the job was registered, WP_Error if an invalid configuration was given or the job
 *                       otherwise failed to be added.
 */
function add_batch_job( array $config ) {

	$default_args = array(
		'queue'        => 'rcp_core',
		'status'       => 'incomplete',
		'date_created' => current_time( 'mysql' )
	);

	$config = wp_parse_args( $config, $default_args );

	$error_message = sprintf( __( 'You must supply a valid queue, name, and callback in %s', 'rcp' ), __FUNCTION__ );

	try {

		$clean_config = array(
			'queue'          => 'rcp_core',
			'name'           => '',
			'description'    => '',
			'callback'       => '',
			'total_count'    => 0,
			'current_count'  => 0,
			'step'           => 0,
			'status'         => 'incomplete',
			'data'           => '',
			'date_created'   => current_time( 'mysql' ),
			'date_completed' => null
		);

		foreach ( [ 'name', 'callback' ] as $key ) {
			if ( empty( $config[$key] ) ) {
				throw new \InvalidArgumentException( $error_message );
			}
		}

		foreach ( $config as $key => $value ) {
			switch ( $key ) {
				case 'queue' :
					$clean_config['queue'] = sanitize_text_field( $value );
					break;

				case 'name':
					$clean_config['name'] = sanitize_text_field( $value );
					break;

				case 'description':
					$clean_config['description'] = wp_kses_post( $value );
					break;

				case 'callback':
					$clean_config['callback'] = sanitize_text_field( $value );
					break;

				case 'total_count' :
				case 'current_count' :
				case 'step' :
					$clean_config[$key] = absint( $value );
					break;

				case 'status':
					$clean_config['status'] = sanitize_key( $config['status'] );
					break;

				case 'data' :
					$clean_config['data'] = is_array( $config['data'] ) ? maybe_serialize( $config['data'] ) : '';
					break;

				case 'date_created' :
					$clean_config['date_created'] = sanitize_text_field( $value );
					break;
			}
		}

		$queue_query = new \RCP\Database\Queries\Queue();

		$existing = $queue_query->query( array(
			'name'  => $clean_config['name'],
			'queue' => $clean_config['queue']
		) );

		if ( ! empty( $existing ) ) {
			throw new \InvalidArgumentException( sprintf( __( 'Unable to add job %s to the %s queue. It already exists.', 'rcp' ), $clean_config['name'], $clean_config['queue'] ) );
		}

		$job_id = $queue_query->add_item( array(
			'queue'          => $clean_config['queue'],
			'name'           => $clean_config['name'],
			'description'    => $clean_config['description'],
			'callback'       => $clean_config['callback'],
			'total_count'    => $clean_config['total_count'],
			'current_count'  => $clean_config['current_count'],
			'step'           => $clean_config['step'],
			'status'         => $clean_config['status'],
			'data'           => $clean_config['data'],
			'date_created'   => $clean_config['date_created'],
			'date_completed' => null
		) );

	} catch ( \InvalidArgumentException $exception ) {
		add_action( 'admin_notices', function () use ( $config, $exception ) {
			echo '<div class="error"><p>' . sprintf( __( 'There was an error adding job: %s. Error message: %s If this issue persists, please contact the Restrict Content Pro support team.', 'rcp' ), $config['name'], $exception->getMessage() ) . '</p></div>';
		} );
		return new \WP_Error( 'invalid_job_config', sprintf( __( 'Invalid job configuration: %s', 'rcp' ), $exception->getMessage() ) );
	}

	if ( empty( $job_id ) ) {
		return new \WP_Error( 'error_inserting_job', __( 'Failed to add job to queue', 'rcp' ) );
	}

	return $job_id;
}

/**
 * Retrieve a single job by ID.
 *
 * @param int $job_id ID of the job to retrieve.
 *
 * @since 3.0
 * @return Job|false
 */
function get_job( $job_id ) {

	$queue_query = new \RCP\Database\Queries\Queue();
	$job_config  = $queue_query->get_item( absint( $job_id ) );

	return $job_config;

}

/**
 * Update a job
 *
 * @param int   $job_id ID of the job to update.
 * @param array $data   Data to update.
 *
 * @since 3.1
 * @return bool
 */
function update_job( $job_id, $data = array() ) {

	$queue_query = new \RCP\Database\Queries\Queue();

	// Maybe serialize the data.
	if ( ! empty( $data['data'] ) ) {
		$data['data'] = maybe_serialize( $data['data'] );
	}

	return $queue_query->update_item( $job_id, $data );

}

/**
 * Get a single job by a field/value pair.
 *
 * @param string $field Column to search in.
 * @param string $value Value of the row.
 *
 * @since 3.1
 * @return Job|false
 */
function get_job_by( $field = '', $value = '' ) {

	$queue_query = new \RCP\Database\Queries\Queue();

	return $queue_query->get_item_by( $field, $value );

}

/**
 * Get jobs
 *
 * @param array $args Query arguments to override the defaults.
 *
 * @since 3.0
 * @return array Array of Job objects.
 */
function get_jobs( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'queue' => 'rcp_core'
	) );

	$queue_query = new \RCP\Database\Queries\Queue();

	return $queue_query->query( $args );

}

/**
 * Deletes a job.
 *
 * @since 3.0
 *
 * @param int $job_id ID of job to delete.
 *
 * @return bool True if the job is deleted, false if not.
 */
function delete_batch_job( $job_id ) {

	$queue_query = new \RCP\Database\Queries\Queue();
	$deleted     = $queue_query->delete_item( absint( $job_id ) );

	return ! empty( $deleted );

}

/**
 * Processes the specified batch job.
 *
 * @since 3.0
 *
 * @param int $job_id The ID job to process.
 *
 * @return bool|\WP_Error True if the batch was successful, WP_Error if not.
 */
function process_batch( $job_id ) {

	$job = get_job( $job_id );

	if ( empty( $job ) ) {
		return new \WP_Error( 'invalid_job_id', __( 'Invalid job ID.', 'rcp' ) );
	}

	return $job->process_batch();
}

/**
 * Display admin notices.
 *
 * @since 3.0
 *
 * @return void
 */
function display_admin_notice() {

	// Don't show notices on batch processing page.
	if ( isset( $_GET['rcp-queue'] ) ) {
		return;
	}

	$queue = get_jobs( array(
		'status' => 'incomplete'
	) );

	if ( ! empty( $queue ) ) {
		echo '<div class="notice notice-info"><p>' . __( 'Restrict Content Pro needs to perform system maintenance. This maintenance is <strong>REQUIRED</strong>.', 'rcp' ) . '</p>';
		echo '<p>' . sprintf( __( '<a href="%s">Click here</a> to learn more and start the upgrade.', 'rcp' ), esc_url( admin_url( 'admin.php?page=rcp-tools&tab=batch&rcp-queue=rcp_core' ) ) );
		echo '</div>';
	}
}

add_action( 'admin_notices', __NAMESPACE__ . '\display_admin_notice' );
