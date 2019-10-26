<?php
/* ------------------------------------------------------------------------ *
 * Determining country library for Geo Redirect using local GeoIp2
 * ------------------------------------------------------------------------ */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use GeoIp2\Database\Reader;

function geoip2( $ip ) {
	$dbfile = plugin_dir_path( dirname( __FILE__ ) ) . 'DB' . DIRECTORY_SEPARATOR . 'GeoIP' . DIRECTORY_SEPARATOR . 'GeoLite2-Country.mmdb';

	if ( file_exists( $dbfile ) ) {

		$reader  = new Reader( $dbfile );
		$record  = $reader->country( $ip );
		$country = $record->country->isoCode;
		if ( in_country_code( $country ) ) {
			return $country;
		}
	}

	return false;
}