<?php
/*
Plugin Name: country-redirect
Plugin URI: https://github.com/andriisgit/country-redirect
Description: Simple to use free and safety plugin for redirection depending visitor's country
Version: 1.0
Text Domain: cntrdl10n
Domain Path: /lang/
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* ------------------------------------------------------------------------ *
 * Connect JS and CSS
 * ------------------------------------------------------------------------ */
add_action( 'wp_enqueue_scripts', function () {
	// empty
});


/* ------------------------------------------------------------------------ *
 * Activate the plugin
 * ------------------------------------------------------------------------ */
register_activation_hook( __FILE__, function () {

	if ( false == get_option( 'cntrd_engine_sxgeo' ) ) {
		add_option( 'cntrd_engine_sxgeo', 1, '', 'no' );
	}

	if ( false == get_option( 'cntrd_engine_ipapi' ) ) {
		add_option( 'cntrd_engine_ipapi', 0, '', 'no' );
	}

	if ( false == get_option( 'cntrd_engine_geoip2' ) ) {
		add_option( 'cntrd_engine_geoip2', 1, '', 'no' );
	}

	set_transient( 'cntrd-activation-notice', true, 5 );
});


/* ------------------------------------------------------------------------ *
 * Deactivate the plugin
 * ------------------------------------------------------------------------ */
register_deactivation_hook( __FILE__, function () {
	// empty
});


/* ------------------------------------------------------------------------ *
 * Show notice after plugin activation
 * ------------------------------------------------------------------------ */
function cntrd_activation_notice() {
	if ( get_transient( 'cntrd-activation-notice' ) ) {
		?>
        <div class="updated notice is-dismissible">
            <p>Country Redirect <?php _e( 'activated', 'cntrdl10n' ) ?>.</p>
            <p><?php _e( 'You can set redirection', 'cntrdl10n' ) ?> <a href="options-general.php?page=country-redirect-options"><?php _e( 'Settings', 'cntrdl10n' ) ?> - Country Redirect</a></p>
        </div>
		<?php
		/* Delete transient, only display this notice once. */
		delete_transient( 'cntrd-activation-notice' );
	}
}

add_action( 'admin_notices', 'cntrd_activation_notice' );


/* ------------------------------------------------------------------------ *
 * Localization
 * ------------------------------------------------------------------------ */
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'cntrdl10n', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
} );


/* ------------------------------------------------------------------------ *
 * Add menu item "Country Redirect" under Settings admin menu
 * ------------------------------------------------------------------------ */
add_action( 'admin_menu', function () {
	add_options_page( 'Country Redirect', 'Country Redirect', 'manage_options', 'country-redirect-options', 'cntrd_admin_page' );
} );

function cntrd_admin_page() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>

    <div class="wrap">

        <h2>Country Redirect <?php _e( 'Settings', 'cntrdl10n' ) ?></h2>

		<?php
		// Make a call to the WordPress function for rendering errors when settings are saved.
		settings_errors();

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'engine_options';
		?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=country-redirect-options&tab=engine_options"
               class="nav-tab <?php echo $active_tab == 'engine_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Engine Settings', 'cntrdl10n' ) ?></a>
            <a href="?page=country-redirect-options&tab=redirect_options"
               class="nav-tab <?php echo $active_tab == 'redirect_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Redirect Settings', 'cntrdl10n' ) ?></a>
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

function cntrd_initialize_options() {

	$path = plugin_dir_path( __FILE__ ) . 'DB' . DIRECTORY_SEPARATOR . 'SxGeo' . DIRECTORY_SEPARATOR . 'SxGeo';

	include_once( $path . '.php' );
	$SxGeo = new CountryRedirect\SxGeo( $path . '.dat' );

	$engine_settings = [
		[
			'id'    => 'cntrd_engine_sxgeo',
			'title' => 'Sypex Geo (local)',
			'label' => __('Check to use the local "Sypex Geo" database for determining visitor\'s country. It\'s free for using both commercial and non commercial purposes', 'cntrdl10n') . ': https://sypexgeo.net/ru/faq/'
		],
		[
			'id'    => 'cntrd_engine_geoip2',
			'title' => 'GeoLite2 Free (local)',
			'label' => __('Check to use the local "GeoLite2 Free" database for determining visitor\'s country. Database is distributed under the Creative Commons Attribution-ShareAlike 4.0 International License. More information can be found at', 'cntrdl10n') . ' https://dev.maxmind.com/geoip/geoip2/geolite2/'
		],
		[
			'id'    => 'cntrd_engine_ipapi',
			'title' => 'ip-api (remote)',
			'label' => __('Check to use free for non commercial use "ip-api" engine for determining visitor\'s country. Get more information and obtain commercial license', 'cntrdl10n') . ': https://ip-api.com/'
		]
	];

	add_settings_section(
		'cntrd_engine_settings',
		__( 'Engine Settings', 'cntrdl10n' ),
		'cntrd_engine_settings_callback',
		'engine'
	);

	add_settings_section(
		'cntrd_redirect_settings',
		__( 'Redirect Settings', 'cntrdl10n' ),
		'cntrd_redirect_settings_callback',
		'redirect'
	);

	foreach ( $engine_settings as $setting ) {
		add_settings_field( $setting['id'], $setting['title'], 'cntrd_toggle_engine', 'engine', 'cntrd_engine_settings', [
			$setting['id'],
			$setting['label']
		] );
		register_setting( 'engine', $setting['id'] );
	}

	foreach ( $SxGeo->id2iso as $code ) {
		if ( ! empty( $code ) ) {
			add_settings_field( 'cntrd_redirect_' . $code, $code, 'cntrd_toggle_redirect', 'redirect', 'cntrd_redirect_settings', [ $code ] );
			register_setting( 'redirect', 'cntrd_redirect_' . $code, 'cntrd_validate_url' );
		}
	}

}

add_action( 'admin_init', 'cntrd_initialize_options' );


/* ------------------------------------------------------------------------ *
 * Sections Callbacks
 * ------------------------------------------------------------------------ */

function cntrd_engine_settings_callback() {
	echo '<p>' . __( 'Select DB and/or API you want to use to determine visitor\'s country', 'cntrdl10n' ) . '.</p>';
	echo '<p>' . __( 'If no determine engine will be selected below, all Redirect Settings will be skipped', 'cntrdl10n' ) . '.</p>';
}

function cntrd_redirect_settings_callback() {
	echo '<p>' . __( 'Assign desired redirection to country code. Just enter the full URL into textbox', 'cntrdl10n' ) . '.</p>';
	echo '<p>' . __( 'To see complete list of codes, check Wikipedia page', 'cntrdl10n' ) . ': https://en.wikipedia.org/wiki/ISO_3166-1</p>';
}


/* ------------------------------------------------------------------------ *
 * Validating URL
 * ------------------------------------------------------------------------ */
function cntrd_validate_url( $input ) {
	$output = esc_url_raw( $input );

	return apply_filters( 'cntrd_validate_url', $output, $input );
}


/* ------------------------------------------------------------------------ *
 * Field Callbacks
 * ------------------------------------------------------------------------ */

function cntrd_toggle_engine( $args ) {

	$html = '<input type="checkbox" id="' . $args[0] . '" name="' . $args[0] . '" value="1" ' . checked( 1, get_option( $args[0] ), false ) . '/>';
	$html .= '<label for="' . $args[0] . '"> ' . $args[1] . '</label>';

	echo $html;
}

function cntrd_toggle_redirect( $args ) {

	$option_name = 'cntrd_redirect_' . $args[0];

	$html = '<input type="text" id="' . $option_name . '" name="' . $option_name . '" value="' . esc_url( get_option( $option_name ) ) . '" class="regular-text code"/>';
	$html .= '<label for="' . $option_name . '"> ' . __( 'Put here redirection URL for ', 'cntrdl10n' ) . $args[0] . '</label>';

	echo $html;
}


/* ------------------------------------------------------------------------ *
 * Redirect
 * ------------------------------------------------------------------------ */
add_action( 'template_redirect', function () {
	// redirect only NOT logged in users
    if ( ! is_user_logged_in() ) {
		$ip = $_SERVER['REMOTE_ADDR'];
		$redirect = null;

		// Checking country using local Sypex Geo
		if ( get_option( 'cntrd_engine_sxgeo' ) ) {
			include_once 'engine' . DIRECTORY_SEPARATOR . 'sxgeo.php';
			$country = cntrd_sxgeo( $ip );
			if ( $country ) {
				$url = get_option( 'cntrd_redirect_' . $country );
				if ( wp_http_validate_url( $url ) ) {
					$redirect = $url;
				}
			}
		}

		// Checking country using local GeoLite2 Free
		if ( is_null( $redirect ) && get_option( 'cntrd_engine_geoip2' ) ) {
			include_once 'engine' . DIRECTORY_SEPARATOR . 'geoip2.php';
			$country = cntrd_geoip2( $ip );
			if ( $country ) {
				$url = get_option( 'cntrd_redirect_' . $country );
				if ( wp_http_validate_url( $url ) ) {
					$redirect = $url;
				}
			}
		}

		// Checking country using remote ip-api
		if ( is_null( $redirect ) && get_option( 'cntrd_engine_ipapi' ) ) {
			include_once 'engine' . DIRECTORY_SEPARATOR . 'ipapi.php';
			$country = cntrd_ipapi( $ip );
			if ( $country ) {
				$url = get_option( 'cntrd_redirect_' . $country );
				if ( wp_http_validate_url( $url ) ) {
					$redirect = $url;
				}
			}
		}

		if ( ! is_null( $redirect ) ) {
			wp_redirect( $redirect );
			exit;
		}
	}
} );

/**
 * Check is country code presents in the global country code list
 *
 * @param string $country Two letter country code
 *
 * @return bool
 */
function cntrd_in_country_code( $country ) {
	$path = plugin_dir_path( __FILE__ ) . 'DB' . DIRECTORY_SEPARATOR . 'SxGeo' . DIRECTORY_SEPARATOR . 'SxGeo';
	include_once( $path . '.php' );
	try {
		$SxGeo = new CountryRedirect\SxGeo( $path . '.dat' );
		$countries = $SxGeo->id2iso;
	} catch ( Exception $e ) {
	    return false;
    }

	if ( $countries[0] == '' ) {
		array_shift( $countries );
	}

	if ( in_array( $country, $countries ) ) {
		return true;
	}

	return false;
}
