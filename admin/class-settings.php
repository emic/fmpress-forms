<?php
/**
 * Add custom fields in post and page
 * Used to specify an external data source
 *
 * @since 1.0.0
 * @package WordPress
 */

namespace Emic\FMPress\Connect;

defined( 'ABSPATH' ) || die( 'Access denied.' );

/**
 * Settings.
 */
final class Settings {

	/**
	 * Name of custom post type
	 *
	 * @since 1.0.0
	 * @access private
	 * @var object $custompost_name
	 */
	private $custompost_name = 'connect_datasource';

	/**
	 * Class constructor
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_meta_boxes' ), 10 );
			add_action( 'save_post', array( $this, 'save_datasource' ) );
		}
	}

	/**
	 * Add meta box
	 */
	public function add_meta_boxes() {
		$id       = 'datasources';
		$title    = did_action( 'fmpress_connect_loaded' ) ? 'FMPress Pro' : 'FMPress';
		$callback = array( $this, 'add_custom_fields_for_settings' );

		// For post.
		$screen = 'post';
		add_meta_box( $id, $title, $callback, $screen );

		// For page.
		$screen = 'page';
		add_meta_box( $id, $title, $callback, $screen );
	}

	/**
	 * Add custom fields
	 */
	public function add_custom_fields_for_settings() {
		echo '<div id="fmpressConnectSettings" class="form-wrap categorydiv">';

		/**
		 * Tabs
		 */

		echo '<ul class="category-tabs">';

		$tabs = array(
			'datasources' => array(
				'name'         => 'datasources',
				'title'        => __( 'Datasources', 'fmpress-forms' ),
				'icon_classes' => 'dashicons dashicons-cloud',
				'selected'     => true,
				'callback'     => array( $this, 'add_tabpanel_datasource' ),
			),
		);

		$tabs = apply_filters( 'fmpress_connect_add_tabs', $tabs );

		foreach ( $tabs as $tab ) {
			$this->add_tab( $tab );
		}

		echo '</ul>';

		/**
		 * Tab panels
		 */

		foreach ( $tabs as $tab ) {
			if ( isset( $tab['callback'] ) && is_callable( $tab['callback'] ) ) {
				call_user_func( $tab['callback'] );
			}
		}

		echo '</div>'; // end #fmpressConnectSettings.
	}

	/**
	 * Save datasource
	 */
	public function save_datasource() {
		global $post;

		if ( ! isset( $post->ID ) ) {
			return;
		}

		$fields = array(
			'datasource_id' => array(
				'name' => FMPRESS_CONNECT_NAMEPREFIX . '_datasource_id',
				'type' => 'input',
			),
			'fm_layout'     => array(
				'name' => FMPRESS_CONNECT_NAMEPREFIX . '_fm_layout',
				'type' => 'input',
			),
			'fm_script'     => array(
				'name' => FMPRESS_CONNECT_NAMEPREFIX . '_fm_script',
				'type' => 'input',
			),
		);

		if ( did_action( 'fmpress_connect_loaded' ) ) {
			$field['_fm_to_of_member_table'] = array(
				'name' => FMPRESS_CONNECT_NAMEPREFIX . '_fm_to_of_member_table',
				'type' => 'input',
			);
		}

		$fields = apply_filters( 'fmpress_connect_save_fields', $fields );

		foreach ( $fields as $key => $field ) {
			if ( 'checkbox' === $field['type'] ) {
				self::save_post_meta_checkbox( $post->ID, $field['name'] );
			} else {
				self::save_post_meta( $post->ID, $field['name'] );
			}
		}
	}

	/**
	 * Save custom field
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
			if ( isset( $post->ID ) ) {
				delete_post_meta( $post->ID, $field_name );
			}
		}
	}

	/**
	 * Save custom field（for checkbox）
	 *
	 * @param int    $post_id .
	 * @param string $field_name .
	 */
	private function save_post_meta_checkbox( $post_id, $field_name ) {
		$check = wp_verify_nonce( $_POST['fmpress_forms_nonce'], 'fmpress_forms' );
		if ( false === $check ) {
			return;
		}

		if ( isset( $_POST[ $field_name ] ) ) {
			$sanitized = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );
			update_post_meta( $post_id, $sanitized, 1 );
		} else {
			update_post_meta( $post_id, $field_name, 0 );
		}
	}

	/**
	 * Create option elements
	 * Used to select data source by custom post
	 *
	 * @param string $saved_value .
	 * @return void
	 */
	private function generate_options_from_custom_post( $saved_value ) {
		$posts_array = $this->get_posts_array();
		foreach ( $posts_array as $key => $value ) {
			$selected = (int) $saved_value === $value->ID ? ' selected' : '';
			printf(
				'<option value="%1$s"%3$s>%2$s</option>',
				esc_attr( $value->ID ),
				esc_html( $value->post_title ),
				esc_html( $selected )
			);
		}
	}

	/**
	 * Add tab
	 *
	 * @param array $tab Tab array.
	 */
	public function add_tab( $tab ) {
		echo sprintf(
			'<li class="%s"><span class="%s" style="%s"></span><a href="%s">%s</a></li>',
			esc_attr( $tab['selected'] ? 'tabs' : 'hide-if-no-js' ),
			esc_attr( $tab['icon_classes'] ),
			'',
			esc_attr( '#fmpress-connect-' . strtolower( $tab['name'] ) ),
			esc_html( $tab['title'] )
		);
	}

	/**
	 * Add tabpanel (data source)
	 *
	 * @param string $value1 .
	 * @param string $value2 .
	 * @param string $value3 .
	 */
	public function add_tabpanel_datasource( $value1 = null, $value2 = null, $value3 = null ) {
		echo '<div id="fmpress-connect-datasources" class="tabs-panel">';

		echo '<table class="table fmpress-admin-table"><tbody>';

		wp_nonce_field( 'fmpress_forms', 'fmpress_forms_nonce' );

		// Datasource id.
		$field_name  = FMPRESS_CONNECT_NAMEPREFIX . '_datasource_id';
		$saved_value = $value1 ?? Utils::get_custom_field_value( $field_name );
		$this->add_datasource_id_field( $field_name, $saved_value );

		// Layout.
		$field_name  = FMPRESS_CONNECT_NAMEPREFIX . '_fm_layout';
		$saved_value = $value2 ?? Utils::get_custom_field_value( $field_name );
		$this->add_layout_name_field( $field_name, $saved_value );

		// Table occurrence name of Member table.
		if ( did_action( 'fmpress_connect_loaded' ) && false !== $value3 ) {
			$field_name  = FMPRESS_CONNECT_NAMEPREFIX . '_fm_to_of_member_table';
			$saved_value = $value3 ?? Utils::get_custom_field_value( $field_name );
			$this->add_to_name_field( $field_name, $saved_value );
		}

		echo '</tbody></table>';

		echo '</div>';
	}

	/**
	 * Add custom field to select data source
	 *
	 * @param string $field_name .
	 * @param string $saved_value .
	 */
	public function add_datasource_id_field( $field_name, $saved_value ) {
		printf(
			'<tr>' .
			'<th><label for="%1$s">%2$s</label></th>' .
			'<td><select id="%1$s" name="%4$s">' .
			'<option selected value="-1">%3$s</option>',
			esc_attr( 'dataSourceId' ),
			esc_html__( 'Datasource', 'fmpress-forms' ),
			esc_html__( 'Choose datasource', 'fmpress-forms' ),
			esc_attr( $field_name )
		);

		$this->generate_options_from_custom_post( $saved_value );

		echo '</select></td></tr>';
	}

	/**
	 * Add custom field to select layout
	 *
	 * @param string $field_name .
	 * @param string $saved_value .
	 */
	public function add_layout_name_field( $field_name, $saved_value ) {
		echo sprintf(
			'<tr>' .
			'<th><label for="%1$s">%2$s</label></th>' .
			'<td><input id="%1$s" type="%5$s" name="%3$s" value="%4$s"></td>' .
			'</tr>',
			esc_attr( 'fileMakerLayoutName' ),
			esc_html__( 'Layout', 'fmpress-forms' ),
			esc_attr( $field_name ),
			esc_attr( $saved_value ),
			esc_attr( 'text' )
		);
	}

	/**
	 * Add custom field (for FileMaker)
	 * Table occurrence name of member table
	 *
	 * @param string $field_name .
	 * @param string $saved_value .
	 */
	public function add_to_name_field( $field_name, $saved_value ) {
		printf(
			'<tr>' .
			'<th><label for="%1$s">%2$s</label></th>' .
			'<td><input id="%1$s" type="%5$s" name="%3$s" value="%4$s"></td>' .
			'</tr>',
			esc_attr( 'fileMakerTONameOfMemberTable' ),
			esc_html__( 'TO name of member table (Optional)', 'fmpress-forms' ),
			esc_attr( $field_name ),
			esc_attr( $saved_value ),
			esc_attr( 'text' )
		);
	}

	/**
	 * Get custom posts
	 *
	 * @return array
	 */
	public function get_posts_array() {
		$args = array(
			'posts_per_page' => 50,
			'offset'         => 0,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_type'      => $this->custompost_name,
			'post_status'    => 'publish',
		);

		return get_posts( $args );
	}
}
