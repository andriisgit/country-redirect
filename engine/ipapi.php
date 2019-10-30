<?php
/* ------------------------------------------------------------------------ *
 * Determining country library for Country Redirect using
 * free for non commercial using remote ip-api service
 * ------------------------------------------------------------------------ */


/**
 * @param string $ip IPv4
 *
 * @return string || false Returns two symbols country code
 */
function cntrd_ipapi( $ip ) {
	if ( ! defined( 'ABSPATH' ) ) {
		return false;
	}

	$response = wp_remote_get( 'http://ip-api.com/json/' . $ip . '?fields=status,countryCode');
	$json = wp_remote_retrieve_body( $response );

	$data = json_decode( $json );

	if ( $data && $data->status != 'fail' && json_last_error() == JSON_ERROR_NONE ) {
		$country = $data->countryCode;
		if ( cntrd_in_country_code( $country ) ) {
			return $country;
		}
	}

	return false;
}