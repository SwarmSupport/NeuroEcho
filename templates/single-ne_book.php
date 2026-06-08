<?php
/**
 * Single book reader template.
 *
 * @package NeuroEcho_Book_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

neuroecho_book_gallery()->enqueue_assets();
get_header();
?>

<main id="primary" class="ne-reader-shell" data-ne-reader>
	<div class="ne-reading-progress" data-ne-reading-progress aria-hidden="true"></div>

	<?php
	while ( have_posts() ) :
		the_post();

		$meta             = NeuroEcho_Book_Gallery::get_book_meta( get_the_ID() );
		$archive_url      = get_post_type_archive_link( NeuroEcho_Book_Gallery::CPT );
		$gallery_url      = $archive_url ? $archive_url : home_url( '/' );
		$comments_enabled = comments_open() || get_comments_number();
		?>
		<article <?php post_class( 'ne-reader' ); ?>>
			<header class="ne-reader-header">
				<div class="ne-reader-cover-wrap">
					<?php echo NeuroEcho_Book_Gallery::render_cover( get_the_ID(), 'large' ); ?>
				</div>

				<div class="ne-reader-title-block">
					<a class="ne-back-link" href="<?php echo esc_url( $gallery_url ); ?>"><?php esc_html_e( 'Back to gallery', 'neuroecho-book-gallery' ); ?></a>

					<p class="ne-kicker"><?php esc_html_e( 'Reader', 'neuroecho-book-gallery' ); ?></p>
					<h1><?php the_title(); ?></h1>

					<?php if ( $meta['subtitle'] ) : ?>
						<p class="ne-reader-subtitle"><?php echo esc_html( $meta['subtitle'] ); ?></p>
					<?php endif; ?>

					<?php if ( $meta['share_note'] ) : ?>
						<p class="ne-reader-share-note"><?php echo esc_html( $meta['share_note'] ); ?></p>
					<?php endif; ?>

					<div class="ne-reader-authors">
						<?php echo NeuroEcho_Book_Gallery::render_author_links( get_the_ID() ); ?>
					</div>

					<div class="ne-reader-facts">
						<?php echo NeuroEcho_Book_Gallery::render_book_facts( get_the_ID(), $meta ); ?>
						<span><?php echo esc_html( NeuroEcho_Book_Gallery::get_comment_count_label( get_the_ID() ) ); ?></span>
					</div>

					<div class="ne-reader-actions" aria-label="<?php esc_attr_e( 'Reader shortcuts', 'neuroecho-book-gallery' ); ?>">
						<a href="#ne-reader-content"><?php esc_html_e( 'Start reading', 'neuroecho-book-gallery' ); ?></a>
						<?php if ( $comments_enabled ) : ?>
							<a href="#ne-reader-comments"><?php esc_html_e( 'Comments', 'neuroecho-book-gallery' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			</header>

			<div class="ne-reader-layout">
				<aside class="ne-reader-tools" aria-label="<?php esc_attr_e( 'Reading settings', 'neuroecho-book-gallery' ); ?>">
					<div class="ne-reader-mode-group" aria-label="<?php esc_attr_e( 'Reader theme', 'neuroecho-book-gallery' ); ?>">
						<button type="button" data-ne-theme-value="paper" aria-pressed="true"><?php esc_html_e( 'Paper', 'neuroecho-book-gallery' ); ?></button>
						<button type="button" data-ne-theme-value="night" aria-pressed="false"><?php esc_html_e( 'Night', 'neuroecho-book-gallery' ); ?></button>
						<button type="button" data-ne-theme-value="focus" aria-pressed="false"><?php esc_html_e( 'Focus', 'neuroecho-book-gallery' ); ?></button>
					</div>

					<div class="ne-reader-control">
						<label for="ne-reader-font-size"><?php esc_html_e( 'Text size', 'neuroecho-book-gallery' ); ?></label>
						<input id="ne-reader-font-size" type="range" min="16" max="23" step="1" value="18" data-ne-font-size />
					</div>

					<div class="ne-reader-control">
						<label for="ne-reader-measure"><?php esc_html_e( 'Line width', 'neuroecho-book-gallery' ); ?></label>
						<input id="ne-reader-measure" type="range" min="56" max="84" step="2" value="70" data-ne-measure />
					</div>

					<nav class="ne-reader-toc" aria-label="<?php esc_attr_e( 'Chapter navigation', 'neuroecho-book-gallery' ); ?>" data-ne-toc></nav>

					<div class="ne-reader-tool-actions">
						<button type="button" data-ne-reset-reader><?php esc_html_e( 'Reset', 'neuroecho-book-gallery' ); ?></button>
						<a href="#primary"><?php esc_html_e( 'Top', 'neuroecho-book-gallery' ); ?></a>
					</div>
				</aside>

				<div id="ne-reader-content" class="ne-reader-content" data-ne-reader-content tabindex="-1">
					<?php
					the_content();

					wp_link_pages(
						array(
							'before' => '<nav class="ne-page-links" aria-label="' . esc_attr__( 'Reader pages', 'neuroecho-book-gallery' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>
			</div>
		</article>

		<?php if ( $comments_enabled ) : ?>
			<section id="ne-reader-comments" class="ne-reader-comments" aria-label="<?php esc_attr_e( 'Reader comments', 'neuroecho-book-gallery' ); ?>">
				<?php comments_template(); ?>
			</section>
		<?php endif; ?>
	<?php endwhile; ?>
</main>

<?php
get_footer();
