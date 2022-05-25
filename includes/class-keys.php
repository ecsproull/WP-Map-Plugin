<?php
/**
 * Summary
 * Key management  class.
 *
 * @package     Maps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Class to manage the api keys..
 */
class Keys extends EdsMapBase {

	/**
	 * Entry point for an admin menu item.
	 *
	 * @return void
	 */
	public function keys_menu_handler() {
		global $wpdb;
		$mynonce = wp_create_nonce( 'my-nonce' );
		$post    = wp_unslash( $_POST );
		if ( isset( $post['mynonce'] ) ) {
			$my_nonce = $post['mynonce'];
			if ( wp_verify_nonce( $my_nonce, 'my-nonce' ) ) {
				if ( isset( $post['submit'] ) ) {
					$where = array( 'key_type' => $post['submit'] );
					$key   = array( 'key_value' => $post[ $post['submit'] ] );
					$rows = $wpdb->update(
						self::MAP_KEY_TABLE,
						$key,
						$where
					);

					?>
					<div class='text-center mt-4'>
						<h1><?php echo esc_html( $post['submit'] ); ?> was updated.</h1>
					</div>
					<?php
				}
			}
		}

		$keys = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %1s;', self::MAP_KEY_TABLE ), OBJECT );
		?>
		<form  method="POST">
			<div id="content" class="container">
				<table class="mb-100px table table-striped mr-auto ml-auto">
					<?php
					foreach ( $keys as $key ) {
						?>
						<tr><td class="text-right mr-2"><label><?php echo esc_html( $key->key_label ); ?></label></td>
							<td><input  style="width: 350px;"
										type="text"
										name='<?php echo esc_html( $key->key_type ); ?>'
										value='<?php echo esc_html( $key->key_value ); ?>'
										placeholder="Enter Key" /> </td>
							<td><input 	class='submitbutton addItem'
										type="submit"
										value='<?php echo esc_html( $key->key_type ); ?>'
										name="submit"></td>
						</tr>
						<?php
					}
					?>
				</table>
			</div>
			<input type="hidden" name="mynonce" value="<?php echo esc_html( $mynonce ); ?>">
		</form>
		<?php
	}
}

