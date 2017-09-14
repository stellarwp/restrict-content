<?php
/**
 * Display Functions
 *
 * @package     Restrict Content
 * @subpackage  Display Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Display editor message
 *
 * @param string $content
 *
 * @return string
 */
function rcMetaDisplayEditor( $content ) {
	global $rc_options;
	global $post;

	$rcp_user_level = get_post_meta( $post->ID, 'rcp_user_level', true );

	if ( $rcp_user_level == 'Administrator' ) {
		return do_shortcode( $rc_options['editor_message'] );
	} else {
		return $content;
	}
}

/**
 * Display author message
 *
 * @param string $content
 *
 * @return string
 */
function rcMetaDisplayAuthor( $content ) {
	global $rc_options;
	global $post;

	$rcp_user_level = get_post_meta( $post->ID, 'rcp_user_level', true );

	if ( $rcp_user_level == 'Administrator' || $rcp_user_level == 'Editor' ) {
		return do_shortcode( $rc_options['author_message'] );
	} else {
		// return the content unfilitered
		return $content;
	}
}

/**
 * Display contributor message
 *
 * @param string $content
 *
 * @return string
 */
function rcMetaDisplayContributor( $content ) {
	global $rc_options;
	global $post;

	$rcp_user_level = get_post_meta( $post->ID, 'rcp_user_level', true );

	if ( $rcp_user_level == 'Administrator' || $rcp_user_level == 'Editor' || $rcp_user_level == 'Author' ) {
		return do_shortcode( $rc_options['contributor_message'] );
	} else {
		// return the content unfilitered
		return $content;
	}
}

/**
 * Display subscriber message
 *
 * @param string $content
 *
 * @return string
 */
function rcMetaDisplaySubscriber( $content ) {
	global $rc_options;
	global $post;

	$rcp_user_level = get_post_meta( $post->ID, 'rcp_user_level', true );

	if ( $rcp_user_level == 'Administrator' || $rcp_user_level == 'Editor' || $rcp_user_level == 'Author' || $rcp_user_level == 'Contributor' ) {
		return do_shortcode( $rc_options['subscriber_message'] );
	} else {
		// return the content unfilitered
		return $content;
	}
}

/**
 * Display error message to non-logged in users
 *
 * @param $content
 *
 * @return string
 */
function rcMetaDisplayNone( $content ) {
	global $rc_options;
	global $post;

	$rcp_user_level = get_post_meta( $post->ID, 'rcp_user_level', true );

	if ( ! current_user_can( 'read' ) && ( $rcp_user_level == 'Administrator' || $rcp_user_level == 'Editor' || $rcp_user_level == 'Author' || $rcp_user_level == 'Contributor' || $rcp_user_level == 'Subscriber' ) ) {
		$userLevelMessage = strtolower( $rcp_user_level );

		return do_shortcode( $rc_options[ $userLevelMessage . '_message' ] );
	} else {
		// return the content unfilitered
		return $content;
	}
}