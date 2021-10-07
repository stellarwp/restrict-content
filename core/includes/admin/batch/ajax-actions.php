<?php
/**
 * Batch Ajax Functions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

namespace RCP\Utils\Batch;

/**
 * Processes ajax batch jobs.
 *
 * @since 3.0
 */
function ajax_process_batch() {

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		wp_die();
	}

	check_ajax_referer( 'rcp_batch_nonce', 'rcp_batch_nonce' );

	$step   = ! empty( $_POST['step'] ) ? absint( $_POST['step'] ) : 0;
	$job_id = ! empty( $_POST['job_id'] ) ? sanitize_text_field( $_POST['job_id'] ) : false;

	$job = get_job( $job_id );

	if ( ! $job_id || empty( $job ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid job ID provided.', 'rcp' )
		) );
	}

	/**
	 * Use this hook to include the job callback class file.
	 *
	 * @param string $job_callback Name of the class file.
	 */
	do_action( 'rcp_batch_processing_class_include', $job->get_callback() );

	if ( ! class_exists( $job->get_callback() ) ) {
		wp_send_json_error( array(
			'message' => __( 'Job callback doesn\'t exist.', 'rcp' )
		) );
	}

	if ( $job->get_step() != $step ) {
		$job->set_step( $step );
	}

	$result = $job->process_batch();

	if ( true === $result ) {
		wp_send_json_success( array(
			'step'             => $step,
			'next_step'        => $job->is_completed() ? false : ++$step,
			'job_name'         => $job->get_name(),
			'job_description'  => $job->get_description(),
			'percent_complete' => $job->is_completed() ? 100 : $job->get_percent_complete(),
			'items_processed'  => $job->get_current_count(),
			'complete'         => $job->is_completed(),
			'complete_message' => $job->get_callback_object()->get_complete_message(),
			'has_errors'       => $job->has_errors(),
			'errors'           => '<p><strong>' . __( 'Errors', 'rcp' ) . '</strong></p>' . implode( '<br>', $job->get_errors() )
		) );
	}

	// An error occurred during process. Return the error message.
	if ( is_wp_error( $result ) ) {
		// TODO: what to do with failed jobs?
		wp_send_json_error( array(
			'message'         => $result->get_error_message(),
			'step'            => $step,
			'job_name'        => $job->get_name(),
			'job_description' => $job->get_description()
		) );
	}

	// We didn't get an expected return.
	wp_send_json_error( array(
		'message'         => sprintf( __( 'An unknown error occurred processing %s.', 'rcp' ), $job->get_name() ),
		'step'            => $step,
		'job_name'        => $job->get_name(),
		'job_description' => $job->get_description()
	) );

}

add_action( 'wp_ajax_rcp_process_batch', __NAMESPACE__ . '\ajax_process_batch' );
