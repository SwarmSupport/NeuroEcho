<?php
/**
 * Theme bootstrap for users who upload this package as a theme.
 *
 * @package NeuroEcho_Book_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/neuroecho-book-gallery.php';

add_action(
	'after_setup_theme',
	function() {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		register_nav_menus(
			array(
				'primary' => __( 'Primary Menu', 'neuroecho-book-gallery' ),
			)
		);
	}
);

add_action(
	'after_switch_theme',
	function() {
		neuroecho_book_gallery()->register_content_types();
		flush_rewrite_rules();
	}
);

add_action(
	'wp_enqueue_scripts',
	function() {
		wp_enqueue_style( 'neuroecho-book-gallery-theme', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
	},
	5
);

add_action(
	'wp_enqueue_scripts',
	function() {
		if ( is_front_page() || is_home() ) {
			neuroecho_book_gallery()->enqueue_assets();
		}
	},
	20
);
