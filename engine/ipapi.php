<?php
/* ------------------------------------------------------------------------ *
 * Determining country library for Geo Redirect using remote ip-api
 * ------------------------------------------------------------------------ */

function ipapi( $ip ) {
	$json = file_get_contents( 'http://ip-api.com/json/' . $ip );
	$data = json_decode( $json );

	if ( $data && json_last_error() == JSON_ERROR_NONE ) {
		$country = $data->countryCode;
		if ( in_country_code( $country ) ) {
			return $country;
		}
	}

	return false;
}