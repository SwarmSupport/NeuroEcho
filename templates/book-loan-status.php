<?php
/**
 * Template Name: NeuroEcho Book Loan Status
 *
 * @package NeuroEcho_Book_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

neuroecho_book_gallery()->enqueue_assets();
get_header();
?>

<main id="primary" class="ne-library-page ne-loan-status-page">
	<?php echo do_shortcode( '[neuroecho_library_loan_status]' ); ?>
</main>

<?php
get_footer();
