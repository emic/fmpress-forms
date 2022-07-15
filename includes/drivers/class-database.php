<?php
/**
 * Class that communicates with FileMaker Data API
 *
 * @since 1.0.0
 * @package WordPress
 */

namespace Emic\FMPress\Connect;

defined( 'ABSPATH' ) || die( 'Access denied.' );
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-includes/sodium_compat/src/Core/Util.php';
require_once ABSPATH . 'wp-includes/sodium_compat/src/Compat.php';

use \WP_Http;
use \WP_Error;

/**
 * Driver for FileMaker Data API
 */
abstract class Database {

	/**
	 * Enable debug mode
	 *
	 * @since 1.0.0
	 * @access private
	 * @var bool $debug
	 */
	private $debug = false;

	/**
	 * FileMaker Data API version
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string $api_version
	 */
	public $api_version = 'vLatest';

	/**
	 * Protocol used
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string $protocol
	 */
	protected $protocol = 'https';

	/**
	 * Datasource name (Database name in FileMaker)
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string $datasource
	 */
	public $datasource = '';

	/**
	 * Server address
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array $server
	 */
	public $server = '';

	/**
	 * Database cache
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array $cache
	 */
	private $cache = array();

	/**
	 * FileMaker layout name
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string $layout
	 */
	public $layout = '';

	/**
	 * FileMaker record id
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string $recordid.
	 */
	public $record_id = '';

	/**
	 * Used to get token
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string $auth_strings.
	 */
	private $auth_strings = null;

	/**
	 * Generate base uri.
	 */
	public function generate_base_uri() {}

	/**
	 * Set connection info for datasource
	 *
	 * @since 1.0.0
	 * @param array  $datasource .
	 * @param string $layout .
	 */
	public function set_connection_info( $datasource, $layout ) {
		$this->datasource = $datasource['datasource']; // database name.
		$this->server     = $datasource['server'];
		$this->layout     = $layout;
		$this->set_auth_strings( $datasource['datasource_username'] . ':' . $datasource['datasource_password'] );
	}

	/**
	 * Set auth strings
	 *
	 * @since 1.0.0
	 * @param string $auth_strings .
	 */
	public function set_auth_strings( $auth_strings ) {
		$this->auth_strings = $auth_strings;
	}

	/**
	 * Get auth strings
	 *
	 * @since 1.0.0
	 * @return void|string
	 */
	public function get_auth_strings() {
		if ( ! is_null( $this->auth_strings ) ) {
			return;
		}

		$auth_strings = $this->get_datasource_auth_strings();
		if ( is_wp_error( $auth_strings ) ) {
			return $auth_strings;
		}
		$this->auth_strings = $auth_strings;
	}

	/**
	 * Get token from FileMaker Server
	 *
	 * @since 1.0.0
	 * @param bool $force_update .
	 * @param int  $driver_id Driver id.
	 * @return object|void|bool
	 */
	public function get_token( $force_update = false, $driver_id = 1 ) {
		$datasource = $this->datasource;
		if ( ! $datasource ) {
			// Could not get datasource name.
			return Utils::generate_wp_error(
				__( 'Could not get datasource.', 'fmpress-forms' )
			);
		}
		$encoded = rawurlencode( $datasource );

		// Use token from session.
		if ( ! $force_update && isset( $_SESSION['fmpress_connect'][ $encoded ]['token'] ) &&
			! empty( $_SESSION['fmpress_connect'][ $encoded ]['token'] )
		) {
			return;
		}

		// Get auth strings.
		$get_auth_strings = $this->get_auth_strings();
		if ( is_wp_error( $get_auth_strings ) ) {
			return $get_auth_strings;
		}

		// Generate parameters.
		$method = 'POST';
		$uri    = $this->generate_base_uri() . 'sessions';

		$authorization_header = '';
		if ( 1 === (int) $driver_id ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			$authorization_header = 'Basic ' . base64_encode( $this->auth_strings );
		} elseif ( 2 === (int) $driver_id ) {
			$auth_elements        = explode( ':', $this->auth_strings );
			$refresh_token        = isset( $auth_elements[1] ) ? $auth_elements[1] : '';
			$token                = apply_filters( 'fmpress_forms_cf7_get_token_for_cloud', $refresh_token );
			$authorization_header = 'FMID ' . $token;
		}

		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => $authorization_header,
		);

		// Send request.
		$wp_http  = new WP_Http();
		$response = $wp_http->request(
			$uri,
			array(
				'method'  => $method,
				'headers' => $headers,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Generate result.
		if ( 200 === $response['response']['code'] ) {
			$response_body = json_decode( $response['body'], false );
			// Set token to session.
			$_SESSION['fmpress_connect'][ $encoded ]['token'] = $response_body->response->token;
			return true;
		} else {
			return $this->generate_wp_error( $response );
		}
	}

	/**
	 * Create record.
	 *
	 * @since 1.0.0
	 * @param array $field_data Layout name.
	 * @param array $script .
	 * @return object|array
	 */
	public function create( $field_data, $script = null ) {
		// Add FileMaker native script.
		$script_params = $this->set_filemaker_script( $script );

		// Generate parameters.
		$uri  = $this->generate_base_uri() . sprintf(
			'layouts/%s/records',
			$this->layout
		);
		$body = wp_json_encode(
			array_merge( array( 'fieldData' => $field_data ), $script_params )
		);

		// Send request.
		$request_params = array(
			'method'       => 'POST',
			'uri'          => $uri,
			'options'      => array( 'body' => $body ),
			'content_type' => null,
		);
		$response       = $this->request( $request_params );

		// Error handling.
		if ( is_wp_error( $response ) ) {
			// WordPress error.
			return $response;
		} elseif ( 200 !== $response['response']['code'] ) {
			// FileMaker error.
			return $this->generate_wp_error( $response );
		}

		// Generate result.
		$result = $this->generate_result( $response );

		return $result;
	}

	/**
	 * Upload file to container.
	 *
	 * @since 1.1.0
	 * @param string $layout Layout name.
	 * @param string $record_id  Record id.
	 * @param string $key        Input element name.
	 * @param string $field_name Field name.
	 * @param string $file_path  File path.
	 * @param int    $repetition Field repetition.
	 * @return void|object|array
	 */
	public function upload( $layout, $record_id, $key, $field_name, $file_path, $repetition = 1 ) {
		// Get parameters of upload file.
		if ( isset( $_FILES[ $key ]['name'] ) && isset( $_FILES[ $key ]['tmp_name'] ) ) {
			$file_name = sanitize_file_name( wp_unslash( $_FILES[ $key ]['name'] ) );
		} else {
			return;
		}

		// Generate uri.
		$uri = $this->generate_base_uri() . sprintf(
			'layouts/%s/records/%s/containers/%s/%s/',
			$layout,
			$record_id,
			$field_name,
			$repetition
		);

		// Get contents from file.
		$contents = $this->get_contents( $file_path );
		if ( false === $contents ) {
			return Utils::generate_wp_error(
				__( 'An error occurred while uploading a file.', 'fmpress-forms' )
			);
		}

		$boundary = 'FMPress_Connect_UploadFile-' . uniqid();
		$lb       = chr( 13 ) . chr( 10 );
		$body     = sprintf(
			'--%1$s%4$sContent-Disposition: form-data; name="upload"; filename="%2$s"%4$s%4$s%3$s%4$s%4$s--%1$s--%4$s',
			$boundary,
			esc_attr( trim( preg_replace( '/\r|\n/', '', $file_name ) ) ),
			$contents,
			$lb
		);

		// Send request.
		$request_params = array(
			'method'       => 'POST',
			'uri'          => $uri,
			'options'      => array( 'body' => $body ),
			'content_type' => 'multipart/form-data; boundary=' . $boundary,
		);
		$response       = $this->request( $request_params );

		// Error handling.
		if ( is_wp_error( $response ) ) {
			// WordPress error.
			return $response;
		} elseif ( 200 !== $response['response']['code'] ) {
			// FileMaker error.
			return $this->generate_wp_error( $response );
		}

		// Generate result.
		$result = $this->generate_result( $response );

		return $result;
	}

	/**
	 * Display Errors of WordPress
	 *
	 * @since 1.0.0
	 * @param string|object $response Response.
	 */
	public function display_wp_errors( $response ) {
		if ( isset( $response->errors ) ) {
			$key = key( $response->errors );
			echo '<p>' . esc_html( $key ) . '<br>';
			echo esc_html( $response->errors[ $key ][0] );
			echo '</p>';
		} else {
			echo '<p>' . esc_html( $response ) . '</p>';
		}

	}

	/**
	 * Generate result data
	 *
	 * @since 1.0.0
	 * @param bool|object $response Response.
	 * @return array
	 */
	protected function generate_result( $response ) {
		$result = array();
		if ( false !== $response ) {
			// Records may not exist.
			// For example, update.
			$result = array(
				'response_code' => $this->get_response_code( $response ),
				'records'       => $this->get_response_data( $response ),
				'dataInfo'      => $this->get_response_dataInfo( $response ),
				'recordId'      => $this->get_record_id( $response ),
			);
		}
		return $result;
	}

	/**
	 * Get record id from response
	 *
	 * @since 1.0.0
	 * @param array $response Response.
	 * @return string|null
	 */
	private function get_record_id( $response ) {
		$response_array = json_decode( $response['body'], true );
		if ( isset( $response_array['response']['recordId'] ) ) {
			return $response_array['response']['recordId'];
		}
		return null;
	}

	/**
	 * Generate key for cache array
	 *
	 * @since 1.0.0
	 * @param array $query Database query.
	 * @return string|false
	 */
	private function generate_cache_key( $query = array() ) {
		$query['__layout_name__'] = $this->layout;
		return wp_json_encode( $query );
	}

	/**
	 * Read contents from file
	 *
	 * @since 1.0.0
	 * @param string $file_path Filepath.
	 * @return string|false
	 */
	private function get_contents( $file_path ) {
		if ( file_exists( $file_path ) ) {
			if ( WP_Filesystem() ) {
				global $wp_filesystem;
				return $wp_filesystem->get_contents( $file_path );
			}
		}
		return false;
	}

	/**
	 * Get auth info strings
	 *
	 * @since 1.0.0
	 * @return object|string
	 */
	private function get_datasource_auth_strings() {
		$auth = $this->get_datasource_auth_info();
		if ( is_wp_error( $auth ) ) {
			return $auth;
		}
		if ( ! is_null( $auth ) ) {
			$auth_info = $auth['username'] . ':' . $auth['password'];
			return $auth_info;
		}
	}

	/**
	 * Get auth info from custom post
	 *
	 * @since 1.0.0
	 * @return object|array
	 */
	private function get_datasource_auth_info() {
		// Get post_id.
		// phpcs:disable WordPress.Security.NonceVerification
		$post_id = is_admin() ?
			sanitize_text_field( wp_unslash( $_POST['wp_post_id'] ) ) :
			Utils::get_datasource_post_id();
		// phpcs:enable

		if ( ! $post_id ) {
			return Utils::generate_wp_error(
				__( 'Could not get datasource( custom post ).', 'fmpress-forms' )
			);
		}

		// Get username.
		$name     = 'fmpress_connect_datasource_username';
		$username = get_post_meta(
			(int) $post_id,
			$name,
			true
		);

		// Get password.
		$password = Utils::get_datasource_password( $post_id );

		return array(
			'username' => $username,
			'password' => $password,
		);
	}

	/**
	 * Set FileMaker native script to request.
	 * For `HTTP POST` or `HTTP PATCH`
	 *
	 * @since 1.2.0
	 * @param array $script .
	 * @return array
	 */
	protected function set_filemaker_script( $script ) {
		$script_params = array();

		if ( is_null( $script ) ) {
			return $script_params;
		}
		if ( ! 'array' === gettype( $script ) ) {
			return $script_params;
		}

		$params = array(
			'script',
			'script.param',
			'script.prerequest',
			'script.prerequest.param',
			'script.presort',
			'script.presort.param',
		);

		foreach ( $params as $key => $param ) {
			if ( array_key_exists( $param, $script ) && '' !== $script[ $param ] ) {
				$script_params[ $param ] = $script[ $param ];
			}
		}

		return $script_params;
	}

	/**
	 * Generate WP_Error object
	 *
	 * @since 1.0.0
	 * @param array $response .
	 * @return object
	 */
	protected function generate_wp_error( $response ) {
		$errors = new WP_Error();

		// Add HTTP error.
		if ( isset( $response['response']['code'] ) ) {
			$errors->add(
				FMPRESS_CONNECT_NAMEPREFIX . '_http: ' . $response['response']['code'],
				'HTTP: ' .
				$this->concat_error_message(
					$response['response']['message'],
					$response['response']['code']
				)
			);
		}

		// Add FileMaker error.
		if ( isset( $response['http_response'] ) ) {
			$response_object = $response['http_response']->get_response_object();
			$response_body   = json_decode(
				$response['http_response']->get_data(),
				false
			);

			if ( ! is_null( $response_body ) ) {
				$code = (int) $response_body->messages[0]->code;

				if ( $code > 0 ) {
					$errors->add(
						FMPRESS_CONNECT_NAMEPREFIX . '_fms: ' . $response_body->messages[0]->code,
						'FileMaker Server: ' .
						$this->concat_error_message(
							$response_body->messages[0]->message,
							$response_body->messages[0]->code
						)
					);
				}
			}
		}

		return $errors;
	}

	/**
	 * Concat error message and error code
	 *
	 * @since 1.0.0
	 * @param string $message .
	 * @param string $code .
	 * @return string
	 */
	private function concat_error_message( $message, $code ) {
		return $message . ' (' . $code . ')';
	}
}
