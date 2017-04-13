<?php
/**
 * Feed Functions
 *
 * @package     Restrict Content
 * @subpackage  Feed Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Add content filter when on the RSS feed
 *
 * @see rcIsFeed()
 *
 * @return void
 */
function rcCheckFeed() {
	add_filter( 'the_content', 'rcIsFeed' );
}

add_action( 'rss_head', 'rcCheckFeed' );

/**
 * Maybe adds restriction message to post content in RSS feeds
 *
 * @param string $content
 *
 * @return string
 */
function rcIsFeed( $content ) {
	$custom_meta = get_post_custom( $post->ID );
	$rcUserLevel = isset( $custom_meta['rcUserLevel'] ) ? $custom_meta['rcUserLevel'][0] : false;
	$rcFeedHide  = isset( $custom_meta['rcFeedHide'] ) ? $custom_meta['rcFeedHide'][0] : false;

	if ( is_feed() && $rcFeedHide == 'on' ) {
		return sprintf( __( 'This content is restricted to %ss', 'restrict-content' ), $rcUserLevel );
	} else {
		return $content;
	}

}