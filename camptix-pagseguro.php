<?php
/**
 * Plugin Name: CampTix PagSeguro
 * Plugin URI: https://github.com/claudiosmweb/camptix-pagseguro
 * Description: PagSeguro Gateway for CampTix
 * Author: claudiosanches, rafaelfunchal
 * Author URI: http://claudiosmweb.com/
 * Version: 1.4.0
 * License: GPLv2 or later
 * Text Domain: ctpagseguro
 * Domain Path: /languages/
 */

/**
 * CampTix fallback notice.
 *
 * @return string HTML Message.
 */
function ctpagseguro_admin_notice() {
    $html = '<div class="error">';
        $html .= '<p>' . sprintf( __( 'CampTix PagSeguro Gateway depends on the last version of %s to work!', 'ctpagseguro' ), '<a href="http://wordpress.org/extend/plugins/camptix/">CampTix</a>' ) . '</p>';
    $html .= '</div>';

    echo $html;
}

/**
 * Load functions.
 *
 * @return void
 */
function ctpagseguro_plugins_loaded() {
    load_plugin_textdomain( 'ctpagseguro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    if ( ! class_exists( 'CampTix_Plugin' ) || ! class_exists( 'CampTix_Payment_Method' ) ) {
        add_action( 'admin_notices', 'ctpagseguro_admin_notice' );
        return;
    }

    add_action( 'camptix_load_addons', 'ctpagseguro_camptix_load_addons' );
}

add_action( 'plugins_loaded', 'ctpagseguro_plugins_loaded' );

/**
 * Include PagSeguro Payment on CampTix load addons.
 *
 * @return void
 */
function ctpagseguro_camptix_load_addons() {
    require_once plugin_dir_path( __FILE__ ) . 'payment-pagseguro.php';
}
