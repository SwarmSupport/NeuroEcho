<?php
/**
 * Book search template.
 *
 * @package NeuroEcho_Book_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

neuroecho_book_gallery()->enqueue_assets();

$search_query = trim( get_search_query( false ) );
$heading      = $search_query
	? sprintf(
		/* translators: %s: search query. */
		__( 'Search: %s', 'neuroecho-book-gallery' ),
		$search_query
	)
	: __( 'Book Gallery', 'neuroecho-book-gallery' );

get_header();
?>

<main id="primary" class="ne-search-page">
	<?php echo neuroecho_book_gallery()->render_gallery_shortcode( array( 'heading' => $heading ) ); ?>
</main>

<?php
get_footer();
