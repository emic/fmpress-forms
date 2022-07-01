<?php
/**
 * Define custom post type
 * Use as external data sources
 *
 * @since 1.0.0
 * @package WordPress
 */

namespace Emic\FMPress\Connect;

require_once ABSPATH . 'wp-includes/sodium_compat/src/Core/Util.php';
require_once ABSPATH . 'wp-includes/sodium_compat/src/Compat.php';

/**
 * Datasources
 */
final class Datasources {

	/**
	 * Custom post type name
	 *
	 * @since 1.0.0
	 * @access private
	 * @var object $custompost_name
	 */
	private $custompost_name = 'connect_datasource';

	/**
	 * Drivers of Connect
	 *
	 * @since 1.0.0
	 * @access private
	 * @var object $drivers
	 */
	private $drivers = array(
		'1' => 'FileMaker',
	);

	/**
	 * Class constructor
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'admin_menu', array( $this, 'add_custom_fields' ) );
			add_action( 'admin_menu', array( $this, 'add_submenu' ), 11, 1 );
			add_action( 'admin_menu', array( $this, 'remove_submenu' ) );
			add_action( 'admin_notices', array( $this, 'check_constants' ) );
			add_action( 'save_post', array( $this, 'save_datasource' ) );
			add_action( 'wp_ajax_connection_test', array( $this, 'connection_test' ) );
			add_action( 'post_updated_messages', array( $this, 'connect_datasource_updated_messages' ) );
		}
	}

	/**
	 * Add custom post
	 */
	public function init() {
		$supports = array(
			'title',
			'author',
			'excerpt',
			'revisions',
		);
		$labels   = array(
			'name'          => did_action( 'fmpress_connect_loaded' ) ? 'FMPress Pro' : 'FMPress',
			'singular_name' => __( 'Datasources', 'fmpress-forms' ),
			'all_items'     => __( 'Datasources', 'fmpress-forms' ),
			'add_new_item'  => __( 'Add new datasource', 'fmpress-forms' ),
			'edit_item'     => __( 'Edit datasource', 'fmpress-forms' ),
			'new_item'      => __( 'Add new datasource', 'fmpress-forms' ),
			'view_item'     => __( 'Show datasource', 'fmpress-forms' ),
		);
		$args     = array(
			'labels'               => $labels,
			'publicly_queryable'   => false,
			'exclude_from_search'  => false,
			'capability_type'      => 'post',
			'public'               => true,
			'rewrite'              => true,
			'has_archive'          => false,
			'supports'             => $supports,
			'register_meta_box_cb' => null,
			'taxonomies'           => array(),
			'show_ui'              => true,
			'menu_position'        => null,
			'menu_icon'            => 'dashicons-admin-generic',
		);
		register_post_type( $this->custompost_name, $args );
	}

	/**
	 * Add sub menu
	 */
	public function add_submenu() {
		add_submenu_page(
			'edit.php?post_type=connect_datasource',
			__( 'Documentation', 'fmpress-forms' ),
			__( 'Documentation', 'fmpress-forms' ),
			'edit_themes',
			'support',
			array( $this, 'display_submenu' )
		);
	}

	/**
	 * Display sub menu
	 */
	public function display_submenu() {
		echo sprintf(
			'<div class="wrap"><h1>%s</h1><p><a href="%s" target="_blank" rel="noopener">%s</a></p></div>',
			esc_html__( 'Documentation', 'fmpress-forms' ),
			esc_attr( 'https://github.com/emic/fmpress-forms/wiki' ),
			esc_html__( 'Click here for documentation.', 'fmpress-forms' )
		);
	}

	/**
	 * Remove sub menu
	 */
	public function remove_submenu() {
		remove_submenu_page(
			'edit.php?post_type=connect_datasource',
			'post-new.php?post_type=connect_datasource'
		);
	}

	/**
	 * Add custom fields
	 */
	public function add_custom_fields() {
		/**
		 * Define data source
		 */
		$id       = 'datasources';
		$title    = __( 'Datasource info', 'fmpress-forms' );
		$callback = array( $this, 'add_datasource_fields' );
		$screen   = $this->custompost_name;
		add_meta_box(
			$id,
			$title,
			$callback,
			$screen
		);
	}

	/**
	 * Add fields to set the data source
	 */
	public function add_datasource_fields() {
		echo '<div class="form-wrap">';

		wp_nonce_field( 'fmpress_forms', 'fmpress_forms_nonce' );

		// Driver.
		printf(
			'<div class="form-field">' .
			'<label for="%1$s">%2$s</label>' .
			'<select id="%1$s" name="%3$s">',
			esc_attr( 'driver' ),
			esc_html__( 'Driver', 'fmpress-forms' ),
			esc_attr( FMPRESS_CONNECT_NAMEPREFIX . '_driver' )
		);

		$this->generate_options_from_drivers();

		echo '</select></div>';

		// Server address.
		printf(
			'<div class="form-field">' .
			'<label for="%1$s">%2$s</label>' .
			'<input id="%1$s" type="%5$s" name="%3$s" value="%4$s">' .
			'</div>',
			esc_attr( 'serverAddress' ),
			esc_html__( 'Server', 'fmpress-forms' ),
			esc_attr( FMPRESS_CONNECT_NAMEPREFIX . '_server' ),
			esc_attr( Utils::get_custom_field_value( FMPRESS_CONNECT_NAMEPREFIX . '_server' ) ),
			esc_attr( 'text' )
		);

		// Datasource name.
		echo sprintf(
			'<div class="form-field">' .
			'<label for="%1$s">%2$s</label>' .
			'<input id="%1$s" type="%5$s" name="%3$s" value="%4$s">' .
			'</div>',
			esc_attr( 'databaseName' ),
			esc_html__( 'Database', 'fmpress-forms' ),
			esc_attr( FMPRESS_CONNECT_NAMEPREFIX . '_datasource' ),
			esc_attr( Utils::get_custom_field_value( FMPRESS_CONNECT_NAMEPREFIX . '_datasource' ) ),
			esc_attr( 'text' )
		);

		// Username.
		echo sprintf(
			'<div class="form-field">' .
			'<label for="%1$s">%2$s</label>' .
			'<input id="%1$s" type="%5$s" name="%3$s" value="%4$s">' .
			'</div>',
			esc_attr( 'databaseUsername' ),
			esc_html__( 'Username', 'fmpress-forms' ),
			esc_attr( FMPRESS_CONNECT_NAMEPREFIX . '_datasource_username' ),
			esc_attr( Utils::get_custom_field_value( FMPRESS_CONNECT_NAMEPREFIX . '_datasource_username' ) ),
			esc_attr( 'text' )
		);

		// Password.
		echo sprintf(
			'<div class="form-field">' .
			'<label for="%1$s">%2$s</label>' .
			'<button id="%8$s" type="button" class="button wp-generate-pw hide-if-no-js">%6$s</button>' .
			'<div data-aria="%8$s" class="wp-pwd hide-if-js">' .
			'<span class="password-input-wrapper">' .
			'<input id="%1$s" type="%5$s" name="%3$s" class="regular-text strong" value="%4$s" autocomplete="off" style="%7$s">' .
			'</span>&nbsp;' .
			'<button id="%9$s" type="button" class="button wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="Hide password">' .
			'<span class="dashicons dashicons-hidden" aria-hidden="true"></span>' .
			'<span class="text">%11$s</span>' .
			'</button>&nbsp;' .
			'<button id="%10$s" type="button" class="button wp-cancel-pw hide-if-no-js" data-toggle="0" aria-label="Cancel password change">' .
			'<span class="dashicons dashicons-no" aria-hidden="true"></span>' .
			'<span class="text">%12$s</span>' .
			'</button>' .
			'</div>' .
			'</div>',
			esc_attr( 'databasePassword' ),
			esc_html__( 'Password', 'fmpress-forms' ),
			esc_attr( FMPRESS_CONNECT_NAMEPREFIX . '_datasource_password' ),
			'',
			esc_attr( 'text' ),
			esc_html__( 'Set password', 'fmpress-forms' ),
			esc_attr( 'width: 50%;' ),
			esc_attr( 'setDatabasePassword' ),
			esc_attr( 'hideDatabasePassword' ),
			esc_attr( 'cancelDatabasePassword' ),
			esc_html__( 'Hide', 'fmpress-forms' ),
			esc_html__( 'Cancel', 'fmpress-forms' )
		);

		// Show connection test button.
		$this->show_connection_test();

		echo '</div>';
	}

	/**
	 * Save data source
	 */
	public function save_datasource() {
		global $post;

		if ( ! isset( $post->ID ) || get_post_type( $post ) !== $this->custompost_name ) {
			return;
		}

		if ( ! is_null( $this->check_constants() ) ) {
			return;
		}

		// Driver.
		self::save_post_meta( $post->ID, FMPRESS_CONNECT_NAMEPREFIX . '_driver' );

		// Server.
		self::save_post_meta( $post->ID, FMPRESS_CONNECT_NAMEPREFIX . '_server' );

		// Datasource.
		self::save_post_meta( $post->ID, FMPRESS_CONNECT_NAMEPREFIX . '_datasource' );

		// Username.
		self::save_post_meta( $post->ID, FMPRESS_CONNECT_NAMEPREFIX . '_datasource_username' );

		// Password.
		self::save_post_meta_password( $post->ID, FMPRESS_CONNECT_NAMEPREFIX . '_datasource_password' );
	}

	/**
	 * Save custom fields
	 *
	 * @param int    $post_id .
	 * @param string $field_name .
	 */
	private function save_post_meta( $post_id, $field_name ) {
		$check = wp_verify_nonce( $_POST['fmpress_forms_nonce'], 'fmpress_forms' );
		if ( false === $check ) {
			return;
		}

		if ( isset( $_POST[ $field_name ] ) ) {
			$sanitized = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );
			update_post_meta( $post_id, $field_name, $sanitized );
		} else {
			if ( isset( $post_id ) ) {
				delete_post_meta( $post_id, $field_name );
			}
		}
	}

	/**
	 * Check constants and show notices
	 *
	 * @since 1.2.0
	 * @return null|string
	 */
	public function check_constants() {
		$message = null;

		if ( ! is_callable( 'openssl_encrypt' ) ) {
			$message = __( 'The OpenSSL extension is not installed, or openssl_encrypt() is not available.', 'fmpress-forms' );
		} elseif ( ! \ParagonIE_Sodium_Compat::crypto_aead_aes256gcm_is_available() ) {
			$message = __( 'AES-256-GCM is not available.', 'fmpress-forms' );
		} elseif ( \ParagonIE_Sodium_Core_Util::strlen( FMPRESS_CONNECT_ENCRYPT_KEY )
			!== \ParagonIE_Sodium_Compat::CRYPTO_AEAD_AES256GCM_KEYBYTES ) {
			$message = __( 'The value of FMPRESS_CONNECT_ENCRYPT_KEY in wp-config.php is invalid for some reason. Please flush PHP OPcache if Opcache is enabled.', 'fmpress-forms' );
		} elseif ( ! ctype_xdigit( FMPRESS_CONNECT_ENCRYPT_IV ) ) {
			$message = __( 'The value of FMPRESS_CONNECT_ENCRYPT_IV in wp-config.php is invalid for some reason. Please flush PHP OPcache if Opcache is enabled.', 'fmpress-forms' );
		} elseif ( \ParagonIE_Sodium_Core_Util::strlen( hex2bin( FMPRESS_CONNECT_ENCRYPT_IV ) )
			!== \ParagonIE_Sodium_Compat::CRYPTO_AEAD_AES256GCM_NPUBBYTES ) {
			$message = __( 'The value of FMPRESS_CONNECT_ENCRYPT_IV in wp-config.php is invalid for some reason. Please flush PHP OPcache if Opcache is enabled.', 'fmpress-forms' );
		}

		if ( 'admin_notices' === current_action() ) {
			$screen = get_current_screen();
			if ( $this->custompost_name === $screen->post_type && 'post' === $screen->base ) {
				if ( ! is_null( $message ) ) {
					echo '<div class="error"><p>',
						esc_html( $message ),
						'</p></div>', PHP_EOL;
				}
			}
		}

		return $message;
	}

	/**
	 * Update messages
	 *
	 * @since 1.2.0
	 * @param array $messages .
	 * @return array
	 */
	public function connect_datasource_updated_messages( $messages ) {
		$screen = get_current_screen();
		if ( $this->custompost_name === $screen->post_type ) {
			if ( ! is_null( $this->check_constants() ) ) {
				// Hide admin notices ('Post published.', 'Post updated.').
				$messages[ $this->custompost_name ][1] = '';
			}
		}

		return $messages;
	}

	/**
	 * Save password
	 *
	 * @param int    $post_id .
	 * @param string $field_name .
	 */
	private function save_post_meta_password( $post_id, $field_name ) {
		$check = wp_verify_nonce( $_POST['fmpress_forms_nonce'], 'fmpress_forms' );
		if ( false === $check ) {
			return;
		}

		$form_value = '';

		if ( isset( $_POST[ $field_name ] ) ) {
			$form_value = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );
		} else {
			return;
		}

		if ( empty( $form_value ) ) {
			return;
		}

		if ( defined( 'FMPRESS_CONNECT_ENCRYPT_KEY' ) && defined( 'FMPRESS_CONNECT_ENCRYPT_IV' ) ) {
			$tag        = '';
			$ciphertext = openssl_encrypt(
				$form_value,
				'aes-256-gcm',
				FMPRESS_CONNECT_ENCRYPT_KEY,
				OPENSSL_RAW_DATA,
				hex2bin( FMPRESS_CONNECT_ENCRYPT_IV ),
				$tag
			) . $tag;

			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			update_post_meta( $post_id, $field_name, base64_encode( $ciphertext ) );
		} else {
			if ( isset( $post_id ) ) {
				delete_post_meta( $post_id, $field_name );
			}
		}
	}

	/**
	 * Generate option elements
	 *
	 * @return void
	 */
	private function generate_options_from_drivers() {
		$driver_id = Utils::get_custom_field_value( FMPRESS_CONNECT_NAMEPREFIX . '_driver' );

		// Choose driver.
		printf(
			'<option disabled value=""%1$s>%2$s</option>',
			esc_html( empty( $driver_id ) ? ' selected' : '' ),
			esc_html__( 'Choose driver', 'fmpress-forms' )
		);

		// Generate option elements.
		foreach ( $this->drivers as $key => $value ) {
			$selected = (string) $key === $driver_id ? ' selected' : '';
			printf(
				'<option value="%1$s"%3$s>%2$s</option>',
				esc_attr( $key ),
				esc_html( $value ),
				esc_html( $selected )
			);
		}
	}

	/**
	 * Display button for connection test
	 */
	private function show_connection_test() {
		if ( ! $this->validate() ) {
			return;
		}

		echo sprintf(
			'<button id="connectionTest" class="button">%1$s</button>',
			esc_html__( 'Connection test', 'fmpress-forms' )
		);
	}

	/**
	 * Validate datasource
	 *
	 * @return bool
	 */
	private function validate() {
		$keys = array(
			'_driver',
			'_server',
			'_datasource',
		);

		foreach ( $keys as $key ) {
			$value = Utils::get_custom_field_value( FMPRESS_CONNECT_NAMEPREFIX . $key );
			if ( empty( $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Testing the connection of data source
	 */
	public function connection_test() {
		$messages = array();

		// Check nonce.
		check_ajax_referer( 'fmpress_ajax_nonce', 'fmpress_ajax_nonce' );

		// Getting post id.
		if ( isset( $_POST['wp_post_id'] ) ) {
			$post_id = sanitize_text_field( wp_unslash( $_POST['wp_post_id'] ) );
		} else {
			$messages[] = __( 'Post ID is not included.', 'fmpress-forms' );
			exit( wp_json_encode( $messages ) );
		}

		// Setting driver.
		$fmdapi             = new Fmdapi();
		$fmdapi->server     = get_post_meta( $post_id, 'fmpress_connect_server', true );
		$fmdapi->datasource = get_post_meta( $post_id, 'fmpress_connect_datasource', true );

		// Error handling.
		if ( ! $fmdapi->server || ! $fmdapi->datasource ) {
			return;
		}

		// Get response.
		$response = $fmdapi->get_layouts();

		// Set messages.
		$messages = $this->set_messages( $response );

		exit( wp_json_encode( $messages ) );
	}

	/**
	 * Return error code and message.
	 *
	 * @param WP_Error|array $response .
	 * @return array
	 */
	private function set_messages( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response->get_error_messages();
		}

		return array(
			'HTTP: OK (200)',
			'FileMaker Server: No error (0)',
		);
	}
}
