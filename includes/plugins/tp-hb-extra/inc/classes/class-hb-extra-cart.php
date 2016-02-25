<?php

class HB_Extra_Cart
{
	/**
	 * instead of new class()
	 * @var null
	 */
	static $_instance = null;

	function __construct( $cart_id = null )
	{
		/**
		 * new script
		 */
		add_action( 'hotel_booking_added_cart', array( $this, 'ajax_added_cart' ), 10, 3 );
		/**
		 * add filter add to cart results array
		 * render object build mini cart
		 */
		add_filter( 'hotel_booking_add_to_cart_results', array( $this, 'add_to_cart_results' ), 10, 2 );

		/**
		 * after mini cart item loop
		 */
		add_action( 'hotel_booking_before_mini_cart_loop_price', array( $this, 'mini_cart_loop' ), 10, 2 );
		/**
		 * wp.template layout filter
		 */
		add_filter( 'hb_get_template', array( $this, 'mini_cart_layout' ), 10, 5 );

		/**
		 * sortable cart item
		 */
		add_filter( 'hotel_booking_load_cart_from_session', array( $this, 'hotel_booking_load_cart_from_session' ), 10, 1 );

		// /**
		//  * ajax remove packages
		//  */
		add_action( 'wp_ajax_tp_hotel_booking_remove_package', array( $this, 'remove_package' ) );
		add_action( 'wp_ajax_nopriv_tp_hotel_booking_remove_package', array( $this, 'remove_package' ) );

		/**
		 * append package into cart
		 */
		add_action( 'hotel_booking_cart_after_item', array( $this, 'cart_package_after_item' ), 10, 2 );

		/**
		 * append package into cart admin
		 */
		add_action( 'hotel_booking_admin_cart_after_item', array( $this, 'admin_cart_package_after_item' ), 10, 3 );

		// email new booking hook
		add_action( 'hotel_booking_email_new_booking', array( $this, 'email_new_booking' ), 10, 3 );

		add_filter( 'tp_hb_extra_cart_input', array( $this, 'check_respondent' ) );

	}

	// add extra
	function ajax_added_cart( $cart_id, $cart_item, $posts )
	{
		if( empty( $posts[ 'hb_optional_quantity_selected' ] ) || empty( $posts[ 'hb_optional_quantity' ] ) ) {
			return;
		}

		remove_action( 'hotel_booking_added_cart', array( $this, 'ajax_added_cart' ), 10, 3 );

		if( $posts['hb_optional_quantity_selected'] )
		{
			$selected_quantity = $posts['hb_optional_quantity'];
			$turn_on = $posts['hb_optional_quantity_selected'];

			foreach ( $selected_quantity as $extra_id => $qty ) {
				// param
				$param = array(
						'product_id'		=> $extra_id,
						'parent_id'			=> $cart_id,
						'check_in_date'		=> $cart_item['check_in_date'],
						'check_out_date'	=> $cart_item['check_out_date']
					);
				if ( array_key_exists( $extra_id, $turn_on ) ) {
					$extra_cart_item_id = TP_Hotel_Booking::instance()->cart->add_to_cart( $extra_id, $param, $qty );
				}
				else
				{
					$extra_cart_item_id = TP_Hotel_Booking::instance()->cart->generate_cart_id( $param );
					TP_Hotel_Booking::instance()->cart->remove_cart_item( $extra_cart_item_id );
				}
			}
		}
		add_action( 'hotel_booking_added_cart', array( $this, 'ajax_added_cart' ), 10, 3 );
	}

	/**
	 * wp.template hook template
	 * @param  [type] $located       [description]
	 * @param  [type] $template_name [description]
	 * @param  [type] $args          [description]
	 * @param  [type] $template_path [description]
	 * @param  [type] $default_path  [description]
	 * @return [type]                [description]
	 */
	public function mini_cart_layout( $located, $template_name, $args, $template_path, $default_path )
	{
		if( $template_name === 'shortcodes/mini_cart_layout.php' )
		{
			return tp_hb_extra_locate_template( 'shortcodes/mini_cart_layout.php' );
		}

		return $located;
	}

	/**
	 * extra package each cart item
	 * @param
	 * @return
	 */
	public function mini_cart_loop( $room, $cart_id )
	{
		$cart_item = TP_Hotel_Booking::instance()->cart->get_cart_item( $cart_id );
		if( ! $cart_item ) return;

		$packages = array();
		foreach ( TP_Hotel_Booking::instance()->cart->cart_contents as $id => $cart_item ) {
			if( isset( $cart_item->parent_id ) && $cart_item->parent_id === $cart_id )
			{
				$cart_item->cart_id = $id;
				$packages[] = $cart_item;
			}
		}

		ob_start();
		tp_hb_extra_get_template( 'loop/mini-cart-extra.php',
			array(
				'packages' 	=> $packages
		));
		echo ob_get_clean();
	}

	// add extra price
	function hotel_booking_load_cart_from_session( $cart_contents ) {
		foreach ( $cart_contents  as $parent_id => $cart_item ) {
			if ( ! isset( $cart_item->parent_id ) ) {
				foreach ( $cart_contents  as $id => $item ) {
					if ( isset( $item->parent_id ) && $item->parent_id === $parent_id ) {
						$cart_contents[$parent_id]->amount += $item->amount;
					}
				}
			}
		}

		return $cart_contents;
	}

	function remove_package()
	{
		if( ! isset( $_POST ) || ! defined( 'HB_BLOG_ID' ) )
			return;

		if( ! isset( $_POST['cart_id'] ) ) {
			wp_send_json( array( 'status' => 'success', 'message' => __( 'Cart ID is not exists.', 'tp-hotel-booking' ) ) );
		}

		$cart_id = sanitize_text_field( $_POST['cart_id'] );

		// cart item is exists
		if ( $package_item = TP_Hotel_Booking::instance()->cart->get_cart_item( $cart_id ) ) {
			if ( TP_Hotel_Booking::instance()->cart->remove_cart_item( $cart_id ) ) {
				// room cart item id
				$room = TP_Hotel_Booking::instance()->cart->get_cart_item( $package_item->parent_id );
				$results =  array(
		                    'status'    		=> 'success',
		                    'cart_id'        	=> $package_item->parent_id,
		                    'permalink' 		=> get_permalink( $room->product_id ),
		                    'name'      		=> sprintf( '%s', $room->product_data->name ) . ( $room->product_data->capacity_title ? sprintf( '(%s)', $room->product_data->capacity_title) : '' ),
		                    'quantity'  		=> $room->quantity,
		                    'total'     		=> hb_format_price( $room->amount ),
		                    // use to cart table
		                    'package_id'		=> $cart_id,
		                    'item_total'		=> hb_format_price( $room->amount_include_tax ),
		                    'sub_total'  		=> hb_format_price( TP_Hotel_Booking::instance()->cart->sub_total ),
			                'grand_total'   	=> hb_format_price( TP_Hotel_Booking::instance()->cart->total ),
			                'advance_payment' 	=> hb_format_price( TP_Hotel_Booking::instance()->cart->advance_payment )
		            );

				$extraRoom = TP_Hotel_Booking::instance()->cart->get_extra_packages( $package_item->parent_id );
				$extra_packages = array();
				if( $extraRoom ) {
					foreach ( $extraRoom as $cart_id => $cart_item ) {
						$extra = HB_Extra_Package::instance( $cart_item->product_id );
						$extra_packages[] = array(
								'package_title'				=> sprintf( '%s (%s)', $extra->title, hb_format_price( $extra->amount_singular ) ),
								'cart_id'					=> $cart_id,
								'package_quantity'			=> sprintf( 'x%s', $cart_item->quantity ),
								'package_respondent'		=> $extra->respondent
							);
					}
				}
				$results['extra_packages'] = $extra_packages;

				$results = apply_filters( 'hb_remove_package_results', $results, $package_item );

				do_action( 'hb_extra_removed_package', $package_item );
		        hb_send_json( $results );
			}

		} else {
			wp_send_json( array( 'status' => 'success', 'message' => __( 'Cart item is not exists.', 'tp-hotel-booking' ) ) );
		}
	}

	/**
	 * add to cart results
	 * @param [array] $results [results]
	 * @param [object] $room    [room object class]
	 */
	function add_to_cart_results( $results, $room )
	{
		if( ! isset( $results[ 'cart_id' ] ) ) return $results;

		$cart_id = $results[ 'cart_id' ];
		$cart_contents = TP_Hotel_Booking::instance()->cart->cart_contents;

		if ( $cart_contents ) {
			$extra_packages = array();
			foreach ( $cart_contents as $cart_item_id => $cart_item ) {
				if ( isset( $cart_item->parent_id ) && $cart_item->parent_id === $cart_id ) {
					// extra class
					$extra = HB_Extra_Package::instance( $cart_item->product_id );
					$extra_packages[] = array(
							'package_title'		=> sprintf( '%s (%s)', $extra->title, hb_format_price( $extra->amount_singular ) ),
							'package_id'		=> $extra->ID,
							'cart_id'			=> $cart_item_id,
							'package_quantity'	=> sprintf( 'x%s', $cart_item->quantity )
						);
				}
			}
			$results[ 'extra_packages' ] = $extra_packages;
		}

		return $results;
	}

	// cart fontend
	function cart_package_after_item( $room, $cart_id )
	{
		$extra_packages = TP_Hotel_Booking::instance()->cart->get_extra_packages( $cart_id );

		if( $extra_packages ) {
			if( is_hb_checkout() ) {
				$page = 'checkout';
			}
			else {
				$page = 'cart';
			}

			tp_hb_extra_get_template( 'loop/addition-services-title.php', array( 'page' => $page, 'room' => $room, 'cart_id' => $cart_id ) );
			foreach ( $extra_packages as $package_cart_id => $cart_item )
			{
				tp_hb_extra_get_template( 'loop/cart-extra-package.php', array( 'cart_id' => $package_cart_id, 'package' => $cart_item ) );
			}
		}
	}

	// cart admin
	function admin_cart_package_after_item( $cart_params, $cart_id, $booking )
	{
		$html = array();
		ob_start();
		TP_Hotel_Booking::instance()->_include( 'includes/admin/views/update/admin-addition-services-title.php', true, array( 'room' => $cart_params[ $cart_id ], 'cart_id' => $cart_id ), false );
		$html[] = ob_get_clean();
		foreach ( $cart_params as $id => $cart_item ) {
			if ( isset( $cart_item->parent_id ) && $cart_item->parent_id === $cart_id ) {
				ob_start();
				TP_Hotel_Booking::instance()->_include( 'includes/admin/views/update/admin-cart-extra-package.php', true, array( 'package' => $cart_item, 'booking' => $booking ), false );
				$html[] = ob_get_clean();
			}
		}
		echo implode( '', $html );
	}

	// email new booking
	function email_new_booking( $cart_params, $cart_id, $booking ) {
	?>
		<tr class="hb_addition_services_title hb_table_center">
			<td style="text-align: center;" colspan="7">
				<?php _e( 'Addition Services', 'tp-hotel-booking' ); ?>
			</td>
		</tr>
	<?php
		foreach ( $cart_params as $id => $cart_item ) {
			if ( isset( $cart_item->parent_id ) && $cart_item->parent_id === $cart_id ) {
				?>
					<tr style="background-color: #FFFFFF;">

						<td></td>

						<td>
							<?php echo esc_html( $cart_item->quantity ); ?>
						</td>

						<td colspan="3">
							<?php printf( '%s', $cart_item->product_data->title ) ?>
						</td>

						<td>
							<?php echo hb_format_price( $cart_item->amount_singular_exclude_tax, hb_get_currency_symbol( $booking->currency ) ) ?>
						</td>

					</tr>

				<?php
			}
		}
	}

	function check_respondent( $respondent )
	{
		// remove_filter( 'tp_hb_extra_cart_input', array( $this, 'check_respondent' ) );
		if( is_page( hb_get_page_id( 'checkout' ) ) || hb_get_request( 'hotel-booking' ) === 'checkout' )
			return false;

		if( is_page( hb_get_page_id( 'my-rooms' ) ) || hb_get_request( 'hotel-booking' ) === 'cart' )
		{
			if( $respondent === 'trip' )
				return false;
		}
		add_filter( 'tp_hb_extra_cart_input', array( $this, 'check_respondent' ) );
		return $respondent;
	}

	/**
	 * instead of new class. quickly, helpfully
	 * @param $cart_id [description]
	 * @return object or null
	 */
	static function instance( $cart_id = null )
	{
		if( ! empty( self::$_instance[ $cart_id ] ) )
			return self::$_instance[ $cart_id ];

		return new self();
	}

}
new HB_Extra_Cart();