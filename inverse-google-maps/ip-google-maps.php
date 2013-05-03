<?php
/*
Plugin Name: IP Google Map Plugin
Description: Simple google map plugin, display using shortcode [ip-google-map show_directions="true" zoom="13"].
Version: 1.0
Author: Shawn Hogan
*/

/************************************
	REGISTER & ENQUEUE SCRIPTS
************************************/

function enqueue_scripts() {
	global $post;
	
	if(strpos($post->post_content, 'ip-google-map')) {
		wp_register_script("ip-google-maps", 'http://maps.google.com/maps/api/js?sensor=false', array("jquery"));
		wp_register_script("ip-google-map-controls", plugin_dir_url( __FILE__ )."js/google-maps.js", array("jquery", "ip-google-maps"));
		wp_enqueue_script('ip-google-maps');
		wp_enqueue_script('ip-google-map-controls');
	}
}
add_action('wp_enqueue_scripts', 'enqueue_scripts');

/************************************
	FUNCTIONALITY
************************************/

function ipGoogleMaps($atts) {

	extract( shortcode_atts( array(
		'show_directions' => '',
		'zoom'            => '13',
		'display'         => 'all',
		'map_id'              => 'map',
	), $atts ) );

	$locations = get_option( 'sgm_location' );

	// get centerpoint and location fields
	if( isset($locations[0]['title']) && isset($locations[0]['address']) && isset($locations[0]['coordinates']) ) {
		$count = 0;
		$directions_options = '';
		$markers = '';

		if ( $display != 'all' && strstr( $display, ',' ) ) {
			$display = str_replace( ' ', '', $display );
			$display = explode( ',', $display );
			foreach ( $display as $display_count ) {
				$directions_options .= '<option value="' . $locations[$display_count]['address'] . '">' . $locations[$display_count]['title'] . '</option>';
				if ( $count == 0 ) {
					$centerpoint = $location['coordinates'];
					$marker_coords = 'new google.maps.LatLng (' . $locations[$display_count]['coordinates'] . ')';
				} else {
					$marker_coords .= ', new google.maps.LatLng (' . $locations[$display_count]['coordinates'] . ')';
				}
				$markers .= 'var marker' . $count . ' = new google.maps.Marker({
					position: new google.maps.LatLng(' . $locations[$display_count]['coordinates'] . '),
					map: '.$map_id.',
					title: "' . $locations[$display_count]['title'] . '"
				});';
				$count++;
			}
		} elseif ( $display != 'all' ) {
				$single_location_option = '<input type="hidden" id="g-end_'.$map_id.'" value="' . $locations[$display]['address'] . '" />';
				$centerpoint = $locations[$display]['coordinates'];
				$markers .= 'var marker' . $count . ' = new google.maps.Marker({
					position: new google.maps.LatLng(' . $locations[$display]['coordinates'] . '),
					map: '.$map_id.',
					title: "' . $locations[$display]['title'] . '"
				});';
				$count++;
		} else {
			foreach ( $locations as $location ) {
				if( $count == 0 ) {
					$single_location_option = '<input type="hidden" id="g-end_'.$map_id.'" value="' . $location['address'] . '" />';
					$centerpoint = $location['coordinates'];
					$marker_coords = 'new google.maps.LatLng (' . $location['coordinates'] . ')';
				} else {
					$marker_coords .= ', new google.maps.LatLng (' . $location['coordinates'] . ')';
				}
				$markers .= 'var marker' . $count . ' = new google.maps.Marker({
					position: new google.maps.LatLng(' . $location['coordinates'] . '),
					map: '.$map_id.',
					title: "' . $location['title'] . '"
				});';
				$directions_options .= '<option value="' . $location['address'] . '">' . $location['title'] . '</option>';
				$count++;
			}
		}

		if( $count == 1 ) {
			$directions_options = $single_location_option;
		} else {
			$directions_options = '<label>End Location: </label><select id="g-end_'.$map_id.'">' . $directions_options . '</select>';
			$center_js = '
			var LatLngList = new Array (' . $marker_coords . ');
			var bounds = new google.maps.LatLngBounds ();
			for (var i = 0, LtLgLen = LatLngList.length; i < LtLgLen; i++) {
				bounds.extend (LatLngList[i]);
			}
			'.$map_id.'.fitBounds (bounds);
			';
		}

		// if locations exist, build the javascript that creates the map
		if( $locations ) {
			$count = 0;

			$map_js = '
			<script>
				var '.$map_id.';
				var directionsDisplay_'.$map_id.';
				var directionsService = new google.maps.DirectionsService();

				function initialize_'.$map_id.'()
				{
					// setup the map
					'.$map_id.' = new google.maps.Map(document.getElementById("g-map-'.$map_id.'"), {
						zoom: ' . $zoom . ',
						mapTypeId: google.maps.MapTypeId.ROADMAP,
						center: new google.maps.LatLng('.$centerpoint.')
					});'

					.$markers.

					'// setup the directions
					directionsDisplay_'.$map_id.' = new google.maps.DirectionsRenderer();
					directionsDisplay_'.$map_id.'.setMap('.$map_id.');
					directionsDisplay_'.$map_id.'.setPanel(document.getElementById("g-directions-'.$map_id.'"));'

					.$center_js.
				'}

				function calcRoute_'.$map_id.'() {
					var start = document.getElementById("g-start_'.$map_id.'").value;
					var end = document.getElementById("g-end_'.$map_id.'").value;

					var request = {
						origin:start,
						destination:end,
						travelMode: google.maps.TravelMode.DRIVING
					};

					directionsService.route(request, function(result, status) {
						if (status == google.maps.DirectionsStatus.OK) {
							directionsDisplay_'.$map_id.'.setDirections(result);
						}
					});
				}

			    if(window.addEventListener)
			    {
			        //All browsers, except IE before version 9.
			        window.addEventListener("load", initialize_'.$map_id.', false);
			    } 
			    else if(window.attachEvent)
			    {
			        //IE before version 9.
			        window.attachEvent("load", initialize_'.$map_id.');
			    }
			</script>
			';

			$directions_html = '';

			if($show_directions != 'false') {
				$directions_html = '
				<div class="directions-form">
					<form action="">
						<label>Start Address: </label><input type="text" id="g-start_'.$map_id.'">'
						.$directions_options.
						'<input type="submit" value="Get Directions" onclick="calcRoute_'.$map_id.'(); return false;">
					</form>
				</div><!--/directions-form-->

				<div id="g-directions-'.$map_id.'"></div>
				';
			}

			$map_html = $map_js . '<div id="g-map-'.$map_id.'" class="g-map" style="height: 250px;"></div>' . $directions_html;

			return $map_html;
		} else {
			return 'Error, something is not setup properly.';
		}
	}
}

//google maps short code [ip-google-map]
function ip_google_map_shortcode($atts) {
	$html = ipGoogleMaps($atts);

	return do_shortcode( $html );
}
add_shortcode( 'ip-google-map', 'ip_google_map_shortcode' );

/************************************
	SETTINGS PAGE
************************************/

// create google map settings page
add_action( 'admin_menu', 'sgm_menu' );

function sgm_menu() {
	// create new top level menu
	add_menu_page( 'Google Map Options', 'Map Settings', 'administrator', __FILE__, 'sgm_settings', plugins_url( 'images/icon.png', __FILE__ ) );

	// call register settings function
	add_action( 'admin_init', 'register_sgm_settings' );
}

// register the map settings
function register_sgm_settings() {
	register_setting( 'sgm-settings-group', 'sgm_center_coordinates' );
	register_setting( 'sgm-settings-group', 'sgm_location' );
}

// create the actual settings page
function sgm_settings() {
?>

<script>
jQuery(document).ready(function($) {
	// add location row
	$('#sgm-add-location').click(function() {
		var last_row = $('.sgm_location_row').last().index();

		$('.sgm_location_row').last().after('<tr valign="top" class="sgm_location_row"><td><p><strong>Location Title</strong><br /><input type="text" name="sgm_location[' + last_row + '][title]" value=""></p></td><td><p><strong>Location Address</strong><br /><input type="text" name="sgm_location[' + last_row + '][address]" value=""></p></td><td><p><strong>Location Coordinates</strong><br /><input type="text" name="sgm_location[' + last_row + '][coordinates]" value=""></p></td></tr>');
	});

	// remove location row
	$('#sgm-remove-location').click(function() {
		$('.sgm_location_row').last().remove();
	});

	// submit conversion form
	$( '#sgm-conversion-form' ).submit(function() {
        var current_form = $( this ).attr('id');
        var submission_url = '<?php echo plugin_dir_url( __FILE__ ); ?>get-coordinates.php?sgm_convert_address=' + $('#sgm_convert_address').val();

        // $('#' + current_form + ' .form-msg').remove();
        // $('#' + current_form + ' .ajax-loading').show();

        $.get( submission_url, function(data) {
        	$('#sgm_convert_address').val(data);
        });

        return false;
    });
});
</script>

<div class="wrap">
	<h2>IP Google Map Plugin</h2>

	<table>
		<tr valign="top">
			<td colspan="2">
				<p><strong>Convert Address to Coordinates:</strong><br />
				<form id="sgm-conversion-form" action="" method="get">
					<input type="text" id="sgm_convert_address" name="sgm_convert_address"> <input type="submit" id="sgm_convert_button" class="button" value="Convert"></p>
				</form>
			</td>
		</tr>
	</table>

	<form method="post" action="options.php">
		<?php settings_fields( 'sgm-settings-group' ); ?>
		<?php do_settings_sections( 'sgm-settings-group' ); ?>

		<!--/<table class="form-table">-->
		<table>
			<?php // get the array of locations
			$locations = get_option( 'sgm_location' );

			if( ! is_array( $locations ) ) {
				$locations[0]['title'] = '';
				$locations[0]['address'] = '';
				$locations[0]['coordinates'] = '';
			} ?>

			<?php // loop through the locations array and display the locations section of the form
			$count = 0;

			foreach ( $locations as $location ) { ?>
				<tr valign="top" class="sgm_location_row">
					<td>
						<p><strong>Location Title</strong><br />
						<?php if( isset($location['title']) ) { ?>
							<input type="text" name="sgm_location[<?php echo $count; ?>][title]" value="<?php echo $location['title']; ?>"></p>
						<?php } else { ?>
							<input type="text" name="sgm_location[<?php echo $count; ?>][title]" value="<?php echo $location['title']; ?>"></p>
						<?php } ?>
					</td>

					<td>
						<p><strong>Location Address</strong><br />
						<?php if( isset($location['address']) ) { ?>
							<input type="text" name="sgm_location[<?php echo $count; ?>][address]" value="<?php echo $location['address']; ?>"></p>
						<?php } else { ?>
							<input type="text" name="sgm_location[<?php echo $count; ?>][address]" value=""></p>
						<?php } ?>
					</td>

					<td>
						<p><strong>Location Coordinates</strong><br />
						<?php if( isset($location['coordinates']) ) { ?>
							<input type="text" name="sgm_location[<?php echo $count; ?>][coordinates]" value="<?php echo $location['coordinates']; ?>"></p>
						<?php } else { ?>
							<input type="text" name="sgm_location[<?php echo $count; ?>][title]" value=""></p>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<td colspan="3">
						<p style="margin: 0; padding: 5px; background: #ddd;">[ip-google-map show_directions="true" zoom="13" display="<?php echo $count; ?>" map_id="YOUR_ID"]</p>	
					</td>
				</tr>

				<?php $count++;
			} ?>

			<tr valign="top">
				<td colspan="3">
					<a href="#" id="sgm-add-location">Add Location</a> | <a href="#" id="sgm-remove-location">Remove Location</a>
				</td>
			</tr>
			<tr>
				<td colspan="3">
					<p style="margin: 20px 0 0 0; padding: 5px; background: #ddd;"><strong>Map with all markers:</strong> [ip-google-map show_directions="true" zoom="13"]</p>	
				</td>
			</tr>
		</table>

		<p><strong>If you are adding multiple maps to a page, make sure to set a unique "map_id" for each map.</strong></p>
		<p><strong>If a map has multiple markers, it will ignore "zoom" settings, and center the map with ass markers visible.</strong></p>

		<?php submit_button(); ?>
	</form>
</div><!--/wrap-->
<?php 
}
?>
