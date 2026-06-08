<?php
/**
 * Template Name: NeuroEcho Library Hours
 *
 * @package NeuroEcho_Book_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

neuroecho_book_gallery()->enqueue_assets();
get_header();
?>

<main id="primary" class="ne-library-page ne-hours-page">
	<?php echo do_shortcode( '[neuroecho_library_hours]' ); ?>
</main>

<?php
get_footer();
