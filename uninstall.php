<?php
/**
 * Uninstall script for Gift Note for WooCommerce.
 * 
 * This script will clean up the plugin's settings from the WordPress database
 * when the plugin is uninstalled.
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete the plugin options from the database.
delete_option( 'wc_gift_note_label' );
delete_option( 'wc_gift_note_placeholder' );
delete_option( 'wc_gift_note_position' );
delete_option( 'wc_gift_note_width' );
delete_option( 'wc_gift_note_height' );
