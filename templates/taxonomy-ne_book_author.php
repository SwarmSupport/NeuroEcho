<?php
/**
 * Book author taxonomy archive template.
 *
 * @package NeuroEcho_Book_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

neuroecho_book_gallery()->enqueue_assets();

$term  = get_queried_object();
$books = new WP_Query(
	array(
		'post_type'      => NeuroEcho_Book_Gallery::get_library_post_types(),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'tax_query'      => array(
			array(
				'taxonomy' => NeuroEcho_Book_Gallery::TAX_AUTHOR,
				'field'    => 'term_id',
				'terms'    => $term instanceof WP_Term ? absint( $term->term_id ) : 0,
			),
		),
	)
);

get_header();
?>

<main id="primary" class="ne-author-page">
	<section class="ne-book-gallery ne-author-archive" data-ne-book-gallery>
		<div class="ne-gallery-header ne-author-header">
			<div>
				<p class="ne-kicker"><?php esc_html_e( 'Book Author', 'neuroecho-book-gallery' ); ?></p>
				<h2><?php echo esc_html( $term instanceof WP_Term ? $term->name : __( 'Book Author', 'neuroecho-book-gallery' ) ); ?></h2>
			</div>
			<p class="ne-gallery-count">
				<?php
				printf(
					/* translators: %s: number of books. */
					esc_html( _n( '%s book', '%s books', (int) $books->found_posts, 'neuroecho-book-gallery' ) ),
					esc_html( number_format_i18n( $books->found_posts ) )
				);
				?>
			</p>
		</div>

		<?php if ( $term instanceof WP_Term && $term->description ) : ?>
			<div class="ne-author-bio">
				<p><?php echo esc_html( $term->description ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $books->have_posts() ) : ?>
			<div class="ne-book-grid">
				<?php
				while ( $books->have_posts() ) :
					$books->the_post();
					echo NeuroEcho_Book_Gallery::render_book_card( get_the_ID() );
				endwhile;
				?>
			</div>
		<?php else : ?>
			<div class="ne-gallery-empty ne-author-empty">
				<h3><?php esc_html_e( 'No books from this author yet', 'neuroecho-book-gallery' ); ?></h3>
				<p><?php esc_html_e( 'Assign this Book Author to articles or Books to show them here.', 'neuroecho-book-gallery' ); ?></p>
			</div>
		<?php endif; ?>
	</section>
</main>

<?php
wp_reset_postdata();
get_footer();
