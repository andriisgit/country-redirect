<?php
/* ------------------------------------------------------------------------ *
 * Determining country library for Country Redirect using local Sypex Geo
 * ------------------------------------------------------------------------ */

/**
 * @param string $ip IPv4
 *
 * @return string || false Returns two symbols country code
 */
function sxgeo( $ip ) {

	$path = plugin_dir_path( dirname( __FILE__ ) ) . 'DB' . DIRECTORY_SEPARATOR . 'SxGeo' . DIRECTORY_SEPARATOR . 'SxGeo';

	if ( file_exists( $path . '.php' ) && file_exists( $path . '.dat' ) ) {
		include_once( $path . '.php' );
		try {
			$SxGeo   = new SxGeo( $path . '.dat' );
			$country = $SxGeo->getCountry( $ip );
		} catch ( Exception $e ) {
			return false;
		}
		if ( $country && in_country_code( $country ) ) {
			return $country;
		}
	}

	return false;
}