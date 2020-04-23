<?php

if ( !defined( 'ABSPATH' ) || !defined('WP_UNINSTALL_PLUGIN') ) {
    die;
}

$cntrd_mainsettings_list = [ 'cntrd_engine_sxgeo', 'cntrd_engine_ipapi', 'cntrd_engine_geoip2', 'cntrd_whitelist_ip', 'cntrd_whitelist_bot' ];
foreach ( $cntrd_mainsettings_list as $cntrd_option ) {
    delete_option($cntrd_option);
    delete_site_option($cntrd_option);
}



$cntrd_sxgeo_path = plugin_dir_path( __FILE__ ) . 'DB' . DIRECTORY_SEPARATOR . 'SxGeo' . DIRECTORY_SEPARATOR . 'SxGeo';
include_once( $cntrd_sxgeo_path . '.php' );
$SxGeo = new CountryRedirect\SxGeo( $cntrd_sxgeo_path . '.dat' );

foreach ( $SxGeo->id2iso as $cntrd_ccode ) {
    if (!empty($cntrd_ccode)) {
        $cntrd_option = 'cntrd_redirect_' . $cntrd_ccode;
        delete_option($cntrd_option);
        delete_site_option($cntrd_option);
    }
}

if ( get_option('cntrd_version') ) {
    delete_option('cntrd_version');
    delete_site_option('cntrd_version');
}
