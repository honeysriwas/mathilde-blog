<?php
/**
 * Search form.
 *
 * @package Mathilde
 */
?>
<form role="search" method="get" class="search-form field-inline" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="s-<?php echo esc_attr( wp_unique_id() ); ?>"><?php esc_html_e( 'Search for:', 'mathilde' ); ?></label>
	<input type="search" class="search-field" placeholder="<?php esc_attr_e( 'Search…', 'mathilde' ); ?>" value="<?php echo get_search_query(); ?>" name="s">
	<button type="submit" class="btn" aria-label="<?php esc_attr_e( 'Search', 'mathilde' ); ?>"><?php mathilde_the_icon( 'search', 18 ); ?></button>
</form>
