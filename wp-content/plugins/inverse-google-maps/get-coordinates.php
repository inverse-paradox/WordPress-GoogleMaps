<?php // convert address to coordinates
// if the conversion form is submitted, output the results
if ( isset( $_GET['sgm_convert_address'] ) ) {
	$coordinates = sgm_get_coordinates( $_GET['sgm_convert_address'] );

	if( '' != $coordinates['lat'] && '' != $coordinates['lng'] ) {
		echo $coordinates['lat'] . ',' . $coordinates['lng'];
	} else {
		echo 'Error: You must enter a valid address.';
	}
}

function sgm_get_coordinates( $address ) {
	$bad = array(
		" " => "+",
		"," => "",
		"?" => "",
		"&" => "",
		"=" => ""
	);

	$address = str_replace( array_keys( $bad ), array_values( $bad ), $address );
	
	// this doesn't work anymore: http://maps.google.com/maps/geo?output=xml&q=

	$data = file_get_contents( "http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=$address" );
	$data = json_decode( $data );

	$coordinates['lat'] = $data->results[0]->geometry->location->lat;
	$coordinates['lng'] = $data->results[0]->geometry->location->lng;

	return $coordinates;
} ?>