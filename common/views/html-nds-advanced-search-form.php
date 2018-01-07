<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
 * Markup for the custom search form goes here.
 *
 * Note: Form input is stored inside an array with the plugin's name
 * e.g. $_POST['plugin_name']
 */

/**
 * Get fields from the ACF group.
 * https://www.advancedcustomfields.com/resources/get_field_object/
 */

// create a dropdown list for video languages.
$video_language_meta_key = 'field_5a06e1ba30626';
$video_language_meta = get_field_object( $video_language_meta_key );

if ( $video_language_meta ) {
	$nds_video_language = $video_language_meta['choices'];
	$language_select = '
		<label for="nds_video_language">' . __( 'Filter by Language', $this->plugin_text_domain ) . '</label>
		<select id="nds_video_language" name="' . $this->plugin_name . '[acf_video_metadata][video_language]">
			<option value="">' . __( 'Select Language', $this->plugin_text_domain ) . '</option>';
	foreach ( $nds_video_language as $key => $value ) {
		$language_select .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>' . "\n";
	}
	$language_select .= '</select>';
}

// create a dropdown for the video duration.
$video_duration_meta_key = 'field_5a06e25730627';
$video_duration_meta = get_field_object( $video_duration_meta_key );

if ( $video_duration_meta ) {
	$nds_video_duration = $video_duration_meta['choices'];

	$duration_select = '
		<label for="nds_video_language">' . __( 'Filter by Length', $this->plugin_text_domain ) . '</label>
		<select id="nds_video_duration" name="' . $this->plugin_name . '[acf_video_metadata][video_duration]">
			<option value="">' . __( 'Select Duration', $this->plugin_text_domain ) . '</option>';
	foreach ( $nds_video_duration as $key => $value ) {
		$duration_select .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>' . "\n";
	}
	$duration_select .= '</select>';
}

// get taxonomies associated with the specified post type.
$custom_taxonomies = get_object_taxonomies( 'videolib', 'objects' );
$custom_tax_checkbox_list = '';
foreach ( $custom_taxonomies as $custom_taxonomy ) {
	// https://developer.wordpress.org/reference/classes/wp_term_query/__construct.
	$args = array(
		'hide_empty' => false,
		'parent' => 0, // only top level terms.
		'taxonomy' => $custom_taxonomy->name,
	);

	// get terms for the specified taxonomies.
	$custom_taxonomy_terms = get_terms( $args );

	// create a checkbox list for the the terms.
	$custom_tax_checkbox_list .= '<fieldset class="checkboxgroup">';
	$custom_tax_checkbox_list .= '<legend>' . $custom_taxonomy->label . ' </legend>';

	foreach ( $custom_taxonomy_terms as $taxonomy_term ) {

		$custom_tax_checkbox_list .= '<p>
			<label for="' . $taxonomy_term->slug . '">
				<input type="checkbox" id="' . $taxonomy_term->slug . '" name="' . $this->plugin_name . '[video_taxonomies][' . $taxonomy_term->taxonomy . '][]" value="' . $taxonomy_term->slug . '"> ' . $taxonomy_term->name .
			'</label></p>';

	}
	$custom_tax_checkbox_list .= '</fieldset>';
}
?>

<div class="nds-advanced-search-form-container">
	<form id="nds-advanced-search-form" role="search" method="POST" class="search-form" action="">
		<button class="nds-search-button" type="submit" class="search-submit"><i class="fa fa-search" aria-hidden="true"><span class="screen-reader-text"><?php echo esc_html_x( 'Search', 'submit button', '$this->plugin_text_domain' ); ?></span></i></button>
		<div class="nds-input-container">
			<label>
				<span class="screen-reader-text"><?php echo esc_attr_x( 'Search for:', 'label', $this->plugin_text_domain ); ?></span>
				<input required class="nds-search-input" id="nds-search-box" type="search" class="search-field" placeholder="<?php echo esc_attr_x( 'start typing for suggestions &hellip;', 'placeholder', $this->plugin_text_domain ); ?>" name="<?php echo esc_attr( $this->plugin_name ); ?>[search_key]" />
			</label>
		</div> <!-- nds-input-container -->
		<br>
		<div class="nds-search-options-container">
			<div id="nds-search-options" class="nds-search-options">
				<div class="nds-search-options-left">
					<?php echo $custom_tax_checkbox_list; ?>
				</div> <!-- nds-search-options-left -->
				<div class="nds-search-options-right">
					<fieldset>
						<div>
							<p>
								<?php echo $language_select; ?>
							</p>
							<p>
								<?php echo $duration_select; ?>
							</p>
						</div>
						<div>
							<p>
								<label for="nds-video-from"><?php esc_attr_e( 'From', $this->plugin_text_domain ); ?></label>
								<input type="text" id="nds-video-from" name="<?php echo $this->plugin_name; ?>[acf_video_metadata][video_from_date]" placeholder="<?php _e( 'Start Date', $this->plugin_text_domain );  ?>">
							</p>
							<p>
								<label for="nds-video-to"><?php _e( 'To', $this->plugin_text_domain ); ?></label>
								<input type="text" id="nds-video-to" name="<?php echo $this->plugin_name; ?>[acf_video_metadata][video_to_date]" placeholder="<?php _e( 'End Date', $this->plugin_text_domain );  ?>" >
							</p>
						</div>
					</fieldset>
				</div> <!-- nds-search-options-right -->
			</div> <!-- nds-search-options -->
		</div> <!-- nds-search-options-container -->
	</form> <!-- nds-advanced-search-form -->
	<a class="btn-show-options" href="#"><i><?php _e( 'Advanced Options', $this->plugin_text_domain ); ?></i></a>
</div> <!-- nds-advanced-search-form-container -->
