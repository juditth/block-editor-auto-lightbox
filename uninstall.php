<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Block_Editor_Auto_Lightbox
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('beal_settings');
