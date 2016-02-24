<?php
/**
 * Plugin Name: CampTix PagSeguro
 * Plugin URI: https://github.com/claudiosmweb/camptix-pagseguro
 * Description: PagSeguro Gateway for CampTix
 * Author: Claudio Sanches
 * Author URI: https://claudiosmweb.com/
 * Version: 1.5.5
 * License: GPLv2 or later
 * Text Domain: camptix-pagseguro
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CampTix_PagSeguro' ) ) :

/**
 * CampTix PagSeguro main class.
 *
 * @package CampTix_PagSeguro
 */
class CampTix_PagSeguro {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.5.5';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin actions.
	 */
	private function __construct() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		if ( class_exists( 'CampTix_Payment_Method' ) ) {
			add_action( 'camptix_load_addons', array( $this, 'load_addons' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'missing_dependencies_notice' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'camptix-pagseguro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Load CampTix addons.
	 */
	public function load_addons() {
		require_once 'includes/class-payment-method-pagseguro.php';

		camptix_register_addon( 'CampTix_Payment_Method_PagSeguro' );
	}

	/**
	 * Action links.
	 *
	 * @param  array $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array();

		$plugin_links[] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=tix_ticket&page=camptix_options&tix_section=payment' ) ) . '">' . __( 'Settings', 'camptix-pagseguro' ) . '</a>';

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Missing dependencies notice.
	 */
	public function missing_dependencies_notice() {
		include_once 'includes/admin/views/html-notice-missing-camptix.php';
	}
}

add_action( 'plugins_loaded', array( 'CampTix_PagSeguro', 'get_instance' ) );

endif;
