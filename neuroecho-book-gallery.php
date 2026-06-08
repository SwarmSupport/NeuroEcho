<?php
/**
 * Plugin Name: NeuroEcho Book Gallery
 * Description: Adds a searchable book gallery, multi-author support, and an accessible long-form reader for WordPress.
 * Version: 1.0.0
 * Author: NeuroEcho
 * Text Domain: neuroecho-book-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NeuroEcho_Book_Gallery {
	const VERSION    = '1.0.0';
	const CPT        = 'ne_book';
	const TAX_AUTHOR = 'ne_book_author';
	const META       = '_ne_book_';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_content_types' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::CPT, array( $this, 'save_book_meta' ) );
		add_action( 'save_post_post', array( $this, 'save_book_meta' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		add_shortcode( 'neuroecho_book_gallery', array( $this, 'render_gallery_shortcode' ) );

		add_filter( 'single_template', array( $this, 'single_template' ) );
		add_filter( 'archive_template', array( $this, 'archive_template' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'comment_form_defaults', array( $this, 'comment_form_defaults' ) );
	}

	public static function activate() {
		self::instance()->register_content_types();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public function register_content_types() {
		$book_labels = array(
			'name'                  => __( 'Books', 'neuroecho-book-gallery' ),
			'singular_name'         => __( 'Book', 'neuroecho-book-gallery' ),
			'menu_name'             => __( 'Books', 'neuroecho-book-gallery' ),
			'name_admin_bar'        => __( 'Book', 'neuroecho-book-gallery' ),
			'add_new'               => __( 'Add New', 'neuroecho-book-gallery' ),
			'add_new_item'          => __( 'Add New Book', 'neuroecho-book-gallery' ),
			'new_item'              => __( 'New Book', 'neuroecho-book-gallery' ),
			'edit_item'             => __( 'Edit Book', 'neuroecho-book-gallery' ),
			'view_item'             => __( 'View Book', 'neuroecho-book-gallery' ),
			'all_items'             => __( 'All Books', 'neuroecho-book-gallery' ),
			'search_items'          => __( 'Search Books', 'neuroecho-book-gallery' ),
			'not_found'             => __( 'No books found.', 'neuroecho-book-gallery' ),
			'not_found_in_trash'    => __( 'No books found in Trash.', 'neuroecho-book-gallery' ),
			'featured_image'        => __( 'Book Cover', 'neuroecho-book-gallery' ),
			'set_featured_image'    => __( 'Set book cover', 'neuroecho-book-gallery' ),
			'remove_featured_image' => __( 'Remove book cover', 'neuroecho-book-gallery' ),
			'use_featured_image'    => __( 'Use as book cover', 'neuroecho-book-gallery' ),
		);

		register_post_type(
			self::CPT,
			array(
				'labels'             => $book_labels,
				'public'             => true,
				'has_archive'        => true,
				'menu_icon'          => 'dashicons-book-alt',
				'rewrite'            => array( 'slug' => 'books' ),
				'show_in_rest'       => true,
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'revisions' ),
				'taxonomies'         => array( self::TAX_AUTHOR ),
				'publicly_queryable' => true,
			)
		);

		register_taxonomy(
			self::TAX_AUTHOR,
			$this->get_gallery_post_types(),
			array(
				'labels'            => array(
					'name'                       => __( 'Book Authors', 'neuroecho-book-gallery' ),
					'singular_name'              => __( 'Book Author', 'neuroecho-book-gallery' ),
					'search_items'               => __( 'Search Authors', 'neuroecho-book-gallery' ),
					'popular_items'              => __( 'Popular Authors', 'neuroecho-book-gallery' ),
					'all_items'                  => __( 'All Authors', 'neuroecho-book-gallery' ),
					'edit_item'                  => __( 'Edit Author', 'neuroecho-book-gallery' ),
					'update_item'                => __( 'Update Author', 'neuroecho-book-gallery' ),
					'add_new_item'               => __( 'Add New Author', 'neuroecho-book-gallery' ),
					'new_item_name'              => __( 'New Author Name', 'neuroecho-book-gallery' ),
					'separate_items_with_commas' => __( 'Separate authors with commas', 'neuroecho-book-gallery' ),
					'add_or_remove_items'        => __( 'Add or remove authors', 'neuroecho-book-gallery' ),
					'choose_from_most_used'      => __( 'Choose from the most used authors', 'neuroecho-book-gallery' ),
					'menu_name'                  => __( 'Authors', 'neuroecho-book-gallery' ),
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'book-author' ),
			)
		);

		$this->register_meta_fields();
	}

	private function register_meta_fields() {
		$fields = array(
			'subtitle'     => 'string',
			'cover_url'    => 'string',
			'format'       => 'string',
			'share_note'   => 'string',
			'reading_time' => 'integer',
		);

		foreach ( $this->get_gallery_post_types() as $post_type ) {
			foreach ( $fields as $field => $type ) {
				register_post_meta(
					$post_type,
					self::META . $field,
					array(
						'type'              => $type,
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => array( $this, 'sanitize_meta_value' ),
						'auth_callback'     => function() {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}
	}

	public function sanitize_meta_value( $value, $meta_key ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		switch ( $meta_key ) {
			case self::META . 'cover_url':
				return esc_url_raw( $value );
			case self::META . 'reading_time':
				return absint( $value );
			case self::META . 'share_note':
				return sanitize_textarea_field( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	public function add_meta_boxes() {
		foreach ( $this->get_gallery_post_types() as $post_type ) {
			add_meta_box(
				'ne-book-details',
				__( 'Share Details', 'neuroecho-book-gallery' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'ne_save_book_meta', 'ne_book_meta_nonce' );
		$meta = self::get_book_meta( $post->ID );
		?>
		<p>
			<label for="ne_book_subtitle"><strong><?php esc_html_e( 'Subtitle', 'neuroecho-book-gallery' ); ?></strong></label>
			<input class="widefat" id="ne_book_subtitle" name="ne_book_subtitle" type="text" value="<?php echo esc_attr( $meta['subtitle'] ); ?>" />
		</p>
		<p>
			<label for="ne_book_cover_url"><strong><?php esc_html_e( 'Book Cover URL', 'neuroecho-book-gallery' ); ?></strong></label>
			<input class="widefat" id="ne_book_cover_url" name="ne_book_cover_url" type="url" value="<?php echo esc_url( $meta['cover_url'] ); ?>" />
			<span class="description"><?php esc_html_e( 'Optional fallback. The WordPress featured image is used first as the cover.', 'neuroecho-book-gallery' ); ?></span>
		</p>
		<p>
			<label for="ne_book_format"><strong><?php esc_html_e( 'Format Label', 'neuroecho-book-gallery' ); ?></strong></label>
			<input class="widefat" id="ne_book_format" name="ne_book_format" type="text" value="<?php echo esc_attr( $meta['format'] ); ?>" placeholder="<?php esc_attr_e( 'Short story, draft chapter, essay, poem...', 'neuroecho-book-gallery' ); ?>" />
		</p>
		<p>
			<label for="ne_book_reading_time"><strong><?php esc_html_e( 'Estimated Reading Time', 'neuroecho-book-gallery' ); ?></strong></label>
			<input id="ne_book_reading_time" name="ne_book_reading_time" type="number" min="0" step="1" value="<?php echo esc_attr( $meta['reading_time'] ); ?>" />
			<span class="description"><?php esc_html_e( 'Minutes. Leave blank to hide.', 'neuroecho-book-gallery' ); ?></span>
		</p>
		<p>
			<label for="ne_book_share_note"><strong><?php esc_html_e( 'Share Note', 'neuroecho-book-gallery' ); ?></strong></label>
			<textarea class="widefat" id="ne_book_share_note" name="ne_book_share_note" rows="3" placeholder="<?php esc_attr_e( 'A short note about why you are sharing this piece.', 'neuroecho-book-gallery' ); ?>"><?php echo esc_textarea( $meta['share_note'] ); ?></textarea>
		</p>
		<?php
	}

	public function save_book_meta( $post_id ) {
		$nonce = isset( $_POST['ne_book_meta_nonce'] ) ? $this->sanitize_request_value( $_POST['ne_book_meta_nonce'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'ne_save_book_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'subtitle',
			'cover_url',
			'format',
			'share_note',
			'reading_time',
		);

		foreach ( $fields as $field ) {
			$form_key = 'ne_book_' . $field;
			$meta_key = self::META . $field;

			if ( ! isset( $_POST[ $form_key ] ) ) {
				delete_post_meta( $post_id, $meta_key );
				continue;
			}

			$value = $this->sanitize_meta_value( wp_unslash( $_POST[ $form_key ] ), $meta_key );

			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	public function register_assets() {
		wp_register_style(
			'neuroecho-book-gallery',
			$this->asset_url( 'assets/neuroecho-book-gallery.css' ),
			array(),
			self::VERSION
		);

		wp_register_script(
			'neuroecho-book-gallery',
			$this->asset_url( 'assets/neuroecho-book-gallery.js' ),
			array(),
			self::VERSION,
			true
		);

		if ( $this->should_enqueue_assets() ) {
			$this->enqueue_assets();
		}
	}

	public function enqueue_assets() {
		wp_enqueue_style( 'neuroecho-book-gallery' );
		wp_enqueue_script( 'neuroecho-book-gallery' );
	}

	private function asset_url( $path ) {
		$path       = ltrim( $path, '/' );
		$plugin_dir = trailingslashit( wp_normalize_path( plugin_dir_path( __FILE__ ) ) );

		if ( function_exists( 'get_stylesheet_directory' ) && function_exists( 'get_stylesheet_directory_uri' ) ) {
			$theme_dir = trailingslashit( wp_normalize_path( get_stylesheet_directory() ) );

			if ( 0 === strpos( $plugin_dir, $theme_dir ) ) {
				return trailingslashit( get_stylesheet_directory_uri() ) . $path;
			}
		}

		return plugins_url( $path, __FILE__ );
	}

	public function render_gallery_shortcode( $atts ) {
		$this->enqueue_assets();

		$atts = shortcode_atts(
			array(
				'limit'       => 'all',
				'author'      => '',
				'show_search' => 'true',
				'heading'     => __( 'Book Gallery', 'neuroecho-book-gallery' ),
			),
			$atts,
			'neuroecho_book_gallery'
		);

		$limit_value     = trim( (string) $atts['limit'] );
		$show_all        = $this->is_all_limit( $limit_value );
		$limit           = $show_all ? -1 : max( 1, min( 200, absint( $limit_value ) ) );
		$search          = isset( $_GET['ne_book_search'] ) ? trim( $this->sanitize_request_value( $_GET['ne_book_search'] ) ) : '';
		$has_search      = '' !== $search;
		$request_author  = isset( $_GET['ne_book_author'] ) ? sanitize_title( $this->sanitize_request_value( $_GET['ne_book_author'] ) ) : '';
		$selected_author = $has_search ? $request_author : '';
		$fixed_author    = sanitize_title( $atts['author'] );
		$paged           = ( ! $show_all && isset( $_GET['ne_book_page'] ) ) ? max( 1, absint( $this->sanitize_request_value( $_GET['ne_book_page'] ) ) ) : 1;
		$instance_id     = wp_unique_id( 'ne-book-gallery-' );

		if ( $fixed_author ) {
			$selected_author = $fixed_author;
		}

		$args = array(
			'post_type'      => $this->get_gallery_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
		);

		if ( ! $show_all ) {
			$args['paged'] = $paged;
		}

		if ( $has_search ) {
			$matching_ids      = $this->find_book_ids_for_search( $search );
			$args['post__in'] = $matching_ids ? $matching_ids : array( 0 );
			$args['orderby']  = 'post__in';
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		}

		if ( $selected_author ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => self::TAX_AUTHOR,
					'field'    => 'slug',
					'terms'    => $selected_author,
				),
			);
		}

		$books   = new WP_Query( $args );
		$authors = get_terms(
			array(
				'taxonomy'   => self::TAX_AUTHOR,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		ob_start();
		?>
		<section class="ne-book-gallery" data-ne-book-gallery>
			<div class="ne-gallery-header">
				<div>
					<p class="ne-kicker"><?php esc_html_e( 'Library', 'neuroecho-book-gallery' ); ?></p>
					<h2><?php echo esc_html( $atts['heading'] ); ?></h2>
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

			<?php if ( 'false' !== strtolower( (string) $atts['show_search'] ) ) : ?>
				<form class="ne-gallery-search" role="search" method="get" action="<?php echo esc_url( remove_query_arg( array( 'ne_book_page', 'ne_book_search', 'ne_book_author' ) ) ); ?>">
					<label class="screen-reader-text" for="<?php echo esc_attr( $instance_id ); ?>search"><?php esc_html_e( 'Search books', 'neuroecho-book-gallery' ); ?></label>
					<input id="<?php echo esc_attr( $instance_id ); ?>search" name="ne_book_search" type="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search titles, authors, notes, or comments', 'neuroecho-book-gallery' ); ?>" />

					<?php if ( ! is_wp_error( $authors ) && $authors && ! $fixed_author ) : ?>
						<label class="screen-reader-text" for="<?php echo esc_attr( $instance_id ); ?>author"><?php esc_html_e( 'Filter by author', 'neuroecho-book-gallery' ); ?></label>
						<select id="<?php echo esc_attr( $instance_id ); ?>author" name="ne_book_author">
							<option value=""><?php esc_html_e( 'All authors', 'neuroecho-book-gallery' ); ?></option>
							<?php foreach ( $authors as $author ) : ?>
								<option value="<?php echo esc_attr( $author->slug ); ?>" <?php selected( $selected_author, $author->slug ); ?>>
									<?php echo esc_html( $author->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>

					<button type="submit"><?php esc_html_e( 'Search', 'neuroecho-book-gallery' ); ?></button>

					<?php if ( $has_search || ( $request_author && ! $fixed_author ) ) : ?>
						<a class="ne-gallery-reset" href="<?php echo esc_url( remove_query_arg( array( 'ne_book_search', 'ne_book_author', 'ne_book_page' ) ) ); ?>">
							<?php esc_html_e( 'Reset', 'neuroecho-book-gallery' ); ?>
						</a>
					<?php endif; ?>
				</form>
			<?php endif; ?>

			<?php if ( $books->have_posts() ) : ?>
				<div class="ne-book-grid">
					<?php
					while ( $books->have_posts() ) :
						$books->the_post();
						$meta = self::get_book_meta( get_the_ID() );
						?>
						<article <?php post_class( 'ne-book-card' ); ?> data-ne-book-card>
							<a class="ne-book-cover-link" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Read %s', 'neuroecho-book-gallery' ), get_the_title() ) ); ?>">
								<?php echo self::render_cover( get_the_ID(), 'medium_large' ); ?>
							</a>
							<div class="ne-book-card-body">
								<div class="ne-book-authors"><?php echo self::render_author_links( get_the_ID() ); ?></div>
								<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<?php if ( $meta['subtitle'] ) : ?>
									<p class="ne-book-subtitle"><?php echo esc_html( $meta['subtitle'] ); ?></p>
								<?php endif; ?>
								<p class="ne-book-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></p>
								<?php if ( $meta['share_note'] ) : ?>
									<p class="ne-share-note"><?php echo esc_html( wp_trim_words( $meta['share_note'], 22 ) ); ?></p>
								<?php endif; ?>
								<div class="ne-book-meta">
									<?php echo self::render_book_facts( get_the_ID(), $meta ); ?>
								</div>
								<div class="ne-book-card-footer">
									<span><?php echo esc_html( self::get_comment_count_label( get_the_ID() ) ); ?></span>
									<a href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read', 'neuroecho-book-gallery' ); ?></a>
								</div>
							</div>
						</article>
					<?php endwhile; ?>
				</div>

				<?php echo $show_all ? '' : $this->render_pagination( $books, $paged ); ?>
			<?php else : ?>
				<div class="ne-gallery-empty">
					<?php $fallback_books_html = $this->render_empty_user_books(); ?>
					<h3><?php esc_html_e( 'No matching books', 'neuroecho-book-gallery' ); ?></h3>
					<?php if ( $fallback_books_html ) : ?>
						<p><?php esc_html_e( 'No exact result for that search. Here are books from site authors instead.', 'neuroecho-book-gallery' ); ?></p>
						<?php echo $fallback_books_html; ?>
					<?php else : ?>
						<p><?php esc_html_e( 'Try a different title, author, note, or comment keyword.', 'neuroecho-book-gallery' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}

	private function should_enqueue_assets() {
		if ( is_singular( $this->get_gallery_post_types() ) || is_post_type_archive( self::CPT ) ) {
			return true;
		}

		if ( is_singular() ) {
			$post = get_post();

			if ( $post && has_shortcode( $post->post_content, 'neuroecho_book_gallery' ) ) {
				return true;
			}
		}

		return false;
	}

	private function is_all_limit( $limit_value ) {
		$limit_value = strtolower( trim( (string) $limit_value ) );

		if ( '' === $limit_value || 'all' === $limit_value || '-1' === $limit_value ) {
			return true;
		}

		return ! is_numeric( $limit_value ) || absint( $limit_value ) < 1;
	}

	private function sanitize_request_value( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $value ) );
	}

	private function get_gallery_post_types() {
		return array( self::CPT, 'post' );
	}

	private function is_gallery_post_type( $post_type ) {
		return in_array( $post_type, $this->get_gallery_post_types(), true );
	}

	private function find_book_ids_for_search( $search ) {
		$ids        = array();
		$search     = trim( $search );
		$post_types = $this->get_gallery_post_types();

		if ( '' === $search ) {
			return $ids;
		}

		$ids = array_merge( $ids, $this->find_book_ids_by_plain_text( $search, $post_types ) );

		$text_query = new WP_Query(
			array(
				'post_type'              => $post_types,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				's'                      => $search,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$ids = array_merge( $ids, $text_query->posts );

		$meta_query = new WP_Query(
			array(
				'post_type'              => $post_types,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					'relation' => 'OR',
					array(
						'key'     => self::META . 'subtitle',
						'value'   => $search,
						'compare' => 'LIKE',
					),
					array(
						'key'     => self::META . 'format',
						'value'   => $search,
						'compare' => 'LIKE',
					),
					array(
						'key'     => self::META . 'share_note',
						'value'   => $search,
						'compare' => 'LIKE',
					),
				),
			)
		);

		$ids = array_merge( $ids, $meta_query->posts );

		$term_ids = get_terms(
			array(
				'taxonomy'   => self::TAX_AUTHOR,
				'hide_empty' => false,
				'fields'     => 'ids',
				'search'     => $search,
			)
		);

		if ( ! is_wp_error( $term_ids ) && $term_ids ) {
			$author_query = new WP_Query(
				array(
					'post_type'              => $post_types,
					'post_status'            => 'publish',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'tax_query'              => array(
						array(
							'taxonomy' => self::TAX_AUTHOR,
							'field'    => 'term_id',
							'terms'    => array_map( 'absint', $term_ids ),
						),
					),
				)
			);

			$ids = array_merge( $ids, $author_query->posts );
		}

		$user_ids = $this->find_user_author_ids_for_search( $search );

		if ( $user_ids ) {
			$user_author_query = new WP_Query(
				array(
					'post_type'              => $post_types,
					'post_status'            => 'publish',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'author__in'             => $user_ids,
				)
			);

			$ids = array_merge( $ids, $user_author_query->posts );
		}

		$comments = get_comments(
			array(
				'status'      => 'approve',
				'post_status' => 'publish',
				'search'      => $search,
				'number'      => 100,
			)
		);

		foreach ( $comments as $comment ) {
			$comment_post_id = absint( $comment->comment_post_ID );

			if ( $comment_post_id && $this->is_gallery_post_type( get_post_type( $comment_post_id ) ) ) {
				$ids[] = $comment_post_id;
			}
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	}

	private function find_book_ids_by_plain_text( $search, $post_types ) {
		global $wpdb;

		$post_types = array_values( array_filter( array_map( 'sanitize_key', (array) $post_types ) ) );

		if ( empty( $post_types ) ) {
			return array();
		}

		$like              = '%' . $wpdb->esc_like( $search ) . '%';
		$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$sql               = "SELECT ID FROM {$wpdb->posts} WHERE post_status = %s AND post_type IN ({$type_placeholders}) AND (post_title LIKE %s OR post_excerpt LIKE %s OR post_content LIKE %s) ORDER BY post_date DESC";
		$values            = array_merge( array( 'publish' ), $post_types, array( $like, $like, $like ) );

		return array_map( 'absint', $wpdb->get_col( $wpdb->prepare( $sql, $values ) ) );
	}

	private function render_empty_user_books() {
		$fallback_books = new WP_Query(
			array(
				'post_type'      => $this->get_gallery_post_types(),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( ! $fallback_books->have_posts() ) {
			wp_reset_postdata();
			return '';
		}

		ob_start();
		?>
		<div class="ne-empty-books">
			<h4><?php esc_html_e( 'Books from site authors', 'neuroecho-book-gallery' ); ?></h4>
			<div class="ne-empty-book-list">
				<?php
				while ( $fallback_books->have_posts() ) :
					$fallback_books->the_post();
					$meta = self::get_book_meta( get_the_ID() );
					?>
					<article <?php post_class( 'ne-empty-book' ); ?>>
						<a class="ne-empty-book-cover" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Read %s', 'neuroecho-book-gallery' ), get_the_title() ) ); ?>">
							<?php echo self::render_cover( get_the_ID(), 'thumbnail' ); ?>
						</a>
						<div class="ne-empty-book-body">
							<div class="ne-book-authors"><?php echo self::render_author_links( get_the_ID() ); ?></div>
							<h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
							<div class="ne-book-meta">
								<?php echo self::render_book_facts( get_the_ID(), $meta ); ?>
							</div>
						</div>
						<a class="ne-empty-book-read" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read', 'neuroecho-book-gallery' ); ?></a>
					</article>
				<?php endwhile; ?>
			</div>
		</div>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}

	private function find_user_author_ids_for_search( $search ) {
		$user_query = new WP_User_Query(
			array(
				'fields'         => 'ID',
				'number'         => 50,
				'search'         => '*' . $search . '*',
				'search_columns' => array( 'display_name', 'user_login', 'user_nicename' ),
			)
		);

		$user_ids = $user_query->get_results();

		return array_values( array_unique( array_filter( array_map( 'absint', $user_ids ) ) ) );
	}

	private function render_pagination( $books, $current_page ) {
		if ( $books->max_num_pages <= 1 ) {
			return '';
		}

		$base_url = remove_query_arg( 'ne_book_page' );
		$links    = paginate_links(
			array(
				'base'      => esc_url_raw( add_query_arg( 'ne_book_page', '%#%', $base_url ) ),
				'format'    => '',
				'current'   => $current_page,
				'total'     => $books->max_num_pages,
				'type'      => 'list',
				'prev_text' => __( 'Previous', 'neuroecho-book-gallery' ),
				'next_text' => __( 'Next', 'neuroecho-book-gallery' ),
			)
		);

		if ( ! $links ) {
			return '';
		}

		return '<nav class="ne-book-pagination" aria-label="' . esc_attr__( 'Book pagination', 'neuroecho-book-gallery' ) . '">' . $links . '</nav>';
	}

	public static function get_book_meta( $post_id ) {
		return array(
			'subtitle'     => get_post_meta( $post_id, self::META . 'subtitle', true ),
			'cover_url'    => get_post_meta( $post_id, self::META . 'cover_url', true ),
			'format'       => get_post_meta( $post_id, self::META . 'format', true ),
			'share_note'   => get_post_meta( $post_id, self::META . 'share_note', true ),
			'reading_time' => get_post_meta( $post_id, self::META . 'reading_time', true ),
		);
	}

	public static function render_cover( $post_id, $size = 'large' ) {
		if ( has_post_thumbnail( $post_id ) ) {
			return get_the_post_thumbnail(
				$post_id,
				$size,
				array(
					'class'   => 'ne-book-cover-image',
					'loading' => 'lazy',
				)
			);
		}

		$meta = self::get_book_meta( $post_id );

		if ( $meta['cover_url'] ) {
			return sprintf(
				'<img class="ne-book-cover-image" src="%s" alt="%s" loading="lazy" />',
				esc_url( $meta['cover_url'] ),
				esc_attr( get_the_title( $post_id ) )
			);
		}

		$title   = get_the_title( $post_id );
		$letters = strtoupper( substr( preg_replace( '/[^A-Za-z0-9]/', '', $title ), 0, 2 ) );
		$letters = $letters ? $letters : 'NE';

		return sprintf(
			'<span class="ne-cover-placeholder" aria-hidden="true"><span>%s</span></span>',
			esc_html( $letters )
		);
	}

	public static function render_author_links( $post_id ) {
		$terms = get_the_terms( $post_id, self::TAX_AUTHOR );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return self::render_post_author_link( $post_id );
		}

		$links = array();

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				$links[] = '<span>' . esc_html( $term->name ) . '</span>';
				continue;
			}

			$links[] = sprintf( '<a href="%s">%s</a>', esc_url( $link ), esc_html( $term->name ) );
		}

		return implode( '<span aria-hidden="true">, </span>', $links );
	}

	private static function render_post_author_link( $post_id ) {
		$author_id = (int) get_post_field( 'post_author', $post_id );

		if ( ! $author_id ) {
			return '<span>' . esc_html__( 'Unknown author', 'neuroecho-book-gallery' ) . '</span>';
		}

		$name = get_the_author_meta( 'display_name', $author_id );

		if ( ! $name ) {
			return '<span>' . esc_html__( 'Unknown author', 'neuroecho-book-gallery' ) . '</span>';
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_author_posts_url( $author_id ) ),
			esc_html( $name )
		);
	}

	public static function render_book_facts( $post_id, $meta = null ) {
		if ( null === $meta ) {
			$meta = self::get_book_meta( $post_id );
		}

		$facts = array();

		if ( $meta['format'] ) {
			$facts[] = esc_html( $meta['format'] );
		}

		if ( $meta['reading_time'] ) {
			$facts[] = sprintf(
				/* translators: %s: number of minutes. */
				esc_html__( '%s min read', 'neuroecho-book-gallery' ),
				esc_html( number_format_i18n( $meta['reading_time'] ) )
			);
		}

		$shared_date = get_the_date( '', $post_id );

		if ( $shared_date ) {
			$facts[] = sprintf(
				/* translators: %s: shared date. */
				esc_html__( 'Shared %s', 'neuroecho-book-gallery' ),
				esc_html( $shared_date )
			);
		}

		if ( empty( $facts ) ) {
			return '';
		}

		return '<span>' . implode( '</span><span>', $facts ) . '</span>';
	}

	public static function get_comment_count_label( $post_id ) {
		$count = (int) get_comments_number( $post_id );

		return sprintf(
			/* translators: %s: number of comments. */
			_n( '%s comment', '%s comments', $count, 'neuroecho-book-gallery' ),
			number_format_i18n( $count )
		);
	}

	public function single_template( $template ) {
		if ( is_singular( $this->get_gallery_post_types() ) ) {
			$plugin_template = plugin_dir_path( __FILE__ ) . 'templates/single-ne_book.php';

			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	public function archive_template( $template ) {
		if ( is_post_type_archive( self::CPT ) ) {
			$plugin_template = plugin_dir_path( __FILE__ ) . 'templates/archive-ne_book.php';

			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	public function body_class( $classes ) {
		if ( is_singular( $this->get_gallery_post_types() ) ) {
			$classes[] = 'ne-book-reader-page';
		}

		if ( is_post_type_archive( self::CPT ) ) {
			$classes[] = 'ne-book-archive-page';
		}

		return $classes;
	}

	public function comment_form_defaults( $defaults ) {
		if ( is_singular( $this->get_gallery_post_types() ) ) {
			$defaults['class_form'] = isset( $defaults['class_form'] ) ? trim( $defaults['class_form'] . ' ne-comment-form' ) : 'comment-form ne-comment-form';
			$defaults['title_reply'] = __( 'Join the discussion', 'neuroecho-book-gallery' );
		}

		return $defaults;
	}
}

function neuroecho_book_gallery() {
	return NeuroEcho_Book_Gallery::instance();
}

register_activation_hook( __FILE__, array( 'NeuroEcho_Book_Gallery', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'NeuroEcho_Book_Gallery', 'deactivate' ) );

neuroecho_book_gallery();
