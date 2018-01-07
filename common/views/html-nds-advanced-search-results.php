<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$transient_name = $this->plugin_transients['autosuggest_transient'];

// retrieve the post types to search from the plugin settings.
$plugin_options = get_option( $this->plugin_name );
$post_types = array_keys( $plugin_options, 1, true );

// check if cached posts are available.
$cached_posts = get_transient( $transient_name );
if ( false === $cached_posts ) {

	// retrieve posts for the specified post types by running get_posts and cache the posts as well.
	$cached_posts = $this->cache_posts_in_post_types();
}

// extract the cached post ids from the transient into an array.
$cached_post_ids = array_column( $cached_posts, 'id' );

// run a new query against the search key and the cached post ids for the seleted post types.
$args = array(
	'post_type'           => $post_types,
	'posts_per_page'      => -1,
	'no_found_rows'       => true, // as we don't need pagination.
	'post__in'            => $cached_post_ids, // use post ids that were cached in the query earlier.
	'ignore_sticky_posts' => true,
	's'                   => $search_term,  // the keyword/phrase to search.
	'sentence'            => true, // perform a phrase search.
);

// create a dynamic tax_query if the user selected taxonomy terms in the search filters.
$tax_query = array();

// array to hold the selected taxonomies to create a dynamic filter buttons.
$user_tax_filter = array();

foreach ( $user_selected_video_taxonomies as $taxonomy_slug => $taxonomy_terms ) {

	if ( ! empty( $user_selected_video_taxonomies[ $taxonomy_slug ] ) ) {

		// tax_query takes an array of arrays.
		if ( ! array_key_exists( 'relation', $tax_query ) && count( $user_selected_video_taxonomies ) > 1 ) {
			/*
			 * Add 'Relation' only for multiple inner taxonomy arrays.
			 * This is Relation between the Taxonomies. (Not terms)
			 */
			$tax_query['relation'] = 'OR'; // 'AND' for a strict search.
			// With 'OR' the posts can contain either of the specified taxonomies.
		}

		/*
		 * Dynamically create the array of tax query arguments
		 * based on the custom taxonmies that were selected.
		 */
		array_push( $tax_query,
			array(
				'taxonomy' => $taxonomy_slug, // Taxonomy.
				'field' => 'slug', // Select by 'id' or 'slug'
				'terms' => $taxonomy_terms, // Taxonomy term(s).
				'include_children' => true, // Whether or not to include children for hierarchical taxonomies.
				'operator' => 'IN', // Relation between Terms. Possible values are 'IN', 'NOT IN', 'AND'.
			)
		);

		// populate the terms array for creating the filter buttons later.
		$user_tax_filter[ $taxonomy_slug ] = $taxonomy_terms;
	}
}

// create a dynamic meta_query if the user selected custom fields in the advanced options.
$meta_query = array();

foreach ( $user_selected_video_meta as $key => $value ) {

	if ( ! empty( $user_selected_video_meta[ $key ] ) ) {

		if ( ! array_key_exists( 'relation', $meta_query ) ) {
			$meta_query['relation'] = 'AND';
		}

		switch ( $key ) {
			case 'video_language':
			case 'video_duration':
				array_push( $meta_query,
					array(
						'key' => 'video_metadata_' . $key, // Custom field key.
						'value' => $value, // Custom field value. Array support is limited to a compare value of 'IN', 'NOT IN', 'BETWEEN', or 'NOT BETWEEN'
						'compare' => '=', // For arrays values are 'IN', 'NOT IN', 'BETWEEN', or 'NOT BETWEEN'.
					)
				);
				break;
			case 'video_from_date':
				$from_date = new \DateTime( $user_selected_video_meta['video_from_date'] );
				$to_date = new \DateTime( $user_selected_video_meta['video_to_date'] );
				array_push( $meta_query,
					array(
						'key' => 'video_metadata_video_date', // Custom field key.
						'compare' => 'BETWEEN', // For arrays values are 'IN', 'NOT IN', 'BETWEEN', or 'NOT BETWEEN'.
						'value' => array( $from_date->format( 'Ymd' ), $to_date->format( 'Ymd' ) ), // Custom field value.
					)
				);
				break;
		}
	}
}

// whether to include the tax_query in the wp_query args.
if ( ! empty( $tax_query ) ) {
	$args['tax_query'] = $tax_query;
}

// whether to include the meta_query in the wp_query args.
if ( ! empty( $meta_query ) ) {
	$args['meta_query'] = $meta_query;
}
$search_query = new \WP_Query( $args );

?>

<!-- Search Results -->
<div class="nds-search-results">
	<?php
	if ( $search_query->have_posts() ) :

		/**
		 * Create a dynamic list of filter buttons to show/hide posts
		 * in the displayed search results based on the Taxonomy Terms.
		 */
		$tax_filter_html = false;
		$add_all_results_button = true;
		foreach ( $user_tax_filter as $tax_slug => $taxonomy ) {
			if ( count( $taxonomy ) <= 1 ) {
				continue;
			}

			$tax_filter_html .= '<div class="nds-tax-filters">';
			if ( $add_all_results_button ) {
				$tax_filter_html .= '<button class="active" id="all">' . __( 'All Results', $this->plugin_text_domain ) . '</button>';
				$add_all_results_button = false;
			}
			$taxonomy_obj = get_taxonomy( $tax_slug );
			foreach ( $taxonomy as $tax_term_slug ) {
				$term_filter = get_term_by ( 'slug', $tax_term_slug, $tax_slug );
				$tax_filter_html .= '<button class="' . $taxonomy_obj->name . '" id="' . $taxonomy_obj->name . '-' . $tax_term_slug . '">' . esc_html( $term_filter->name ) . '</button>';
			}
			$tax_filter_html .= '</div>';
		}
		// buttons for further filtering the dispalyed search results by Taxonomy.
		echo $tax_filter_html;
	?>
		<ul class="flex-grid-container">
			<!-- Start the Loop. -->
			<?php
			while ( $search_query->have_posts() ) :
				$search_query->the_post();
				$post_id = get_the_ID();

				// get the acf meta associated with the post.
				$video_metadata = get_field( 'video_metadata' );

				// get the terms associated with the post.
				$post_taxonomies = get_post_taxonomies();
				$tax_filters = array();
				foreach ( $post_taxonomies as $post_taxonomy ) {
					$post_terms = get_the_terms( $post_id, $post_taxonomy );
					if ( $post_terms ) {
						array_push( $tax_filters, $post_taxonomy );
						foreach ( $post_terms as $post_term ) {
							$term = ( $post_term->parent === 0 ) ? $post_term : get_term( $post_term->parent, $post_taxonomy );
							array_push( $tax_filters, $term->taxonomy . '-' . $term->slug );
						}
					}
				}

				// extract subfields.
				$video_thumbnail = ! empty( $video_metadata['video_thumbnail'] ) ? esc_attr( $video_metadata['video_thumbnail'] ) : false;
				$video_url = ! empty( $video_metadata['video_url'] ) ? $video_metadata['video_url'] : false;
				$video_credits = ! empty( $video_metadata['video_credits'] ) ? $video_metadata['video_credits'] : false;
		?>
				<li class="flex-grid-item <?php echo join( ' ', $tax_filters ); ?>" >

					<!-- the thumbnail -->
					<p>
						<?php if ( $video_thumbnail ) : ?>
							<a data-credits="<?php echo esc_attr( $video_credits ); ?>" class="video-colorbox" href="<?php echo esc_attr( $video_url ); ?>">
								<?php echo wp_get_attachment_image( $video_thumbnail, 'medium' ); ?>
							</a>
						<?php elseif ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>">
								<?php the_post_thumbnail( 'medium' ); ?>
							</a>
						<?php endif; ?>
					</p>
					<!-- title -->
					<p class="card-title">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</p>
					<!-- excerpt -->
					<p class="card-excerpt">
						<?php echo wp_trim_words( get_the_content(), 30, ' ...' ); ?>
					</p>

				</li> <!-- flex-grid-item -->
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
		</ul> <!-- flex-grid-container -->
		<?php else : ?>
		<p>
			<?php echo __( 'Nothing Found ...', $this->plugin_text_domain ); ?>
		</p>
		<?php endif; ?>
</div> <!-- nds-search-results -->


