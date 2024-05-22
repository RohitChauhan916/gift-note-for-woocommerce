<?php
/**
 * Plugin Name:       Gift Note for WooCommerce
 * Description:       Allows customers to add a gift note to each product in their order with customizable label, placeholder text, position, height, and width.
 * Version:           1.4.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Rohit Chauhan
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gift-note-for-woocommerce
 * 
 */

 /*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2005-2023 Rohit Chauhan, Inc.
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue custom CSS
function wc_gift_note_enqueue_styles() {
    wp_enqueue_style( 'wc-gift-note-styles', plugins_url( 'css/gift-note-styles.css', __FILE__ ), array(), '1.0.0' );
}
add_action( 'wp_head', 'wc_gift_note_enqueue_styles' );

// Add gift note field to product page
function add_gift_note_field() {
    $gift_note_label = get_option( 'wc_gift_note_label', __( 'Gift Note', 'woocommerce' ) );
    $gift_note_placeholder = get_option( 'wc_gift_note_placeholder', __( 'Enter your gift note here...', 'woocommerce' ) );
    $gift_note_width = get_option( 'wc_gift_note_width', '100%' );
    $gift_note_height = get_option( 'wc_gift_note_height', '100px' );
    echo '<div class="gift-note-field">';
    echo '<label for="gift_note" class="gift-note-label">' . esc_html( $gift_note_label ) . '</label>';
    echo '<textarea name="gift_note" id="gift_note" class="gift-note-textarea" style="width: ' . esc_attr( $gift_note_width ) . '; height: ' . esc_attr( $gift_note_height ) . ';" placeholder="' . esc_attr( $gift_note_placeholder ) . '"></textarea>';
    wp_nonce_field( 'save_gift_note', 'gift_note_nonce' );
    echo '</div>';
}

function maybe_add_gift_note_field() {
    $position = get_option( 'wc_gift_note_position', 'before' );
    if ( $position === 'before' ) {
        add_action( 'woocommerce_before_add_to_cart_button', 'add_gift_note_field' );
    } else {
        add_action( 'woocommerce_after_add_to_cart_button', 'add_gift_note_field' );
    }
}
add_action( 'wp', 'maybe_add_gift_note_field' );

// Save gift note to cart item data
function save_gift_note_field( $cart_item_data, $product_id ) {
    if ( isset( $_POST['gift_note_nonce'] ) && wp_verify_nonce( $_POST['gift_note_nonce'], 'save_gift_note' ) ) {
        if ( isset( $_POST['gift_note'] ) ) {
            $cart_item_data['gift_note'] = sanitize_text_field( $_POST['gift_note'] );
        }
    }
    return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'save_gift_note_field', 10, 2 );

// Display gift note in cart and checkout
function display_gift_note_cart_checkout( $item_data, $cart_item ) {
    if ( isset( $cart_item['gift_note'] ) ) {
        $item_data[] = array(
            'key'   => __( 'Gift Note', 'woocommerce' ),
            'value' => wc_clean( $cart_item['gift_note'] )
        );
    }
    return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'display_gift_note_cart_checkout', 10, 2 );

// Save gift note to order meta
function save_gift_note_order_meta( $item_id, $values, $cart_item_key ) {
    if ( isset( $values['gift_note'] ) ) {
        wc_add_order_item_meta( $item_id, 'Gift Note', $values['gift_note'] );
    }
}
add_action( 'woocommerce_add_order_item_meta', 'save_gift_note_order_meta', 10, 3 );

// Display gift note in admin order
function display_gift_note_order_admin( $item_id, $item, $product ) {
    if ( isset( $item['Gift Note'] ) ) {
        echo '<p><strong>' . esc_html_e( 'Gift Note', 'woocommerce' ) . ':</strong> ' . esc_html($item['Gift Note']) . '</p>';
    }
}
add_action( 'woocommerce_order_item_meta_end', 'display_gift_note_order_admin', 10, 3 );

// Add settings page
function wc_gift_note_settings_menu() {
    add_options_page(
        'Gift Note Settings',
        'Gift Note Settings',
        'manage_options',
        'wc-gift-note-settings',
        'wc_gift_note_settings_page'
    );
}
add_action( 'admin_menu', 'wc_gift_note_settings_menu' );

// Display settings page content
function wc_gift_note_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Gift Note Settings', 'woocommerce' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'wc_gift_note_settings_group' );
            do_settings_sections( 'wc-gift-note-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function wc_gift_note_register_settings() {
    register_setting( 'wc_gift_note_settings_group', 'wc_gift_note_label' );
    register_setting( 'wc_gift_note_settings_group', 'wc_gift_note_placeholder' );
    register_setting( 'wc_gift_note_settings_group', 'wc_gift_note_position' );
    register_setting( 'wc_gift_note_settings_group', 'wc_gift_note_width' );
    register_setting( 'wc_gift_note_settings_group', 'wc_gift_note_height' );

    add_settings_section(
        'wc_gift_note_settings_section',
        __( 'Gift Note Settings', 'woocommerce' ),
        null,
        'wc-gift-note-settings'
    );

    add_settings_field(
        'wc_gift_note_label',
        __( 'Gift Note Label', 'woocommerce' ),
        'wc_gift_note_label_field_callback',
        'wc-gift-note-settings',
        'wc_gift_note_settings_section'
    );

    add_settings_field(
        'wc_gift_note_placeholder',
        __( 'Gift Note Placeholder', 'woocommerce' ),
        'wc_gift_note_placeholder_field_callback',
        'wc-gift-note-settings',
        'wc_gift_note_settings_section'
    );

    add_settings_field(
        'wc_gift_note_position',
        __( 'Gift Note Position', 'woocommerce' ),
        'wc_gift_note_position_field_callback',
        'wc-gift-note-settings',
        'wc_gift_note_settings_section'
    );

    add_settings_field(
        'wc_gift_note_width',
        __( 'Gift Note Width', 'woocommerce' ),
        'wc_gift_note_width_field_callback',
        'wc-gift-note-settings',
        'wc_gift_note_settings_section'
    );

    add_settings_field(
        'wc_gift_note_height',
        __( 'Gift Note Height', 'woocommerce' ),
        'wc_gift_note_height_field_callback',
        'wc-gift-note-settings',
        'wc_gift_note_settings_section'
    );
}
add_action( 'admin_init', 'wc_gift_note_register_settings' );

// Display label field
function wc_gift_note_label_field_callback() {
    $label = get_option( 'wc_gift_note_label', __( 'Gift Note', 'woocommerce' ) );
    echo '<input type="text" name="wc_gift_note_label" value="' . esc_attr( $label ) . '" />';
}

// Display placeholder field
function wc_gift_note_placeholder_field_callback() {
    $placeholder = get_option( 'wc_gift_note_placeholder', __( 'Enter your gift note here...', 'woocommerce' ) );
    echo '<input type="text" name="wc_gift_note_placeholder" value="' . esc_attr( $placeholder ) . '" />';
}

// Display position field
function wc_gift_note_position_field_callback() {
    $position = get_option( 'wc_gift_note_position', 'before' );
    ?>
    <select name="wc_gift_note_position">
        <option value="before" <?php selected( $position, 'before' ); ?>><?php esc_html_e( 'Before Add to Cart Button', 'woocommerce' ); ?></option>
        <option value="after" <?php selected( $position, 'after' ); ?>><?php esc_html_e( 'After Add to Cart Button', 'woocommerce' ); ?></option>
    </select>
    <?php
}

// Display width field
function wc_gift_note_width_field_callback() {
    $width = get_option( 'wc_gift_note_width', '100%' );
    echo '<input type="text" name="wc_gift_note_width" value="' . esc_attr( $width ) . '" />';
}

// Display height field
function wc_gift_note_height_field_callback() {
    $height = get_option( 'wc_gift_note_height', '100px' );
    echo '<input type="text" name="wc_gift_note_height" value="' . esc_attr( $height ) . '" />';
}
