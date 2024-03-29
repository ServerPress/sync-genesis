/*
 * @copyright Copyright (C) 2015-2017 SpectrOMtech.com. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author SpectrOMtech.com <hello@SpectrOMtech.com>
 * @url https://wpsitesync.com/license
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images,
 * manuals, cascading style sheets, and included JavaScript *are NOT GPL*, and are released under the
 * SpectrOMtech Proprietary Use License v1.0
 * More info at https://wpsitesync.com
 */

function WPSiteSyncContent_Genesis()
{
	this.inited = false;
}

/**
 * Initialize the Sync Genesis UI behaviors
 */
WPSiteSyncContent_Genesis.prototype.init = function()
{
	this.inited = true;
	var page = this.get_param('page');
	switch (page) {
	case 'genesis':
		this.show_theme_settings_msg();
		break;
	case 'seo-settings':
		this.show_seo_settings_msg();
		break;
	case 'genesis-import-export':
		this.show_ui();
		break;
	}

/*	jQuery('.sync-genesis-contents').on('click', '.sync-genesis-push, .sync-genesis-pull', function()
	{
		var settings = [];
		jQuery('form input:checked').each(function ()
		{
			settings.push(jQuery(this).attr('name'));
		});

		wpsitesynccontent.genesis.hide_msgs();

		if (0 === settings.length) {
			wpsitesynccontent.genesis.set_message('select');
			return;
		}

		if (jQuery(this).hasClass('sync-genesis-pull')) {
			wpsitesynccontent.genesis.pull_genesis(settings);
		} else if (jQuery(this).hasClass('sync-genesis-push')) {
			wpsitesynccontent.genesis.push_genesis(settings);
		}
	}); */
};

/**
 * Callback for when the Push to Target button is clicked
 */
WPSiteSyncContent_Genesis.prototype.push_handler = function()
{
	var settings = this.get_selected_settings();
	if (false === settings)
		return;
	this.push_genesis(settings);
};

/**
 * Callback for when the Pull from Target button is clicked
 */
WPSiteSyncContent_Genesis.prototype.pull_handler = function()
{
	var settings = this.get_selected_settings();
	if (false === settings)
		return;
	this.pull_genesis(settings);
};

/**
 * Callback for the Pull from Target button is clicked and the Pull add-on is not active
 */
WPSiteSyncContent_Genesis.prototype.pull_notice = function()
{
	this.hide_msgs();
	this.set_message('pull-notice');
};

/**
 * Helper method to retrieve the settings (checkboxes) and display a notice message if none are selected
 * @returns {Array|Boolean} false when nothing is selected; otherwise an array of the selected settings
 */
WPSiteSyncContent_Genesis.prototype.get_selected_settings = function()
{
	this.hide_msgs;

	var settings = [];
	jQuery('form input:checked').each(function ()
	{
		settings.push(jQuery(this).attr('name'));
	});

	// check to see that some settings are selected
	if (0 === settings.length) {
		this.set_message('select');
		return false;
	}

	return settings;
};

/**
 * Get the value of a parameter from the URL
 * @param {string} name Name of the parameter to retrieve
 * @returns {String} The value of the parameter (can be empty) or null if not found
 */
WPSiteSyncContent_Genesis.prototype.get_param = function(name)
{
	// TODO: replace with get_param() from main sync class
	var url = window.location.href;
	name = name.replace(/[\[\]]/g, "\\$&");
	var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
		results = regex.exec(url);
	if (!results)
		return null;
	if (!results[2])
		return '';
	return decodeURIComponent(results[2].replace(/\+/g, " "));
};


WPSiteSyncContent_Genesis.prototype.show_theme_settings_msg = function()
{
	this.hide_msgs();
	jQuery('div.wrap.genesis-metaboxes div.bottom-buttons').after(jQuery('#spectrom-sync-settings-msg').html());
};

WPSiteSyncContent_Genesis.prototype.show_seo_settings_msg = function()
{
	this.hide_msgs();
	jQuery('div.wrap.genesis-metaboxes div.bottom-buttons').after(jQuery('#spectrom-sync-seo-settings-msg').html());
};

/**
 * Shows the Menu UI area within the Genesis Import/Export page
 */
WPSiteSyncContent_Genesis.prototype.show_ui = function()
{
	this.hide_msgs();

	// copy the UI DOM elements into place within the Genesis table
	jQuery('table.form-table tbody tr:last').after(
		'<tr><th scope="row">' + jQuery('#sync-genesis-ui-header').html() + '</th>' +
		'<td>' + jQuery('#sync-genesis-ui').html() + '</td>');
};

/**
 * Hides all messages within the Genesis UI area
 * @returns {undefined}
 */
WPSiteSyncContent_Genesis.prototype.hide_msgs = function()
{
	// TODO: rework to have a single message <div> that all messages are written into
	jQuery('.sync-genesis-msgs').hide();
	jQuery('.sync-genesis-loading-indicator').hide();
	jQuery('.sync-genesis-failure-msg').hide();
	jQuery('.sync-genesis-success-msg').hide();
	jQuery('.sync-genesis-pull-notice').hide();
};

/**
 * Sets the message area
 * @param {string} msg The HTML contents of the message to be shown.
 * @param {string} type The type of message to display.
 */
WPSiteSyncContent_Genesis.prototype.set_message = function(type, msg)
{
	if (!this.inited)
		return;

	// TODO: simplify this
	this.hide_msgs();
	jQuery('.sync-genesis-msgs').show();
	if ('loading' === type) {
		jQuery('.sync-genesis-loading-indicator').show();
	} else if ('success' === type) {
		jQuery('.sync-genesis-success-msg').show();
	} else if ('select' === type) {
		jQuery('.sync-genesis-failure-select').show();
		jQuery('.sync-genesis-failure-api').hide();
		jQuery('.sync-genesis-failure-msg').show();
	} else if ('api' === type) {
		jQuery('.sync-genesis-failure-api').show();
		jQuery('.sync-genesis-failure-select').hide();
		jQuery('.sync-genesis-failure-msg').show();
	} else if ('pull-notice' === type) {
		jQuery('.sync-genesis-pull-notice').show();
	} else {
		jQuery('.sync-genesis-failure-detail').html(msg);
		jQuery('.sync-genesis-failure-api').hide();
		jQuery('.sync-genesis-failure-select').hide();
		jQuery('.sync-genesis-failure-msg').show();
	}
};

/**
 * Push Genesis settings from target site
 * @param {array} settings The checked export values.
 */
WPSiteSyncContent_Genesis.prototype.push_genesis = function(settings)
{
//console.log('PUSH' + settings);

	// Do nothing when in a disabled state
//	if (this.disable || !this.inited)
//		return;

	var data = {
		action: 'spectrom_sync',
		operation: 'pushgenesis',
		selected_genesis_settings: settings,
		_sync_nonce: jQuery('#_sync_nonce').val()
	};

	wpsitesynccontent.genesis.hide_msgs();
	wpsitesynccontent.genesis.set_message('loading');

	jQuery.ajax({
		type: 'post',
		async: true, // false,
		data: data,
		url: ajaxurl,
		success: function (response)
		{
			wpsitesynccontent.genesis.hide_msgs();
//console.log('in ajax success callback - response');
//			console.log(response);
			if (response.success) {
				wpsitesynccontent.genesis.set_message('success');
			} else if (0 !== response.error_code) {
				wpsitesynccontent.genesis.set_message('failure', response.error_message);
			} else {
				wpsitesynccontent.genesis.set_message('api');
			}
		}
	});
};

/**
 * Pulls Genesis settings from target site
 * @param {array} settings The checked export values.
 */
WPSiteSyncContent_Genesis.prototype.pull_genesis = function(settings)
{
	// Do nothing when in a disabled state
//	if (this.disable || !this.inited)
//		return;

	var data = {
		action: 'spectrom_sync',
		operation: 'pullgenesis',
		selected_genesis_settings: settings,
		_sync_nonce: jQuery('#_sync_nonce').val()
	};

	wpsitesynccontent.genesis.hide_msgs();
	wpsitesynccontent.genesis.set_message('loading');

	jQuery.ajax({
		type: 'post',
		async: true, // false,
		data: data,
		url: ajaxurl,
		success: function (response)
		{
			wpsitesynccontent.genesis.hide_msgs();
//console.log('in ajax success callback - response');
//console.log(response);
			if (response.success) {
				wpsitesynccontent.genesis.set_message('success');
//				location.reload();
			} else if (0 !== response.error_code) {
				wpsitesynccontent.genesis.set_message('failure', response.error_message);
			} else {
				wpsitesynccontent.genesis.set_message('api');
			}
		}
	});
};

wpsitesynccontent.genesis = new WPSiteSyncContent_Genesis();

// initialize the WPSiteSync operation on page load
jQuery(document).ready(function ()
{
	wpsitesynccontent.genesis.init();
});
