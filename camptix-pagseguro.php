<?php
/**
 * Plugin Name: CampTix PagSeguro
 * Plugin URI: https://github.com/claudiosmweb/camptix-pagseguro
 * Description: PagSeguro Gateway for CampTix
 * Author: claudiosanches, rafaelfunchal
 * Author URI: http://claudiosmweb.com/
 * Version: 1.5.2
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

/**
 * Adds custom settings url in plugins page.
 *
 * @param  array $links Default links.
 *
 * @return array        Default links and settings link.
 */
function ctpagseguro_action_links( $links ) {

    $settings = array(
        'settings' => sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'edit.php?post_type=tix_ticket&page=camptix_options&tix_section=payment' ),
            __( 'Settings', 'ctpagseguro' )
        )
    );

    return array_merge( $settings, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ctpagseguro_action_links' );
