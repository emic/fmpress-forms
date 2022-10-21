<?php
/**
 * Class to handle AJAX in admin
 *
 * @since 1.3.0
 * @package WordPress
 */

namespace Emic\FMPress\Forms;

defined( 'ABSPATH' ) || die( 'Access denied.' );
use Emic\FMPress\Connect as Core;

/**
 * Admin Ajax.
 */
final class AdminAjax {
	/**
	 * Datasource driver.
	 *
	 * @since 1.3.0
	 * @access private
	 * @var object $fmdapi
	 */
	private $fmdapi = null;

	/**
	 * Driver error.
	 *
	 * @since 1.3.0
	 * @access private
	 * @var bool $error
	 */
	private $error = false;

	/**
	 * Class constructor
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'wp_ajax_get_layout_from_filemaker', array( $this, 'get_layout_from_filemaker' ) );
		}
	}

	/**
	 * Get layout from FileMaker.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function get_layout_from_filemaker() {
		// Check nonce.
		check_ajax_referer( 'fmpress_ajax_nonce', 'fmpress_ajax_nonce' );

		// Getting post id.
		if ( isset( $_POST['wp_post_id'] ) ) {
			$post_id = sanitize_text_field( wp_unslash( $_POST['wp_post_id'] ) );
			$layout  = sanitize_text_field( wp_unslash( $_POST['filemaker_layout_name'] ) );
		} else {
			$messages          = array();
			$messages['error'] = __( 'Post ID is not included.', 'emic-fmpress-connect' );
			exit( wp_json_encode( $messages ) );
		}

		// Init database driver.
		$this->init_driver( $post_id, $layout );
		if ( $this->error ) {
			return;
		}

		// Get the layout.
		$response = $this->fmdapi->get_layout_meta();

		exit( wp_json_encode( $response ) );
	}

	/**
	 * Initializing FileMaker DataAPI driver.
	 *
	 * @since 1.3.0
	 * @param int    $post_id .
	 * @param string $layout .
	 * @return void
	 */
	private function init_driver( int $post_id, string $layout ) {
		$server     = get_post_meta( $post_id, 'fmpress_connect_server', true );
		$datasource = get_post_meta( $post_id, 'fmpress_connect_datasource', true );

		$this->fmdapi             = new Core\Fmdapi();
		$this->fmdapi->server     = $server;
		$this->fmdapi->datasource = $datasource;
		$this->fmdapi->layout     = $layout;

		// Error handling.
		if ( ! $this->fmdapi->server || ! $this->fmdapi->datasource ) {
			$this->error = true;
		}
	}
}
