<?php
/**
 * Plugin Name:       Preload Featured Media
 * Description:       Preload images on pages, posts and custom post types.
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Version:           0.1.0
 * Author:            Nathan Johnson
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       preload-featured-media
 */

namespace NathanDozen3\PreloadFeaturedMedia;

/**
 * Print the <link>.
 * 
 * @param string $rel
 * @param string $fetchpriority
 * @param string $as
 * @param string $href
 * @param string $type
 * @param string $media
 * 
 * @return void
 */
function print_link(
	string $rel,
	string $fetchpriority,
	string $as,
	string $href,
	string $type,
	string $media = '',
	string $imagesrcset = '',
) : void {
	$media_or_imagesrcset = '';
	if( $media ) {
		$media_or_imagesrcset = 'media="' . $media . '"';
	}
	else if( $imagesrcset ) {
		$media_or_imagesrcset = 'imagesrcset="' . $imagesrcset . '"';
	}
	printf(
		'<link rel="%1$s" fetchpriority="%2$s" as="%3$s" href="%4$s" type="%5$s" %6$s>',
		$rel,
		$fetchpriority,
		$as,
		$href,
		$type,
		$media_or_imagesrcset,
	);
	printf( "\n" );
}

/**
 * Preload the featured media.
 * 
 * @return void
 */
function preload_media() : void {
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$post_id = get_the_ID();
	is_home() && $post_id =  get_option( 'page_for_posts' );

	/**
	 * Filters the thumbnail ID
	 * 
	 * @since 0.1.0
	 */
	$thumbnail_id = apply_filters( 'NathanDozen3\PreloadFeaturedMedia\thumbnail_id', get_post_thumbnail_id( $post_id ) );
	if( ! $thumbnail_id ) return;

	/**
	 * Filters the thumbnail size
	 */
	$thumbnail_size = apply_filters( 'NathanDozen3\PreloadFeaturedMedia\thumbnail_size', 'full' );

	$desktop_href = wp_get_attachment_image_url( $thumbnail_id, $thumbnail_size );
	$link = str_replace( trailingslashit( get_site_url() ), get_home_path(), $desktop_href );

	if( ! $link ) return;

	$type = exif_imagetype( $link );
	$mime = image_type_to_mime_type( $type );

	/**
	 * Filters the image srcset.
	 * 
	 * @since 0.1.0
	 */
	$imagesrcset = apply_filters( 'NathanDozen3\PreloadFeaturedMedia\imagesrcset', wp_get_attachment_image_srcset( $thumbnail_id, $thumbnail_size ) );

	if( $imagesrcset ) {
		print_link(
			rel: 'preload',
			fetchpriority: 'high',
			as: 'image',
			href: $desktop_href,
			type: $mime,
			imagesrcset: $imagesrcset,
		);
		return;
	}

	/**
	 * Filters the media sizes
	 * 
	 * @since 0.1.0
	 */
	$sizes = apply_filters( 'NathanDozen3\PreloadFeaturedMedia\sizes', wp_get_registered_image_subsizes() );

	$last_size = 0;
	$count = count( $sizes );
	$n = 0;

	foreach( $sizes as $size => $atts ) {
		$min = '';
		$max = '';
		$media = '';
		if( $last_size !== 0 ) {
			$sz = $last_size + 1;
			$media .= "(min-width: {$sz}px)";	
		}
		$last_size = $atts[ 'width' ];

		if( $n++ !== $count - 1 ) {
			if( $media !== '' ) {
				$media .= ' and ';
			}
			$media .= "(max-width: {$atts[ 'width' ]}px)";
		}

		print_link(
			rel: 'preload',
			fetchpriority: 'high',
			as: 'image',
			href: wp_get_attachment_image_url( $thumbnail_id, $size ),
			type: $mime,
			media: $media
		);
	}
}
add_action( 'wp_head', __NAMESPACE__ . '\preload_media', 1 );