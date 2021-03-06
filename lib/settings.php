<?php
/**
 * REFR Shortlinks - Settings Module
 *
 * Contains the specific settings page configuration
 *
 * @package REFR Shortlinks
 */
/*  Copyright 2015 Reaktiv Studios

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License (GPL v2) only.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists( 'REFRCreator_Settings' ) ) {

// Start up the engine
class REFRCreator_Settings
{

	/**
	 * This is our constructor
	 *
	 * @return REFRCreator_Settings
	 */
	public function __construct() {
		add_action( 'admin_menu',                   array( $this, 'refr_menu_item'    )           );
		add_action( 'admin_init',                   array( $this, 'reg_settings'        )           );
		add_action( 'admin_init',                   array( $this, 'store_settings'      )           );
		add_action( 'admin_notices',                array( $this, 'settings_messages'   )           );
		add_filter( 'plugin_action_links',          array( $this, 'quick_link'          ),  10, 2   );
	}

	/**
	 * show settings link on plugins page
	 *
	 * @param  [type] $links [description]
	 * @param  [type] $file  [description]
	 * @return [type]        [description]
	 */
	public function quick_link( $links, $file ) {

		static $this_plugin;

		if ( ! $this_plugin ) {
			$this_plugin = REFR_BASE;
		}

		// check to make sure we are on the correct plugin
		if ( $file != $this_plugin ) {
			return $links;
		}

		// buil my link
		$single = '<a href="' . menu_page_url( 'refr-settings', 0 ) . '">' . __( 'Settings', 'wprefr' ) . '</a>';

		// get it in the group
		array_push( $links, $single );

		// return it
		return $links;
	}

	/**
	 * call the menu page for the REFR settings
	 *
	 * @return void
	 */
	public function refr_menu_item() {
		add_options_page( __( 'REFR Settings', 'wprefr' ), __( 'REFR Settings', 'wprefr' ), apply_filters( 'refr_settings_cap', 'manage_options' ), 'refr-settings', array( __class__, 'refr_settings_page' ) );
	}

	/**
	 * Register settings
	 *
	 * @return
	 */
	public function reg_settings() {
		register_setting( 'refr_options', 'refr_options' );
	}

	/**
	 * check for, sanitize, and store our options
	 *
	 * @return [type] [description]
	 */
	public function store_settings() {

		// make sure we have our settings item
		if ( empty( $_POST['refr-options'] ) ) {
			return;
		}

		// verify our nonce
		if ( ! isset( $_POST['refr_settings_save'] ) || ! wp_verify_nonce( $_POST['refr_settings_save'], 'refr_settings_save_nonce' ) ) {
			return;
		}

		// cast our options as a variable
		$data   = (array) $_POST['refr-options'];

		// set an empty
		$store  = array();

		// check and sanitize the URL
		if ( ! empty( $data['url'] ) ) {
			$store['url']   = esc_url( REFRCreator_Helper::strip_trailing_slash( $data['url'] ) );
		}

		// check and sanitize the API key
		if ( ! empty( $data['api'] ) ) {
			$store['api']   = sanitize_text_field( $data['api'] );
		}

		// check the boolean for autosave
		if ( ! empty( $data['sav'] ) ) {
			$store['sav']   = true;
		}

		// check the boolean for scheduled
		if ( ! empty( $data['sch'] ) ) {
			$store['sch']   = true;
		}

		// check the boolean for shortlink
		if ( ! empty( $data['sht'] ) ) {
			$store['sht']   = true;
		}

		// check the boolean for using CPTs
		if ( ! empty( $data['cpt'] ) ) {
			$store['cpt']   = true;
		}

		// check the each possible CPT
		if ( ! empty( $data['cpt'] ) && ! empty( $data['typ'] ) ) {
			$store['typ']   = REFRCreator_Helper::sanitize_array_text( $data['typ'] );
		}

		// filter it
		$store  = array_filter( $store );

		// pass it
		self::save_redirect_settings( $store );
	}

	/**
	 * save our settings and redirect to the proper place
	 *
	 * @param  array  $data [description]
	 * @param  string $key  [description]
	 * @return [type]       [description]
	 */
	public static function save_redirect_settings( $data = array(), $key = 'refr-settings' ) {

		// first purge the API check
		delete_option( 'refr_api_test' );

		// delete if empty, else go through some checks
		if ( empty( $data ) ) {
			// delete the key
			delete_option( 'refr_options' );
			// get the link
			$redirect   = self::get_settings_page_link( $key, 'refr-deleted=1' );
			// and redirect
			wp_redirect( $redirect, 302 );
			// and exit
			exit();
		}

		// we got something. check and store
		if ( get_option( 'refr_options' ) !== false ) {
			update_option( 'refr_options', $data );
		} else {
			add_option( 'refr_options', $data, null, 'no' );
		}

		// get the link
		$redirect   = self::get_settings_page_link( $key, 'refr-saved=1' );

		// and redirect
		wp_redirect( $redirect, 302 );

		// and exit
		exit();
	}

	/**
	 * display the admin settings based on the
	 * provided query string
	 *
	 * @return [type] [description]
	 */
	public function settings_messages() {

		// check for string first
		if ( empty( $_GET['refr-action'] ) ) {
			return;
		}

		// our saved
		if ( ! empty( $_GET['refr-saved'] ) ) {
			// the message
			echo '<div class="updated settings-error" id="setting-error-settings_updated">';
			echo '<p><strong>' . __( 'Your settings have been saved.', 'wprefr' ) . '</strong></p>';
			echo '</div>';
		}

		// our deleted
		if ( ! empty( $_GET['refr-deleted'] ) ) {
			// the message
			echo '<div class="error settings-error" id="setting-error-settings_updated">';
			echo '<p><strong>' . __( 'Your settings have been deleted.', 'wprefr' ) . '</strong></p>';
			echo '</div>';
		}
	}

	/**
	 * get the link of my settings page
	 *
	 * @param  string $page   [description]
	 * @param  string $string [description]
	 * @return [type]         [description]
	 */
	public static function get_settings_page_link( $page = 'refr-settings', $string = '' ) {

		// get the base
		$base   = menu_page_url( $page, 0 ) . '&refr-action=1';

		// build the link
		$link   = ! empty( $string ) ? $base . '&' . $string : $base;

		// return it as base or with a string
		return esc_url_raw( html_entity_decode( $link ) );
	}

	/**
	 * Display main options page structure
	 *
	 * @return void
	 */
	public static function refr_settings_page() {

		// bail if current user cannot manage options
		if(	! current_user_can( apply_filters( 'refr_settings_cap', 'manage_options' ) ) ) {
			return;
		}
		?>

		<div class="wrap">
		<h2><?php _e( 'REFR Shortlinks Settings', 'wprefr' ); ?></h2>

		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<?php
			self::settings_side();
			self::settings_open();
			?>

		   	<div class="refr-form-text">
		   	<p><?php _e( 'Below are the basic settings for the REFR creator. A reminder, your REFR install cannot be public.', 'wprefr' ); ?></p>
			</div>

			<div class="refr-form-options">
				<form method="post">
				<?php
				// fetch our data for the settings
				$data   = REFRCreator_Helper::get_refr_option();

				// filter and check each one
				$url    = ! empty( $data['url'] ) ? $data['url'] : '';
				$api    = ! empty( $data['api'] ) ? $data['api'] : '';
				$save   = ! empty( $data['sav'] ) ? true : false;
				$schd   = ! empty( $data['sch'] ) ? true : false;
				$short  = ! empty( $data['sht'] ) ? true : false;
				$cpts   = ! empty( $data['cpt'] ) ? true : false;
				$types  = ! empty( $data['typ'] ) ? (array) $data['typ'] : array();

				// load the settings fields
				wp_nonce_field( 'refr_settings_save_nonce', 'refr_settings_save', false, true );
				?>

				<table class="form-table refr-table">
				<tbody>
					<tr>
						<th><?php _e( 'REFR Custom URL', 'wprefr' ); ?></th>
						<td>
							<input type="url" class="regular-text code" value="<?php echo esc_url( $url ); ?>" id="refr-url" name="refr-options[url]">
							<p class="description"><?php _e( 'Enter the domain URL for your REFR API', 'wprefr' ); ?></p>
						</td>
					</tr>

					<tr>
						<th><?php _e( 'REFR API Signature Key', 'wprefr' ); ?></th>
						<td class="apikey-field-wrapper">
							<input type="text" class="regular-text code" value="<?php echo esc_attr( $api ); ?>" id="refr-api" name="refr-options[api]" autocomplete="off">
							<span class="dashicons dashicons-visibility password-toggle"></span>
							<p class="description"><?php _e('Found in the tools section on your REFR admin page.', 'wprefr') ?></p>
						</td>
					</tr>

					<tr>
						<th><?php _e( 'Auto generate links', 'wprefr' ) ?></th>
						<td class="setting-item">
							<input type="checkbox" name="refr-options[sav]" id="refr-sav" value="true" <?php checked( $save, true ); ?> />
							<label for="refr-sav"><?php _e( 'Create a REFR link when a post is saved.', 'wprefr' ); ?></label>
						</td>
					</tr>

					<tr>
						<th><?php _e( 'Scheduled Content', 'wprefr' ) ?></th>
						<td class="setting-item">
							<input type="checkbox" name="refr-options[sch]" id="refr-sch" value="true" <?php checked( $schd, true ); ?> />
							<label for="refr-sch"><?php _e( 'Create a REFR link when a scheduled post publishes.', 'wprefr' ); ?></label>
						</td>
					</tr>

					<tr>
						<th><?php _e( 'Use REFR for shortlink', 'wprefr' ) ?></th>
						<td class="setting-item">
							<input type="checkbox" name="refr-options[sht]" id="refr-sht" value="true" <?php checked( $short, true ); ?> />
							<label for="refr-sht"><?php _e( 'Use the REFR link wherever wp_shortlink is fired', 'wprefr' ); ?></label>
						</td>
					</tr>

					<tr class="setting-item-types">
						<th><?php _e( 'Include Custom Post Types', 'wprefr' ) ?></th>
						<td class="setting-item">
							<input type="checkbox" name="refr-options[cpt]" id="refr-cpt" value="true" <?php checked( $cpts, true ); ?> />
							<label for="refr-cpt"><?php _e( 'Display the REFR creator on public custom post types', 'wprefr' ); ?></label>
						</td>
					</tr>

					<tr class="secondary refr-types" style="display:none;">
						<th><?php _e( 'Select the types to include', 'wprefr' ); ?></th>
						<td><?php echo self::post_types( $types ); ?></td>
					</tr>

				</tbody>
				</table>

				<p><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /></p>
				</form>

			</div>

		<?php self::settings_close(); ?>

		</div>
		</div>

	<?php }

	/**
	 * fetch our custom post types and display checkboxes
	 * @param  array  $types [description]
	 * @return [type]        [description]
	 */
	private static function post_types( $selected = array() ) {

		// grab CPTs
		$args	= array(
			'public'    => true,
			'_builtin'  => false
		);

		// fetch the types
		$types	= get_post_types( $args, 'objects' );

		// return empty if none exist
		if ( empty( $types ) ) {
			return;
		}

		// output loop of types
		$boxes	= '';

		// loop my types
		foreach ( $types as $type ) {

			// type variables
			$name	= $type->name;
			$label	= $type->labels->name;

			// check for setting in array
			$check  = ! empty( $selected ) && in_array( $name, $selected ) ? 'checked="checked"' : '';

			// output checkboxes
			$boxes	.= '<span>';
				$boxes	.= '<input type="checkbox" name="refr-options[typ][' . esc_attr( $name ) . ']" id="refr-options-typ-' . esc_attr( $name ) . '" value="' . esc_attr( $name ) . '" ' . $check . ' />';
				$boxes	.= '<label for="refr-options-typ-' . esc_attr( $name ) . '">' . esc_attr( $label ) . '</label>';
			$boxes	.= '</span>';
		}

		// return my boxes
		return $boxes;
	}

	/**
	 * Some extra stuff for the settings page
	 *
	 * this is just to keep the area cleaner
	 *
	 */
	public static function settings_side() { ?>

		<div id="side-info-column" class="inner-sidebar">

			<div class="meta-box-sortables">
				<?php self::sidebox_about(); ?>
			</div>

			<div class="meta-box-sortables">
				<?php self::sidebox_status(); ?>
			</div>

			<div class="meta-box-sortables">
				<?php self::sidebox_data(); ?>
			</div>

			<div class="meta-box-sortables">
				<?php self::sidebox_links(); ?>
			</div>

		</div> <!-- // #side-info-column .inner-sidebar -->

	<?php }

	/**
	 * the about sidebox
	 */
	public static function sidebox_about() { ?>

		<div id="refr-admin-about" class="postbox refr-sidebox">
			<h3 class="hndle" id="about-sidebar"><?php _e( 'About the Plugin', 'wprefr' ); ?></h3>
			<div class="inside">

				<p><strong><?php _e( 'Questions?', 'wprefr' ); ?></strong><br />

				<?php echo sprintf( __( 'Talk to <a href="%s" class="external">@norcross</a> on twitter or visit the <a href="%s" class="external">plugin support forum</a> for bugs or feature requests.', 'wprefr' ), esc_url( 'https://twitter.com/norcross' ), esc_url( 'https://wordpress.org/support/plugin/refr-link-creator/' ) ); ?></p>

				<p><strong><?php _e( 'Enjoy the plugin?', 'wprefr' ); ?></strong><br />

				<?php echo sprintf( __( '<a href="%s" class="admin-twitter-link">Tweet about it</a> and consider donating.', 'wprefr' ), 'http://twitter.com/?status=I\'m using @norcross\'s REFR Shortlinks plugin - check it out! http://l.norc.co/refr/' ); ?>

				<p><strong><?php _e( 'Donate:', 'wprefr' ) ?></strong><br />

				<?php _e( 'A lot of hard work goes into building plugins - support your open source developers. Include your twitter username and I\'ll send you a shout out for your generosity. Thank you!', 'wprefr' ); ?></p>

				<?php self::side_paypal(); ?>
			</div>
		</div>

	<?php }

	/**
	 * the status sidebox
	 */
	public static function sidebox_status() {

		// get my API status data
		if ( false === $data = REFRCreator_Helper::get_api_status_data() ) {
			return;
		}
		?>

		<div id="refr-admin-status" class="postbox refr-sidebox">
			<h3 class="hndle" id="status-sidebar"><?php echo $data['icon']; ?><?php _e( 'API Status Check', 'wprefr' ); ?></h3>
			<div class="inside">
				<form>

				<p class="api-status-text"><?php echo esc_attr( $data['text'] ); ?></p>

				<p class="api-status-actions">
					<input type="button" class="refr-click-status button-primary" value="<?php _e( 'Check Status', 'wprefr' ); ?>" >
					<span class="spinner refr-spinner refr-status-spinner"></span>
					<?php wp_nonce_field( 'refr_status_nonce', 'refr_status', false, true ); ?>

				</p>

				</form>
			</div>
		</div>

	<?php }

	/**
	 * the data sidebox
	 */
	public static function sidebox_data() { ?>

		<div id="refr-data-refresh" class="postbox refr-sidebox">
			<h3 class="hndle" id="data-sidebar"><?php _e( 'Data Options', 'wprefr' ); ?></h3>
			<div class="inside">
				<form>
					<p><?php _e( 'Click the button below to refresh the click count data for all posts with a REFR link.', 'wprefr' ); ?></p>
					<input type="button" class="refr-click-updates button-primary" value="<?php _e( 'Refresh Click Counts', 'wprefr' ); ?>" >
					<span class="spinner refr-spinner refr-refresh-spinner"></span>
					<?php wp_nonce_field( 'refr_refresh_nonce', 'refr_refresh', false, true ); ?>

					<hr />

					<p><?php _e( 'Click the button below to attempt an import of existing REFR links.', 'wprefr' ); ?></p>
					<input type="button" class="refr-click-import button-primary" value="<?php _e( 'Import Existing URLs', 'wprefr' ); ?>" >
					<span class="spinner refr-spinner refr-import-spinner"></span>
					<?php wp_nonce_field( 'refr_import_nonce', 'refr_import', false, true ); ?>

					<hr />

					<p><?php _e( 'Using Ozh\'s plugin? Click here to convert the existing meta keys', 'wprefr' ); ?></p>
					<input type="button" class="refr-convert button-primary" value="<?php _e( 'Convert Meta Keys', 'wprefr' ); ?>" >
					<span class="spinner refr-spinner refr-convert-spinner"></span>
					<?php wp_nonce_field( 'refr_convert_nonce', 'refr_convert', false, true ); ?>

				</form>
			</div>
		</div>

	<?php }

	/**
	 * the links sidebox
	 */
	public static function sidebox_links() { ?>

		<div id="refr-admin-links" class="postbox refr-sidebox">
			<h3 class="hndle" id="links-sidebar"><?php _e( 'Additional Links', 'wprefr' ); ?></h3>
			<div class="inside">
				<ul>
					<li><a href="http://refr.org/" target="_blank"><?php _e( 'REFR homepage', 'wprefr' ); ?></a></li>
					<li><a href="http://wordpress.org/extend/plugins/refr-link-creator/" target="_blank"><?php _e( 'Plugin on WP.org', 'wprefr' ); ?></a></li>
					<li><a href="https://github.com/webmarka/refr-shortlinks/" target="_blank"><?php _e( 'Plugin on GitHub', 'wprefr' ); ?></a></li>
					<li><a href="http://wordpress.org/support/plugin/refr-link-creator/" target="_blank"><?php _e( 'Support Forum', 'wprefr' ); ?></a><li>
				</ul>
			</div>
		</div>

	<?php }

	/**
	 * paypal form for donations
	 */
	public static function side_paypal() { ?>

		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="11085100">
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="<?php _e( 'PayPal - The safer, easier way to pay online!', 'wprefr' ); ?>">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>

	<?php }

	/**
	 * open up the settings page markup
	 */
	public static function settings_open() { ?>

		<div id="post-body" class="has-sidebar">
			<div id="post-body-content" class="has-sidebar-content">
				<div id="normal-sortables" class="meta-box-sortables">
					<div id="about" class="postbox">
						<div class="inside">

	<?php }

	/**
	 * close out the settings page markup
	 */
	public static function settings_close() { ?>

						<br class="clear" />
						</div>
					</div>
				</div>
			</div>
		</div>

	<?php }

// end class
}

// end exists check
}

// Instantiate our class
new REFRCreator_Settings();

