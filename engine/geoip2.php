<?php
/* ------------------------------------------------------------------------ *
 * Country determining library for Country Redirect using local GeoLite2 Free database
 * This product includes GeoLite2 data created by MaxMind, available from
 * <a href="https://www.maxmind.com">https://www.maxmind.com</a>.
 * ------------------------------------------------------------------------ */

$autoload = plugin_dir_path( dirname( __FILE__ ) ) . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if ( ! file_exists( $autoload ) ) {
	return false;
}
require_once $autoload;

/**
 * @param string $ip IPv4
 *
 * @return string || false Returns two symbols country code
 */
function cntrd_geoip2( $ip ) {
	$dbfile = plugin_dir_path( dirname( __FILE__ ) ) . 'DB' . DIRECTORY_SEPARATOR . 'GeoIP' . DIRECTORY_SEPARATOR . 'GeoLite2-Country.mmdb';

	if ( defined( 'ABSPATH' ) && file_exists( $dbfile ) ) {

		try {
			$reader = new GeoIp2\Database\Reader( $dbfile );
			$record = $reader->country( $ip );
			$country = $record->country->isoCode;
		} catch ( Exception $e ) {
			return false;
		}
		if ( $country && cntrd_in_country_code( $country ) ) {
			return $country;
		}
	}

	return false;
}