<?php

/****************************************
* Functions for getting non-member
* specific info about subscription
* levels
*****************************************/

/*
* Gets an array of all available subscription levels
* @param $status string - the status of subscription levels we want to retrieve: active, inactive, or all
* @param $cache bool - whether to pull from a cache or not
* return mixed - array of objects if levels exist, false otherwise
*/
function rcp_get_subscription_levels( $status = 'all', $cache = true ) {
	global $wpdb, $rcp_db_name;

	$rcp_levels = new RCP_Levels();

	if( $cache ) {

		$levels = get_transient( 'rcp_subscription_levels' );
		if($levels === false) {
			$levels = $rcp_levels->get_levels( array( 'status' => $status ) );
			// cache the levels with a 3 hour expiration
			set_transient( 'rcp_subscription_levels', $levels, 10800 );
		}
	} else {
		$levels = $rcp_levels->get_levels( array( 'status' => $status ) );
	}

	if( $levels )
		return $levels;
	else
		return array();
}

/*
* Gets all details of a specified subscription level
* @param int $id - the ID of the subscription level to retrieve
* return mixed - object on success, false otherwise
*/
function rcp_get_subscription_details( $id ) {
	$levels = new RCP_Levels();
	$level = $levels->get_level( $id );
	if( $level )
		return $level;
	return false;
}

/*
* Gets all details of a specified subscription level
* @param int $name - the name of the subscription level to retrieve
* return mixed - object on success, false otherwise
*/
function rcp_get_subscription_details_by_name( $name ) {
	global $wpdb, $rcp_db_name;
	$level = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $rcp_db_name . " WHERE name='%s';", $name ) );
	if( $level )
		return $level[0];
	return false;
}

/*
* Gets the name of a specified subscription level
* @param int $id - the ID of the subscription level to retrieve
* return string - name of subscription, or error message on failure
*/
function rcp_get_subscription_name( $id ) {

	$levels_db = new RCP_Levels;
	return $levels_db->get_level_field( $id, 'name' );
}

/*
* Gets the duration of a subscription
* @param int $id - the ID of the subscription level to retrieve
* return object - length and unit (m/d/y) of subscription
*/
function rcp_get_subscription_length( $id ) {
	global $wpdb, $rcp_db_name;
	$level_length = $wpdb->get_results( $wpdb->prepare( "SELECT duration, duration_unit FROM " . $rcp_db_name . " WHERE id='%d';", $id ) );
	if( $level_length )
		return $level_length[0];
	return false;
}

/*
* Gets the day of expiration of a subscription from the current day
* @param int $id - the ID of the subscription level to retrieve
* return string - nicely formatted date of expiration
*/
function rcp_calculate_subscription_expiration( $id ) {
	global $wpdb, $rcp_db_name;

	$length = rcp_get_subscription_length( $id );
	$expiration = date( 'Y-m-d H:i:s', strtotime( '+' . $length->duration . ' ' . $length->duration_unit . ' 23:59:59'  ) );

	return $expiration;
}

/*
* Gets the price of a subscription level
* @param int $id - the ID of the subscription level to retrieve
* return mixed - price of subscription level, false on failure
*/
function rcp_get_subscription_price( $id ) {
	$levels = new RCP_Levels();
	$price = $levels->get_level_field( $id, 'price' );
	if( $price )
		return $price;
	return false;
}

/*
* Gets the access level of a subscription package
* @param int $id - the ID of the subscription level to retrieve
* return int - the numerical access level the subscription gives
*/
function rcp_get_subscription_access_level( $id ) {
	$levels = new RCP_Levels();
	$level = $levels->get_level_field( $id, 'level' );
	if( $level )
		return $level;
	return false;
}


/*
* Get a formatted duration unit name for subscription lengths
* @param string $unit - the duration unit to return a formatted string for
* @param int - the duration of the subscription level
* return string - a formatted unit display. Example "days" becomes "Days". Return is localized
*/
function rcp_filter_duration_unit( $unit, $length ) {
	switch ( $unit ) :
		case 'day' :
			if( $length > 1 )
				$new_unit = __( 'Days', 'rcp' );
			else
				$new_unit = __( 'Day', 'rcp' );
		break;
		case 'month' :
			if( $length > 1 )
				$new_unit = __( 'Months', 'rcp' );
			else
				$new_unit = __( 'Month', 'rcp' );
		break;
		case 'year' :
			if( $length > 1 )
				$new_unit = __( 'Years', 'rcp' );
			else
				$new_unit = __( 'Year', 'rcp' );
		break;
	endswitch;
	return $new_unit;
}

/*
* Checks to see if there are any paid subscription levels created
*
* @since 1.1.9
* @return boolean - TRUE if paid levels exist, false if only free
*/
function rcp_has_paid_levels() {
	$levels = rcp_get_subscription_levels();
	if( $levels ) {
		foreach( $levels as $level ) {
			if( $level->price > 0 && $level->status == 'active' )
				return true;
		}
	}
	return false;
}


/*
* Retrieves available access levels
*
* @since 1.3.2
* @return array
*/
function rcp_get_access_levels() {
	$levels = array(
		0 => 'None',
		1 => '1',
		2 => '2',
		3 => '3',
		4 => '4',
		5 => '5',
		6 => '6',
		7 => '7',
		8 => '8',
		9 => '9',
		10 => '10'
	);
	return apply_filters( 'rcp_access_levels', $levels );
}


/*
 * Generates a new subscription key
 *
 * @since 1.3.2
 * @return array
 */
function rcp_generate_subscription_key() {
	return apply_filters( 'rcp_subscription_key', urlencode( strtolower( md5( uniqid() ) ) ) );
}


/*
 * Determines if a subscription level should be shown
 *
 * @since 1.3.2.3
 * @return bool
 */
function rcp_show_subscription_level( $level_id = 0, $user_id = 0 ) {

	if( empty( $user_id ) )
		$user_id = get_current_user_id();

	$ret = true;

	$user_level = rcp_get_subscription_id( $user_id );
	$sub_price 	= rcp_get_subscription_price( $level_id );

	if( is_user_logged_in() && $sub_price == '0' )
		$ret = false;

	return apply_filters( 'rcp_show_subscription_level', $ret, $level_id, $user_id );
}