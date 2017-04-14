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

	$rcUserLevel = get_post_meta( $post->ID, 'rcUserLevel', true );

	if ( $rcUserLevel == 'Administrator' ) {
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

	$rcUserLevel = get_post_meta( $post->ID, 'rcUserLevel', true );

	if ( $rcUserLevel == 'Administrator' || $rcUserLevel == 'Editor' ) {
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

	$rcUserLevel = get_post_meta( $post->ID, 'rcUserLevel', true );

	if ( $rcUserLevel == 'Administrator' || $rcUserLevel == 'Editor' || $rcUserLevel == 'Author' ) {
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

	$rcUserLevel = get_post_meta( $post->ID, 'rcUserLevel', true );

	if ( $rcUserLevel == 'Administrator' || $rcUserLevel == 'Editor' || $rcUserLevel == 'Author' || $rcUserLevel == 'Contributor' ) {
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

	$rcUserLevel = get_post_meta( $post->ID, 'rcUserLevel', true );

	if ( ! current_user_can( 'read' ) && ( $rcUserLevel == 'Administrator' || $rcUserLevel == 'Editor' || $rcUserLevel == 'Author' || $rcUserLevel == 'Contributor' || $rcUserLevel == 'Subscriber' ) ) {
		$userLevelMessage = strtolower( $rcUserLevel );

		return do_shortcode( $rc_options[ $userLevelMessage . '_message' ] );
	} else {
		// return the content unfilitered
		return $content;
	}
}