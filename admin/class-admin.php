<?php
/**
 * Adding FMPress settings panel to Contact Form 7 in Admin
 *
 * @since 1.0.0
 * @package WordPress
 */

namespace Emic\FMPress\Forms;

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

	/**
	 * Set property
	 *
	 * @param array             $properties .
	 * @param WPCF7_ContactForm $contact_form .
	 * @return array
	 */
	public function add_fmpress_property( $properties, $contact_form ) {
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

		// Section to specify the mode.
		self::form_mode_section( $cf7_settings );

		// Section for specifying database relationships.
		self::relationship_settings_section( $cf7_settings );

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
			'<label for="modeCreate">%4$s</label>',
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
				'To use the update mode, you need to use both FMPress Forms plug-in and FMPress Members plug-in.',
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
	 * Section to specify field assignments
	 *
	 * @param WPCF7_ContactForm $contact_form .
	 */
	private function assign_fields_section( $contact_form ) {
		echo '<hr style="margin: 1em 0 2em;">',
			'<h2>', esc_html__( 'Assign fields', 'fmpress-forms' ), '</h2>','<table class="table fmpress-admin-table"><tbody>';

		// Generate fields for field assignment.
		$mailtags = $contact_form->collect_mail_tags();
		$this->generate_input( $mailtags );

		echo '</tbody></table>';
	}

	/**
	 * Generate and display input tags for field assignment
	 *
	 * @param array $mailtags .
	 */
	private function generate_input( $mailtags ) {
		$cf7          = \WPCF7_ContactForm::get_current();
		$cf7_settings = $cf7->prop( FMPRESS_FORMS_CF7_SETTINGS_KEY );

		foreach ( $mailtags as $key => $mailtag ) {
			if ( self::FM_FIELD_PREFIX === substr( $mailtag, 0, 9 ) ) {
				// Generate and display input tag.
				echo '<tr>',
					'<th>' . esc_html( $mailtag ) . '</th>',
					'<td>';

				printf(
					'<input type="text" id="%1$s" name="%3$s[fields][%1$s]" value="%2$s">',
					esc_attr( $mailtag ),
					esc_attr( $cf7_settings['fields'][ $mailtag ] ),
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
		if ( 'save' !== $context ) {
			return;
		}

		// Get CF7 tags.
		$scanned_form_tags = $contact_form ? $contact_form->scan_form_tags( null ) : null;
		if ( is_null( $scanned_form_tags ) ) {
			return;
		}

		// Generate fields.
		$fm_fields = array();
		foreach ( $scanned_form_tags as $key => $tag ) {
			if ( $this->is_fm_field( $tag ) ) {
				$fm_fields[ $tag->name ] = $this->get_fm_field( $tag );
			}
		}

		// Add fields.
		if ( ! isset( $args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ] ) ) {
			$merged = $fm_fields;
		} else {
			$merged = array_merge( $fm_fields, $args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]['fields'] );
		}
		$args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]['fields'] = $merged;

		// Set properties.
		$properties = array();
		$args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]['datasource_id'] = $args['fmpress_connect_datasource_id'];
		$args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]['fm_layout']     = $args['fmpress_connect_fm_layout'];
		$properties[ FMPRESS_FORMS_CF7_SETTINGS_KEY ]            = $args[ FMPRESS_FORMS_CF7_SETTINGS_KEY ];
		$contact_form->set_properties( $properties );

		// Save.
		$contact_form->save();
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
	 * @param bool                $array true if the arg is an array.
	 * @return string|null
	 */
	private function get_fm_field( $tag, $array = false ) {
		$name = $array ? $tag['name'] : $tag->name;
		return isset( $name ) ? mb_substr( $name, mb_strlen( self::FM_FIELD_PREFIX ) ) : null;
	}
}
