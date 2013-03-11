<?php 
/*
Plugin Name: IP Google Maps Plugin
Description: Set map locations under Options > Map Locations. Shortcode example: [ip-google-map show_directions="true" zoom="13"].
Author: Shawn Hogan
*/

wp_enqueue_script("google-maps", 'http://maps.google.com/maps/api/js?sensor=false', array("jquery"));
wp_enqueue_script("google-map-controls", plugin_dir_url( __FILE__ )."js/google-maps.js", array("jquery", "google-maps"));

// when the plugin is first activated, make sure ACF is installed, and the proper ACF addons are activated
register_activation_hook( __FILE__, 'activate_acf_addons' );

function ipGoogleMaps($atts) {

	extract( shortcode_atts( array(
		'show_directions' => '',
		'zoom' => '13',
	), $atts ) );

	// get centerpoint and location fields from acf
	if( get_field('ip_map_locations', 'option') ) {
		$count = 0;
		$directions_options = '';

		while( has_sub_field('ip_map_locations', 'option') ) {
			$locations[$count]['long_lat'] = get_sub_field('ip_location_coordinates');
			$locations[$count]['title'] = get_sub_field('ip_location_title');

			if( $count == 0 ) {
				$single_location_option = '<input type="hidden" id="g-end" value="' . get_sub_field('ip_location_address') . '" />';
			}
			$directions_options .= '<option value="' . get_sub_field('ip_location_address') . '">' . get_sub_field('ip_location_title') . '</option>';

			$count++;
		}

		if( $count == 1 ) {
			$directions_options = $single_location_option;
		} else {
			$directions_options = '<label>End Location: </label><select id="g-end">' . $directions_options . '</select>';
		}

		$centerpoint = get_field('ip_map_centerpoint', 'option');
	}

	// if centerfield and locations both exist, build the javascript that creates the map
	if( $centerpoint && $locations ) {
		$markers = '';
		$count = 0;

		// add a marker for each location
		foreach( $locations as $location ) {
			$markers .= 'var marker' . $count . ' = new google.maps.Marker({
				position: new google.maps.LatLng(' . $location['long_lat'] . '),
				map: map,
				title: "' . $location['title'] . '"
			});';
		}

		$map_js = '
		<script>
			var map;
			var directionsDisplay;
			var directionsService = new google.maps.DirectionsService();

			function initialize()
			{
				// setup the map
				var map = new google.maps.Map(document.getElementById("g-map"), {
					zoom: ' . $zoom . ',
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					center: new google.maps.LatLng('.$centerpoint.')
				});'

				.$markers.

				'// setup the directions
				directionsDisplay = new google.maps.DirectionsRenderer();
				directionsDisplay.setMap(map);
				directionsDisplay.setPanel(document.getElementById("g-directions"));
			}
		</script>
		';

		$directions_html = '';

		if($show_directions != 'false') {
			$directions_html = '
			<div class="directions-form">
				<form action="">
					<label>Start Address: </label><input type="text" id="g-start">'
					.$directions_options.
					'<input type="submit" value="Get Directions" onclick="calcRoute(); return false;">
				</form>
			</div><!--/directions-form-->

			<div id="g-directions"></div>
			';
		}

		$map_html = $map_js . '<div id="g-map"></div>' . $directions_html;

		return $map_html;
	} else {
		return false;
	}
}

//google maps short code [ip-google-map]
function ip_google_map_shortcode($atts) {
	$html = ipGoogleMaps($atts);

	return do_shortcode( $html );
}
add_shortcode( 'ip-google-map', 'ip_google_map_shortcode' );

// add the 'Map Locations' tab under options in ACF
if(function_exists("register_options_page"))
{
    register_options_page('Map Locations');
}

// activate ACF add-ons 
function activate_acf_addons() {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

	if ( is_plugin_active( 'advanced-custom-fields/acf.php' ) )
	{
		require_once ( WP_PLUGIN_DIR . '/advanced-custom-fields/acf.php' );
		if(!get_option('acf_repeater_ac')) update_option('acf_repeater_ac', "QJF7-L4IX-UCNP-RF2W");
		if(!get_option('acf_options_page_ac')) update_option('acf_options_page_ac', "OPN8-FA4J-Y2LW-81LS");
	}
	else
	{
	 	// deactivate dependent plugin
		deactivate_plugins( __FILE__);
		exit ('Requires Advanced Custom Fields plugin to be installed!');
	}
}

// register field groups from ACF
if(function_exists("register_field_group"))
{
	register_field_group(array (
		'id' => '50cb548f84f98',
		'title' => 'Map Locations',
		'fields' => 
		array (
			0 => 
			array (
				'key' => 'field_17',
				'label' => 'Map Centerpoint Coordinates',
				'name' => 'ip_map_centerpoint',
				'type' => 'text',
				'order_no' => 0,
				'instructions' => '',
				'required' => '0',
				'conditional_logic' => 
				array (
					'status' => '0',
					'rules' => 
					array (
						0 => 
						array (
							'field' => 'null',
							'operator' => '==',
						),
					),
					'allorany' => 'all',
				),
				'default_value' => '',
				'formatting' => 'none',
			),
			1 => 
			array (
				'key' => 'field_18',
				'label' => 'Map Locations',
				'name' => 'ip_map_locations',
				'type' => 'repeater',
				'order_no' => 1,
				'instructions' => '',
				'required' => '0',
				'conditional_logic' => 
				array (
					'status' => '0',
					'rules' => 
					array (
						0 => 
						array (
							'field' => 'null',
							'operator' => '==',
						),
					),
					'allorany' => 'all',
				),
				'sub_fields' => 
				array (
					'field_19' => 
					array (
						'label' => 'Title',
						'name' => 'ip_location_title',
						'type' => 'text',
						'instructions' => '',
						'column_width' => '',
						'default_value' => '',
						'formatting' => 'none',
						'order_no' => 0,
						'key' => 'field_19',
					),
					'field_20' => 
					array (
						'label' => ' Coordinates',
						'name' => 'ip_location_coordinates',
						'type' => 'text',
						'instructions' => '',
						'column_width' => '',
						'default_value' => '',
						'formatting' => 'none',
						'order_no' => 1,
						'key' => 'field_20',
					),
					'field_21' => 
					array (
						'label' => 'Address',
						'name' => 'ip_location_address',
						'type' => 'text',
						'instructions' => '',
						'column_width' => '',
						'default_value' => '',
						'formatting' => 'none',
						'order_no' => 2,
						'key' => 'field_21',
					),
				),
				'row_min' => '0',
				'row_limit' => '',
				'layout' => 'table',
				'button_label' => 'Add Location',
			),
		),
		'location' => 
		array (
			'rules' => 
			array (
				0 => 
				array (
					'param' => 'options_page',
					'operator' => '==',
					'value' => 'acf-options-map-locations',
					'order_no' => 0,
				),
			),
			'allorany' => 'all',
		),
		'options' => 
		array (
			'position' => 'normal',
			'layout' => 'no_box',
			'hide_on_screen' => 
			array (
			),
		),
		'menu_order' => 0,
	));
}


?>