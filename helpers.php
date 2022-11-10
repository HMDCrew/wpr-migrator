<?php

if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * Polyfill for `str_contains()` function added in PHP 8.0.
	 *
	 * Performs a case-sensitive check indicating if needle is
	 * contained in haystack.
	 *
	 * @since 5.9.0
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the haystack.
	 * @return bool True if `$needle` is in `$haystack`, otherwise false.
	 */
	function str_contains( $haystack, $needle ) {
		return ( '' === $needle || false !== strpos( $haystack, $needle ) );
	}
}

if ( ! function_exists( 'keywords_in_string' ) ) {
	/**
	 * It returns true if the string contains any of the keywords in the array
	 *
	 * @param string string The string to search for the keywords in.
	 * @param array exclusion_keywords An array of keywords to check for in the string.
	 *
	 * @return True or False
	 */
	function keywords_in_string( string $string, array $exclusion_keywords = array() ) {

		foreach ( $exclusion_keywords as $keyword ) {
			if ( str_contains( $string, $keyword ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'wpr_request_migrator_api' ) ) {
	/**
	 * It makes a GET request to the endpoint provided, and returns the response
	 *
	 * @param endpoint The endpoint of the API you're calling.
	 * @param params {
	 *
	 * @return The response from the API.
	 */
	function wpr_request_migrator_api( $endpoint, $params ) {

		$ch      = curl_init();
		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
		);

		$args      = http_build_query(
			array(
				'dest_key' => $params['dest_key'],
				'key_site' => $params['site_key'],
				'origin'   => $params['origin'],
			)
		);
		$endpoint .= ( ! str_contains( $endpoint, '?' ) ? '?' . $args : '&' . $args );

		curl_setopt( $ch, CURLOPT_URL, $endpoint );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		// Timeout in seconds
		curl_setopt( $ch, CURLOPT_TIMEOUT, 800 );

		$data = curl_exec( $ch );

		curl_close( $ch );

		return $data;
	}
}
