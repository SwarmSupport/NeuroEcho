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

<?php
	while ( have_posts() ) :
		the_post();

			$meta             = NeuroEcho_Book_Gallery::get_book_meta( get_the_ID() );
			$archive_url      = get_post_type_archive_link( NeuroEcho_Book_Gallery::CPT );
			$gallery_url      = $archive_url ? $archive_url : home_url( '/' );
			$comments_enabled = comments_open() || get_comments_number();
			$tag_links        = NeuroEcho_Book_Gallery::render_tag_links( get_the_ID() );
		?>
<main id="primary" class="ne-reader-shell" data-ne-reader data-ne-reader-book-id="<?php echo esc_attr( get_the_ID() ); ?>">
	<div class="ne-reading-progress" data-ne-reading-progress aria-hidden="true"></div>
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

						<div class="ne-reader-library-card">
							<h2><?php esc_html_e( 'Book Details', 'neuroecho-book-gallery' ); ?></h2>
							<?php echo NeuroEcho_Book_Gallery::render_loan_status_badge( get_the_ID(), $meta ); ?>
							<?php if ( $tag_links ) : ?>
								<div class="ne-reader-tags" aria-label="<?php esc_attr_e( 'Book tags', 'neuroecho-book-gallery' ); ?>"><?php echo $tag_links; ?></div>
							<?php endif; ?>
							<dl>
							<?php if ( $meta['shelf_location'] ) : ?>
								<div>
									<dt><?php esc_html_e( 'Shelf', 'neuroecho-book-gallery' ); ?></dt>
									<dd><?php echo esc_html( $meta['shelf_location'] ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( $meta['isbn'] ) : ?>
								<div>
									<dt><?php esc_html_e( 'ISBN', 'neuroecho-book-gallery' ); ?></dt>
									<dd><?php echo esc_html( $meta['isbn'] ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( $meta['publication_year'] ) : ?>
								<div>
									<dt><?php esc_html_e( 'Published', 'neuroecho-book-gallery' ); ?></dt>
									<dd><?php echo esc_html( number_format_i18n( absint( $meta['publication_year'] ) ) ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( $meta['page_count'] ) : ?>
								<div>
									<dt><?php esc_html_e( 'Pages', 'neuroecho-book-gallery' ); ?></dt>
									<dd><?php echo esc_html( number_format_i18n( absint( $meta['page_count'] ) ) ); ?></dd>
								</div>
							<?php endif; ?>
						</dl>
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
							<a href="#ne-reader-bottom"><?php esc_html_e( 'Bottom', 'neuroecho-book-gallery' ); ?></a>
						</div>

					<div class="ne-reader-memory" data-ne-reader-memory hidden>
						<p data-ne-reader-memory-text></p>
						<div class="ne-reader-mini-actions">
							<button type="button" data-ne-resume-reading><?php esc_html_e( 'Resume', 'neuroecho-book-gallery' ); ?></button>
							<button type="button" data-ne-clear-position><?php esc_html_e( 'Clear', 'neuroecho-book-gallery' ); ?></button>
						</div>
					</div>

					<div class="ne-reader-bookmark-panel">
						<h2><?php esc_html_e( 'Bookmarks', 'neuroecho-book-gallery' ); ?></h2>
						<button type="button" data-ne-bookmark-toggle><?php esc_html_e( 'Save bookmark', 'neuroecho-book-gallery' ); ?></button>
						<div class="ne-reader-saved-list" data-ne-bookmarks></div>
					</div>

					<div class="ne-reader-annotation-panel">
						<label for="ne-reader-annotation"><?php esc_html_e( 'Annotation', 'neuroecho-book-gallery' ); ?></label>
						<textarea id="ne-reader-annotation" rows="3" data-ne-annotation-input></textarea>
						<button type="button" data-ne-save-annotation><?php esc_html_e( 'Save note', 'neuroecho-book-gallery' ); ?></button>
						<div class="ne-reader-saved-list" data-ne-annotations></div>
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
			<span id="ne-reader-bottom" class="ne-reader-bottom-anchor" tabindex="-1"></span>
		</main>
	<?php endwhile; ?>

<?php
get_footer();
