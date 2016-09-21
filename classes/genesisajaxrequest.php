<?php

/*
 * Allows management of Genesis Settings between the Source and Target sites
 * @package Sync
 * @author WPSiteSync
 */

class SyncGenesisAjaxRequest extends SyncInput
{
	private static $_instance = NULL;

	/**
	 * Retrieve singleton class instance
	 *
	 * @since 1.0.0
	 * @static
	 * @return null|SyncGenesisAdmin instance reference to plugin
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Push Genesis Settings ajax request
	 *
	 * @since 1.0.0
	 * @param SyncApiResponse $resp The response object after the API request has been made
	 * @return void
	 */
	public function push_genesis($resp)
	{
		$selected_genesis_settings = $this->post('selected_genesis_settings', 0);

		if (0 === $selected_genesis_settings) {
			// No Settings selected. Return error message
			WPSiteSync_Genesis::get_instance()->load_class('genesisapirequest');
			$resp->error_code(SyncGenesisApiRequest::ERROR_NO_GENESIS_SETTINGS_SELECTED);
			return TRUE;        // return, signaling that we've handled the request
		}

		$args = array('selected_genesis_settings' => $selected_genesis_settings);
		$api = new SyncApiRequest();
		$api_response = $api->api('pushgenesis', $args);

		// copy contents of SyncApiResponse object from API call into the Response object for AJAX call
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from api() call; copying response');
		$resp->copy($api_response);

		if (0 === $api_response->get_error_code()) {
			SyncDebug::log(' - no error, setting success');
			$resp->success(TRUE);
		} else {
			$resp->success(FALSE);
			SyncDebug::log(' - error code: ' . $api_response->get_error_code());
		}

		return TRUE; // return, signaling that we've handled the request
	}

	/**
	 * Pull Genesis Settings ajax request
	 *
	 * @since 1.0.0
	 * @param SyncApiResponse $resp The response object after the API request has been made
	 * @return void
	 */
	public function pull_genesis($resp)
	{
		$selected_genesis_settings = $this->post('selected_genesis_settings', 0);

		if (0 === $selected_genesis_settings) {
			// No settings selected. Return error message
			WPSiteSync_Genesis::get_instance()->load_class('genesisapirequest');
			$resp->error_code(SyncGenesisApiRequest::ERROR_NO_GENESIS_SETTINGS_SELECTED);
			return TRUE;        // return, signaling that we've handled the request
		}

		$args = array('selected_genesis_settings' => $selected_genesis_settings);
		$api = new SyncApiRequest();
		$api_response = $api->api('pullgenesis', $args);

		// copy contents of SyncApiResponse object from API call into the Response object for AJAX call
		SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from api() call; copying response');
		$resp->copy($api_response);

		if (0 === $api_response->get_error_code()) {
SyncDebug::log(' - no error, setting success');
			$resp->success(TRUE);
		} else {
			$resp->success(FALSE);
SyncDebug::log(' - error code: ' . $api_response->get_error_code());
		}

		return TRUE; // return, signaling that we've handled the request
	}
}

// EOF
