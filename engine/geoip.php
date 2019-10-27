<?php
/* ------------------------------------------------------------------------ *
 * Determining country library for Country Redirect using remote GEOIP DB API
 * ------------------------------------------------------------------------ */

function geoip( $ip ) {
	$json = file_get_contents( 'https://geoip-db.com/json/' . $ip );
	$data = json_decode( $json );

	if ( $data && json_last_error() == JSON_ERROR_NONE ) {
		$country = $data->country_code;
		if ( in_country_code( $country ) ) {
			return $country;
		}
	}

	return false;
}
