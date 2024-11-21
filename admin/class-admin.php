<?php
/**
 * Adding FMPress settings panel to Contact Form 7 in Admin
 *
 * @since 1.0.0
 * @package WordPress
 */

namespace Emic\FMPress\Forms;

defined( 'ABSPATH' ) || die( 'Access denied.' );
use Emic\FMPress\Connect as Core;

/**
 * Admin.
 */
final class Admin {
	public const FM_FIELD_PREFIX = 'fm_field-';

	/**
	 * Class constructor
	 */
	public function __construct() {
		if ( defined( 'WPCF7_VERSION' ) ) {
			if ( version_compare( WPCF7_VERSION, '5.5.3', '>=' ) ) {
				add_filter( 'wpcf7_pre_construct_contact_form_properties', array( $this, 'add_fmpress_property' ), 10, 2 );
			} else {
				add_filter( 'wpcf7_contact_form_properties', array( $this, 'add_fmpress_property' ), 10, 2 );
			}

			if ( is_admin() ) {
				add_filter( 'wpcf7_editor_panels', array( $this, 'add_fmpress_tab' ) );
				add_action( 'wpcf7_save_contact_form', array( $this, 'save_fmpress_settings' ), 10, 3 );
			}
		}
	}

	/**
	 * Set property
	 *
	 * @param array $properties .
	 * @return array
	 */
	public function add_fmpress_property( $properties ) {
		$properties = wp_parse_args(
			$properties,
			array(
				FMPRESS_FORMS_CF7_SETTINGS_KEY => array(),
			)
		);
		return $properties;
	}

	/**
	 * Added FMPress configuration panel to CF7 tab
	 *
	 * @param array $panels .
	 * @return array
	 */
	public function add_fmpress_tab( $panels ) {
		$panels['form-fmpress-panel'] = array(
			'title'    => 'FMPress',
			'callback' => array( $this, 'generate_fmpress_settings' ),
		);
		return $panels;
	}

	/**
	 * Generate configuration section for FMPress
	 *
	 * @param WPCF7_ContactForm $contact_form .
	 */
	public function generate_fmpress_settings( $contact_form ) {
		// Get CF7 form.
		$cf7          = \WPCF7_ContactForm::get_current();
		$cf7_settings = $cf7->prop( FMPRESS_FORMS_CF7_SETTINGS_KEY );

		// Initialize FMPress settings.
		$cf7_settings['form_mode']     = $cf7_settings['form_mode'] ?? '1';
		$cf7_settings['datasource_id'] = $cf7_settings['datasource_id'] ?? '';
		$cf7_settings['fm_layout']     = $cf7_settings['fm_layout'] ?? '';
		$cf7_settings['fm_script']     = $cf7_settings['fm_script'] ?? '';

		// Section to specify the mode.
		self::form_mode_section( $cf7_settings );

		// Section for specifying database relationships.
		self::relationship_settings_section( $cf7_settings );

		// Section for specifying a script to perform.
		self::script_settings_section( $cf7_settings );

		// Section to specify field assignments.
		self::assign_fields_section( $contact_form );
	}

	/**
	 * Add datasource id field.
	 *
	 * @param string $field_name .
	 * @param string $value .
	 */
	private function add_form_mode_field( $field_name, $value ) {
		if ( ! did_action( 'fmpress_forms_pro_loaded' ) ) {
			return;
		}

		echo '<h2>', esc_html__( 'Form mode', 'fmpress-forms' ), '</h2>',
			'<div class="form-field">';

		$checked = '1' === $value ? ' checked' : '';

		printf(
			'<input type="radio" id="modeCreate" name="%3$s" value="%1$s"%5$s>' .
			'<label for="modeCreate" style="padding-right: 0.5em;">%4$s</label>',
			esc_attr( 1 ),
			esc_html__( 'Mode', 'fmpress-forms' ),
			esc_attr( $field_name ),
			esc_html__( 'Create', 'fmpress-forms' ),
			esc_html( $checked )
		);

		$checked = '2' === $value ? ' checked' : '';

		printf(
			'<input type="radio" id="modeUpdate" name="%3$s" value="%1$s"%5$s>' .
			'<label for="modeUpdate">%4$s</label>',
			esc_attr( 2 ),
			esc_html__( 'Mode', 'fmpress-forms' ),
			esc_attr( $field_name ),
			esc_html__( 'Update', 'fmpress-forms' ),
			esc_html( $checked )
		);

		echo '<p><small>',
			esc_html__(
				'To use the update mode, you need to use both FMPress Forms Pro plug-in and FMPress Members Pro plug-in.',
				'fmpress-forms'
			),
			'</small></p></div>',
			'<hr style="margin: 1em 0 2em;">';
	}

	/**
	 * Section to specify the mode
	 *
	 * @param array $cf7_settings .
	 */
	private function form_mode_section( $cf7_settings ) {
		$field_name = FMPRESS_FORMS_CF7_SETTINGS_KEY . '[form_mode]';

		$this->add_form_mode_field( $field_name, $cf7_settings['form_mode'] );

		echo '<h2>', esc_html__( 'Datasource', 'fmpress-forms' ), '</h2>';

		// Generating fields for setting data source and layout.
		$fmpress_core_admin =
			did_action( 'fmpress_connect_loaded' ) ?
			new Core\Admin() :
			new Core\Settings();

		$fmpress_core_admin->add_tabpanel_datasource(
			$cf7_settings['datasource_id'],
			$cf7_settings['fm_layout'],
			false
		);
	}

	/**
	 * Section for specifying database relationships
	 *
	 * @param array $cf7_settings .
	 */
	private function relationship_settings_section( $cf7_settings ) {
		if ( ! did_action( 'fmpress_forms_pro_loaded' ) ) {
			return;
		}

		echo '<hr style="margin: 1em 0 2em;">',
			'<h2>', esc_html__( 'Relationship', 'fmpress-forms' ), '</h2>',
			'<table class="table fmpress-admin-table"><tbody>';

		// Create a field to set a foreign key.
		$name  = 'external_table_key_field';
		$label = __( 'foreign key field', 'fmpress-forms' );

		$cf7_settings[ $name ] = $cf7_settings[ $name ] ?? '';

		echo '<tr><th>',
			esc_html( $label ),
			'</th>';

		printf(
			'<td><input type="text" id="%1$s" name="%3$s[%1$s]" value="%2$s"></td>',
			esc_attr( $name ),
			esc_attr( $cf7_settings[ $name ] ),
			esc_attr( FMPRESS_FORMS_CF7_SETTINGS_KEY )
		);

		echo '</tr>',
			'</tbody></table>';
	}

	/**
	 * Section for specifying a script to perform
	 *
	 * @param array $cf7_settings .
	 */
	private function script_settings_section( $cf7_settings ) {
		echo '<hr style="margin: 1em 0 2em;">',
			'<h2>', esc_html__( 'Script', 'fmpress-forms' ), '</h2>',
			'<table class="table fmpress-admin-table"><tbody>';

		// Create a field to set a script name.
		$name  = 'fm_script';
		$label = __( 'Script', 'fmpress-forms' );

		$cf7_settings[ $name ] = $cf7_settings[ $name ] ?? '';

		echo '<tr><th>',
			esc_html( $label ),
			'</th>';

		printf(
			'<td><input type="text" id="%1$s" name="%3$s" value="%2$s"></td>',
			'fileMakerScriptName',
			esc_attr( $cf7_settings[ $name ] ),
			esc_attr( FMPRESS_CONNECT_NAMEPREFIX ) . '_fm_script'
		);

		echo '</tr>',
			'</tbody></table>';
	}

	/**
	 * Section to specify field assignments
	 *
	 * @param WPCF7_ContactForm $contact_form .
	 */
	private function assign_fields_section( $contact_form ) {
		echo '<hr style="margin: 1em 0 2em;">',
			'<h2>', esc_html__( 'Assign fields', 'fmpress-forms' ), '</h2>', '<table class="table fmpress-admin-table"><tbody>',
			'<p><small>', esc_html__( 'To specify fields, you must add fm_field- as a prefix to the beginning of the form-tag name in the Form tab panel. (e.g. fm_field-company_name)', 'fmpress-forms' ), '</small></p>';

		// Generate fields for field assignment.
		$this->generate_input( FMPress_Forms::CF7_SPECAIL_MAIL_TAGS, true );
		$mailtags = $contact_form->collect_mail_tags();
		$this->generate_input( $mailtags, false );

		echo '</tbody></table>';
	}

	/**
	 * Generate and display input tags for field assignment
	 *
	 * @param array $mailtags .
	 * @param bool  $special .
	 */
	private function generate_input( $mailtags, $special = false ) {
		$cf7          = \WPCF7_ContactForm::get_current();
		$cf7_settings = $cf7->prop( FMPRESS_FORMS_CF7_SETTINGS_KEY );

		foreach ( $mailtags as $key => $mailtag ) {
			if ( self::FM_FIELD_PREFIX === substr( $mailtag, 0, 9 ) || $special ) {
				// Generate and display input tag.
				echo '<tr>',
					'<th>' . esc_html( $mailtag ) . '</th>',
					'<td>';

				printf(
					'<input type="text" id="%1$s" name="%3$s[fields][%1$s]" value="%2$s">',
					esc_attr( $mailtag ),
					esc_attr( $cf7_settings['fields'][ $mailtag ] ?? '' ),
					esc_attr( FMPRESS_FORMS_CF7_SETTINGS_KEY )
				);

				echo '</td>',
					'</tr>';
			}
		}
	}

	/**
	 * Save settings
	 *
	 * @param WPCF7_ContactForm $contact_form .
	 * @param array             $args .
	 * @param string            $context .
	 */
	public function save_fmpress_settings( $contact_form, $args, $context ) {
		if ( 'save' !== $context || ! $this->validate_cf7_args( $args ) ) {
			return;
		}

		// Get CF7 tags.
		$scanned_form_tags = $contact_form ? $contact_form->scan_form_tags( null ) : null;
		if ( is_null( $scanned_form_tags ) ) {
			return;
		}

		// Generate an array for the Assign fields on the FMPress tab.
		$args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]['fields'] = $this->generate_array_for_assign_fields(
			$args,
			$scanned_form_tags
		);

		// Set properties.
		$properties = array();
		$args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]['datasource_id'] = $args['fmpress_connect_datasource_id'];
		$args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]['fm_layout']     = $args['fmpress_connect_fm_layout'];
		$args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]['fm_script']     = $args['fmpress_connect_fm_script'];
		$properties[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]            = $args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ];
		$contact_form->set_properties( $properties );

		// Save.
		$contact_form->save();
	}

	/**
	 * Generate an array for the Assign fields on the FMPress tab.
	 *
	 * @since 1.3.0
	 * @param array $args .
	 * @param array $scanned_form_tags .
	 * @return array
	 */
	private function generate_array_for_assign_fields( $args, $scanned_form_tags ) {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_POST['fmpress-form-editor-form-structure'] ) ) {
			// phpcs:enable
			$fm_fields = $this->get_submitted_data_from_fmpress_form_editor();
			return array_merge( $args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]['fields'], $fm_fields );
		}

		$auto_generate_fields = $this->auto_generate_fields( $scanned_form_tags );
		return array_merge( $auto_generate_fields, $args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]['fields'] );
	}

	/**
	 * Create array for Assign fields
	 * Based on CF7 form-tag name.
	 *
	 * @since 1.3.0
	 * @param array $scanned_form_tags .
	 * @param array $fm_fields .
	 * @return array
	 */
	private function auto_generate_fields( $scanned_form_tags, $fm_fields = array() ) {
		foreach ( $scanned_form_tags as $key => $tag ) {
			if ( $this->is_fm_field( $tag ) ) {
				$fm_fields[ $tag->name ] = $this->get_fm_field( $tag );
			}
		}

		return $fm_fields;
	}

	/**
	 * Create array for Assign fields
	 * Based on data submitted from FMPress Form Editor.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	private function get_submitted_data_from_fmpress_form_editor() {
		// phpcs:disable WordPress.Security.NonceVerification
		$replaced = str_replace( '\\"', '"', $_POST['fmpress-form-editor-form-structure'] );
		// phpcs:enable
		return json_decode( $replaced, true );
	}

	/**
	 * Validate args
	 *
	 * @since 1.3.0
	 * @param array $args .
	 * @return bool
	 */
	private function validate_cf7_args( $args ) {
		if ( ! isset( $args['fmpress_connect_datasource_id'] ) ) {
			return false;
		} elseif ( ! isset( $args['fmpress_connect_fm_layout'] ) ) {
			return false;
		} elseif ( ! isset( $args['fmpress_connect_fm_script'] ) ) {
			return false;
		} elseif ( ! isset( $args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine if the tag name is specified as a field in FileMaker
	 *
	 * @param WPCF7_FormTag $tag .
	 * @return bool
	 */
	private function is_fm_field( $tag ) {
		$prefix = self::FM_FIELD_PREFIX;
		return mb_substr( $tag->name, 0, mb_strlen( $prefix ) ) === $prefix;
	}

	/**
	 * Remove prefix and return field name
	 *
	 * @param WPCF7_FormTag|array $tag CF7 tag.
	 * @return string|null
	 */
	private function get_fm_field( $tag ) {
		$name = is_array( $tag ) ? $tag['name'] : $tag->name;
		return isset( $name ) ? mb_substr( $name, mb_strlen( self::FM_FIELD_PREFIX ) ) : null;
	}
}
