<?php
/**
 * Utilities
 *
 * @since 1.0.0
 * @package WordPress
 */

namespace Emic\FMPress\Connect;

defined( 'ABSPATH' ) || die( 'Access denied.' );
require_once ABSPATH . 'wp-includes/sodium_compat/src/Core/Util.php';
require_once ABSPATH . 'wp-includes/sodium_compat/src/Compat.php';

use \WP_Error;

/**
 * Utils
 */
final class Utils {
	/**
	 * Validate date format
	 *
	 * @param string $content Date text.
	 * @return bool
	 */
	public static function is_valid_date( $content ) {
		$valid = preg_match( '/^\d{4}-\d{2}-\d{2}$/u', $content );
		if ( 1 === $valid ) {
			return true;
		}
		return false;
	}

	/**
	 * Formatting Date text for updating record
	 *
	 * @param string $content Date text.
	 * @return void|string
	 */
	public static function format_date_for_update( $content ) {
		if ( empty( $content ) ) {
			return;
		}
		if ( strpos( $content, '-' ) >= 0 ) {
			$array = explode( '-', $content );
			return $array[1] . '/' . $array[2] . '/' . $array[0];
		}
		return '';
	}

	/**
	 * Generate WP_Error object
	 *
	 * @param string $message .
	 * @param string $code .
	 * @param string $name .
	 * @return object
	 */
	public static function generate_wp_error( $message, $code = null, $name = null ) {
		$_code  = $code ?? 'core';
		$_name  = $name ?? 'FMPress';
		$errors = new WP_Error();
		$errors->add(
			FMPRESS_CONNECT_NAMEPREFIX . '_' . $_code,
			$_name . ': ' . $message
		);
		return $errors;
	}

	/**
	 * Get value of custom field
	 *
	 * @param string $name Custom field name.
	 * @param bool   $single Is single line text.
	 * @param int    $post_id Post id.
	 * @return string
	 */
	public static function get_custom_field_value( $name, $single = true, $post_id = null ) {
		if ( is_null( $post_id ) ) {
			global $post;
			if ( is_null( $post ) ) {
				return '';
			}
			$post_id = $post->ID;
		}

		$value = get_post_meta(
			$post_id,
			$name,
			$single
		);
		return $value;
	}

	/**
	 * Get all data sources
	 *
	 * @param  int $post_id Post id of datasource.
	 * @return array
	 */
	public static function get_datasource_info( $post_id ) {
		$labels = array(
			'driver',
			'server',
			'datasource',
			'datasource_username',
			'datasource_password',
		);

		$datasource_info = array();
		foreach ( $labels as $label ) {
			$datasource_info[ $label ] = get_post_meta(
				$post_id,
				FMPRESS_CONNECT_NAMEPREFIX . '_' . $label,
				true
			);
		}

		$datasource_info['datasource_password'] = self::get_datasource_password(
			$post_id,
			$datasource_info['datasource_password']
		);

		return $datasource_info;
	}

	/**
	 * Get password of data source
	 *
	 * @param  int    $post_id .
	 * @param  string $ciphertext .
	 * @return string
	 */
	public static function get_datasource_password( $post_id = null, $ciphertext = null ) {
		// Get post id of custom posts.
		$post_id = is_null( $post_id ) ? self::get_datasource_post_id() : $post_id;

		if ( is_null( $ciphertext ) ) {
			$name       = 'fmpress_connect_datasource_password';
			$ciphertext = get_post_meta(
				$post_id,
				$name,
				true
			);
		}

		if ( ! empty( $ciphertext ) ) {
			return \ParagonIE_Sodium_Compat::crypto_aead_aes256gcm_decrypt(
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
				base64_decode( $ciphertext ),
				'',
				hex2bin( FMPRESS_CONNECT_ENCRYPT_IV ),
				FMPRESS_CONNECT_ENCRYPT_KEY
			);
		}
		return '';
	}

	/**
	 * Get post id of custom posts
	 *
	 * @return string
	 */
	public static function get_datasource_post_id() {
		global $post;
		if ( ! isset( $post->ID ) ) {
			return;
		}
		return get_post_meta(
			$post->ID,
			'fmpress_connect_datasource_id',
			true
		);
	}

	/**
	 * Returns whether the page is a member page
	 *
	 * @return false
	 */
	public static function is_member_page() {
		return false;
	}
}
