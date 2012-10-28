<?php


/**
 * Defining the functionality of the Facebook Feed widget.
 *
 * @package widget_facebookfeed
 */
class FacebookFeedWidget extends Widget {


	/**
	 * Create the database fields for configuring the widget.
	 */
	public static $db = array(
		'Heading' => 'Varchar(64)',
		'Limit' => 'Int',
	);


	/**
	 * Provide meaningful default values - not possible for the ID.
	 */
	public static $defaults = array(
		'Limit' => 5,
	);
	
	public static $show_status_messages = TRUE;
	
	public static $show_fb_id_only = TRUE;


	/**
	 * Provide title and description to be used in the CMS.
	 */
	//public static $cmsTitle = 'Facebook Messages';
	public function cmsTitle(){
		return _t('FacebookFeedWidget.TITLE', 'Facebook Wall Feed');
	}
	//public static $description = 'A list of the most recent Facebook messages';
	public function description(){
		return _t('FacebookFeedWidget.DESCRIPTION', 'A list of the most recent Facebook wall stream activity');
	}


	/**
	 * Make the widget's configuration fields available in the CMS.
	 *
	 * @return FieldSet The added CMS fields.
	 */
	public function getCMSFields(){
		return new FieldList(
			new TextField('Heading', _t('FacebookFeedWidget.Heading', 'Heading title on top of feeds')),
			new NumericField('Limit', _t('FacebookFeedWidget.LIMIT', 'Maximum number of feed items to display'))
		);
	}


	/**
	 * Fetch the messages from Facebook and make them available to the template.
	 * The feed includes both your own posts and the ones from others. We are however only interested in our own posts, so we'll fetch some more and throw the others away.
	 * Taking five more might not be sufficient, but we'll assume it's enough for our scenario.
	 *
	 * @return TODO: DataList containing all relevant fields.
	 */
	public function Feeds(){
		$config = SiteConfig::current_site_config();
		$facebook_page_id = $config->FacebookPageID;
		$facebook_access_token = $config->FacebookAccessToken;

		/**
		 * URL for fetching the information, convert the returned JSON into an array.
		 * It is required to use an access_token which in turn mandates https.
		 */
		if(!isset($facebook_access_token)){
			user_error('Missing Facebook access token - please get one and add it to your site configuration.', E_USER_WARNING);
			return;
		}

		if (extension_loaded('openssl') && in_array('https', stream_get_wrappers())) {
		
			$url = 'https://graph.facebook.com/' . $facebook_page_id . '/feed?limit=' . ($this->Limit + 5) . '&access_token=' . $facebook_access_token . '&locale=' . i18n::get_locale();
			$facebook_feed = json_decode(@file_get_contents($url), true);

			$res = $this->display_fb_wall_feed($facebook_feed["data"], $facebook_page_id, $facebook_access_token);

			return $res;
		} else {
			user_error('You need a valid SSL wrapper. Enable open_ssl extension in php.ini.', E_USER_WARNING);
			return;
		}
	}


	/**
	 * Displays the Facebook Wall feed
	 *
	 * Parses the Facebook Wall feed data and prints out the wall feed html.
	 *
	 * @param array $fb_feed an array of Facebook Wall feed data.
	 *
	 * @return string the facebook wall as html
	 *
	 * @access public
	 */
	function display_fb_wall_feed($fb_feed, $facebook_page_id, $access_token) {

		$result = '';
		$target = '';
		$post_count = 0;
		$fb_privacy = "All";
		$fb_id_only = self::$show_fb_id_only;
		$show_status = self::$show_status_messages;
		$profile = TRUE;
		$new_win = TRUE;
		$fb_icons = TRUE;

		if ($new_win)
			$target = ' target="_blank"';

		// loop through each post in the feed
		for ( $i = 0; $i < count( $fb_feed ); $i++) {
			
			// exit the loop if we have reached the limit
			if ( $post_count >= $this->Limit ) {
				break;
			}
			$fb_id = $fb_feed[ $i ][ 'from' ][ 'id' ];

			// privacy check, if no privacy then assume public
			$privacy = 'EVERYONE';
			if ( isset( $fb_feed[ $i ][ 'privacy' ][ 'value' ] ) )
				$privacy = $fb_feed[ $i ][ 'privacy' ][ 'value' ];

			$privacy_good = FALSE;
			if ( $fb_privacy == 'All' )
				$privacy_good = TRUE;
			elseif ( $fb_privacy == $privacy )
				$privacy_good = TRUE;
					
			// check to see if we are not getting posts by other facebook
			// friends
			$show_post = FALSE;
			if ( $fb_id_only ) {
				if ( $facebook_page_id == $fb_id ) {
					if ( $privacy_good )
						$show_post = TRUE;
				}
			} else {
				if ( $privacy_good )
					$show_post = TRUE;
			}

			$is_status = TRUE;
			if ( isset( $fb_feed[ $i ][ 'message' ] ) ||
				isset( $fb_feed[ $i ][ 'name' ] ) ||
				isset( $fb_feed[ $i ][ 'caption' ] ) ||
				isset( $fb_feed[ $i ][ 'description' ] ) )
				$is_status = FALSE;
			

			
			// don't display posts without a message, name, caption,
			// or description they are just usually "is now friends with"
			// posts
			if ( $show_post && ( $show_status || ! $is_status ) ) {
					
				$comment_link =
					$this->fb_comment_link( $fb_feed[ $i ][ 'id' ] );
				$like_link =
					$this->fb_like_link( $fb_feed[ $i ][ 'id' ] );
				if ( $profile )
					$fb_photo  =
						'https://graph.facebook.com/' . $fb_id .
						'/picture?access_token=' . $access_token;
				else
					$fb_photo  =
						"http://graph.facebook.com/$fb_id/picture";
				$post_time =
					$this->parse_fb_timestamp(
						$fb_feed[ $i ][ 'created_time' ] );
				$fb_picture = NULL;
				if ( isset( $fb_feed[ $i ][ 'picture' ] ) )
					$fb_picture =
						$this->fb_fix( $fb_feed[ $i ][ 'picture' ] );
				$fb_source = NULL;
				if ( isset( $fb_feed[ $i ][ 'source' ] ) )
					$fb_source = $fb_feed[ $i][ 'source' ];
				$fb_link = NULL;
				if ( isset( $fb_feed[ $i ][ 'link' ] ) )
					$fb_link = $fb_feed[ $i ][ 'link' ];
				$fb_likes = 0;
				if ( isset( $fb_feed[ $i ][ 'likes' ][ 'count' ] ) )
					$fb_likes = $fb_feed[ $i ][ 'likes' ][ 'count' ];
				$fb_comments = 0;
				if ( isset( $fb_feed[ $i ][ 'comments' ][ 'count' ] ) )
					$fb_comments = $fb_feed[ $i ][ 'comments' ][ 'count' ];
				
				$fb_prop = FALSE;
				$fb_prop_name = NULL;
				$fb_prop_text = NULL;
				$fb_prop_href = NULL;
				if ( isset( $fb_feed[ $i ][ 'properties' ][ 0 ] ) ) {
					$fb_prop = TRUE;
					if ( isset(
						$fb_feed[ $i ][ 'properties' ][ 0 ][ 'name' ] ) )
						$fb_prop_name =
							$fb_feed[ $i ][ 'properties' ][ 0 ][ 'name' ];
					if ( isset(
						$fb_feed[ $i ][ 'properties' ][ 0 ][ 'text' ] ) )
						$fb_prop_text =
							$fb_feed[ $i ][ 'properties' ][ 0 ][ 'text' ];
					if ( isset(
						$fb_feed[ $i ][ 'properties' ][ 0 ][ 'href' ] ) )
						$fb_prop_href =
							$fb_feed[ $i ][ 'properties' ][ 0 ][ 'href' ];
				}

				$result .=
				  '    <div class="fb_post">' .
				  '      <div class="fb_photoblock">' .
				  '        <div class="fb_photo">' .
				  '          <a href="http://www.facebook.com/profile.php?id=' . $fb_id . '"' . $target . '>' .
				  '            <img src="' . $fb_photo . '" alt="' . 'Facebook Profile Pic' . '" />' .
				  '          </a>' .
				  '        </div>' .
				  '        <div class="fb_photo_content">' .
				  '          <h5>' .
				  '            <a href="http://www.facebook.com/profile.php?id=' . $fb_id  . '"' . $target . '>' . $fb_feed[ $i ][ 'from' ][ 'name' ] . '</a>' .
				  '          </h5>' .
				  '          <div class="fb_time">';
				if ( $fb_icons && isset( $fb_feed[ $i ][ 'icon' ] ) )
					$result .=
				  '            <img class="fb_post_icon" src="' . htmlentities( $fb_feed[ $i ][ 'icon' ], ENT_QUOTES, 'UTF-8' ) . '" alt="' . 'Facebook Icon' . '" />';
				$result .= $post_time .
				  '          </div>' .
				  '        </div>' .
				  '      </div>' .
				  '      <div class="fb_msg">';
				if ( isset( $fb_feed[ $i ][ 'story' ] ) )
					$result .= 
				  '        <p class="fb_story">' . htmlentities( $fb_feed[ $i ][ 'story' ], ENT_QUOTES, 'UTF-8' ) . '</p>';
				if ( isset( $fb_feed[ $i ][ 'message' ] ) )
					$result .= 
				  '        <p>' . htmlentities( $fb_feed[ $i ][ 'message' ], ENT_QUOTES, 'UTF-8' ) . '</p>';
				$result .= 
				  '        <div class="fb_link_post">';
				if ( isset( $fb_picture ) && isset( $fb_source ) )
					$result .=
				  '          <a href="' . htmlentities( $fb_source, ENT_QUOTES, 'UTF-8' ) . '"' . $target . '>';
				elseif ( isset( $fb_picture ) && isset( $fb_link ) )
					$result .=
				  '          <a href="' . htmlentities( $fb_link, ENT_QUOTES, 'UTF-8' ) . '"' . $target . '>';
				if ( isset( $fb_picture ) )
					$result .= $fb_picture;

				if ( isset( $fb_picture ) && ( isset( $fb_source ) ||
					isset( $fb_link ) ) )
					$result .=
				  '          </a>';
				if ( isset( $fb_feed[ $i ][ 'name' ] ) )
					$result .=
				  '          <h6><a href="' . htmlentities( $fb_link, ENT_QUOTES, 'UTF-8' ) . '"' . $target . '>' . htmlentities( $fb_feed[ $i ][ 'name' ], ENT_QUOTES, 'UTF-8' ) . '</a></h6>';
				if ( isset( $fb_feed[ $i ][ 'caption' ] ) )
					$result .=
				  '          <p class="fb_cap">' . htmlentities( $fb_feed[ $i ][ 'caption' ], ENT_QUOTES, 'UTF-8' ) . '</p>';
				if ( isset( $fb_feed[ $i ][ 'description' ] ) )
					$result .=
				  '          <p class="fb_desc">' . htmlentities( $fb_feed[ $i ][ 'description' ], ENT_QUOTES, 'UTF-8' ) . '</p>';
				if ( $fb_prop )
					$result .=
				  '          <p class="fb_vid_length">';
				if ( isset( $fb_prop_name ) )
					$result .= $fb_prop_name . ': ';
				if ( isset( $fb_prop_href ) )
					$result .= '<a href="' . htmlentities( $fb_prop_href, ENT_QUOTES, 'UTF-8' ) . '"' . $target . '>';
				if ( isset( $fb_prop_text ) )
					$result .= $fb_prop_text;
				if ( isset( $fb_prop_href ) )
					$result .= '</a>';
				if ( $fb_prop )
					$result .=
				  '          </p>';
				$result .=
				  '        </div>' .
				  '      </div>';
				if ( $this->show_comments &&
					isset( $fb_feed[ $i ][ 'comments' ][ 'data' ] ) )
					$result .= $this->display_comments(
						$fb_feed[ $i ][ 'comments' ][ 'data' ], $facebook_page_id, $access_token);
				$result .=
				  '      <div class="fb_commLink">' .
				  '        <span class="fb_likes">';
				if ( $fb_likes > 0 )
					$result .=
				  '          <a class="tooltip" title="' . $fb_likes . ' ' . 'people like this'. '" href="' . $like_link . '"' . $target . '>' . $fb_likes . '</a>';
				$result .=
				  '        </span>' .
				  '        <span class="fb_comment">' .
				  '          <a href="' . $comment_link . '"' . $target . '>' . 'Comment' . '</a> (' . $fb_comments .')'.
				  '        </span>' .
				  '      </div>' .
				  '      <div style="clear: both;"></div>' .
				  '    </div>';
				
				$post_count++;

			} // end if
			
		} // End for

		return $result;

	} // End display_fb_wall_feed function


	/**
	 * Displays comments
	 *
	 * Parses the comments for a particular post and prints out the html.
	 *
	 * @param array $fb_feed an array of comment data.
	 *
	 * @return string the comments as html
	 *
	 * @access public
	 */
	function display_comments($fb_feed , $facebook_page_id, $access_token) {

		$result = '';
		$target = '';
		$new_win = TRUE;
		$fb_id_only = FALSE;
		$profile = FALSE;
		
		if ( $new_win )
			$target = ' target="_blank"';

		// loop through each post in the feed
		for ( $i = 0; $i < count( $fb_feed ); $i++) {
			
			$fb_id = $fb_feed[ $i ][ 'from' ][ 'id' ];

			// check to see if we are not getting posts by other facebook
			// friends
			$show_post = FALSE;
			if ( $fb_id_only ) {
				if ( $facebook_page_id == $fb_id )
					$show_post = TRUE;
			} else {
				$show_post = TRUE;
			}

			if ( $show_post ) {
			
				if ( $this->profile )
					$fb_photo  = 'https://graph.facebook.com/' . $fb_id .
						'/picture?access_token=' . $access_token;
				else
					$fb_photo  = "http://graph.facebook.com/$fb_id/picture";
				$post_time = $this->parse_fb_timestamp(
					$fb_feed[ $i ][ 'created_time' ] );
				
				$result .=
			  '          <div class="fb_comments">' .
			  '            <div class="fb_photo">' .
			  '              <a href="http://www.facebook.com/profile.php?id=' . $fb_id . '"' . $target . '>' .
			  '                <img src="' . $fb_photo . '" alt="' . 'Facebook Profile Pic' . '" />' .
			  '              </a>' .
			  '            </div>' .
			  '            <div class="fb_photo_content">' .
			  '              <p>' .
			  '                <a href="http://www.facebook.com/profile.php?id=' . $fb_id  . '"' . $target . '>' . $fb_feed[ $i ][ 'from' ][ 'name' ] . '</a>' .
			  '                ' . htmlentities( $fb_feed[ $i ][ 'message' ], ENT_QUOTES, 'UTF-8' ) .
			  '              </p>' .
			  '              <p class="fb_time">';
				$result .= $post_time .
			  '              </p>' .
			  '            </div>' .
			  '          </div>';

			} // End if
			
		} // End for

		return $result;

	} // End display_comments function


	/**
	 * Forms a Facebook comment link
	 *
	 * Forms a Facebook comment link by parsing the ID of the post.
	 *
	 * @param string $fb_story_id the id of the post to be parsed.
	 *
	 * @return string the parsed comment link
	 *
	 * @access public
	 * @since Method available since Release 1.0
	 */
	function fb_comment_link( $fb_story_id ) {
		$link = 'http://www.facebook.com/permalink.php?';
		$split_id = explode( '_', $fb_story_id );
		$link .= 'id=' . $split_id[ 0 ] . '&amp;story_fbid=' . $split_id[ 1 ];

		return $link;
	}

	// }}}
	// {{{ fb_like_link()

	/**
	 * Forms a Facebook like link
	 *
	 * Forms a Facebook like link by parsing the ID of the post.
	 *
	 * @param string $fb_story_id the id of the post to be parsed.
	 *
	 * @return string the parsed comment link
	 *
	 * @access public
	 */
	function fb_like_link( $fb_story_id ) {
		$link = 'http://www.facebook.com/';
		$split_id = explode( '_', $fb_story_id );
		$link .= $split_id[ 0 ] . '/posts/' . $split_id[ 1 ];

		return $link;
	}

	/**
	 * Forms a time stamp
	 *
	 * Adjusts the time stamp to local time.
	 *
	 * @param string $fb_time the time stamp of the post.
	 *
	 * @return string the parsed time stamp.
	 *
	 * @access public
	 */
	function parse_fb_timestamp( $fb_time ) {

		$this_tz_str = date_default_timezone_get();
		$this_tz = new DateTimeZone($this_tz_str);
		$now = new DateTime("now", $this_tz);
		$offset = $this_tz->getOffset($now);

		$time_stamp = explode( 'T', $fb_time );
		$date_str = $time_stamp[ 0 ];
		$date_items = explode( '-', $date_str );
		$time_arr = explode( ':', $time_stamp[ 1 ] );
		$time_hr = $time_arr[ 0 ]; // TODO use $offset
		if ( $time_hr < 0 ) {
			$time_hr += 24;
			$date_items[ 2 ]--;
		}

		$unix_time_stamp = mktime( $time_hr, $time_arr[ 1 ], 0,
					$date_items[ 1 ], $date_items[ 2 ], $date_items[ 0 ] );
		$date_str = date( "d F Y ", $unix_time_stamp );

		$time_str = date( "G:i", $unix_time_stamp );

		return $date_str . ' ' . 'om' . ' ' . $time_str;
	}


	/**
	 * Facebook image fix
	 *
	 * Fixes issue with safe_image.php displaying 1 pixel image..
	 *
	 * @param string $str the image url
	 *
	 * @return string the fixed image url.
	 *
	 * @access public
	 */
	function fb_fix( $str ) {
		$pos = strpos( $str, 'safe_image.php' );
		if ( $pos !== FALSE ) {
			parse_str( $str );
			$str = $url;
		}

		$result = '<img src="' . htmlentities( $str, ENT_QUOTES, 'UTF-8' ) . '" alt="' . 'Facebook Picture';
		if ( isset( $w ) && isset( $h ) )
			$result .= '" width="' . $w . '" height="' . $h;
		$result .= '" />';

		return $result;
	}


}