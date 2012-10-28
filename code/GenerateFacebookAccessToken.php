<?php
class GenerateFacebookAccessToken extends BuildTask {

	protected $title = 'Generate your Facebook Access Token';

	protected $description = 'This task will let you generate Facebook Access Token, please make sure you have saved your Page or User ID, App ID and App Secret in SiteConfiguration - see documentation #configuration section for more details';

	protected $enabled = true;

	function run($request) {

		$config =SiteConfig::current_site_config();
		$facebook_page_id = $config->FacebookPageID;
		$facebook_app_secret = $config->FacebookAppSecret;
		$facebook_app_id = $config->FacebookAppID;

		if(isset($facebook_page_id) && isset($facebook_app_secret) && isset($facebook_app_id)){

			// first request access /permission to 
			// if the $request["code"] is present it means we just got back from facebook hence skip to set_access
			if (!empty($request['code'])) {

				// save the access_token in the configuration
				$access_token = $this->get_token($request["code"], $request["state"]);

				if(isset($access_token)){

					echo "Your access_token is: <strong>" . $access_token . "</strong> - it has been stored in the database/site configuration for you!";

					$config->FacebookAccessToken = $access_token;
					$config->write();

				}

			} else {
				$state = $this->set_access();
			}

		} else {

			user_error('Missing Facebook Page / User ID and/or Facebook App Secret and/or Facebook App ID', E_USER_WARNING);

		}
	}


	/**
	 * Requests access to the Facebook App
	 *
	 * Redirects browser to the Facebook Request for Permission page so
	 * that the widget can gain access to Facebook. A session string is
	 * stored for later verification.
	 *
	 */
	function set_access() {

		$config = SiteConfig::current_site_config();
		$facebook_app_id = $config->FacebookAppID;

		// CSRF protection
		$session = md5(uniqid(rand(), TRUE));
		Session::set('state', $session);

		// TODO offline_access is being deprecated see: https://developers.facebook.com/roadmap/offline-access-removal/

		$dialog_url =
			"http://www.facebook.com/dialog/oauth?" .
			"scope=read_stream,offline_access,manage_pages,user_status&" .
			"client_id=" . $facebook_app_id . "&state=$session&" .
			"redirect_uri=" .  "http://" . $_SERVER["HTTP_HOST"] .  $_SERVER['REDIRECT_URL'];

		Director::redirect($dialog_url);

	}


	/**
	 * Requests access token from Facebook
	 *
	 * The access token is used by the widget to request
	 * a facebook wall feed. A check is made comparing
	 * the session state in the query string against
	 * the stored session string.
	 *
	 * @return string the access token.
	 *
	 * @access public
	 */
	function get_token($code, $state) {

		$config = SiteConfig::current_site_config();
		$facebook_app_id = $config->FacebookAppID;
		$facebook_app_secret = $config->FacebookAppSecret;

		$access_token = NULL;
		$session_state = Session::get('state');

		// check for a matching session
		if ($state == $session_state) {

			$token_url =
				"https://graph.facebook.com/oauth/access_token" .
				"?client_id=" . $facebook_app_id .
				"&client_secret=" . $facebook_app_secret .
				"&code=$code&redirect_uri=" . "http://" . $_SERVER["HTTP_HOST"] . $_SERVER['REDIRECT_URL'];
				// TODO also make it work with https://

			$err_msg = '';
			$response = FALSE;

			if (in_array('curl', get_loaded_extensions())) {
				$ch = curl_init();

				curl_setopt($ch, CURLOPT_URL, $token_url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				// TODO Add Verify SSL peer option to SiteConfiguration for now just set it to FALSE

				$response = curl_exec($ch);

				if (!$response) {
					$err_msg = '[' . curl_errno($ch) . '] ' .
						curl_error($ch);
				}

				curl_close($ch);
			}

			// check if allow_url_fopen is on
			if (!$response && ini_get('allow_url_fopen')) {
				echo $token_url;die();
				$response = @file_get_contents($token_url);

				if (!$response && empty($err_msg)) {
					$err_msg = 'file_get_contents failed to open URL.';

				}
			}

			// no way to get the access token
			if (!$response && empty($err_msg))
				$err_msg = 'Server Configuration Error: allow_url_fopen is off and cURL is not loaded.';

			if (!$response && !empty($err_msg)) {
				user_error($err_msg, E_USER_ERROR);
				//$this->error_msg_fn( $err_msg );
				return $access_token;
			}

			$params = NULL;
			parse_str($response, $params);

			if (isset($params[ 'access_token' ])) {
				$access_token = $params[ 'access_token' ];
			} else {
				$response = json_decode($response, TRUE);
				if (isset($response[ 'error' ]))
					user_error($response[ 'error' ][ 'type' ] . ': ' . $response[ 'error' ][ 'message' ] , E_USER_ERROR);
				else
					user_error('No access token returned.  Please double check you have the correct Facebook ID, App ID, and App Secret.', E_USER_ERROR);
			}

		// if the session doesn't match alert the user
		} else {
			user_error('The state does not match. You may be a victim of CSRF.', E_USER_ERROR);
		}

		return $access_token;
	} // End get_token function
}