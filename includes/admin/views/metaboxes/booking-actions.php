<?php

/**
 * Admin View: Booking actions.
 *
 * @version     2.0
 * @package     WP_Hotel_Booking/Views
 * @category    View
 * @author      Thimpress, leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

global $post;
?>

<div class="submitbox">
    <div id="delete-action">
		<?php if ( current_user_can( 'delete_post', $post->ID ) ) : ?>
            <a class="submitdelete deletion"
               href="<?php echo esc_attr( get_delete_post_link( $post->ID ) ) ?>"><?php _e( 'Move to Trash', 'wp-hotel-booking' ); ?></a>
		<?php endif; ?>
    </div>
    <div id="publishing-action">
        <button name="save" type="submit" class="button button-primary" id="publish">
			<?php printf( '%s', $post->post_status !== 'auto-draft' ? __( 'Update', 'wp-hotel-booking' ) : __( 'Save Book', 'wp-hotel-booking' ) ) ?>
        </button>
    </div>
</div>
