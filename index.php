<?php
/**
 * Theme index.
 *
 * @package NeuroEcho_Book_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="ne-book-archive-main">
	<?php echo do_shortcode( '[neuroecho_book_gallery heading="Book Gallery"]' ); ?>
</main>

<?php
get_footer();
