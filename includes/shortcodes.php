<?php

/*******************************************
* Restrict Content Short Codes
*******************************************/

function restrict_shortcode( $atts, $content = null ) {
   extract( shortcode_atts( array(
      'userlevel' => 'none',
      ), $atts ) );
	  
      global $rc_options;

      if ($userlevel == 'admin' && current_user_can('switch_themes')) {
            return do_shortcode($content);
      }
      if ($userlevel == 'editor' && current_user_can('moderate_comments')) {
            return do_shortcode($content);
      }
      if ($userlevel == 'author' && current_user_can('upload_files')) {
            return do_shortcode($content);
      }
      if ($userlevel == 'contributor' && current_user_can('edit_posts')) {
            return do_shortcode($content);
      }
      if ($userlevel == 'subscriber' && current_user_can('read')) {
            return do_shortcode($content);
      }
      if ($userlevel == 'none' && is_user_logged_in()) {
            return do_shortcode($content);
      } else{ 
            return '<span style="color: red;">' . str_replace('{userlevel}', $userlevel, $rc_options['shortcode_message']) . '</span>';
      }
}
add_shortcode('restrict', 'restrict_shortcode');

function rc_not_logged_in($atts, $content = null) {
	if(!is_user_logged_in()) {
		return do_shortcode( $content );
	}
}
add_shortcode('not_logged_in', 'rc_not_logged_in');
