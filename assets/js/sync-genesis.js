/*
 * @copyright Copyright (C) 2015 SpectrOMtech.com. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author SpectrOMtech.com <hello@SpectrOMtech.com>
 * @url https://www.SpectrOMtech.com/products/
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the SpectrOMtech Proprietary Use License v1.0
 * More info at https://SpectrOMtech.com/products/
 */

function WPSiteSyncContent_Genesis()
{
    this.inited = false;
}

/**
 * Init
 */
WPSiteSyncContent_Genesis.prototype.init = function ()
{
    this.inited = true;
    this.show();
};

/**
 * Shows the Menu UI area
 */
WPSiteSyncContent_Genesis.prototype.show = function ()
{
    this.hide_msgs();

    jQuery('.genesis_page_genesis-import-export #download').after(jQuery('#sync-genesis-ui').html());
};

/**
 * Hides all messages within the Genesis UI area
 * @returns {undefined}
 */
WPSiteSyncContent_Genesis.prototype.hide_msgs = function ()
{
    jQuery('.sync-genesis-msgs').hide();
    jQuery('.sync-genesis-loading-indicator').hide();
    jQuery('.sync-genesis-failure-msg').hide();
    jQuery('.sync-genesis-success-msg').hide();
};

/**
 * Sets the message area
 * @param {string} msg The HTML contents of the message to be shown.
 * @param {string} type The type of message to display.
 */
WPSiteSyncContent_Genesis.prototype.set_message = function (type, msg)
{
    if (!this.inited)
        return;

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
    } else {
        jQuery('.sync-genesis-failure-detail').html(msg);
        jQuery('.sync-genesis-failure-api').hide();
        jQuery('.sync-genesis-failure-select').hide();
        jQuery('.sync-genesis-failure-msg').show();
    }
};

/**
 * Push Genesis settings from target site
 * @param settings
 */
WPSiteSyncContent_Genesis.prototype.push_genesis = function (settings)
{
//console.log('PUSH' + settings);

    // Do nothing when in a disabled state
    if (this.disable || !this.inited)
        return;

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
            console.log(response);
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
 * @param genesis_name
 */
WPSiteSyncContent_Genesis.prototype.pull_genesis = function (settings)
{
    // Do nothing when in a disabled state
    if (this.disable || !this.inited)
        return;

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
                location.reload();
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

    jQuery('.sync-genesis-contents').on('click', '.sync-genesis-push, .sync-genesis-pull', function ()
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
    });
});
