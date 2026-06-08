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
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( $this, 'render_book_admin_column' ), 10, 2 );

		add_shortcode( 'neuroecho_book_gallery', array( $this, 'render_gallery_shortcode' ) );
		add_shortcode( 'neuroecho_library_summary', array( $this, 'render_library_summary_shortcode' ) );
		add_shortcode( 'neuroecho_library_loan_status', array( $this, 'render_loan_status_shortcode' ) );
		add_shortcode( 'neuroecho_book_reservations', array( $this, 'render_reservations_shortcode' ) );
		add_shortcode( 'neuroecho_reading_room', array( $this, 'render_reading_room_shortcode' ) );
		add_shortcode( 'neuroecho_book_reviews', array( $this, 'render_book_reviews_shortcode' ) );
		add_shortcode( 'neuroecho_library_hours', array( $this, 'render_library_hours_shortcode' ) );

		add_filter( 'single_template', array( $this, 'single_template' ) );
		add_filter( 'archive_template', array( $this, 'archive_template' ) );
		add_filter( 'theme_page_templates', array( $this, 'register_page_templates' ) );
		add_filter( 'template_include', array( $this, 'page_template' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'comment_form_defaults', array( $this, 'comment_form_defaults' ) );
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( $this, 'book_admin_columns' ) );
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
			'subtitle'         => 'string',
			'cover_url'        => 'string',
			'format'           => 'string',
			'share_note'       => 'string',
			'reading_time'     => 'integer',
			'isbn'             => 'string',
			'publication_year' => 'integer',
			'page_count'       => 'integer',
			'shelf_location'   => 'string',
			'available_copies' => 'integer',
			'loan_status'      => 'string',
			'due_date'         => 'string',
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
			case self::META . 'publication_year':
			case self::META . 'page_count':
			case self::META . 'available_copies':
				return absint( $value );
			case self::META . 'loan_status':
				$status = sanitize_key( $value );
				return array_key_exists( $status, self::get_loan_status_options() ) ? $status : '';
			case self::META . 'due_date':
				$date = sanitize_text_field( $value );
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';
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

	public function book_admin_columns( $columns ) {
		$next_columns = array();

		foreach ( $columns as $key => $label ) {
			$next_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$next_columns['ne_book_shelf']  = __( 'Shelf', 'neuroecho-book-gallery' );
				$next_columns['ne_book_status'] = __( 'Loan Status', 'neuroecho-book-gallery' );
				$next_columns['ne_book_copies'] = __( 'Copies', 'neuroecho-book-gallery' );
				$next_columns['ne_book_due']    = __( 'Due Date', 'neuroecho-book-gallery' );
			}
		}

		return $next_columns;
	}

	public function render_book_admin_column( $column, $post_id ) {
		$meta = self::get_book_meta( $post_id );

		switch ( $column ) {
			case 'ne_book_shelf':
				echo $meta['shelf_location'] ? esc_html( $meta['shelf_location'] ) : '&mdash;';
				break;
			case 'ne_book_status':
				echo esc_html( self::get_loan_status_label( $meta['loan_status'] ) );
				break;
			case 'ne_book_copies':
				echo '' !== (string) $meta['available_copies'] ? esc_html( number_format_i18n( absint( $meta['available_copies'] ) ) ) : '&mdash;';
				break;
			case 'ne_book_due':
				$due_label = self::get_due_date_label( $meta );
				echo $due_label ? esc_html( $due_label ) : '&mdash;';
				break;
		}
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'ne_save_book_meta', 'ne_book_meta_nonce' );
		$meta           = self::get_book_meta( $post->ID );
		$status_options = self::get_loan_status_options();
		?>
		<h4><?php esc_html_e( 'Book Details', 'neuroecho-book-gallery' ); ?></h4>
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
			<label for="ne_book_isbn"><strong><?php esc_html_e( 'ISBN', 'neuroecho-book-gallery' ); ?></strong></label>
			<input class="widefat" id="ne_book_isbn" name="ne_book_isbn" type="text" value="<?php echo esc_attr( $meta['isbn'] ); ?>" />
			<span class="description"><?php esc_html_e( 'Optional. Use any local catalog identifier if no ISBN is available.', 'neuroecho-book-gallery' ); ?></span>
		</p>
		<p>
			<label for="ne_book_publication_year"><strong><?php esc_html_e( 'Publication Year', 'neuroecho-book-gallery' ); ?></strong></label>
			<input id="ne_book_publication_year" name="ne_book_publication_year" type="number" min="0" max="9999" step="1" value="<?php echo esc_attr( $meta['publication_year'] ); ?>" />
		</p>
		<p>
			<label for="ne_book_page_count"><strong><?php esc_html_e( 'Page Count', 'neuroecho-book-gallery' ); ?></strong></label>
			<input id="ne_book_page_count" name="ne_book_page_count" type="number" min="0" step="1" value="<?php echo esc_attr( $meta['page_count'] ); ?>" />
		</p>
		<h4><?php esc_html_e( 'Library Shelf', 'neuroecho-book-gallery' ); ?></h4>
		<p>
			<label for="ne_book_shelf_location"><strong><?php esc_html_e( 'Shelf Location', 'neuroecho-book-gallery' ); ?></strong></label>
			<input class="widefat" id="ne_book_shelf_location" name="ne_book_shelf_location" type="text" value="<?php echo esc_attr( $meta['shelf_location'] ); ?>" placeholder="<?php esc_attr_e( 'Fiction A3, Reading Room, Reference Desk...', 'neuroecho-book-gallery' ); ?>" />
		</p>
		<p>
			<label for="ne_book_available_copies"><strong><?php esc_html_e( 'Available Copies', 'neuroecho-book-gallery' ); ?></strong></label>
			<input id="ne_book_available_copies" name="ne_book_available_copies" type="number" min="0" step="1" value="<?php echo esc_attr( $meta['available_copies'] ); ?>" />
		</p>
		<p>
			<label for="ne_book_loan_status"><strong><?php esc_html_e( 'Loan Status', 'neuroecho-book-gallery' ); ?></strong></label>
			<select id="ne_book_loan_status" name="ne_book_loan_status">
				<?php foreach ( $status_options as $status_key => $status_label ) : ?>
					<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $meta['loan_status'], $status_key ); ?>>
						<?php echo esc_html( $status_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="ne_book_due_date"><strong><?php esc_html_e( 'Due Date', 'neuroecho-book-gallery' ); ?></strong></label>
			<input id="ne_book_due_date" name="ne_book_due_date" type="date" value="<?php echo esc_attr( $meta['due_date'] ); ?>" />
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
			'isbn',
			'publication_year',
			'page_count',
			'shelf_location',
			'available_copies',
			'loan_status',
			'due_date',
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
				'show_shelves' => 'true',
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
		$selected_author = $request_author;
		$selected_shelf  = isset( $_GET['ne_book_shelf'] ) ? $this->sanitize_request_value( $_GET['ne_book_shelf'] ) : '';
		$availability    = isset( $_GET['ne_book_availability'] ) ? sanitize_key( $this->sanitize_request_value( $_GET['ne_book_availability'] ) ) : '';
		$status_options  = self::get_loan_status_options();
		$fixed_author    = sanitize_title( $atts['author'] );
		$paged           = ( ! $show_all && isset( $_GET['ne_book_page'] ) ) ? max( 1, absint( $this->sanitize_request_value( $_GET['ne_book_page'] ) ) ) : 1;
		$instance_id     = wp_unique_id( 'ne-book-gallery-' );
		$shelves         = $this->get_shelf_locations();

		if ( ! array_key_exists( $availability, $status_options ) ) {
			$availability = '';
		}

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

		$meta_query = array();

		if ( $selected_shelf ) {
			$meta_query[] = array(
				'key'     => self::META . 'shelf_location',
				'value'   => $selected_shelf,
				'compare' => '=',
			);
		}

		if ( $availability ) {
			if ( 'available' === $availability ) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key'     => self::META . 'loan_status',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => self::META . 'loan_status',
						'value'   => 'available',
						'compare' => '=',
					),
					array(
						'key'     => self::META . 'available_copies',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				);
			} else {
				$meta_query[] = array(
					'key'     => self::META . 'loan_status',
					'value'   => $availability,
					'compare' => '=',
				);
			}
		}

		if ( $meta_query ) {
			$args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta_query );
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
				<form class="ne-gallery-search" role="search" method="get" action="<?php echo esc_url( remove_query_arg( array( 'ne_book_page', 'ne_book_search', 'ne_book_author', 'ne_book_shelf', 'ne_book_availability' ) ) ); ?>">
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

					<?php if ( $shelves ) : ?>
						<label class="screen-reader-text" for="<?php echo esc_attr( $instance_id ); ?>shelf"><?php esc_html_e( 'Filter by shelf', 'neuroecho-book-gallery' ); ?></label>
						<select id="<?php echo esc_attr( $instance_id ); ?>shelf" name="ne_book_shelf">
							<option value=""><?php esc_html_e( 'All shelves', 'neuroecho-book-gallery' ); ?></option>
							<?php foreach ( $shelves as $shelf ) : ?>
								<option value="<?php echo esc_attr( $shelf ); ?>" <?php selected( $selected_shelf, $shelf ); ?>>
									<?php echo esc_html( $shelf ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>

					<label class="screen-reader-text" for="<?php echo esc_attr( $instance_id ); ?>availability"><?php esc_html_e( 'Filter by availability', 'neuroecho-book-gallery' ); ?></label>
					<select id="<?php echo esc_attr( $instance_id ); ?>availability" name="ne_book_availability">
						<option value=""><?php esc_html_e( 'Any status', 'neuroecho-book-gallery' ); ?></option>
						<?php foreach ( $status_options as $status_key => $status_label ) : ?>
							<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $availability, $status_key ); ?>>
								<?php echo esc_html( $status_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<button type="submit"><?php esc_html_e( 'Search', 'neuroecho-book-gallery' ); ?></button>

					<?php if ( $has_search || ( $request_author && ! $fixed_author ) || $selected_shelf || $availability ) : ?>
						<a class="ne-gallery-reset" href="<?php echo esc_url( remove_query_arg( array( 'ne_book_search', 'ne_book_author', 'ne_book_shelf', 'ne_book_availability', 'ne_book_page' ) ) ); ?>">
							<?php esc_html_e( 'Reset', 'neuroecho-book-gallery' ); ?>
						</a>
					<?php endif; ?>
				</form>
			<?php endif; ?>

			<?php if ( 'false' !== strtolower( (string) $atts['show_shelves'] ) ) : ?>
				<?php echo $this->render_shelf_browser( $selected_shelf ); ?>
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
								<div class="ne-book-status-row"><?php echo self::render_loan_status_badge( get_the_ID(), $meta ); ?></div>
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

		if ( is_page() ) {
			$template_slug = get_page_template_slug( get_queried_object_id() );

			if ( isset( $this->get_library_page_templates()[ $template_slug ] ) ) {
				return true;
			}
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

	private function get_shelf_locations() {
		global $wpdb;

		$post_types        = array_values( array_filter( array_map( 'sanitize_key', $this->get_gallery_post_types() ) ) );
		$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$values            = array_merge( $post_types, array( self::META . 'shelf_location' ) );
		$sql               = "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_status = 'publish' AND p.post_type IN ({$type_placeholders}) AND pm.meta_key = %s AND pm.meta_value <> '' ORDER BY pm.meta_value ASC";

		return array_values( array_filter( array_map( 'sanitize_text_field', $wpdb->get_col( $wpdb->prepare( $sql, $values ) ) ) ) );
	}

	private function get_shelf_counts() {
		global $wpdb;

		$post_types        = array_values( array_filter( array_map( 'sanitize_key', $this->get_gallery_post_types() ) ) );
		$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$values            = array_merge( $post_types, array( self::META . 'shelf_location' ) );
		$sql               = "SELECT pm.meta_value AS shelf, COUNT(DISTINCT p.ID) AS total FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_status = 'publish' AND p.post_type IN ({$type_placeholders}) AND pm.meta_key = %s AND pm.meta_value <> '' GROUP BY pm.meta_value ORDER BY pm.meta_value ASC";
		$rows              = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
		$counts            = array();

		foreach ( $rows as $row ) {
			$counts[ sanitize_text_field( $row->shelf ) ] = absint( $row->total );
		}

		return $counts;
	}

	private function render_shelf_browser( $selected_shelf = '' ) {
		$counts = $this->get_shelf_counts();

		if ( ! $counts ) {
			return '';
		}

		ob_start();
		?>
		<nav class="ne-shelf-browser" aria-label="<?php esc_attr_e( 'Shelf browser', 'neuroecho-book-gallery' ); ?>" data-ne-shelf-browser>
			<div class="ne-shelf-browser-head">
				<h3><?php esc_html_e( 'Shelf Browser', 'neuroecho-book-gallery' ); ?></h3>
				<div class="ne-catalog-view" aria-label="<?php esc_attr_e( 'Catalog view', 'neuroecho-book-gallery' ); ?>">
					<button type="button" data-ne-catalog-view="grid" aria-pressed="true"><?php esc_html_e( 'Grid', 'neuroecho-book-gallery' ); ?></button>
					<button type="button" data-ne-catalog-view="list" aria-pressed="false"><?php esc_html_e( 'List', 'neuroecho-book-gallery' ); ?></button>
				</div>
			</div>
			<div class="ne-shelf-list">
				<?php foreach ( $counts as $shelf => $count ) : ?>
					<?php
					$url = remove_query_arg( 'ne_book_page', add_query_arg( 'ne_book_shelf', $shelf ) );
					?>
					<a class="<?php echo $selected_shelf === $shelf ? 'is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
						<span><?php echo esc_html( $shelf ); ?></span>
						<strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong>
					</a>
				<?php endforeach; ?>
			</div>
		</nav>
		<?php

		return ob_get_clean();
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
					array(
						'key'     => self::META . 'isbn',
						'value'   => $search,
						'compare' => 'LIKE',
					),
					array(
						'key'     => self::META . 'publication_year',
						'value'   => $search,
						'compare' => 'LIKE',
					),
					array(
						'key'     => self::META . 'page_count',
						'value'   => $search,
						'compare' => 'LIKE',
					),
					array(
						'key'     => self::META . 'shelf_location',
						'value'   => $search,
						'compare' => 'LIKE',
					),
					array(
						'key'     => self::META . 'loan_status',
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
							<div class="ne-book-status-row"><?php echo self::render_loan_status_badge( get_the_ID(), $meta ); ?></div>
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

	private function get_library_summary() {
		$books = new WP_Query(
			array(
				'post_type'              => $this->get_gallery_post_types(),
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		$summary = array(
			'total'       => 0,
			'available'   => 0,
			'reserved'    => 0,
			'checked_out' => 0,
			'reference'   => 0,
			'shelves'     => count( $this->get_shelf_locations() ),
		);

		foreach ( $books->posts as $book_id ) {
			$meta = self::get_book_meta( $book_id );
			$summary['total']++;

			if ( isset( $summary[ $meta['loan_status'] ] ) ) {
				$summary[ $meta['loan_status'] ]++;
			}
		}

		return $summary;
	}

	public function render_library_summary_shortcode() {
		$this->enqueue_assets();

		$summary = $this->get_library_summary();
		$items   = array(
			'total'       => __( 'Cataloged Books', 'neuroecho-book-gallery' ),
			'shelves'     => __( 'Shelves', 'neuroecho-book-gallery' ),
			'available'   => __( 'Available', 'neuroecho-book-gallery' ),
			'reserved'    => __( 'Reserved', 'neuroecho-book-gallery' ),
			'checked_out' => __( 'Checked Out', 'neuroecho-book-gallery' ),
			'reference'   => __( 'Reference', 'neuroecho-book-gallery' ),
		);

		ob_start();
		?>
		<section class="ne-library-summary" aria-label="<?php esc_attr_e( 'Library summary', 'neuroecho-book-gallery' ); ?>">
			<?php foreach ( $items as $key => $label ) : ?>
				<div class="ne-library-summary-item">
					<strong><?php echo esc_html( number_format_i18n( isset( $summary[ $key ] ) ? $summary[ $key ] : 0 ) ); ?></strong>
					<span><?php echo esc_html( $label ); ?></span>
				</div>
			<?php endforeach; ?>
		</section>
		<?php

		return ob_get_clean();
	}

	public function render_loan_status_shortcode( $atts ) {
		$this->enqueue_assets();

		$atts = shortcode_atts(
			array(
				'limit' => 12,
			),
			$atts,
			'neuroecho_library_loan_status'
		);

		$limit   = max( 1, min( 60, absint( $atts['limit'] ) ) );
		$books   = new WP_Query(
			array(
				'post_type'      => $this->get_gallery_post_types(),
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$groups  = array_fill_keys( array_keys( self::get_loan_status_options() ), array() );
		$unknown = array();

		while ( $books->have_posts() ) {
			$books->the_post();
			$meta   = self::get_book_meta( get_the_ID() );
			$status = $meta['loan_status'];

			if ( isset( $groups[ $status ] ) ) {
				$groups[ $status ][] = get_the_ID();
			} else {
				$unknown[] = get_the_ID();
			}
		}

		wp_reset_postdata();

		ob_start();
		?>
		<section class="ne-library-panel ne-loan-status-panel">
			<div class="ne-library-panel-head">
				<p class="ne-kicker"><?php esc_html_e( 'Circulation', 'neuroecho-book-gallery' ); ?></p>
				<h2><?php esc_html_e( 'Book Loan Status', 'neuroecho-book-gallery' ); ?></h2>
			</div>
			<div class="ne-loan-status-grid">
				<?php foreach ( self::get_loan_status_options() as $status_key => $status_label ) : ?>
					<article class="ne-status-column ne-status-column-<?php echo esc_attr( sanitize_html_class( $status_key ) ); ?>">
						<h3><?php echo esc_html( $status_label ); ?></h3>
						<?php if ( empty( $groups[ $status_key ] ) ) : ?>
							<p><?php esc_html_e( 'No books in this status yet.', 'neuroecho-book-gallery' ); ?></p>
						<?php else : ?>
							<ul>
								<?php foreach ( $groups[ $status_key ] as $book_id ) : ?>
									<?php $meta = self::get_book_meta( $book_id ); ?>
									<li>
										<a href="<?php echo esc_url( get_permalink( $book_id ) ); ?>"><?php echo esc_html( get_the_title( $book_id ) ); ?></a>
										<?php echo self::render_loan_status_badge( $book_id, $meta ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ( $unknown ) : ?>
				<p class="ne-library-note"><?php esc_html_e( 'Some books need their status checked in the editor.', 'neuroecho-book-gallery' ); ?></p>
			<?php endif; ?>
		</section>
		<?php

		return ob_get_clean();
	}

	public function render_reservations_shortcode( $atts ) {
		$this->enqueue_assets();

		$atts = shortcode_atts(
			array(
				'limit' => 12,
			),
			$atts,
			'neuroecho_book_reservations'
		);

		$limit = max( 1, min( 60, absint( $atts['limit'] ) ) );
		$books = new WP_Query(
			array(
				'post_type'      => $this->get_gallery_post_types(),
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'   => self::META . 'loan_status',
						'value' => 'reserved',
					),
					array(
						'key'   => self::META . 'loan_status',
						'value' => 'checked_out',
					),
					array(
						'key'     => self::META . 'available_copies',
						'value'   => 1,
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		ob_start();
		?>
		<section class="ne-library-panel ne-reservations-panel">
			<div class="ne-library-panel-head">
				<p class="ne-kicker"><?php esc_html_e( 'Holds', 'neuroecho-book-gallery' ); ?></p>
				<h2><?php esc_html_e( 'Book Reservations', 'neuroecho-book-gallery' ); ?></h2>
			</div>
			<?php if ( $books->have_posts() ) : ?>
				<div class="ne-reservation-list">
					<?php
					while ( $books->have_posts() ) :
						$books->the_post();
						$meta = self::get_book_meta( get_the_ID() );
						?>
						<article class="ne-reservation-card">
							<a class="ne-reservation-cover" href="<?php the_permalink(); ?>">
								<?php echo self::render_cover( get_the_ID(), 'thumbnail' ); ?>
							</a>
							<div>
								<div class="ne-book-authors"><?php echo self::render_author_links( get_the_ID() ); ?></div>
								<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<?php echo self::render_loan_status_badge( get_the_ID(), $meta ); ?>
								<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
							</div>
							<a class="ne-hold-link" href="<?php the_permalink(); ?>#ne-reader-comments"><?php esc_html_e( 'Request hold', 'neuroecho-book-gallery' ); ?></a>
						</article>
					<?php endwhile; ?>
				</div>
			<?php else : ?>
				<p><?php esc_html_e( 'No reservations are waiting. Available books can be borrowed from the catalog.', 'neuroecho-book-gallery' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}

	public function render_reading_room_shortcode( $atts ) {
		$this->enqueue_assets();

		$atts = shortcode_atts(
			array(
				'heading' => __( 'Reading Room', 'neuroecho-book-gallery' ),
			),
			$atts,
			'neuroecho_reading_room'
		);

		ob_start();
		?>
		<section class="ne-reading-room">
			<div class="ne-library-panel ne-reading-room-intro">
				<p class="ne-kicker"><?php esc_html_e( 'Open Stacks', 'neuroecho-book-gallery' ); ?></p>
				<h2><?php echo esc_html( $atts['heading'] ); ?></h2>
				<p><?php esc_html_e( 'Browse shelves, check availability, save reading positions, and continue discussion from each Book reader page.', 'neuroecho-book-gallery' ); ?></p>
			</div>
			<?php echo $this->render_library_summary_shortcode(); ?>
			<?php echo $this->render_library_hours_shortcode( array( 'compact' => 'true' ) ); ?>
			<?php echo $this->render_gallery_shortcode( array( 'heading' => $atts['heading'] ) ); ?>
		</section>
		<?php

		return ob_get_clean();
	}

	public function render_book_reviews_shortcode( $atts ) {
		$this->enqueue_assets();

		$atts = shortcode_atts(
			array(
				'limit' => 10,
			),
			$atts,
			'neuroecho_book_reviews'
		);

		$limit = max( 1, min( 40, absint( $atts['limit'] ) ) );
		$books = new WP_Query(
			array(
				'post_type'      => $this->get_gallery_post_types(),
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'comment_count',
				'order'          => 'DESC',
			)
		);

		ob_start();
		?>
		<section class="ne-library-panel ne-reviews-panel">
			<div class="ne-library-panel-head">
				<p class="ne-kicker"><?php esc_html_e( 'Reviews', 'neuroecho-book-gallery' ); ?></p>
				<h2><?php esc_html_e( 'Book Reviews', 'neuroecho-book-gallery' ); ?></h2>
			</div>
			<div class="ne-review-list">
				<?php
				while ( $books->have_posts() ) :
					$books->the_post();
					$latest = get_comments(
						array(
							'post_id' => get_the_ID(),
							'status'  => 'approve',
							'number'  => 1,
						)
					);
					?>
					<article class="ne-review-card">
						<h3><a href="<?php the_permalink(); ?>#ne-reader-comments"><?php the_title(); ?></a></h3>
						<div class="ne-book-authors"><?php echo self::render_author_links( get_the_ID() ); ?></div>
						<p class="ne-review-count"><?php echo esc_html( self::get_comment_count_label( get_the_ID() ) ); ?></p>
						<?php if ( $latest ) : ?>
							<blockquote><?php echo esc_html( wp_trim_words( $latest[0]->comment_content, 28 ) ); ?></blockquote>
						<?php endif; ?>
					</article>
				<?php endwhile; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}

	public function render_library_hours_shortcode( $atts ) {
		$this->enqueue_assets();

		$atts = shortcode_atts(
			array(
				'compact' => 'false',
			),
			$atts,
			'neuroecho_library_hours'
		);

		$hours = array(
			__( 'Monday - Friday', 'neuroecho-book-gallery' ) => __( '09:00 - 21:00', 'neuroecho-book-gallery' ),
			__( 'Saturday', 'neuroecho-book-gallery' )        => __( '10:00 - 18:00', 'neuroecho-book-gallery' ),
			__( 'Sunday', 'neuroecho-book-gallery' )          => __( 'Reading room only', 'neuroecho-book-gallery' ),
		);

		ob_start();
		?>
		<section class="ne-library-panel ne-hours-panel <?php echo 'true' === strtolower( (string) $atts['compact'] ) ? 'is-compact' : ''; ?>">
			<div class="ne-library-panel-head">
				<p class="ne-kicker"><?php esc_html_e( 'Hours', 'neuroecho-book-gallery' ); ?></p>
				<h2><?php esc_html_e( 'Library Hours', 'neuroecho-book-gallery' ); ?></h2>
			</div>
			<table class="ne-hours-table">
				<tbody>
					<?php foreach ( $hours as $day => $time ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $day ); ?></th>
							<td><?php echo esc_html( $time ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
		<?php

		return ob_get_clean();
	}

	public static function get_book_meta( $post_id ) {
		$loan_status = get_post_meta( $post_id, self::META . 'loan_status', true );

		if ( ! array_key_exists( $loan_status, self::get_loan_status_options() ) ) {
			$loan_status = 'available';
		}

		return array(
			'subtitle'         => get_post_meta( $post_id, self::META . 'subtitle', true ),
			'cover_url'        => get_post_meta( $post_id, self::META . 'cover_url', true ),
			'format'           => get_post_meta( $post_id, self::META . 'format', true ),
			'share_note'       => get_post_meta( $post_id, self::META . 'share_note', true ),
			'reading_time'     => get_post_meta( $post_id, self::META . 'reading_time', true ),
			'isbn'             => get_post_meta( $post_id, self::META . 'isbn', true ),
			'publication_year' => get_post_meta( $post_id, self::META . 'publication_year', true ),
			'page_count'       => get_post_meta( $post_id, self::META . 'page_count', true ),
			'shelf_location'   => get_post_meta( $post_id, self::META . 'shelf_location', true ),
			'available_copies' => get_post_meta( $post_id, self::META . 'available_copies', true ),
			'loan_status'      => $loan_status,
			'due_date'         => get_post_meta( $post_id, self::META . 'due_date', true ),
		);
	}

	public static function get_loan_status_options() {
		return array(
			'available'   => __( 'Available', 'neuroecho-book-gallery' ),
			'checked_out' => __( 'Checked out', 'neuroecho-book-gallery' ),
			'reserved'    => __( 'Reserved', 'neuroecho-book-gallery' ),
			'reference'   => __( 'Reference only', 'neuroecho-book-gallery' ),
		);
	}

	public static function get_loan_status_label( $status ) {
		$options = self::get_loan_status_options();

		return isset( $options[ $status ] ) ? $options[ $status ] : $options['available'];
	}

	public static function get_due_date_label( $meta ) {
		if ( empty( $meta['due_date'] ) ) {
			return '';
		}

		$timestamp = strtotime( $meta['due_date'] );

		if ( ! $timestamp ) {
			return '';
		}

		return sprintf(
			/* translators: %s: due date. */
			__( 'Due %s', 'neuroecho-book-gallery' ),
			date_i18n( get_option( 'date_format' ), $timestamp )
		);
	}

	public static function render_loan_status_badge( $post_id, $meta = null ) {
		if ( null === $meta ) {
			$meta = self::get_book_meta( $post_id );
		}

		$status = $meta['loan_status'];
		$pieces = array( self::get_loan_status_label( $status ) );

		if ( '' !== (string) $meta['available_copies'] ) {
			$pieces[] = sprintf(
				/* translators: %s: number of copies. */
				__( '%s copies', 'neuroecho-book-gallery' ),
				number_format_i18n( absint( $meta['available_copies'] ) )
			);
		}

		$due_label = self::get_due_date_label( $meta );

		if ( $due_label ) {
			$pieces[] = $due_label;
		}

		return sprintf(
			'<span class="ne-loan-badge ne-loan-badge-%1$s">%2$s</span>',
			esc_attr( sanitize_html_class( $status ) ),
			esc_html( implode( ' / ', $pieces ) )
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

		if ( $meta['shelf_location'] ) {
			$facts[] = sprintf(
				/* translators: %s: shelf location. */
				esc_html__( 'Shelf %s', 'neuroecho-book-gallery' ),
				esc_html( $meta['shelf_location'] )
			);
		}

		if ( $meta['publication_year'] ) {
			$facts[] = esc_html( number_format_i18n( absint( $meta['publication_year'] ) ) );
		}

		if ( $meta['page_count'] ) {
			$facts[] = sprintf(
				/* translators: %s: page count. */
				esc_html__( '%s pages', 'neuroecho-book-gallery' ),
				esc_html( number_format_i18n( absint( $meta['page_count'] ) ) )
			);
		}

		if ( $meta['reading_time'] ) {
			$facts[] = sprintf(
				/* translators: %s: number of minutes. */
				esc_html__( '%s min read', 'neuroecho-book-gallery' ),
				esc_html( number_format_i18n( $meta['reading_time'] ) )
			);
		}

		if ( $meta['isbn'] ) {
			$facts[] = sprintf(
				/* translators: %s: ISBN or local catalog id. */
				esc_html__( 'ISBN %s', 'neuroecho-book-gallery' ),
				esc_html( $meta['isbn'] )
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

	private function get_library_page_templates() {
		return array(
			'templates/reading-room.php'      => __( 'NeuroEcho Reading Room', 'neuroecho-book-gallery' ),
			'templates/book-loan-status.php'  => __( 'NeuroEcho Book Loan Status', 'neuroecho-book-gallery' ),
			'templates/book-reservations.php' => __( 'NeuroEcho Book Reservations', 'neuroecho-book-gallery' ),
			'templates/book-reviews.php'      => __( 'NeuroEcho Book Reviews', 'neuroecho-book-gallery' ),
			'templates/library-hours.php'     => __( 'NeuroEcho Library Hours', 'neuroecho-book-gallery' ),
		);
	}

	public function register_page_templates( $templates ) {
		return array_merge( $templates, $this->get_library_page_templates() );
	}

	public function page_template( $template ) {
		if ( ! is_page() ) {
			return $template;
		}

		$template_slug = get_page_template_slug( get_queried_object_id() );
		$page_templates = $this->get_library_page_templates();

		if ( ! isset( $page_templates[ $template_slug ] ) ) {
			return $template;
		}

		$plugin_template = plugin_dir_path( __FILE__ ) . $template_slug;

		return file_exists( $plugin_template ) ? $plugin_template : $template;
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
			$defaults['title_reply'] = __( 'Add a book review', 'neuroecho-book-gallery' );
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
