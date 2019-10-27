<?php
/*
Plugin Name: country-redirect
Plugin URI: https://github.com/andriisgit/country-redirect
Description: Simple to use free plugin for redirection depending visitor's country
Version: 1.0
Text Domain: grl10n
Domain Path: /lang/
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------ *
 * Activate the plugin
 * ------------------------------------------------------------------------ */
function cr_activation() {

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

	set_transient( 'cr-activation-notice', true, 5 );
}

register_activation_hook( __FILE__, 'cr_activation' );


/* ------------------------------------------------------------------------ *
 * Deactivate the plugin
 * ------------------------------------------------------------------------ */
function cr_deactivation() {
	// empty
}

register_deactivation_hook( __FILE__, 'cr_deactivation' );

/* ------------------------------------------------------------------------ *
 * Show notice after plugin activation
 * ------------------------------------------------------------------------ */
function cr_activation_notice() {
	if ( get_transient( 'cr-activation-notice' ) ) {
		?>
        <div class="updated notice is-dismissible">
            <p>Country Redirect <?php _e( 'activated', 'grl10n' ) ?>.</p>
            <p><?php _e( 'You can set redirection', 'grl10n' ) ?> <a href="options-general.php?page=country-redirect-options"><?php _e( 'Settings', 'grl10n' ) ?> - Country Redirect</a></p>
        </div>
		<?php
		/* Delete transient, only display this notice once. */
		delete_transient( 'cr-activation-notice' );
	}
}

add_action( 'admin_notices', 'cr_activation_notice' );


/* ------------------------------------------------------------------------ *
 * Localization
 * ------------------------------------------------------------------------ */
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'grl10n', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
} );


/* ------------------------------------------------------------------------ *
 * Connect JS and CSS
 * ------------------------------------------------------------------------ */
function cr_enqueue() {
	// empty
}

add_action( 'wp_enqueue_scripts', 'cr_enqueue' );


add_action( 'admin_menu', function () {
	add_options_page( 'Country Redirect', 'Country Redirect', 'manage_options', 'country-redirect-options', 'cr_admin_page' );
} );

function cr_admin_page() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>

    <div class="wrap">

        <h2>Country Redirect <?php _e( 'Settings', 'grl10n' ) ?></h2>

		<?php
		// Make a call to the WordPress function for rendering errors when settings are saved.
		settings_errors();

		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'engine_options';
		?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=country-redirect-options&tab=engine_options"
               class="nav-tab <?php echo $active_tab == 'engine_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Engine Settings', 'grl10n' ) ?></a>
            <a href="?page=country-redirect-options&tab=redirect_options"
               class="nav-tab <?php echo $active_tab == 'redirect_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Redirect Settings', 'grl10n' ) ?></a>
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

function cr_initialize_options() {

	$path = plugin_dir_path( __FILE__ ) . 'DB' . DIRECTORY_SEPARATOR . 'SxGeo' . DIRECTORY_SEPARATOR . 'SxGeo';

	$text1 = __( 'Use ', 'grl10n' );
	$text2 = __( ' library to determine visitor\'s country. For additional info about the library, check ', 'grl10n' );
	$text3 = __( ' API to determine visitor\'s country. For additional info about the API, check ', 'grl10n' );

	include_once( $path . '.php' );
	$SxGeo = new SxGeo( $path . '.dat' );


	$engine_settings = [
		[
			'id'    => 'gr_engine_sxgeo',
			'title' => 'SxGeo (local)',
			'label' => $text1 . 'SxGeo' . $text2 . 'https://sypexgeo.net'
		],
		[
			'id'    => 'gr_engine_geoip2',
			'title' => 'GeoIp2 (local)',
			'label' => $text1 . 'GeoIP2' . $text2 . 'https://dev.maxmind.com/geoip/'
		],
		[
			'id'    => 'gr_engine_geoip',
			'title' => 'GEOIP DB (remote)',
			'label' => $text1 . 'GEOIP DB' . $text3 . 'https://geoip-db.com'
		],
		[
			'id'    => 'gr_engine_ipapi',
			'title' => 'ip-api (remote)',
			'label' => $text1 . 'ip-api' . $text3 . 'http://ip-api.com'
		]
	];


	add_settings_section(
		'cr_engine_settings',
		__( 'Engine Settings', 'grl10n' ),
		'cr_engine_settings_callback',
		'engine'
	);

	add_settings_section(
		'cr_redirect_settings',
		__( 'Redirect Settings', 'grl10n' ),
		'cr_redirect_settings_callback',
		'redirect'
	);

	foreach ( $engine_settings as $setting ) {
		add_settings_field( $setting['id'], $setting['title'], 'cr_toggle_engine', 'engine', 'cr_engine_settings', [
			$setting['id'],
			$setting['label']
		] );
		register_setting( 'engine', $setting['id'] );
	}

	foreach ( $SxGeo->id2iso as $code ) {
		if ( ! empty( $code ) ) {
			add_settings_field( 'gr_redirect_' . $code, $code, 'cr_toggle_redirect', 'redirect', 'cr_redirect_settings', [ $code ] );
			register_setting( 'redirect', 'gr_redirect_' . $code, 'cr_validate_url' );
		}
	}

}

add_action( 'admin_init', 'cr_initialize_options' );

/* ------------------------------------------------------------------------ *
 * Sections Callbacks
 * ------------------------------------------------------------------------ */

function cr_engine_settings_callback() {
	echo '<p>' . __( 'Select DB and/or API you want to use to determine visitor\'s country', 'grl10n' ) . '.</p>';
	echo '<p>' . __( 'If no determine engine will be selected below, all Redirect Settings will be skipped', 'grl10n' ) . '.</p>';
}

function cr_redirect_settings_callback() {
	echo '<p>' . __( 'Assign desired redirection to country code. Just enter the full URL into textbox', 'grl10n' ) . '.</p>';
	echo '<p>' . __( 'To see complete list of codes, check Wikipedia page', 'grl10n' ) . ': https://en.wikipedia.org/wiki/ISO_3166-1</p>';
}

/* ------------------------------------------------------------------------ *
 * Validating URL
 * ------------------------------------------------------------------------ */
function cr_validate_url( $input ) {
	$output = esc_url_raw( $input );

	return apply_filters( 'cr_validate_url', $output, $input );
}

/* ------------------------------------------------------------------------ *
 * Field Callbacks
 * ------------------------------------------------------------------------ */

function cr_toggle_engine( $args ) {

	$html = '<input type="checkbox" id="' . $args[0] . '" name="' . $args[0] . '" value="1" ' . checked( 1, get_option( $args[0] ), false ) . '/>';
	$html .= '<label for="' . $args[0] . '"> ' . $args[1] . '</label>';

	echo $html;
}

function cr_toggle_redirect( $args ) {

	$option_name = 'gr_redirect_' . $args[0];

	$html = '<input type="text" id="' . $option_name . '" name="' . $option_name . '" value="' . get_option( $option_name ) . '" class="regular-text code"/>';
	$html .= '<label for="' . $option_name . '"> ' . __( 'Put here redirection URL for ', 'grl10n' ) . $args[0] . '</label>';

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

		// Checking country using local SxGeo
		if ( get_option( 'gr_engine_sxgeo' ) ) {
			include_once 'engine' . DIRECTORY_SEPARATOR . 'sxgeo.php';
			$country = sxgeo( $ip );
			if ( $country ) {
				$url = get_option( 'gr_redirect_' . $country );
				if ( wp_http_validate_url( $url ) ) {
					$redirect = $url;
				}
			}
		}

		// Checking country using local GeoIp2
		if ( is_null( $redirect ) && get_option( 'gr_engine_geoip2' ) ) {
			include_once 'engine' . DIRECTORY_SEPARATOR . 'geoip2.php';
			$country = geoip2( $ip );
			if ( $country ) {
				$url = get_option( 'gr_redirect_' . $country );
				if ( wp_http_validate_url( $url ) ) {
					$redirect = $url;
				}
			}
		}

		// Checking country using remote GEOIP DB
		if ( is_null( $redirect ) && get_option( 'gr_engine_geoip' ) ) {
			include_once 'engine' . DIRECTORY_SEPARATOR . 'geoip.php';
			$country = geoip( $ip );
			if ( $country ) {
				$url = get_option( 'gr_redirect_' . $country );
				if ( wp_http_validate_url( $url ) ) {
					$redirect = $url;
				}
			}
		}

		// Checking country using remote ip-api
		if ( is_null( $redirect ) && get_option( 'gr_engine_ipapi' ) ) {
			include_once 'engine' . DIRECTORY_SEPARATOR . 'ipapi.php';
			$country = ipapi( $ip );
			if ( $country ) {
				$url = get_option( 'gr_redirect_' . $country );
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
function in_country_code( $country ) {
	$path = plugin_dir_path( __FILE__ ) . 'DB' . DIRECTORY_SEPARATOR . 'SxGeo' . DIRECTORY_SEPARATOR . 'SxGeo';
	include_once( $path . '.php' );
	$SxGeo = new SxGeo( $path . '.dat' );
	$countries = $SxGeo->id2iso;

	if ( $countries[0] == '' ) {
		array_shift( $countries );
	}

	if ( in_array( $country, $countries ) ) {
		return true;
	}

	return false;
}
