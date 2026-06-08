<?php
/**
 * Theme header.
 *
 * @package NeuroEcho_Book_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="ne-site-header">
	<div class="ne-site-branding">
		<a class="ne-site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( has_site_icon() ) : ?>
				<span class="ne-site-icon" aria-hidden="true">
					<img
						src="<?php echo esc_url( get_site_icon_url( 192 ) ); ?>"
						srcset="<?php echo esc_url( get_site_icon_url( 96 ) ); ?> 96w, <?php echo esc_url( get_site_icon_url( 192 ) ); ?> 192w, <?php echo esc_url( get_site_icon_url( 270 ) ); ?> 270w"
						sizes="clamp(46px, 7vw, 72px)"
						width="96"
						height="96"
						alt=""
						decoding="async"
					/>
				</span>
			<?php endif; ?>
			<span class="ne-site-title-text"><?php bloginfo( 'name' ); ?></span>
		</a>
		<?php if ( get_bloginfo( 'description' ) ) : ?>
			<p class="ne-site-tagline"><?php bloginfo( 'description' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => 'nav',
				'container_class'=> 'ne-site-nav',
				'depth'          => 1,
			)
		);
	}
	?>
</header>
