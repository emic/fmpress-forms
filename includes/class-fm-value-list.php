<?php
/**
 * Class FileMaker Value List
 *
 * @since 1.0.0
 * @package WordPress
 */

namespace Emic\FMPress\Forms;

defined( 'ABSPATH' ) || die( 'Access denied.' );
use Emic\FMPress\Connect as Core;

/**
 * Fm_Value_List
 *
 * @since 1.0.0
 */
final class Fm_Value_List {
	private const FM_VALUE_LIST_PREFIX = 'fm_value_list-';

	/**
	 * Record of external datasource
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array $layout_metadata
	 */
	protected $layout_metadata = array();

	/**
	 * Generate and return FileMaker Value List
	 *
	 * @since 1.0.0
	 * @param array $scanned_tag .
	 * @return array
	 */
	public function generate_custom_select( $scanned_tag ) {
		$data   = null;
		$prefix = 'data:' . self::FM_VALUE_LIST_PREFIX;

		// Generate and return FileMaker Value List.
		foreach ( $scanned_tag['options'] as $key => $options ) {
			if ( $this->is_fm_valuelist( $options ) ) {
				$valuelist_name        = str_replace( $prefix, '', $options );
				$data                  = $this->get_custom_valuelist( $valuelist_name );
				$scanned_tag['labels'] = $data['labels'];
				$scanned_tag['values'] = $data['values'];
				break;
			}
		}
		return $scanned_tag;
	}

	/**
	 * Get FileMaker Value List
	 *
	 * @since 1.0.0
	 * @param string $valuelist_name .
	 * @return array
	 */
	private function get_custom_valuelist( $valuelist_name ) {
		$error = array(
			'values' => array( 0 => '値一覧取得エラー' ),
			'labels' => array( 0 => '値一覧取得エラー' ),
		);

		if ( empty( $this->layout_metadata ) ) {
			// Get CF7 data.
			$cf7          = \WPCF7_ContactForm::get_current();
			$cf7_settings = $cf7->prop( FMPRESS_FORMS_CF7_SETTINGS_KEY );

			// Generate external datasource.
			$this->datasource_id = intval( $cf7_settings['datasource_id'] );
			$this->datasource    = Core\Utils::get_datasource_info( $this->datasource_id );
			$fmdapi              = new Core\Fmdapi();
			$fmdapi->set_connection_info( $this->datasource, $cf7_settings['fm_layout'] );

			// Get layout meta data.
			$this->layout_metadata = $fmdapi->get_layout_meta();
		}

		if (
			is_wp_error( $this->layout_metadata ) ||
			! isset( $this->layout_metadata['response']['valueLists'] )
		) {
			return $error;
		}

		foreach ( $this->layout_metadata['response']['valueLists'] as $key => $valuelist ) {
			if ( $valuelist_name === $valuelist['name'] ) {
				return $this->generate_custom_valuelist( $valuelist['values'] );
			}
		}

		return $error;
	}

	/**
	 * Generate Value List
	 *
	 * @since 1.0.0
	 * @param string $lists .
	 * @return array
	 */
	private function generate_custom_valuelist( $lists ) {
		$result = array( 'values', 'labels' );
		foreach ( $lists as $key => $list ) {
			$result['values'][] = esc_attr( $list['value'] );
			$label              = array_key_exists( 'displayValue', $list ) ? $list['displayValue'] : $list['value'];
			$result['labels'][] = esc_html( $label );
		}
		return $result;
	}

	/**
	 * Determine if tag options include FileMaker Value List
	 *
	 * @since 1.0.0
	 * @param string $options CF7 tag option.
	 * @return bool
	 */
	private function is_fm_valuelist( $options ) {
		$prefix = 'data:' . self::FM_VALUE_LIST_PREFIX;
		return mb_substr( $options, 0, mb_strlen( $prefix ) ) === $prefix;
	}
}
