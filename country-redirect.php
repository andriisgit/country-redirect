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

	    if ( !cntrd_is_bot( $ip ) ) {

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


/**
 * Check for bots
 *
 * @param string $ip IPv4
 *
 * @return bool
 */
function cntrd_is_bot( $ip ) {
	$list = [
		//Google
		'64.233.160.0-64.233.191.255',
		'66.102.0.0-66.102.15.255',
		'66.249.64.0-66.249.95.255',
		'72.14.192.0-72.14.255.255',
		'74.125.0.0-74.125.255.255',
		'209.85.128.0-209.85.255.255.',

		//YouTube
		'199.223.232.0-199.223.239.255',
		'207.223.160.0-207.223.175.255',
		'208.65.152.0-208.65.155.255',
		'208.117.224.0-208.117.255.255',
		'209.85.128.0-209.85.255.255',
		'216.58.192.0-216.58.223.255',
		'216.239.32.0-216.239.63.255',

		//Googlebot
		'64.68.90.1-64.68.90.255',

		//Other Google
		'35.190.247.0-35.190.247.255',
		'35.191.0.0-35.191.255.255',
		'108.177.8.0-108.177.15.255',
		'108.177.96.0-108.177.127.255',
		'130.211.0.0-130.211.3.255',
		'172.217.0.0-172.217.47.255',
		'172.217.128.0-172.217.223.255',
		'172.253.56.0-172.253.127.255',
		'173.194.0.0-173.194.255.255',

		//Bingbot
		'65.52.104.0-65.52.111.255',
		'65.55.24.0-65.55.24.255',
		'65.55.52.0-65.55.55.255',
		'65.55.213.0-65.55.217.255',
		'131.253.24.0-131.253.27.255',
		'131.253.46.0-131.253.46.0',
		'40.77.167.0-40.77.167.255',
		'199.30.27.0-199.30.27.255',
		'157.55.16.0-157.55.18.255',
		'157.55.32.0-157.55.48.255',
		'157.55.109.0-157.55.110.255',
		'157.56.92.0-157.56.95.255',
		'157.56.229.0-157.56.229.255',
		'199.30.16.0-199.30.16.255',
		'207.46.12.0-207.46.13.255',
		'207.46.192.0-207.46.192.255',
		'207.46.195.0-207.46.195.255',
		'207.46.199.0-207.46.199.255',
		'207.46.204.0-207.46.204.255',

		//Yandex
		'5.45.192.0-5.45.223.255',
		'5.255.192.0-5.255.255.255',
		'5.45.254.0-5.45.254.127',
		'37.9.109.0-37.9.109.255',
		'37.140.128.0-37.140.191.255',
		'77.88.0.0-77.88.63.255',
		'87.250.224.0-87.250.255.255',
		'93.158.136.48-93.158.136.63',
		'95.108.130.0-95.108.131.255',
		'95.108.192.0-95.108.255.255',
		'141.8.132.0-141.8.132.255',
		'213.180.223.192-213.180.223.255',

		//Mozilla/5.0 (compatible; AhrefsBot/6.1; +http://ahrefs.com/robot/)
		'54.36.148.0-54.36.150.255',
		'95.154.122.0-95.154.123.255',
		'195.154.126.0-195.154.127.255',

		//ia_archiver (+http://www.alexa.com/site/help/webmasters; crawler@alexa.com)
		'204.236.235.245',
		'75.101.186.145',

		//alexa v0.1.4 (http://www.openwebspider.org/)
		'23.22.146.59',
		'50.136.243.177',
		'54.91.225.12',
		'54.147.38.38',
		'54.205.166.45',
		'54.237.161.186',
		'54.245.51.89',
		'82.239.104.246',
		'107.21.78.52',
		'107.20.103.16',
		'107.20.0.179',

		//Mozilla/5.0 (compatible; discobot/2.0;
		'72.94.249.34-72.94.249.38',

		//DuckDuckBot/1.1; (+http://duckduckgo.com/duckduckbot.html)
		'107.21.1.8',
		'107.23.49.117',
		'54.208.80.140',
		'107.23.45.196'
	];

	$test = explode('.', $ip);
	if ( count($test) != 4 ) return false;

	foreach ($list as $line) {
		$result = false;
		//$line = str_replace('–', '-', $line);
		//$line = str_replace(',', '.', $line);
		//$line = str_replace(' ', '', $line);
		//$line = str_replace('	', '', $line);

		/* ------------------------------------------------------------------------ *
		 * Parse the range
		 * ------------------------------------------------------------------------ */
		if (strpos($line, '-') !== false) {

			$d = $range1 = $range2 = [];
			$conjunction = array_fill(0, 4, false);
			$d = explode('-', $line);
			$range1 = explode('.', $d[0]);
			$range2 = explode('.', $d[1]);

			// conjunction
			for ($i = 0; $i < 4; $i++) {
				// check if previous false
				if ($i > 0 && $conjunction[$i - 1] == false) {
					break;
				}

				if ($range1[$i] == '*' && $range2[$i] == '*') {
					$conjunction[$i] = true;
					continue;
				}

				if (!isset($test[$i]) || !is_numeric($test[$i])) {
					continue;
				}

				if ($range1[$i] == '*') {
					if ($test[$i] <= $range2[$i]) {
						$conjunction[$i] = true;
					}
				} elseif ($range2[$i] == '*') {
					if ($test[$i] >= $range1[$i]) {
						$conjunction[$i] = true;
					}
				} else {
					if ($range1[$i] <= $test[$i] && $test[$i] <= $range2[$i]) {
						$conjunction[$i] = true;
					}
				}
			}

			if ($conjunction[0] && $conjunction[1] && $conjunction[2] && $conjunction[3]) {
				$result = true;
				break;
			} else {
				continue;
			}

		}


		/* ------------------------------------------------------------------------ *
		 * Parse the single address
		 * ------------------------------------------------------------------------ */

		$mask = explode('.', $line);
		$conjunction = array_fill(0, 4, false);

		for ($i = 0; $i < 4; $i++) {
			// check if previous false
			if ($i > 0 && $conjunction[$i - 1] == false) {
				break;
			}

			if ($mask[$i] == '*') {
				$conjunction[$i] = true;
				continue;
			}

			if (!isset($test[$i]) || !is_numeric($test[$i])) {
				continue;
			}

			if ($mask[$i] == $test[$i]) {
				$conjunction[$i] = true;
			}
		}

		if ($conjunction[0] && $conjunction[1] && $conjunction[2] && $conjunction[3]) {
			$result = true;
			break;
		}

	}

	return $result;
}