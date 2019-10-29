<?php
/* ------------------------------------------------------------------------ *
 * Country determining library for Country Redirect using local GeoLite2 Free database
 * This product includes GeoLite2 data created by MaxMind, available from
 * <a href="https://www.maxmind.com">https://www.maxmind.com</a>.
 * ------------------------------------------------------------------------ */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use GeoIp2\Database\Reader;

/**
 * @param string $ip IPv4
 *
 * @return string || false Returns two symbols country code
 */
function geoip2( $ip ) {
	$dbfile = plugin_dir_path( dirname( __FILE__ ) ) . 'DB' . DIRECTORY_SEPARATOR . 'GeoIP' . DIRECTORY_SEPARATOR . 'GeoLite2-Country.mmdb';

	if ( file_exists( $dbfile ) ) {

		try {
			$reader = new Reader( $dbfile );
			$record = $reader->country( $ip );
			$country = $record->country->isoCode;
		} catch ( Exception $e ) {
			return false;
		}
		if ( $country && in_country_code( $country ) ) {
			return $country;
		}
	}

	return false;
}