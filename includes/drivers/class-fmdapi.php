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

use \WP_Http;

/**
 * Driver for FileMaker Data API
 */
final class Fmdapi extends Database {

	/**
	 * Generate base uri.
	 *
	 * @return string
	 */
	public function generate_base_uri() {
		if ( 'localhost' === $this->server ) {
			$uri = sprintf(
				'http://%2$s:3000/fmi/data/%3$s/databases/%4$s/',
				$this->protocol,
				$this->server,
				$this->api_version,
				$this->datasource
			);
		} else {
			$uri = sprintf(
				'%1$s://%2$s/fmi/data/%3$s/databases/%4$s/',
				$this->protocol,
				$this->server,
				$this->api_version,
				$this->datasource
			);
		}

		return $uri;
	}

	/**
	 * Get layouts from the database
	 *
	 * @return object|array
	 */
	public function get_layouts() {
		// Generate URI.
		$uri = $this->generate_base_uri() . 'layouts';

		// Send request.
		$request_params = array(
			'method'       => 'GET',
			'uri'          => $uri,
			'content_type' => null,
		);
		$response       = $this->request( $request_params, true );

		// Error handling.
		if ( is_wp_error( $response ) ) {
			// WordPress error.
			return $response;
		} elseif ( 200 !== $response['response']['code'] ) {
			// FileMaker Server error.
			return $this->generate_wp_error( $response );
		}

		// Generate result.
		$result = json_decode( $response['body'], true );

		return $result;
	}

	/**
	 * Get layout metadata
	 * Using in FMPress Forms
	 *
	 * @since 1.3.0
	 * @return object|array
	 */
	public function get_layout_meta() {
		// Generate URI.
		$uri = $this->generate_base_uri() . sprintf(
			'layouts/%s',
			$this->layout
		);

		// Send request.
		$request_params = array(
			'method'       => 'GET',
			'uri'          => $uri,
			'content_type' => null,
			'options'      => array(),
		);
		$response       = $this->request( $request_params, true );

		// Error handling.
		if ( is_wp_error( $response ) ) {
			// WordPress error.
			return $response;
		} elseif ( 200 !== $response['response']['code'] ) {
			// FileMaker Server error.
			return $this->generate_wp_error( $response );
		}

		// Generate result.
		$result = json_decode( $response['body'], true );

		return $result;
	}

	/**
	 * Send request to FileMaker Server
	 *
	 * @param array $request_params Request data.
	 * @param bool  $get_token .
	 * @return object
	 */
	public function request( $request_params, $get_token = false ) {
		// Detect driver.
		$driver_id = 1;
		$haystack  = wp_parse_url( $request_params['uri'], PHP_URL_HOST );
		$needle    = '.account.filemaker-cloud.com';
		$len       = strlen( $needle );
		if ( 0 === substr_compare( $haystack, $needle, -$len, $len ) ) {
			$driver_id = 2;
		}

		// Get and set token.
		$token = $this->get_token( $get_token, $driver_id );
		if ( is_wp_error( $token ) ) {
			return $token;
		} elseif ( false === $token ) {
			return Utils::generate_wp_error(
				__( 'Could not get token.', 'fmpress-forms' )
			);
		}

		// Generate parameters.
		$params = $this->generate_params_for_request( $request_params );

		// Send request.
		$wp_http  = new WP_Http();
		$response = $wp_http->request( $request_params['uri'], $params );

		// Error handling.
		if ( is_wp_error( $response ) ) {
			$this->display_wp_errors( $response );
		}

		return $response;
	}

	/**
	 * Generate parameters to request
	 *
	 * @param array $request_params Request data.
	 * @return object|array
	 */
	protected function generate_params_for_request( $request_params ) {
		$datasource = $this->datasource;
		if ( ! $datasource ) {
			// Could not get data source name.
			return Utils::generate_wp_error(
				__( 'Could not get datasource.', 'fmpress-forms' )
			);
		}
		$encoded      = rawurlencode( $datasource );
		$content_type = is_null( $request_params['content_type'] ) ?
			'application/json' :
			$request_params['content_type'];
		$options      = isset( $request_params['options'] ) ? $request_params['options'] : array();
		return array_merge(
			array(
				'method'  => $request_params['method'],
				'headers' => array(
					'Authorization' => sprintf( 'Bearer %s', $_SESSION['fmpress_connect'][ $encoded ]['token'] ),
					'Content-Type'  => $content_type,
				),
			),
			$options
		);
	}

	/**
	 * Get response code
	 *
	 * @param array $response Response.
	 * @return string|false
	 */
	protected function get_response_code( $response ) {
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_array = json_decode( $response['body'], true );
		if ( isset( $response_array['messages'][0]['code'] ) ) {
			return $response_array['messages'][0]['code'];
		}
		return false;
	}

	/**
	 * Get response data
	 *
	 * @param array $response Response data.
	 * @param array $result .
	 * @return null|array
	 */
	protected function get_response_data( $response, $result = array() ) {
		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_array = json_decode( $response['body'], true );

		if ( array_key_exists( 'data', $response_array['response'] ) ) {
			return $response_array['response']['data'];
		}
		return null;
	}

	/**
	 * Get response dataInfo
	 *
	 * @param array $response Response data.
	 * @param array $result .
	 * @return null|array
	 */
	protected function get_response_dataInfo( $response, $result = array() ) {
		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_array = json_decode( $response['body'], true );

		if ( array_key_exists( 'dataInfo', $response_array['response'] ) ) {
			return $response_array['response']['dataInfo'];
		}
		return null;
	}

}
