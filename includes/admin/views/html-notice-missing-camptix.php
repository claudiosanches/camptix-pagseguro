<?php
/**
 * Missing CampTix notice
 *
 * @package CampTix_PagSeguro/Admin/Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_installed = false;

if ( function_exists( 'get_plugins' ) ) {
	$all_plugins  = get_plugins();
	$is_installed = ! empty( $all_plugins['camptix/camptix.php'] );
}

?>

<div class="error">
	<p><strong><?php esc_html_e( 'CampTix PagSeguro', 'camptix-pagseguro' ); ?></strong> <?php esc_html_e( 'depends on the last version of CampTix to work!', 'camptix-pagseguro' ); ?></p>

	<?php if ( $is_installed && current_user_can( 'install_plugins' ) ) : ?>
		<p><a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=camptix/camptix.php&plugin_status=active' ), 'activate-plugin_camptix/camptix.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Active CampTix', 'camptix-pagseguro' ); ?></a></p>
	<?php else :
		if ( current_user_can( 'install_plugins' ) ) {
			$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=camptix' ), 'install-plugin_camptix' );
		} else {
			$url = 'http://wordpress.org/plugins/camptix/';
		}
	?>
		<p><a href="<?php echo esc_url( $url ); ?>" class="button button-primary"><?php esc_html_e( 'Install CampTix', 'camptix-pagseguro' ); ?></a></p>
	<?php endif; ?>
</div>
