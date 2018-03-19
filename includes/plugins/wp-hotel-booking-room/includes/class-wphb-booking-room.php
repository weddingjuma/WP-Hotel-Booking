<?php

/**
 * WP Hotel Booking Room class.
 *
 * @class       WPHB_Booking_Room
 * @version     2.0
 * @package     WP_Hotel_Booking_Room/Classes
 * @category    Class
 * @author      Thimpress, leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPHB_Booking_Room' ) ) {

	/**
	 * Class WPHB_Booking_Room.
	 *
	 * @since 2.0
	 */
	class WPHB_Booking_Room {

		/**
		 * @var null
		 */
		private static $instance = null;

		/**
		 * WPHB_Booking_Room constructor.
		 *
		 * @since 2.0
		 */
		public function __construct() {

			$book_now_single  = get_option( 'tp_hotel_booking_enable_single_book' );
			$book_now_archive = get_option( 'tp_hotel_booking_enable_single_book' );

			if ( ! $book_now_archive && ! $book_now_single ) {
				return;
			}

			add_action( 'hb_archive_room_thumbnail', array( $this, 'archive_add_button' ) );
			add_action( 'hb_before_single_room_price', array( $this, 'single_add_button' ) );

			add_action( 'wp_footer', array( $this, 'wp_footer' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

			add_action( 'wp_ajax_wphb_room_check_single_room_available', array( $this, 'check_room_available' ) );
			add_action( 'wp_ajax_nopriv_wphb_room_check_single_room_available', array(
				$this,
				'check_room_available'
			) );
			add_action( 'hotel_booking_room_before_quantity', array( $this, 'extra_single_room' ) );

			// redirect cart when booking in single room
			add_filter( 'hotel_booking_add_to_cart_results', array( $this, 'add_to_cart_redirect' ), 10, 2 );
		}

		/**
		 * Add book now button in archive room page.
		 */
		public function archive_add_button() {
			if ( get_option( 'tp_hotel_booking_enable_archive_book', true ) ) {
				ob_start();
				wphb_room_get_template( 'archive-button.php' );
				$html = ob_get_clean();
				echo $html;
			}
		}

		/**
		 * Add book now button in single room page.
		 *
		 * @since 2.0
		 */
		public function single_add_button() {
			if ( get_option( 'tp_hotel_booking_enable_single_book', true ) ) {
				if ( is_singular( 'hb_room' ) ) {
					ob_start();
					wphb_room_get_template( 'single-button.php' );
					$html = ob_get_clean();
					echo $html;
				}
			}
		}

		/**
		 * Add single room available footer.
		 *
		 * @since 2.0
		 */
		public function wp_footer() {
			$html = array();
			ob_start();
			// search form.
			wphb_room_get_template( 'popup.php' );
			$html[] = ob_get_clean();
			echo implode( '', $html );
		}

		/**
		 * Enqueue scripts.
		 *
		 * @since 2.0
		 */
		public function enqueue_scripts() {
			wp_enqueue_script( 'magnific-popup', WPHB_ROOM_URI . '/assets/js/jquery.magnific-popup.min.js', array(), WPHB_ROOM_VER );
			wp_enqueue_style( 'magnific-popup', WPHB_ROOM_URI . '/assets/css/magnific-popup.css', array(), WPHB_ROOM_VER );

			wp_enqueue_script( 'wphb-library-moment' );
			wp_enqueue_style( 'wphb-library-fullcalendar' );
			wp_enqueue_script( 'wphb-library-fullcalendar' );

			wp_enqueue_style( 'wphb-booking-room', WPHB_ROOM_URI . '/assets/css/site.css', array(), WPHB_ROOM_VER );
			wp_enqueue_script( 'wphb-booking-room', WPHB_ROOM_URI . '/assets/js/site.js',
				array( 'jquery', 'wp-util', 'magnific-popup', 'jquery-ui-datepicker' ), WPHB_ROOM_VER );

			wp_localize_script( 'wphb-booking-room', 'wphb_room_js', hb_i18n() );
		}

		public function admin_scripts() {
			wp_enqueue_script( 'magnific-popup', WPHB_ROOM_URI . '/assets/js/jquery.magnific-popup.min.js', array(), WPHB_ROOM_VER );
			wp_enqueue_style( 'magnific-popup', WPHB_ROOM_URI . '/assets/css/magnific-popup.css', array(), WPHB_ROOM_VER );
		}

		/**
		 * Show extra in search room form.
		 *
		 * @since 2.0
		 *
		 * @param $post
		 */
		public function extra_single_room( $post ) {
			ob_start();
			wphb_room_get_template( 'extra.php', array( 'post' => $post ) );
			echo ob_get_clean();
		}

		/**
		 * Add to cart redirect.
		 *
		 * @since 2.0
		 *
		 * @param $param
		 * @param $room
		 *
		 * @return mixed
		 */
		public function add_to_cart_redirect( $param, $room ) {
			if ( is_singular( 'hb_room' ) ) {
				if ( isset( $param['status'] ) && $param['status'] === 'success' && isset( $_POST['is_single'] ) && $_POST['is_single'] ) {
					$param['redirect'] = hb_get_cart_url();
				}
			}

			return $param;
		}

		/**
		 * Check single room available.
		 *
		 * @since 2.0
		 */
		public function check_room_available() {
			if ( ! isset( $_POST['hb-booking-single-room-check-nonce-action'] ) || ! wp_verify_nonce( $_POST['hb-booking-single-room-check-nonce-action'], 'hb_booking_single_room_check_nonce_action' ) ) {
				return;
			}

			$room_id = $check_in_date = $check_out_date = $check_in_date_text = $check_out_date_text = $room_name = '';
			$errors  = array();

			if ( ! isset( $_POST['room-id'] ) || ! is_numeric( $_POST['check_in_date_timestamp'] ) ) {
				$errors[] = __( 'Check in id is required.', 'wphb-booking-room' );
			} else {
				$room_id = absint( $_POST['room-id'] );
			}

			if ( ! isset( $_POST['room-name'] ) ) {
				$errors[] = __( 'Check in name is required.', 'wphb-booking-room' );
			} else {
				$room_name = sanitize_text_field( $_POST['room-name'] );
			}

			if ( ! isset( $_POST['check_in_date'] ) || ! isset( $_POST['check_in_date_timestamp'] ) || ! is_numeric( $_POST['check_in_date_timestamp'] ) ) {
				$errors[] = __( 'Check in date is required.', 'wphb-booking-room' );
			} else {
				$check_in_date_text = sanitize_text_field( $_POST['check_in_date'] );
				$check_in_date      = absint( $_POST['check_in_date_timestamp'] );
			}

			if ( ! isset( $_POST['check_out_date_timestamp'] ) || ! is_numeric( $_POST['check_out_date_timestamp'] ) ) {
				$errors[] = __( 'Check out date is required.', 'wphb-booking-room' );
			} else {
				$check_out_date_text = sanitize_text_field( $_POST['check_out_date'] );
				$check_out_date      = absint( $_POST['check_out_date_timestamp'] );
			}

			// valid request and require field
			if ( empty( $errors ) ) {
				$qty = wphb_get_room_available( $room_id, array(
					'check_in_date'  => $check_in_date,
					'check_out_date' => $check_out_date
				) );

				if ( is_wp_error( $qty ) || ! $qty ) {
					$errors[] = sprintf( __( 'No room found in %s to %s', 'wphb-booking-room' ), $check_in_date_text, $check_out_date_text );
					// input is not pass validate, sanitize
					wp_send_json( array( 'status' => false, 'messages' => $errors ) );
				} else {
					// room has been found
					wp_send_json( array(
						'status'              => true,
						'check_in_date_text'  => $check_in_date_text,
						'check_out_date_text' => $check_out_date_text,
						'check_in_date'       => date( 'm/d/Y', $check_in_date ),
						'check_out_date'      => date( 'm/d/Y', $check_out_date ),
						'room_id'             => $room_id,
						'room_name'           => $room_name,
						'qty'                 => $qty
					) );
				}
			}
		}

		/**
		 * Get instances.
		 *
		 * @since 2.0
		 *
		 * @return null|WPHB_Booking_Room
		 */
		public static function instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

}

WPHB_Booking_Room::instance();