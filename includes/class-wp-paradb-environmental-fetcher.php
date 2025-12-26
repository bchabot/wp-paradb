<?php
/**
 * Environmental data fetching functionality
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.3.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle fetching weather, astronomical, and astrological data
 *
 * @since      1.3.0
 */
class WP_ParaDB_Environmental_Fetcher {

	/**
	 * Fetch all available environmental data for a location and time
	 *
	 * @since    1.3.0
	 */
	public static function fetch_all( $lat, $lng, $datetime ) {
		$data = array();
		
		$data['weather'] = self::fetch_weather( $lat, $lng, $datetime );
		$data['astro'] = self::fetch_astronomical( $lat, $lng, $datetime );
		$data['astrology'] = self::fetch_astrological( $lat, $lng, $datetime );
		$data['geomagnetic'] = self::fetch_geomagnetic( $lat, $lng, $datetime );

		return $data;
	}

	/**
	 * Fetch weather data from Open-Meteo
	 */
	private static function fetch_weather( $lat, $lng, $datetime ) {
		$date = date( 'Y-m-d', strtotime( $datetime ) );
		$hour = (int)date( 'H', strtotime( $datetime ) );
		
		// Use historical archive if date is in the past, or forecast if near present/future
		$is_past = strtotime( $date ) < strtotime( 'today' );
		$base_url = $is_past ? "https://archive-api.open-meteo.com/v1/archive" : "https://api.open-meteo.com/v1/forecast";
		
		$url = "{$base_url}?latitude={$lat}&longitude={$lng}&start_date={$date}&end_date={$date}&hourly=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m";
		
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) return null;

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! $body || ! isset( $body['hourly'] ) ) return null;

		return array(
			'temp' => isset($body['hourly']['temperature_2m'][$hour]) ? $body['hourly']['temperature_2m'][$hour] : null,
			'humidity' => isset($body['hourly']['relative_humidity_2m'][$hour]) ? $body['hourly']['relative_humidity_2m'][$hour] : null,
			'weather_code' => isset($body['hourly']['weather_code'][$hour]) ? $body['hourly']['weather_code'][$hour] : null
		);
	}

	/**
	 * Fetch astronomical data (Moon phase) from WeatherAPI.com
	 */
	private static function fetch_astronomical( $lat, $lng, $datetime ) {
		$options = get_option( 'wp_paradb_options', array() );
		$api_key = isset( $options['weatherapi_api_key'] ) ? $options['weatherapi_api_key'] : '';
		if ( empty( $api_key ) ) return null;

		$date = date( 'Y-m-d', strtotime( $datetime ) );
		$url = "https://api.weatherapi.com/v1/astronomy.json?key={$api_key}&q={$lat},{$lng}&dt={$date}";

		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) return null;

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return isset( $body['astronomy']['astro'] ) ? $body['astronomy']['astro'] : null;
	}

	/**
	 * Fetch astrological transit data from FreeAstroAPI
	 */
	private static function fetch_astrological( $lat, $lng, $datetime ) {
		$options = get_option( 'wp_paradb_options', array() );
		$api_key = isset( $options['freeastroapi_api_key'] ) ? $options['freeastroapi_api_key'] : '';
		
		if ( empty( $api_key ) ) {
			return null;
		}

		$date = date( 'Y-m-d', strtotime( $datetime ) );
		// FreeAstroAPI expects latitude, longitude, and date.
		$url = "https://json.freeastroapi.com/v1/planets/all?date={$date}&latitude={$lat}&longitude={$lng}&timezone=0";
		
		$response = wp_remote_get( $url, array(
			'headers' => array(
				'x-api-key' => $api_key
			)
		) );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! $body || ! isset( $body['output'] ) ) {
			return null;
		}

		// Return formatted planetary positions
		return $body['output'];
	}

	/**
	 * Fetch geomagnetic data (Kp Index) from NOAA
	 */
	private static function fetch_geomagnetic( $lat, $lng, $datetime ) {
		$url = "https://services.swpc.noaa.gov/products/noaa-planetary-k-index.json";
		
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || count( $body ) < 2 ) {
			return null;
		}

		// Find the entry closest to our datetime
		$target_time = strtotime( $datetime );
		$best_kp = null;
		$min_diff = PHP_INT_MAX;

		// Skip header [0]
		for ( $i = 1; $i < count( $body ); $i++ ) {
			$entry_time = strtotime( $body[$i][0] );
			$diff = abs( $target_time - $entry_time );
			if ( $diff < $min_diff ) {
				$min_diff = $diff;
				$best_kp = $body[$i][1];
			}
		}

		// Only return if it's within a reasonable range (e.g., 3 hours)
		if ( $min_diff < 10800 ) {
			return array(
				'kp_index' => $best_kp,
				'source'   => 'NOAA Planetary K-Index'
			);
		}

		return null;
	}
}
