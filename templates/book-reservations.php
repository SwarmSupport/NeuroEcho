<?php
/**
 * Template Name: NeuroEcho Book Reservations
 *
 * @package NeuroEcho_Book_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

neuroecho_book_gallery()->enqueue_assets();
get_header();
?>

<main id="primary" class="ne-library-page ne-reservations-page">
	<?php echo do_shortcode( '[neuroecho_book_reservations]' ); ?>
</main>

<?php
get_footer();
