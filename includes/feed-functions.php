<?php

/*******************************************
* Restrict Content Feed Functions
*******************************************/

function rcCheckFeed() {
	add_filter('the_content', 'rcIsFeed');
}
add_action('rss_head', 'rcCheckFeed');

function rcIsFeed($content) {
	$custom_meta = get_post_custom($post->ID);
	$rcUserLevel = isset( $custom_meta['rcUserLevel'] ) ? $custom_meta['rcUserLevel'][0] : false;
	$rcFeedHide  = isset( $custom_meta['rcFeedHide'] ) ? $custom_meta['rcFeedHide'][0] : false;

	if (is_feed() && $rcFeedHide == 'on') {
		return sprintf( __( 'This content is restricted to %ss', 'restrict-content' ), $rcUserLevel );
	} else {
		return $content;
	}
	
}