<?php
/**
 * User Checks
 *
 * @package     Restrict Content
 * @subpackage  User Checks
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Add a different filter to the post content based on
 * the current user's capabilities.
 *
 * @return void
 */
function rcCheckUser() {
	if ( current_user_can( 'read' ) ) {
		if ( current_user_can( 'edit_posts' ) ) {
			if ( current_user_can( 'upload_files' ) ) {
				if ( current_user_can( 'moderate_comments' ) ) {
					if ( current_user_can( 'switch_themes' ) ) {
						//do nothing here for admin
					} else {
						add_filter( 'the_content', 'rcMetaDisplayEditor' );
					}
				} else {
					add_filter( 'the_content', 'rcMetaDisplayAuthor' );
				}
			} else {
				add_filter( 'the_content', 'rcMetaDisplayContributor' );
			}
		} else {
			add_filter( 'the_content', 'rcMetaDisplaySubscriber' );
		}
	} else {
		add_filter( 'the_content', 'rcMetaDisplayNone' );
	}
}

add_action( 'loop_start', 'rcCheckUser' );