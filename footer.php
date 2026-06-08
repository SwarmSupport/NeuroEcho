<?php
/**
 * Theme footer.
 *
 * @package NeuroEcho_Book_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer class="ne-site-footer">
	<p>
		<?php
		printf(
			esc_html__( '%1$s - Book sharing powered by WordPress.', 'neuroecho-book-gallery' ),
			esc_html( get_bloginfo( 'name' ) )
		);
		?>
	</p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
