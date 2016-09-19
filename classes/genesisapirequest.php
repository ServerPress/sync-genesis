<?php

/*
 * Allows management of Genesis Settings between the Source and Target sites
 * @package Sync
 * @author WPSiteSync
 */
class SyncGenesisApiRequest
{
	private static $_instance = NULL;
	private $_push_data;

	const ERROR_GENESIS_SETTINGS_NOT_FOUND = 500;
	const ERROR_NO_GENESIS_SETTINGS_SELECTED = 501;

	const NOTICE_GENESIS_SETTINGS_MODIFIED = 500;

	/**
	 * Retrieve singleton class instance
	 *
	 * @since 1.0.0
	 * @static
	 * @return null|SyncGenesisAdmin instance reference to plugin
	 */
	// TODO: this class does not need to be a singleton. also, this is loaded from WPSiteSync->load_class() as an instance anyway
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Filters the errors list, adding SyncGenesis specific code-to-string values
	 *
	 * @param string $message The error string message to be returned
	 * @param int $code The error code being evaluated
	 * @return string The modified $message string, with Pull specific errors added to it
	 */
	public function filter_error_codes($message, $code)
	{
		switch ($code) {
		case self::ERROR_GENESIS_SETTINGS_NOT_FOUND:
			$message = __('No Genesis settings were found', 'wpsitesync-genesis');
			break;
		case self::ERROR_NO_GENESIS_SETTINGS_SELECTED:
			$message = __('No Genesis settings were selected.', 'wpsitesync-genesis');
			break;
		}
		return $message;
	}

	/**
	 * Filters the notices list, adding SyncGenesis specific code-to-string values
	 *
	 * @param string $message The notice string message to be returned
	 * @param int $code The notice code being evaluated
	 * @return string The modified $message string, with Pull specific notices added to it
	 */
	public function filter_notice_codes($message, $code)
	{
		switch ($code) {
		case self::NOTICE_GENESIS_SETTINGS_MODIFIED:
			$message = __('Settings have been modified. Please save changes.', 'wpsitesync-genesis');
			break;
		}
		return $message;
	}

	/**
	 * Checks the API request if the action is to pull/push the settings
	 *
	 * @param array $args The arguments array sent to SyncApiRequest::api()
	 * @param string $action The API requested
	 * @param array $remote_args Array of arguments sent to SyncRequestApi::api()
	 * @return array The modified $args array, with any additional information added to it
	 */
	public function api_request($args, $action, $remote_args)
	{
SyncDebug::log(__METHOD__ . '() action=' . $action);

		// TODO: don't create a new SyncLicensing instance- use the one in WPSiteSync_Genesis->_license
		$license = new SyncLicensing();
		if (!$license->check_license('sync_genesis', WPSiteSync_Genesis::PLUGIN_KEY, WPSiteSync_Genesis::PLUGIN_NAME))
			return $args;

		if ('pushgenesis' === $action) {
SyncDebug::log(__METHOD__ . '() args=' . var_export($args, TRUE));

			$push_data = array();
			$settings = array();
			$selected = $args['selected_genesis_settings'];

			$options = array(
				'theme' => array(
					'label' => __('Theme Settings', 'genesis'),
					'settings-field' => GENESIS_SETTINGS_FIELD,
				),
				'seo' => array(
					'label' => __('SEO Settings', 'genesis'),
					'settings-field' => GENESIS_SEO_SETTINGS_FIELD,
				)
			);

			$options = apply_filters('genesis_export_options', $options);

			$push_data['site_key'] = $args['auth']['site_key'];
			$push_data['pull'] = FALSE;

			// Get settings
			// TODO: create $selected as an array so you don't have to keep casting it to an array
			foreach ((array)$selected as $setting) {
				$setting = str_replace('genesis-export[', '', rtrim($setting, ']'));
SyncDebug::log(__METHOD__ . '() setting=' . var_export($setting, TRUE));
				// Grab settings field name (key)
				$setting_key = $options[$setting]['settings-field'];

				// Grab all of the settings from the database under that key
				$setting_value = get_option($setting_key);

				if (FALSE !== $setting_value) {
					$settings[] = array(
						'option_key' => $setting_key,
						'option_value' => $setting_value,
					);
				}
			}

			$push_data['genesis-settings'] = $settings;

SyncDebug::log(__METHOD__ . '() push_data=' . var_export($push_data, TRUE));

			$args['push_data'] = $push_data;
		} else if ('pullgenesis' === $action) {
			// TODO: is Pull implemented for Genesis? If so and this isn't doing anything, it can be removed.
SyncDebug::log(__METHOD__ . '() args=' . var_export($args, TRUE));
		}

		// return the filter value
		return $args;
	}

	/**
	 * Handles the requests being processed on the Target from SyncApiController
	 *
	 * @param type $return
	 * @param type $action
	 * @param SyncApiResponse $response
	 * @return bool $response
	 */
	public function api_controller_request($return, $action, SyncApiResponse $response)
	{
SyncDebug::log(__METHOD__ . "() handling '{$action}' action");

		// TODO: use the WPSiteSync_Genesis->_licensing instance already created
		$license = new SyncLicensing();
		if (!$license->check_license('sync_genesis', WPSiteSync_Genesis::PLUGIN_KEY, WPSiteSync_Genesis::PLUGIN_NAME))
			// TODO: this should return $return, not TRUE. TRUE indicates that the action has been handled and it has not.
			return TRUE;

		if ('pushgenesis' === $action) {
			// TODO: have this class extend SyncInput and you don't need to instantiate
			$input = new SyncInput();
			$selected_genesis_settings = $input->post('selected_genesis_settings', 0);

			// check api parameters
			if (0 === $selected_genesis_settings) {
				$response->error_code(SyncGenesisApiRequest::ERROR_NO_GENESIS_SETTINGS_SELECTED);
				return TRUE;            // return, signaling that the API request was processed
			}

			$this->_push_data = $input->post_raw('push_data', array());
SyncDebug::log(__METHOD__ . '() found push_data information: ' . var_export($this->_push_data, TRUE));

			if (empty($this->_push_data['genesis-settings'])) {
				$response->error_code(SyncGenesisApiRequest::ERROR_GENESIS_SETTINGS_NOT_FOUND);
				return TRUE;            // return, signaling that the API request was processed
			}

			// TODO: this needs to be created as an array rather than casted to an array in several places
			foreach ((array)$this->_push_data['genesis-settings'] as $setting) {
				switch ($setting['option_key']) {
				case 'genesis-settings':
					$key = apply_filters('genesis_settings_field', $setting['option_key']);
					break;
				case 'genesis-seo-settings':
					$key = apply_filters('genesis_seo_settings_field', $setting['option_key']);
					break;
				default:
					$key = $setting['option_key'];
					break;
				}
				update_option($key, $setting['option_value']);
			}

			$return = TRUE; // tell the SyncApiController that the request was handled
		} else if ('pullgenesis' === $action) {
			$input = new SyncInput();
			// TODO: use default value of array() rather than 0- then you don't need to cast it later
			$selected = $input->post('selected_genesis_settings', 0);
			$pull_data = array();
			$settings = array();

			$options = array(
				'theme' => array(
					'label' => __('Theme Settings', 'genesis'),
					'settings-field' => GENESIS_SETTINGS_FIELD,
				),
				'seo' => array(
					'label' => __('SEO Settings', 'genesis'),
					'settings-field' => GENESIS_SEO_SETTINGS_FIELD,
				)
			);
			$options = apply_filters('genesis_export_options', $options);

			// Get settings
			foreach ((array)$selected as $setting) {
				$setting = str_replace('genesis-export[', '', rtrim($setting, ']'));
				// Grab settings field name (key)
				$setting_key = $options[$setting]['settings-field'];

				// Grab all of the settings from the database under that key
				$setting_value = get_option($setting_key);

				$settings[] = array(
					'option_key' => $setting_key,
					'option_value' => $setting_value,
				);
			}

			$pull_data['genesis-settings'] = $settings;

			$response->set('pull_data', $pull_data); // add all the post information to the ApiResponse object
			$response->set('site_key', SyncOptions::get('site_key'));
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - response data=' . var_export($response, TRUE));

			$return = TRUE; // tell the SyncApiController that the request was handled
		}

		return $return;
	}

	/**
	 * Handles the request on the Source after API Requests are made and the response is ready to be interpreted
	 *
	 * @param string $action The API name, i.e. 'push' or 'pull'
	 * @param array $remote_args The arguments sent to SyncApiRequest::api()
	 * @param SyncApiResponse $response The response object after the API request has been made
	 */
	public function api_response($action, $remote_args, $response)
	{
SyncDebug::log(__METHOD__ . "('{$action}')");

		if ('pushgenesis' === $action) {
SyncDebug::log(__METHOD__ . '() response from API request: ' . var_export($response, TRUE));

			$api_response = NULL;

			if (isset($response->response)) {
SyncDebug::log(__METHOD__ . '() decoding response: ' . var_export($response->response, TRUE));
				$api_response = $response->response;
			} else {
SyncDebug::log(__METHOD__ . '() no reponse->response element');
			}

SyncDebug::log(__METHOD__ . '() api response body=' . var_export($api_response, TRUE));

			if (0 === $response->get_error_code()) {
				$response->success(TRUE);
			} else {
				// TODO: remove else when unused
			}
		} else if ('pullgenesis' === $action) {
SyncDebug::log(__METHOD__ . '() response from API request: ' . var_export($response, TRUE));

			$api_response = NULL;

			if (isset($response->response)) {
SyncDebug::log(__METHOD__ . '() decoding response: ' . var_export($response->response, TRUE));
				$api_response = $response->response;
			} else {
SyncDebug::log(__METHOD__ . '() no response->response element');
			}

SyncDebug::log(__METHOD__ . '() api response body=' . var_export($api_response, TRUE));

			if (NULL !== $api_response) {
				$save_post = $_POST;

				// convert the pull data into an array
				$pull_data = json_decode(json_encode($api_response->data->pull_data), TRUE); // $response->response->data->pull_data;
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - pull data=' . var_export($pull_data, TRUE));
				$site_key = $api_response->data->site_key; // $pull_data->site_key;
				$target_url = SyncOptions::get('target');
				$pull_data['site_key'] = $site_key;
				$pull_data['pull'] = TRUE;

				$_POST['selected_genesis_settings'] = $_REQUEST['selected_genesis_settings'];
				$_POST['push_data'] = $pull_data;
				$_POST['action'] = 'pushgenesis';

				$args = array(
					'action' => 'pushgenesis',
					'parent_action' => 'pullgenesis',
					'site_key' => $site_key,
					'source' => $target_url,
					'response' => $response,
					'auth' => 0,
				);

SyncDebug::log(__METHOD__ . '() creating controller with: ' . var_export($args, TRUE));
				$this->_push_controller = new SyncApiController($args);
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from controller');
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - response=' . var_export($response, TRUE));

				$_POST = $save_post;

				if (0 === $response->get_error_code()) {
					$response->success(TRUE);
				}
			}
		}
	}
}

// EOF
