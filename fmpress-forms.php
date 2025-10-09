<?php
/**
 * Plugin Name: FMPress Forms
 * Plugin URI: https://www.emic.co.jp/products/
 * Description: Addon for Contact Form 7.
 * Version: 2.0.0
 * Author: Emic Corporation
 * Author URI: https://www.emic.co.jp/
 * License: GPLv2 or later
 * Text Domain: fmpress-forms
 *
 * @package WordPress
 */

namespace Emic\FMPress\Forms;

defined( 'ABSPATH' ) || die( 'Access denied.' );
define( 'FMPRESS_FORMS_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
define( 'FMPRESS_FORMS_CF7_SETTINGS_KEY', 'fmpress_connect_settings_data' );

use Emic\FMPress\Connect as Core;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * FMPress Forms
 *
 * @since 1.0.0
 */
final class FMPress_Forms {
	/**
	 * Name of this plugin
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const PLUGIN_NAME = 'FMPress Forms';

	/**
	 * Version of this plugin
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '2.0.0';

	/**
	 * Minimum Version of PHP
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const MINIMUM_PHP_VERSION = '7.4.0';

	/**
	 * Minimum Version of Contact Form 7
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const MINIMUM_CF7_VERSION = '5.5';

	/**
	 * Special Mail Tags of Contact Form 7
	 *
	 * @since 1.2.0
	 * @var array
	 */
	const CF7_SPECAIL_MAIL_TAGS = array(
		'_remote_ip',
		'_user_agent',
	);

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'fmpress_load_plugin_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'fmpress_plugin_init' ) );
		add_action( 'admin_notices', array( $this, 'is_plugin_requirements' ) );
		add_filter( 'script_loader_tag', array( $this, 'fmpress_load_js_as_module' ), 10, 3 );
	}

	/**
	 * Load Text Domain
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function fmpress_load_plugin_textdomain() {
		load_plugin_textdomain(
			'fmpress-forms',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Initialize
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function fmpress_plugin_init() {
		if ( ! did_action( 'fmpress_forms_pro_loaded' ) &&
			( is_plugin_active( 'fmpress-connect/fmpress-connect.php' ) || is_plugin_active( 'fmpress-pro/fmpress-connect.php' ) ) ) {
			// Exit if FMPress Pro and FMPress Forms are activated.
			return;
		}

		// Disable CF7 SWV.
		add_action(
			'init',
			function () {
				// Checkbox.
				remove_action( 'wpcf7_swv_create_schema', 'wpcf7_swv_add_checkbox_enum_rules', 20, 2 );

				// Select.
				remove_action( 'wpcf7_swv_create_schema', 'wpcf7_swv_add_select_enum_rules', 20, 2 );
			}
		);

		// Add a link to the settings on the plugin page.
		add_filter(
			'plugin_action_links_' . plugin_basename( __FILE__ ),
			array( $this, 'fmpress_add_link_to_settings' )
		);

		if ( did_action( 'fmpress_forms_loaded' ) || did_action( 'fmpress_forms_pro_loaded' ) ) {
			// Require plugin files.
			$this->fmpress_require_files();

			// Create class instances.
			new Admin();
			new AdminAjax();
			new Forms();
		}

		if ( did_action( 'fmpress_forms_loaded' ) ) {
			new Core\Datasources();
		}

		// Enqueue files.
		$this->fmpress_enqueue_files();
	}

	/**
	 * Check if the plugin meets the specifications
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return bool
	 */
	public function is_plugin_requirements() {
		$errors = array();

		// Admin notice for minimum PHP version.
		$errors[] = $this->is_require_php_version( self::MINIMUM_PHP_VERSION );

		// Admin notice for check for the existence of constants.
		$errors[] = $this->is_defined_encrypt_key();

		// Admin notice for check for the existence of constants.
		$errors[] = $this->is_defined_encrypt_iv();

		// Check if CF7 installed and activated.
		$errors[] = $this->is_require_cf7_version( self::MINIMUM_CF7_VERSION );

		// Exit if FMPress Forms Pro is activated.
		$errors[] = $this->is_activated_fmpress_forms();

		// Check if FMPress Pro installed and activated.
		$errors[] = $this->is_not_activated_fmpress_core();

		// Exit if FMPress Pro is activated.
		$errors[] = $this->is_activated_fmpress_core();

		if ( in_array( false, $errors, true ) ) {
			$this->fmpress_deactivate_plugin();
			return false;
		}

		return true;
	}

	/**
	 * Add a link to the settings on the plugin page
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $links .
	 * @return array
	 */
	public function fmpress_add_link_to_settings( $links ) {
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( array( 'page' => 'wpcf7' ), admin_url( 'admin.php' ) ) ),
			__( 'Settings' )
		);

		return $links;
	}

	/**
	 * Require plugin files
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function fmpress_require_files() {
		require_once FMPRESS_FORMS_PLUGIN_DIR . '/admin/class-admin.php';
		require_once FMPRESS_FORMS_PLUGIN_DIR . '/admin/class-admin-ajax.php';
		require_once FMPRESS_FORMS_PLUGIN_DIR . '/includes/class-forms.php';
		require_once FMPRESS_FORMS_PLUGIN_DIR . '/includes/class-fm-value-list.php';

		if ( did_action( 'fmpress_forms_loaded' ) ) {
			// For FMPress Forms.
			define( 'FMPRESS_CONNECT_NAMEPREFIX', 'fmpress_connect' );
			require_once FMPRESS_FORMS_PLUGIN_DIR . '/admin/class-datasources.php';
			require_once FMPRESS_FORMS_PLUGIN_DIR . '/admin/class-settings.php';
			require_once FMPRESS_FORMS_PLUGIN_DIR . '/includes/class-utils.php';
			require_once FMPRESS_FORMS_PLUGIN_DIR . '/includes/drivers/class-database.php';
			require_once FMPRESS_FORMS_PLUGIN_DIR . '/includes/drivers/class-fmdapi.php';
		} elseif ( did_action( 'fmpress_forms_pro_loaded' ) ) {
			// For FMPress Forms Pro.
			require_once FMPRESS_FORMS_PLUGIN_DIR . '/includes/class-update-form.php';
		}
	}

	/**
	 * Enqueue files
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function fmpress_enqueue_files() {
		// Loading admin CSS.
		add_action(
			'admin_menu',
			function () {
				wp_enqueue_style(
					'fmpress-connect-admin',
					plugins_url( '/admin/css/admin.css', __FILE__ ),
					array(),
					gmdate( 'U' )
				);
			}
		);

		// Loading admin JS (in header).
		add_action(
			'admin_enqueue_scripts',
			function () {
				if ( did_action( 'fmpress_forms_loaded' ) ) {
					wp_enqueue_script(
						'fmpress-connect-admin',
						plugins_url( '/admin/js/admin.min.js', __FILE__ ),
						array(),
						gmdate( 'U' ),
						false
					);
				}

				wp_enqueue_script(
					'module-fmpress-form-editor',
					plugins_url( '/admin/js/form-editor/form-editor.js', __FILE__ ),
					array( 'fmpress-connect-admin' ),
					gmdate( 'U' ),
					false
				);
				wp_localize_script(
					'fmpress-connect-admin',
					'localize',
					array(
						'fmpressAjaxNonce' => wp_create_nonce( 'fmpress_ajax_nonce' ),
					)
				);
			}
		);
	}

	/**
	 * Deactivate the plugin
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function fmpress_deactivate_plugin() {
		$this->unset_get_parameter( 'activate' );
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	/**
	 * Remove value from GET parameter
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $key parameter name of $_GET.
	 */
	private function unset_get_parameter( $key ) {
		//  phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_GET[ $key ] ) ) {
			unset( $_GET[ $key ] );
		}
		//  phpcs:enable
	}

	/**
	 * Show notice
	 * Can not using html tag in message
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $message .
	 */
	private static function fmpress_show_notice( $message ) {
		if ( current_action() === 'admin_notices' ) {
			echo '<div class="notice notice-warning is-dismissible"><p>',
				esc_html( $message ),
				'</p></div>', PHP_EOL;
		}
	}

	/**
	 * Admin notice for minimum PHP version
	 *
	 * @param  string $php_version .
	 * @return bool
	 */
	public function is_require_php_version( $php_version ) {
		if ( version_compare( PHP_VERSION, $php_version, '<' ) ) {
			$message = sprintf(
				/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
				__( '"%1$s" requires "%2$s" version %3$s or greater.', 'fmpress-forms' ),
				self::PLUGIN_NAME,
				'PHP',
				$php_version
			);

			self::fmpress_show_notice( $message );

			return false;
		}

		return true;
	}

	/**
	 * Admin notice for check for the existence of constants
	 *
	 * @return bool
	 */
	public function is_defined_encrypt_key() {
		$name = 'FMPRESS_CONNECT_ENCRYPT_KEY';
		if ( ! defined( $name ) ) {
			$value = wp_generate_password( 32, true, false );

			$message = sprintf(
				/* translators: 1: constant 2: value */
				__( 'Requires %1$s in "%2$s" file.', 'fmpress-forms' ),
				'define( \'' . $name . '\', \'' . $value . '\' );',
				'wp-config.php'
			);

			self::fmpress_show_notice( $message );

			return false;
		}

		return true;
	}

	/**
	 * Admin notice for check for the existence of constants
	 *
	 * @return bool
	 */
	public function is_defined_encrypt_iv() {
		$name = 'FMPRESS_CONNECT_ENCRYPT_IV';
		if ( ! defined( $name ) ) {
			$value = bin2hex(
				openssl_random_pseudo_bytes(
					openssl_cipher_iv_length( 'aes-256-gcm' )
				)
			);

			$message = sprintf(
				/* translators: 1: constant 2: value */
				__( 'Requires %1$s in "%2$s" file.', 'fmpress-forms' ),
				'define( \'' . $name . '\', \'' . $value . '\' );',
				'wp-config.php'
			);

			self::fmpress_show_notice( $message );

			return false;
		}

		return true;
	}

	/**
	 * Check if CF7 installed and activated
	 *
	 * @param  string $cf7_version .
	 * @return bool
	 */
	public function is_require_cf7_version( $cf7_version ) {
		if ( ! defined( 'WPCF7_VERSION' ) ||
			version_compare( WPCF7_VERSION, $cf7_version, '<' ) ) {
			$message = sprintf(
				/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
				__( '"%1$s" requires "%2$s" version %3$s or greater.', 'fmpress-forms' ),
				self::PLUGIN_NAME,
				'Contact Form 7',
				$cf7_version
			);

			self::fmpress_show_notice( $message );

			return false;
		}

		return true;
	}

	/**
	 * Exit if FMPress Forms Pro is activated
	 *
	 * @return bool
	 */
	public static function is_activated_fmpress_forms() {
		if ( did_action( 'fmpress_forms_loaded' ) && did_action( 'fmpress_forms_pro_loaded' ) ) {
			$message = sprintf(
				/* translators: 1: Plugin name 2: FMPress Pro */
				__( '"%1$s" and "%2$s" cannot be activated simultaneously.', 'fmpress-forms' ),
				'FMPress Forms',
				self::PLUGIN_NAME
			);

			self::fmpress_show_notice( $message );

			return false;
		}

		return true;
	}

	/**
	 * Check if FMPress Pro installed and activated
	 *
	 * @return bool
	 */
	public function is_not_activated_fmpress_core() {
		if ( did_action( 'fmpress_forms_pro_loaded' ) && ! did_action( 'fmpress_connect_loaded' ) ) {
			$message = sprintf(
				/* translators: 1: Plugin name 2: FMPress Pro */
				__( '"%1$s" requires "%2$s" to be installed and activated.', 'fmpress-forms' ),
				self::PLUGIN_NAME,
				'FMPress Pro'
			);

			self::fmpress_show_notice( $message );

			return false;
		}

		return true;
	}

	/**
	 * Exit if FMPress Pro is activated
	 *
	 * @return bool
	 */
	public static function is_activated_fmpress_core() {
		if ( did_action( 'fmpress_forms_loaded' ) && did_action( 'fmpress_connect_loaded' ) ) {
			$message = sprintf(
				/* translators: 1: Plugin name 2: FMPress Pro */
				__( '"%1$s" and "%2$s" cannot be activated simultaneously.', 'fmpress-forms' ),
				'FMPress Forms',
				'FMPress Pro'
			);

			self::fmpress_show_notice( $message );

			return false;
		}

		return true;
	}

	/**
	 * Handle JavaScript files as module
	 *
	 * @since 1.3.0
	 * @param string $tag .
	 * @param string $handle .
	 * @param string $source .
	 * @return string
	 */
	public function fmpress_load_js_as_module( $tag, $handle, $source ) {
		if ( 0 === strpos( $handle, 'module-' ) ) {
			// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
			$tag = '<script type="module" src="' . esc_url( $source ) . '"></script>' . PHP_EOL;
			// phpcs:enable
		}

		return $tag;
	}
}

do_action( 'fmpress_forms_loaded' );
new FMPress_Forms();
