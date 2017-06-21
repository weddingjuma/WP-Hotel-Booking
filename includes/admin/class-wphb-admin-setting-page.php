<?php

/**
 * Abstract WP Hotel Booking admin setting class.
 *
 * @class       WPHB_Admin_Setting_Page
 * @version     2.0
 * @package     WP_Hotel_Booking/Classes
 * @category    Abstract Class
 * @author      Thimpress, leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();


if ( ! class_exists( 'WPHB_Admin_Setting_Page' ) ) {

	/**
	 * Class WPHB_Admin_Setting_Page.
	 *
	 * @since 2.0
	 */
	abstract class WPHB_Admin_Setting_Page {

		/**
		 * Setting tab id.
		 *
		 * @var null
		 */
		protected $id = null;

		/**
		 * Setting tab title.
		 *
		 * @var null
		 */
		protected $title = null;

		/**
		 * WPHB_Admin_Setting_Page constructor.
		 *
		 * @since 2.0
		 */
		public function __construct() {
			add_filter( 'hb_admin_settings_tabs', array( $this, 'setting_tabs' ) );
			add_action( 'hb_admin_settings_sections_' . $this->id, array( $this, 'setting_sections' ) );
			add_action( 'hb_admin_settings_tab_' . $this->id, array( $this, 'output' ) );
		}

		/**
		 * Get setting fields.
		 *
		 * @since 2.0
		 *
		 * @return mixed
		 */
		public function get_settings() {
			return apply_filters( 'hotel_booking_admin_setting_fields_' . $this->id, array() );
		}

		/**
		 * Get setting sections.
		 *
		 * @since 2.0
		 *
		 * @return mixed
		 */
		public function get_sections() {
			return apply_filters( 'hotel_booking_admin_setting_sections_' . $this->id, array() );
		}

		/**
		 * Get setting tabs.
		 *
		 * @since 2.0
		 *
		 * @param $tabs
		 *
		 * @return mixed
		 */
		public function setting_tabs( $tabs ) {
			$tabs[ $this->id ] = $this->title;

			return $tabs;
		}

		/**
		 * Output setting page.
		 *
		 * @since 2.0
		 */
		public function output() {
			$settings = $this->get_settings();
			WPHB_Admin_Settings::render_fields( $settings );
		}

		/**
		 * Filter section in tab id.
		 *
		 * @since 2.0
		 */
		public function setting_sections() {
			$sections = $this->get_sections();

			if ( count( $sections ) === 1 ) {
				return;
			}

			$current_section = null;

			if ( isset( $_REQUEST['section'] ) ) {
				$current_section = sanitize_text_field( $_REQUEST['section'] );
			}

			$html = array();

			$html[] = '<ul class="hb-admin-sub-tab subsubsub">';
			$sub    = array();
			foreach ( $sections as $id => $text ) {
				$sub[] = '<li>
						<a href="?page=tp_hotel_booking_settings&tab=' . $this->id . '&section=' . $id . '"' . ( $current_section === $id ? ' class="current"' : '' ) . '>' . esc_html( $text ) . '</a>
					</li>';
			}
			$html[] = implode( '&nbsp;|&nbsp;', $sub );
			$html[] = '</ul><br />';

			echo implode( '', $html );
		}

		/**
		 * Save setting options.
		 *
		 * @since 2.0
		 */
		public function save() {
			$settings = $this->get_settings();
			WPHB_Admin_Settings::save_fields( $settings );
		}

	}

}