<?php
/*
Plugin Name: country-redirect
Plugin URI: https://wordpress.org/plugins/country-redirect/
Description: Simple to use free and safety plugin for redirection depending visitor's country
Version: 1.3.2
Text Domain: cntrdl10n
Domain Path: /lang/
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* ------------------------------------------------------------------------ *
 * Activate the plugin
 * ------------------------------------------------------------------------ */
register_activation_hook( __FILE__, function () {

    cntrd_set_engine_options();
    cntrd_set_whitelist_options();

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
            <a href="?page=country-redirect-options&tab=whitelist_options"
               class="nav-tab <?php echo $active_tab == 'whitelist_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Whitelist Settings', 'cntrdl10n' ) ?></a>
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
            if ( $active_tab == 'whitelist_options' ) {
                settings_fields( 'cntrd_whitelist' );
                do_settings_sections( 'cntrd_whitelist' );
            }
            submit_button();
            ?>
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
    
    add_settings_section(
        'cntrd_whitelist_settings',
        __( 'Whitelist Settings', 'cntrdl10n' ),
        'cntrd_whitelist_settings_callback',
        'cntrd_whitelist'
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
            register_setting( 'redirect', 'cntrd_redirect_' . $code, [ 'sanitize_callback' => 'cntrd_validate_url' ] );
        }
    }
    
    add_settings_field('cntrd_whitelist_ip', 'IP whitelist ', 'cntrd_toggle_whitelist_ip', 'cntrd_whitelist', 'cntrd_whitelist_settings', ['cntrd_whitelist_ip']);
    register_setting('cntrd_whitelist', 'cntrd_whitelist_ip', [ 'sanitize_callback' => 'cntrd_sanitize_whitelist' ]);

    add_settings_field('cntrd_whitelist_bot', 'Bot whitelist ', 'cntrd_toggle_whitelist_bot', 'cntrd_whitelist', 'cntrd_whitelist_settings', ['cntrd_whitelist_bot']);
    register_setting('cntrd_whitelist', 'cntrd_whitelist_bot');

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

    cntrd_change_to_no_redirect_autoload();
}

function cntrd_whitelist_settings_callback() {
    echo '<p>' . __( 'Specify the IPs (IPv4) for which the redirect will NOT be applied', 'cntrdl10n' ) . '.</p>';
    echo '<p>' . __( 'You can add one IP per line or use ";" or "," as a delimiter', 'cntrdl10n' ) . '.</p>';

    cntrd_set_whitelist_options();
}


/* ------------------------------------------------------------------------ *
 * Validating, Sanitizing, Filtering
 * ------------------------------------------------------------------------ */
function cntrd_validate_url( $input ) {
    $output = esc_url_raw( $input );

    return apply_filters( 'cntrd_validate_url', $output, $input );
}

function cntrd_sanitize_whitelist( $input ) {
    $output = esc_textarea( $input );
    $output = str_replace("\r\n", ';', $output);
    $output = str_replace("\n", ';', $output);
    $output = str_replace("\r", ';', $output);
    $output = str_replace(',', ';', $output);
    $output = str_replace(' ', ';', $output);
    $output = str_replace('/', ';', $output);
    $tmp = explode(';', $output);
    $array = [];
    foreach ($tmp as $adr) {
        if (trim($adr) != '') {
            array_push($array, trim($adr));
        }
    }
    $output = json_encode($array);

    return apply_filters( 'cntrd_sanitize_whitelist', $output, $input );
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

    $html = '<input type="text" id="' . $option_name . '" name="' . $option_name . '" value="';
    $html.= esc_url( get_option( $option_name, '' ) ) . '" class="regular-text code"/>';
    $html .= '<label for="' . $option_name . '"> ' . __( 'Put here redirection URL for ', 'cntrdl10n' ) . $args[0] . '</label>';

    echo $html;
}

function cntrd_toggle_whitelist_ip( $args ) {
    $whitelist = get_option( $args[0], '' );
    if ($whitelist != '') {
        $array = json_decode($whitelist);
        $array = array_values($array);
        $whitelist = implode(PHP_EOL, $array);
    }
    $html = '<textarea id="' . $args[0] . '" name="' . $args[0] . '" autocomplete="off" rows="5" cols="30">';
    $html .= $whitelist . '</textarea>';
    echo $html;
}

function cntrd_toggle_whitelist_bot( $args ) {
    $s = __( 'Check to redirect known bots too', 'cntrdl10n' );
    $s .= ' (AhrefsBot, AlehaCrawler, Bingbot, DiscoBot, DuckDuckBot, GoogleBots, Gtmetrix, loader.io, Mediaskunk, Pingdom, WebHint, Woorank, YandexBot)';
    $html = '<input type="checkbox" id="' . $args[0] . '" name="' . $args[0] . '" value="1" ' . checked( 1, get_option( $args[0] ), false ) . '/>';
    $html .= '<label for="' . $args[0] . '"> ' . $s . '</label>';
    echo $html;
}

/* ------------------------------------------------------------------------ *
 * Redirect
 * ------------------------------------------------------------------------ */
add_action( 'template_redirect', function () {
    // redirect only NOT logged in users
    if ( is_user_logged_in() ) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'];

    if (get_option('cntrd_whitelist_bot') && cntrd_is_bot($ip)) {
        return;
    }

    // Check if the IP is in user's IP whitelist range
    if ( $whitelist = get_option('cntrd_whitelist_ip') ) {
        $whitelist = json_decode($whitelist);
        if ( in_array($ip, $whitelist) ) {
            return;
        }
    }

    $redirect = null;

    // Checking country using local Sypex Geo
    if ( get_option( 'cntrd_engine_sxgeo' ) ) {
        include_once 'engine' . DIRECTORY_SEPARATOR . 'sxgeo.php';
        if ( $country = cntrd_sxgeo( $ip ) ) {
            if ( $opt = get_option( 'cntrd_redirect_' . $country ) ) {
                $redirect = wp_sanitize_redirect( esc_url_raw($opt) );
            }
        }
    }

    // Checking country using local GeoLite2 Free
    if ( is_null( $redirect ) && get_option( 'cntrd_engine_geoip2' ) ) {
        include_once 'engine' . DIRECTORY_SEPARATOR . 'geoip2.php';
        if ( $country = cntrd_geoip2( $ip ) ) {
            if ( $opt = get_option( 'cntrd_redirect_' . $country ) ) {
                $redirect = wp_sanitize_redirect( esc_url_raw($opt) );
            }
        }
    }

    // Checking country using remote ip-api
    if ( is_null( $redirect ) && get_option( 'cntrd_engine_ipapi' ) ) {
        include_once 'engine' . DIRECTORY_SEPARATOR . 'ipapi.php';
        if ( $country  = cntrd_ipapi( $ip ) ) {
            if ( $opt = get_option( 'cntrd_redirect_' . $country ) ) {
                $redirect = wp_sanitize_redirect( esc_url_raw($opt) );
            }
        }
    }

    if ( ! is_null( $redirect ) ) {
        wp_redirect( $redirect );
        exit;
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

    if ( count(explode('.', $ip)) != 4 ) {
        return false;
    }

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
        '5.45.254.0-5.45.254.255',
        '5.255.192.0-5.255.255.255',
        '5.255.253.0-5.255.253.255',
        '37.9.84.253',
        '37.9.109.0-37.9.109.255',
        '37.9.115.0-37.9.115.255',
        '37.140.128.0-37.140.191.255',
        '77.88.0.0-77.88.63.255',
        '84.201.146.0-84.201.149.255',
        '87.250.224.0-87.250.255.255',
        '93.158.136.48-93.158.136.63',
        '93.158.147.0-93.158.148.255',
        '93.158.151.0-93.158.151.255',
        '93.158.153.0',
        '93.158.166.23',
        '95.108.128.0-95.108.128.255',
        '95.108.130.0-95.108.131.255',
        '95.108.138.0-95.108.138.255',
        '95.108.150.0-95.108.151.255',
        '95.108.156.0-95.108.156.255',
        '95.108.158.0-95.108.158.255',
        '95.108.188.128-95.108.188.255',
        '95.108.192.0-95.108.255.255',
        '95.108.234.0-95.108.234.255',
        '95.108.246.252',
        '95.108.248.0-95.108.248.255',
        '100.43.80.0-100.43.81.255',
        '100.43.85.0-100.43.85.255',
        '100.43.90.0-100.43.91.255',
        '130.193.62.0-130.193.62.255',
        '141.8.132.0-141.8.132.255',
        '141.8.142.4',
        '141.8.142.27',
        '141.8.142.33',
        '141.8.142.47',
        '141.8.142.173',
        '141.8.153.0-141.8.153.255',
        '141.8.183.17',
        '141.8.183.204',
        '141.8.183.214',
        '141.8.188.41',
        '141.8.188.48',
        '178.154.171.107',
        '178.154.171.112',
        '178.154.171.145',
        '178.154.162.29',
        '178.154.165.0-178.154.166.255',
        '178.154.173.29 ',
        '178.154.200.158',
        '178.154.202.0-178.154.202.255',
        '178.154.203.251',
        '178.154.205.0-178.154.205.255',
        '178.154.211.250',
        '178.154.239.0-178.154.239.255',
        '178.154.243.0-178.154.243.255',
        '199.21.99.0-199.21.99.255',
        '213.180.203.42',
        '213.180.203.106',
        '213.180.203.178',
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
        '107.23.45.196',

        //webhint.io
        '23.96.53.166',
        '64.41.200.103',

        //Pingdom Website Speed Test
        '18.184.113.24',
        '35.158.65.6',
        '54.250.168.253',
        '18.130.75.90',
        '18.206.99.105',
        '18.234.175.206',
        '54.153.66.233',
        '13.210.70.20',
        '18.228.235.244',
        '5.172.196.188',
        '13.232.220.164',
        '23.22.2.46',
        '23.83.129.219',
        '23.111.152.74',
        '23.111.159.174',
        '27.122.14.7',
        '37.252.231.50',
        '43.225.198.122',
        '43.229.84.12',
        '46.20.45.18',
        '46.165.195.139',
        '46.246.122.10',
        '50.16.153.186',
        '50.23.28.35',
        '52.0.204.16',
        '52.24.42.103',
        '52.48.244.35',
        '52.52.34.158',
        '52.52.95.213',
        '52.52.118.192',
        '52.57.132.90',
        '52.59.46.112',
        '52.59.147.246',
        '52.62.12.49',
        '52.63.142.2',
        '52.63.164.147',
        '52.63.167.55',
        '52.67.148.55',
        '52.73.209.122',
        '52.89.43.70',
        '52.194.115.181',
        '52.197.31.124',
        '52.197.224.235',
        '52.198.25.184',
        '52.201.3.199',
        '52.209.34.226',
        '52.209.186.226',
        '52.210.232.124',
        '54.68.48.199',
        '54.70.202.58',
        '54.94.206.111',
        '64.237.49.203',
        '64.237.55.3',
        '66.165.229.130',
        '66.165.233.234',
        '72.46.130.18',
        '72.46.130.44',
        '76.72.167.90',
        '76.72.167.154',
        '76.72.172.208',
        '76.164.234.106',
        '76.164.234.170',
        '81.17.62.205',
        '82.103.136.16',
        '82.103.139.165',
        '82.103.145.126',
        '83.170.113.210',
        '85.195.116.134',
        '89.163.146.247',
        '89.163.242.206',
        '94.75.211.73',
        '94.75.211.74',
        '94.247.174.83',
        '95.141.32.46',
        '96.47.225.18',
        '103.47.211.210',
        '104.129.24.154',
        '104.129.30.18',
        '109.123.101.103',
        '138.219.43.186',
        '148.72.170.233',
        '148.72.171.17',
        '151.106.52.134',
        '162.208.48.94',
        '162.218.67.34',
        '162.253.128.178',
        '168.1.92.58',
        '169.51.2.22',
        '169.56.174.147',
        '172.241.112.86',
        '173.248.147.18',
        '173.254.206.242',
        '174.34.156.130',
        '175.45.132.20',
        '178.255.152.2',
        '178.255.153.2',
        '178.255.155.2',
        '179.50.12.212',
        '184.75.208.210',
        '184.75.209.18',
        '184.75.210.90',
        '184.75.210.226',
        '184.75.214.66',
        '184.75.214.98',
        '185.39.146.214',
        '185.39.146.215',
        '185.70.76.23',
        '185.93.3.92',
        '185.136.156.82',
        '185.152.65.167',
        '185.180.12.65',
        '185.246.208.82',
        '188.172.252.34',
        '199.87.228.66',
        '201.33.21.5',
        '207.244.80.239',
        '209.58.139.193',
        '209.58.139.194 ',

        //gtmetrix.com
        '204.187.14.70-204.187.14.78',
        '199.10.31.194-199.10.31.196',
        '13.70.66.20',
        '13.85.80.124',
        '13.84.43.227',
        '13.84.146.132',
        '13.84.146.226',
        '40.74.254.217',
        '52.66.75.147',
        '52.147.27.127',
        '52.175.28.116',
        '104.214.75.209',
        '172.255.61.34-172.255.61.40',
        '191.235.85.154',
        '191.235.86.0',
        '208.70.247.157',

        //loader.io
        '50.17.172.93',
        '174.129.131.187',
        '54.235.232.26',
        '50.16.116.223',
        '50.16.165.70',
        '54.226.38.198',
        '50.16.104.200',
        '54.243.7.98',
        '23.23.71.32',
        '54.234.136.196',
		
        //mediaskunk.ru - Web Meta Info
        '87.242.64.151',

        //woorank.com
        '3.234.92.30',
        '52.70.79.210',
        '54.88.26.91',
        '66.102.9.67',

        //App Search API Validation Tool
        '17.58.98.1-17.58.98.255'

    ];

    $ip_int = ip2long($ip);

    foreach ( $list as $line ) {

        //$line = str_replace('–', '-', $line);
        //$line = str_replace(',', '.', $line);
        //$line = str_replace(' ', '', $line);
        //$line = str_replace('	', '', $line);

        if ( strpos($line, '-') !== false ) {
            /* --------------------------------------------------------------- *
             * Compare the range
             * ---------------------------------------------------------------*/
            $d = explode( '-', $line );
            $range1 = ip2long( $d[0] );
            $range2 = ip2long( $d[1] );
            if ( $ip_int >= $range1 && $ip_int <= $range2 ) {
                return true;
            }
        } else {
            /* --------------------------------------------------------------- *
             * Compare the single address
             * -------------------------------------------------------------- */
            if ( $ip == $line ) {
                return true;
            }
        }

    }

    return false;
}

/**
 * Activate and initial setup engine plugin's settings
 */
function cntrd_set_engine_options() {
    if ( !get_option( 'cntrd_engine_sxgeo' ) ) {
        add_option( 'cntrd_engine_sxgeo', 1, '', 'no' );
    }

    if ( !get_option( 'cntrd_engine_ipapi' ) ) {
        add_option( 'cntrd_engine_ipapi', 0, '', 'no' );
    }

    if ( !get_option( 'cntrd_engine_geoip2' ) ) {
        add_option( 'cntrd_engine_geoip2', 1, '', 'no' );
    }
}


/**
 * Activate and initial setup whitelist plugin's settings
 */
function cntrd_set_whitelist_options() {
    if ( !get_option( 'cntrd_whitelist_ip' ) ) {
        add_option( 'cntrd_whitelist_ip', '', '', 'no' );
    }

    if ( !get_option( 'cntrd_whitelist_bot' ) ) {
        add_option( 'cntrd_whitelist_bot', 0, '', 'no' );
    }
}


/**
 * Set options autoload to 'no' to reduce memory consumption for previously installed versions
 */
function cntrd_change_to_no_redirect_autoload() {
    global $wpdb;
    try {
        $wpdb->query("UPDATE $wpdb->options SET autoload='no' WHERE option_name LIKE 'cntrd_redirect%'");
    } catch (\Exception $e) {}
}