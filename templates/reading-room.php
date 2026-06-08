<?php
/**
 * Template Name: NeuroEcho Reading Room
 *
 * @package NeuroEcho_Book_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

neuroecho_book_gallery()->enqueue_assets();
get_header();
?>

<main id="primary" class="ne-library-page ne-reading-room-page">
	<?php echo do_shortcode( '[neuroecho_reading_room]' ); ?>
</main>

<?php
get_footer();
