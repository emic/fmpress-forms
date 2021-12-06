<?php
/**
 * Class Forms
 *
 * @since 1.0.0
 * @package WordPress
 */

namespace Emic\FMPress\Forms;

use Emic\FMPress\Connect as Core;

/**
 * Forms
 */
final class Forms {
	private const FM_FIELD_PREFIX = 'fm_field-';

	/**
	 * Results sent to the database server
	 *
	 * @since 1.0.0
	 * @access public
	 * @var null|object $result
	 */
	public $result = null;

	/**
	 * FMPress datasource info
	 *
	 * @since 1.0.0
	 * @access public
	 * @var object $datasource
	 */
	public $datasource = null;

	/**
	 * FMPress datasource id
	 *
	 * @since 1.0.0
	 * @access public
	 * @var object $datasource_id
	 */
	public $datasource_id = null;

	/**
	 * Value of primary key of login user
	 *
	 * @since 1.0.0
	 * @access public
	 * @var object $login_user_primary_key
	 */
	public $login_user_primary_key = null;

	/**
	 * Primary key field name of user table
	 *
	 * @since 1.0.0
	 * @access public
	 * @var object $login_user_primary_key_field
	 */
	public $login_user_primary_key_field = null;

	/**
	 * Record of external datasource
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var object $record
	 */
	public $record;

	/**
	 * Record id of external datasource
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var int $record_id
	 */
	public $record_id;

	/**
	 * Instance of Update_Form class
	 *
	 * @since 1.0.0
	 * @access public
	 * @var object $update_form
	 */
	public $update_form;

	/**
	 * WordPress user object
	 *
	 * @since 1.0.0
	 * @access public
	 * @var object $wp_user
	 */
	public $wp_user;

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			if ( did_action( 'fmpress_forms_pro_loaded' ) ) {
				$this->update_form = new Update_Form( $this );
			}

			add_action( 'the_post', array( $this, 'prepare_form' ), 9 );
			add_action( 'wpcf7_before_send_mail', array( $this, 'post' ), 10, 3 );
			add_action( 'wpcf7_skip_mail', array( $this, 'set_skip_mail' ), 10, 2 );
			add_filter( 'wpcf7_submission_result', array( $this, 'set_error' ), 10, 2 );
		}
	}

	/**
	 * Send post data to the database server
	 * Create record or Save record to datasource
	 *
	 * @since 1.0.0
	 * @access public
	 * @param WPCF7_ContactForm $contact_form .
	 * @param bool              $abort .
	 * @param WPCF7_Submission  $submission .
	 * @return WP_Error|void
	 */
	public function post( $contact_form, $abort, $submission ) {
		$create = true;
		$update = false;

		// Get posted form data.
		if ( $submission ) {
			$posted_data = $submission->get_posted_data();
		} else {
			return Utils::generate_wp_error(
				__( 'Could not get submission of Contact Form 7.', 'emic-fmpress-forms' ),
				'Forms'
			);
		}

		// Get CF7 settings.
		$cf7_settings = $contact_form->prop( FMPRESS_FORMS_CF7_SETTINGS_KEY );
		if ( '-1' === $cf7_settings['datasource_id'] ) {
			// Datasource is not configured.
			return;
		}

		// Get form mode of FMPress settings.
		$cf7_settings['form_mode'] = $cf7_settings['form_mode'] ?? '1';

		// Get Contact Form 7 Multi-Step Forms data.
		if ( isset( $posted_data['cf7msm_options'] ) ) {
			$cf7msm_options = json_decode( stripslashes( $posted_data['cf7msm_options'] ), true );
			if ( ! empty( $cf7msm_options['skip_save'] ) ) {
				return;
			}
		}

		// Generate post data.
		if ( did_action( 'fmpress_forms_pro_loaded' ) &&
			! empty( $cf7_settings['external_table_key_field'] ) &&
			'2' === $cf7_settings['form_mode']
		) {
			// When the external key has been set in settings.
			$update = true;

			// Get user id from external datasource.
			$primary_key = $this->update_form->get_user_primary_key_from_datasource( $posted_data );
			if ( is_wp_error( $primary_key ) ) {
				Core\Utils::show_error( $primary_key->get_error_message() );
			} else {
				$this->login_user_primary_key = $primary_key;
			}
		}

		// Generate external datasource.
		$this->datasource_id = intval( $cf7_settings['datasource_id'] );
		$this->datasource    = Core\Utils::get_datasource_info( $this->datasource_id );
		$fmdapi              = new Core\Fmdapi();
		$fmdapi->set_connection_info( $this->datasource, $cf7_settings['fm_layout'] );

		// Format data to be sent.
		$format_posted_data = $this->format_posted_data( $cf7_settings, $posted_data );

		// Post.
		if ( $update ) {
			// Update mode.
			if ( false === Core\Utils::is_member_page() ) {
				return Utils::generate_wp_error(
					__( 'This page is not a member page, so the update mode is not available.', 'emic-fmpress-forms' ),
					'Forms'
				);
			}
			$this->result = $this->update_form->update( $fmdapi, $format_posted_data );
		} elseif ( $create && '1' === $cf7_settings['form_mode'] ) {
			// Create mode.
			$this->result = $this->create( $fmdapi, $format_posted_data );
		}
	}

	/**
	 * Preparation the form
	 * Function executed in the action hook 'the_post'
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function prepare_form() {
		if ( did_action( 'fmpress_forms_pro_loaded' ) ) {
			// Add FileMaker Value List.
			$fm_value_list = new Fm_Value_List();
			add_filter( 'wpcf7_form_tag', array( $fm_value_list, 'generate_custom_select' ), 10, 1 );

			// Set up the form for updating.
			add_filter( 'wpcf7_form_tag', array( $this->update_form, 'set_up_form_for_update' ), 11, 1 );
		}
	}

	/**
	 * If an error occurs, do not send the email
	 *
	 * @since 1.0.0
	 * @access public
	 * @param bool              $skip_mail .
	 * @param WPCF7_ContactForm $contact_form .
	 * @return bool
	 */
	public function set_skip_mail( $skip_mail, $contact_form ) {
		if ( is_wp_error( $this->result ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Set error of send to the database server to CF7
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array            $result .
	 * @param WPCF7_Submission $submission .
	 * @return array
	 */
	public function set_error( $result, $submission ) {
		$contact_form = $submission->get_contact_form();
		$cf7_settings = $contact_form->prop( FMPRESS_FORMS_CF7_SETTINGS_KEY );

		if ( '-1' === $cf7_settings['datasource_id'] ) {
			// Datasource is not configured.
			return $result;
		}

		if ( is_wp_error( $this->result ) ) {
			if ( $this->is_timeout() ) {
				$result['status']  = 'invalid';
				$result['message'] = __(
					'Timeout has occurred. Please try again.',
					'emic-fmpress-forms'
				);
				session_destroy();
			} else {
				$message = __(
					'An error has occurred. Please contact the system administrator.',
					'emic-fmpress-forms'
				);

				$result['status']  = 'aborted';
				$result['message'] = $message . ' ' . $this->get_error_messages();
			}
		}

		return $result;
	}

	/**
	 * Return error messages from WP_Error
	 *
	 * @since 1.0.0
	 * @access public
	 * @param  string $result .
	 * @return string
	 */
	public function get_error_messages( $result = '' ) {
		$error_messages = $this->result->get_error_messages();
		foreach ( $error_messages as $key => $message ) {
			$result .= 0 === $key ? $message : ' ' . $message;
		}

		return $result;
	}

	/**
	 * Determine if timeout has occurred
	 *
	 * @since 1.0.0
	 * @access public
	 * @param  bool $result .
	 * @return bool
	 */
	public function is_timeout( $result = false ) {
		$error_codes = $this->result->get_error_codes();
		foreach ( $error_codes as $key => $error_code ) {
			if ( FMPRESS_CONNECT_NAMEPREFIX . '_fms: 952' === $error_code ) {
				return true;
			}
		}

		return $result;
	}

	/**
	 * Create record to database.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param object $fmdapi .
	 * @param array  $format_posted_data .
	 */
	private function create( $fmdapi, $format_posted_data ) {
		return $fmdapi->create( $format_posted_data );
	}

	/**
	 * Format posted data
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array $cf7_settings .
	 * @param array $posted_data .
	 * @return array
	 */
	private function format_posted_data( $cf7_settings, $posted_data ) {
		$fmdapi = new Core\Fmdapi();
		$fmdapi->set_connection_info( $this->datasource, $cf7_settings['fm_layout'] );
		$manager           = \WPCF7_FormTagsManager::get_instance();
		$scanned_form_tags = $manager->get_scanned_tags();

		// Generate post data.
		$result = array();
		foreach ( $scanned_form_tags as $key => $tag ) {
			if ( $this->is_fm_field( $tag ) ) {
				$field     = $cf7_settings['fields'][ $tag->name ];
				$group     = $this->get_group_of_field_type( $tag );
				$raw_value = $posted_data[ $tag->name ];

				if ( 'multiple' === $group || is_array( $raw_value ) ) {
					// Multiple values.
					$value = implode( "\r", $raw_value );
				} elseif ( 'date' === $group && Core\Utils::is_valid_date( $raw_value ) ) {
					// Fix date format.
					$value = Core\Utils::format_date_for_update( $raw_value );
				} else {
					// Fix double line feed code.
					$value = str_replace( array( "\r\n", "\n" ), "\r", $raw_value );
				}
				$result[ $field ] = $value;
			}
		}

		if ( did_action( 'fmpress_forms_pro_loaded' ) ) {
			if ( '1' === $cf7_settings['form_mode'] && ! empty( $cf7_settings['external_table_key_field'] ) ) {
				// Create mode.
				// Add primary field and value.
				$external_table_key_field            = $cf7_settings['external_table_key_field'];
				$result[ $external_table_key_field ] = $this->login_user_primary_key;
			} elseif ( '2' === $cf7_settings['form_mode'] ) {
				// Update mode.
				// Add record id for external datasource.
				// recordId (in FileMaker).
				$result['recordId'] = $posted_data['recordId'];
			}
		}

		return $result;
	}

	/**
	 * Determine if the tag name is specified as a field in FileMaker
	 *
	 * @since 1.0.0
	 * @access private
	 * @param object $tag CF7 tag.
	 */
	private function is_fm_field( $tag ) {
		$prefix = self::FM_FIELD_PREFIX;
		return mb_substr( $tag->name, 0, mb_strlen( $prefix ) ) === $prefix;
	}

	/**
	 * Remove prefix and return field name
	 *
	 * @since 1.0.0
	 * @access private
	 * @param object|array $tag CF7 tag.
	 * @param bool         $array true if the arg is an array.
	 */
	private function get_fm_field( $tag, $array = false ) {
		$name = $array ? $tag['name'] : $tag->name;
		return isset( $name ) ? mb_substr( $name, mb_strlen( self::FM_FIELD_PREFIX ) ) : null;
	}

	/**
	 * Determine the group of fields.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param object $tag CF7 tag.
	 * @return string
	 */
	private function get_group_of_field_type( $tag ) {
		switch ( $tag->basetype ) {
			case 'radio':
				$group = 'multiple';
				break;
			case 'checkbox':
				$group = 'multiple';
				break;
			case 'select':
				$group = 'multiple';
				break;
			default:
				$group = $tag->basetype;
				break;
		}
		return $group;
	}
}
