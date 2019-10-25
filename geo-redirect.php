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

/* ------------------------------------------------------------------------ *
 * Activate the plugin
 * ------------------------------------------------------------------------ */
function gr_activation() {

	if ( false == get_option( 'gr_engine_sxgeo' ) ) {
		add_option( 'gr_engine_sxgeo', 1, '', 'no' );
	}

	if ( false == get_option( 'gr_engine_geoip' ) ) {
		add_option( 'gr_engine_geoip', 1, '', 'no' );
	}

	if ( false == get_option( 'gr_engine_ipapi' ) ) {
		add_option( 'gr_engine_ipapi', 1, '', 'no' );
	}

	if ( false == get_option( 'gr_engine_geoip2' ) ) {
		add_option( 'gr_engine_geoip2', 1, '', 'no' );
	}


	set_transient( 'gr-activation-notice', true, 5 );
}

register_activation_hook( __FILE__, 'gr_activation' );


/* ------------------------------------------------------------------------ *
 * Deactivate the plugin
 * ------------------------------------------------------------------------ */
function gr_deactivation() {

}

register_deactivation_hook( __FILE__, 'gr_deactivation' );

/* ------------------------------------------------------------------------ *
 * Show notice after plugin activation
 * ------------------------------------------------------------------------ */
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


/* ------------------------------------------------------------------------ *
 * Connect JS and CSS
 * ------------------------------------------------------------------------ */
function gr_enqueue() {

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

        <h2>Geo Redirect Options</h2>

        <!-- Make a call to the WordPress function for rendering errors when settings are saved. -->
		<?php settings_errors(); ?>

		<?php
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'engine_options';
		?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=geo-redirect-options&tab=engine_options"
               class="nav-tab <?php echo $active_tab == 'engine_options' ? 'nav-tab-active' : ''; ?>">Engine
                Settings</a>
            <a href="?page=geo-redirect-options&tab=redirect_options"
               class="nav-tab <?php echo $active_tab == 'redirect_options' ? 'nav-tab-active' : ''; ?>">Redirect
                Settings</a>
        </h2>

        <form method="post" action="options.php">
			<?php
			if ( $active_tab == 'engine_options' ) {
				settings_fields( 'engine' );
				do_settings_sections( 'engine' );
			}
			if ( $active_tab == 'redirect_options' ) {
				settings_fields( 'redirect' );
				do_settings_sections( 'redirect' );
			}
			submit_button(); ?>
        </form>

    </div><!-- /.wrap -->
	<?php
}

function gr_initialize_options() {

	$path = plugin_dir_path( __FILE__ ) . 'DB' . DIRECTORY_SEPARATOR . 'SxGeo' . DIRECTORY_SEPARATOR . 'SxGeo';

	include_once( $path . '.php' );
	$SxGeo = new SxGeo( $path . '.dat' );


	$engine_settings = [
		[
			'id'    => 'gr_engine_sxgeo',
			'title' => 'SxGeo (local)',
			'label' => 'Use SxGeo library to determine user\'s country. For additional info about the library, check https://sypexgeo.net'
		],
		[
			'id'    => 'gr_engine_geoip2',
			'title' => 'GeoIp2 (local)',
			'label' => 'Use GeoIP2 library to determine user\'s country. For additional info about the library, check https://dev.maxmind.com/geoip/'
		],
		[
			'id'    => 'gr_engine_geoip',
			'title' => 'GEOIP DB (remote)',
			'label' => 'Use GEOIP DB API to determine user\'s country. For additional info about the library, check https://geoip-db.com'
		],
		[
			'id'    => 'gr_engine_ipapi',
			'title' => 'ip-api (remote)',
			'label' => 'Use ip-api API to determine user\'s country. For additional info about the library, check http://ip-api.com'
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
			register_setting( 'redirect', 'gr_redirect_' . $code );
		}
	}

}

add_action( 'admin_init', 'gr_initialize_options' );

/* ------------------------------------------------------------------------ *
 * Sections Callbacks
 * ------------------------------------------------------------------------ */

function gr_engine_settings_callback() {
	echo '<p>Select DB and/or API you want to use to determine visitor\'s country.</p>';
	echo '<p>If no determine engine will be selected below, all Redirect Settings will be skipped.</p>';
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

	$option_name = 'gr_redirect_' . $args[0];

	$html = '<input type="text" id="' . $option_name . '" name="' . $option_name . '" value="' . get_option( $option_name ) . '" class="regular-text code"/>';
	$html .= '<label for="' . $option_name . '"> Put here redirection URL for ' . $args[0] . '</label>';

	echo $html;

}


/* ------------------------------------------------------------------------ *
 * Redirect
 * ------------------------------------------------------------------------ */
add_action( 'template_redirect', function () {
	if ( ! is_user_logged_in() ) {
		$ip = $_SERVER['REMOTE_ADDR'];
		//$ip = '46.133.64.2'; // O mobile
		$ip = '85.209.44.123'; // I work computer
        //$ip = '178.133.73.146'; // I work mobile
		$redirect = null;

		// Checking country using local SxGeo
		if ( get_option( 'gr_engine_sxgeo' ) ) {
			include_once 'engine' . DIRECTORY_SEPARATOR . 'sxgeo.php';
			$country = sxgeo( $ip );
            if ($country) {
	            $url = get_option( 'gr_redirect_' . $country );
	            if ( wp_http_validate_url( $url ) ) {
		            $redirect = $url;
	            }
            }
		}

		// Checking country using local GeoIp2
		if ( !is_null($redirect) && get_option( 'gr_engine_geoip2' ) ) {
			include_once 'engine' . DIRECTORY_SEPARATOR . 'geoip2.php';
			$country = geoip2( $ip );
			if ($country) {
				$url = get_option( 'gr_redirect_' . $country );
				if ( wp_http_validate_url( $url ) ) {
					$redirect = $url;
				}
			}
		}

		// Checking country using remote GEOIP DB

		// Checking country using remote ip-api

		if ( !is_null($redirect) ) {
			wp_redirect( $redirect );
			exit;
		}
	}
} );

function in_country_code( $country ) {
	$path = plugin_dir_path( __FILE__ ) . 'DB' . DIRECTORY_SEPARATOR . 'SxGeo' . DIRECTORY_SEPARATOR . 'SxGeo';
	include_once( $path . '.php' );
	$SxGeo = new SxGeo( $path . '.dat' );
	$countries = $SxGeo->id2iso;

	if ($countries[0] == '')
		array_shift($countries);

    if ( in_array( $country, $countries ) )
		return true;

	return false;

}