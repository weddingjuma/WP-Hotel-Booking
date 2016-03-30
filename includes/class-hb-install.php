<?php
/**
 * @Author: ducnvtt
 * @Date:   2016-03-28 16:31:22
 * @Last Modified by:   ducnvtt
 * @Last Modified time: 2016-03-30 17:12:24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class HB_Install {

	// install hook
	static function install() {

		// create update options
		self::create_options();
	}

	// upgrade database
	static function upgrade_database() {

	}

	// create options default
	static function create_options() {
		if ( ! class_exists( 'HB_Admin_Settings' ) ) {
			TP_Hotel_Booking::instance()->_include( 'includes/admin/class-hb-admin-settings.php' );
		}

		$settings_pages = HB_Admin_Settings::get_settings_pages();

		foreach ( $settings_pages as $setting ) {
			$options = $setting->get_settings();
			foreach ( $options as $option ) {
				if ( isset( $option[ 'id' ], $option[ 'default' ] ) ) {
					if ( ! get_option( $option[ 'id' ], false ) ) {
						update_option( $option['id'], $option['default'] );
					}
				}
			}
		}

		update_option( 'hotel_booking_version', HB_VERSION );
	}

	// create page. Eg: room-checkout, my-rooms
	static function create_pages() {
		if( ! function_exists( 'hb_create_page' ) ){
            $this->_include( 'includes/admin/hb-admin-functions.php' );
            $this->_include( 'includes/hb-functions.php' );
        }

		$pages = array();
		if( ! hb_get_page_id( 'my-rooms' ) || ! get_post( hb_get_page_id( 'my-rooms' ) ) )
		{
		    $pages['my-rooms'] = array(
		        'name'    => _x( 'my-rooms', 'my-rooms', 'tp-hotel-booking' ),
		        'title'   => _x( 'My Rooms', 'My Rooms', 'tp-hotel-booking' ),
		        'content' => '[' . apply_filters( 'hotel_booking_cart_shortcode_tag', 'hotel_booking_cart' ) . ']'
		    );
		}

		if( ! hb_get_page_id( 'checkout' ) || ! get_post( hb_get_page_id( 'checkout' ) ) )
		{
		    $pages['checkout'] = array(
		        'name'    => _x( 'room-checkout', 'room-checkout', 'tp-hotel-booking' ),
		        'title'   => _x( 'Checkout', 'Checkout', 'tp-hotel-booking' ),
		        'content' => '[' . apply_filters( 'hotel_booking_checkout_shortcode_tag', 'hotel_booking_checkout' ) . ']'
		    );
		}

		if( ! hb_get_page_id( 'search' ) || ! get_post( hb_get_page_id( 'search' ) ) )
		{
		    $pages['search'] = array(
		        'name'    => _x( 'hotel-booking', 'hotel-booking', 'tp-hotel-booking' ),
		        'title'   => _x( 'Hotel Booking', 'Hotel Booking', 'tp-hotel-booking' ),
		        'content' => '[' . apply_filters( 'hotel_booking_search_shortcode_tag', 'hotel_booking' ) . ']'
		    );
		}

		if( ! hb_get_page_id( 'terms' ) || ! get_post( hb_get_page_id( 'terms' ) ) )
		{
		    $pages['terms'] = array(
		        'name'    => _x( 'term-condition', 'term-condition', 'tp-hotel-booking' ),
		        'title'   => _x( 'Terms and Conditions ', 'Terms and Conditions', 'tp-hotel-booking' ),
		        'content' => apply_filters( 'hotel_booking_terms_content', 'Something notices' )
		    );
		}

		if( $pages && function_exists( 'hb_create_page' ) )
		{
		    foreach ( $pages as $key => $page ) {
		        $pageId = hb_create_page( esc_sql( $page['name'] ), 'hotel_booking_' . $key . '_page_id', $page['title'], $page['content'], ! empty( $page['parent'] ) ? hb_get_page_id( $page['parent'] ) : '' );
		        $hb_settings->set( $key.'_page_id', $pageId );
		    }
		}

	}

	// create tables. Eg: booking_items
	static function create_tables() {

	}


}
