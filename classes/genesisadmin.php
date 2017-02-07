<?php

/*
 * Allows management of Genesis settings between the Source and Target sites
 * @package Sync
 * @author WPSiteSync
 */

class SyncGenesisAdmin
{
	private static $_instance = NULL;

	private function __construct()
	{
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('admin_print_scripts', array($this, 'print_hidden_div'));
		add_action('spectrom_sync_ajax_operation', array($this, 'check_ajax_query'), 10, 3);
	}

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
	 * Registers js and css to be used.
	 *
	 * @since 1.0.0
	 * @param $hook_suffix
	 * @return void
	 */
	public function admin_enqueue_scripts($hook_suffix)
	{
		wp_register_script('sync-genesis', WPSiteSync_Genesis::get_asset('js/sync-genesis.js'), array('sync'), WPSiteSync_Genesis::PLUGIN_VERSION, TRUE);
		wp_register_style('sync-genesis', WPSiteSync_Genesis::get_asset('css/sync-genesis.css'), array('sync-admin'), WPSiteSync_Genesis::PLUGIN_VERSION);

		if (in_array($hook_suffix, array('toplevel_page_genesis', 'genesis_page_seo-settings', 'genesis_page_genesis-import-export'))) {
			wp_enqueue_script('sync-genesis');
			wp_enqueue_style('sync-genesis');
		}
	}

	/**
	 * Prints hidden menu ui div
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_hidden_div()
	{
		$page = isset($_GET['page']) ? $_GET['page'] : '';
		if (in_array($page, array('genesis', 'seo-settings', 'genesis-import-export'))) {
?>
		<div  style="display:none">
			<div id="sync-genesis-ui-header">
				<img src="<?php echo esc_url(WPSiteSyncContent::get_asset('imgs/wpsitesync-logo-blue.png')); ?>"  width="125" height="45" />
			</div>
			<div id="sync-genesis-ui">
				<div id="spectrom_sync" class="sync-genesis-contents">
<?php				if (SyncOptions::is_auth()) { ?>
						<p><em>WPSiteSync&#8482; for Genesis Settings</em> provides a convenient way to sync your Genesis Settings between two WordPress sites.</p>
						<p>Target site: <b><?php echo esc_url(SyncOptions::get('target')); ?>:</b></p>
						<button class="sync-genesis-push button button-primary sync-button" type="button" onclick="wpsitesynccontent.genesis.push_handler(); return false;"
							title="<?php esc_html_e('Push Genesis Settings to the Target site', 'wpsitesync-genesis'); ?>">
							<span class="sync-button-icon dashicons dashicons-migrate"></span>
							<?php esc_html_e('Push Settings to Target', 'wpsitesync-genesis'); ?>
						</button>
<?php
						$pull_active = FALSE;
						if (class_exists('WPSiteSync_Pull') && WPSiteSyncContent::get_instance()->get_license()->check_license('sync_pull', WPSiteSync_Pull::PLUGIN_KEY, WPSiteSync_Pull::PLUGIN_NAME))
							$pull_active = TRUE;
?>
						<button class="button <?php if ($pull_active) echo 'button-primary sync-genesis-pull'; ?> sync-button" type="button"
							onclick="wpsitesynccontent.genesis.<?php if ($pull_active) echo 'pull_handler'; else echo 'pull_notice'; ?>(); return false;"
							title="<?php esc_html_e('Pull Genesis Settings from the Target site', 'wpsitesync-genesis'); ?>">
							<span class="sync-button-icon sync-button-icon-rotate dashicons dashicons-migrate"></span>
							<?php esc_html_e('Pull Settings from Target', 'wpsitesync-genesis'); ?>
						</button>

						<div class="sync-genesis-msgs" style="display:none">
							<div class="sync-genesis-loading-indicator">
								<img src="<?php echo esc_url(WPSiteSyncContent::get_asset('imgs/ajax-loader.gif')); ?>" />&nbsp;
								<?php esc_html_e('Synchronizing Genesis Settings...', 'wpsitesync-genesis'); ?>
							</div>
							<div class="sync-genesis-failure-msg">
								<?php esc_html_e('Failed to Sync Genesis Settings.', 'wpsitesync-genesis'); ?>
								<span class="sync-genesis-failure-detail"></span>
								<span class="sync-genesis-failure-api"><?php esc_html_e('API Failure', 'wpsitesync-genesis'); ?></span>
								<span class="sync-genesis-failure-select"><?php esc_html_e('Please select settings to sync.', 'wpsitesync-genesis'); ?></span>
							</div>
							<div class="sync-genesis-success-msg">
								<?php esc_html_e('Successfully Synced Genesis Settings.', 'wpsitesync-genesis'); ?>
							</div>
							<div class="sync-genesis-pull-notice">
								<?php esc_html_e('Please install the WPSiteSync for Pull plugin to use the Pull features.', 'wpsitesync-genesis'); ?>
							</div>
						</div>
<?php				} else { // is_auth() ?>
						<p>WPSiteSync&#8482; for Content is not configured with a valid Target. Please go to the <a href="<?php echo esc_url(admin_url('options-general.php?page=sync')); ?>">Settings Page</a> to configure.</p>
<?php				} ?>
				</div>
			</div>
<?php /*
			<div id="spectrom_sync" class="sync-genesis-contents">
				<button class="sync-genesis-push button button-primary sync-button" type="button" title="<?php esc_html_e('Push Genesis Settings to the Target site', 'wpsitesync-genesis'); ?>">
					<span class="sync-button-icon dashicons dashicons-migrate"></span>
					<?php esc_html_e('Push Settings to Target', 'wpsitesync-genesis'); ?>
				</button>
				<?php if (class_exists('WPSiteSync_Pull') && WPSiteSyncContent::get_instance()->get_license()->check_license('sync_pull', WPSiteSync_Pull::PLUGIN_KEY, WPSiteSync_Pull::PLUGIN_NAME)) { ?>
					<button class="sync-genesis-pull button button-primary sync-button" type="button" title="<?php esc_html_e('Pull Genesis Settings from the Target site', 'wpsitesync-genesis'); ?>">
						<span class="sync-button-icon sync-button-icon-rotate dashicons dashicons-migrate"></span>
						<?php esc_html_e('Pull Settings from Target', 'wpsitesync-genesis'); ?>
					</button>
				<?php } ?>
				<?php wp_nonce_field('sync-genesis', '_sync_nonce'); ?>
				<div class="sync-genesis-msgs" style="display:none">
					<div class="sync-genesis-loading-indicator">
						<?php esc_html_e('Synchronizing Genesis Settings...', 'wpsitesync-genesis'); ?>
					</div>
					<div class="sync-genesis-failure-msg">
						<?php esc_html_e('Failed to Sync Genesis Settings.', 'wpsitesync-genesis'); ?>
						<span class="sync-genesis-failure-detail"></span>
						<span class="sync-genesis-failure-api"><?php esc_html_e('API Failure', 'wpsitesync-genesis'); ?></span>
						<span class="sync-genesis-failure-select"><?php esc_html_e('Please select settings to sync.', 'wpsitesync-genesis'); ?></span>
					</div>
					<div class="sync-genesis-success-msg">
						<?php esc_html_e('Successfully Synced Genesis Settings.', 'wpsitesync-genesis'); ?>
					</div>
				</div>
			</div>
 */ ?>
		</div>
		<div style="display:none">
			<div id="spectrom-sync-settings-msg">
				<br/>To use WPSiteSync and sync Theme Settings, please visit <a href="<?php echo esc_url(admin_url('admin.php?page=genesis-import-export')); ?>">Genesis -&gt; Import/Export</a>.
			</div>
			<div id="spectrom-sync-seo-settings-msg">
				<br/>To use WPSiteSync and sync SEO Settings, please visit <a href="<?php echo esc_url(admin_url('admin.php?page=genesis-import-export')); ?>">Genesis -&gt; Import/Export</a>.
			</div>
		</div>
<?php
		}
	}

	/**
	 * Checks if the current ajax operation is for this plugin
	 *
	 * @param  boolean $found Return TRUE or FALSE if the operation is found
	 * @param  string $operation The type of operation requested
	 * @param  SyncApiResponse $resp The response to be sent
	 *
	 * @return boolean Return TRUE if the current ajax operation is for this plugin, otherwise return $found
	 */
	public function check_ajax_query($found, $operation, SyncApiResponse $resp)
	{
		SyncDebug::log(__METHOD__ . '() operation="' . $operation . '"');

		$license = WPSiteSyncContent::get_instance()->get_license();
		if (!$license)
			return $found;

		if ('pushgenesis' === $operation) {
SyncDebug::log(' - post=' . var_export($_POST, TRUE));

			$ajax = WPSiteSync_Genesis::get_instance()->load_class('genesisajaxrequest', TRUE);
			$ajax->push_genesis($resp);
			$found = TRUE;
		} else if ('pullgenesis' === $operation) {
SyncDebug::log(' - post=' . var_export($_POST, TRUE));

			$ajax = WPSiteSync_Genesis::get_instance()->load_class('genesisajaxrequest', TRUE);
			$ajax->pull_genesis($resp);
			$found = TRUE;
		}

		return $found;
	}
}

// EOF
