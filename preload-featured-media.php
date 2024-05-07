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

declare(strict_types=1);
namespace NathanDozen3\PreloadFeaturedMedia;

/**
 * Print the <link>.
 * 
 * @param string $rel           Defines the relationship between a linked resource and the current document.
 * @param string $fetchpriority Represents a hint given to the browser on how it should prioritize the fetch of the image relative to other images.
 * @param string $as            The type of resource.
 * @param string $href          The path to the resource.
 * @param string $type          The MIME type of the resource.
 * @param string $media         Optional. The media query for the resource.
 * @param string $imagesrcset   Optional. The image srcset for the resource.
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
	printf(
		'<link rel="%1$s" fetchpriority="%2$s" as="%3$s" href="%4$s" type="%5$s" media="%6$s" imagesrcset="%7$s">',
		esc_attr( $rel ),
		esc_attr( $fetchpriority ),
		esc_attr( $as ),
		esc_attr( $href ),
		esc_attr( $type ),
		esc_attr( $media ),
		esc_attr( $imagesrcset ),
	);
	printf( "\n" );
}

/**
 * Preload the featured media.
 * 
 * Hooked to `wp_head`, this function will output a `<link rel="preload">` element in the `<head>`.
 * 
 * @return void
 */
function preload_media() : void {
	if( current_action() !== 'wp_head' ) {
		return;
	}

	$post_id = get_the_ID();
	is_archive() && $post_id = -1;
	is_home() && $post_id =  get_option( 'page_for_posts' );

	/**
	 * Filters the thumbnail ID
	 * 
	 * Returning a falsy value will short-circuit printing the `<link>` element.
	 * 
	 * @since 0.1.0
	 */
	$thumbnail_id = apply_filters( 'NathanDozen3\PreloadFeaturedMedia\thumbnail_id', get_post_thumbnail_id( $post_id ) );
	if( ! $thumbnail_id ) return;

	$type = get_post_mime_type( $thumbnail_id );

	/**
	 * Filters the thumbnail size
	 */
	$thumbnail_size = apply_filters( 'NathanDozen3\PreloadFeaturedMedia\thumbnail_size', 'full' );

	$href = wp_get_attachment_image_url( $thumbnail_id, $thumbnail_size );

	/**
	 * Filters the image srcset.
	 * 
	 * Manually edit the imagesrcset property or return a falsy value to use media queries.
	 * 
	 * @since 0.1.0
	 */
	$imagesrcset = apply_filters( 'NathanDozen3\PreloadFeaturedMedia\imagesrcset', wp_get_attachment_image_srcset( $thumbnail_id, $thumbnail_size ) );

	if( $imagesrcset ) {
		print_link(
			rel: 'preload',
			fetchpriority: 'high',
			as: 'image',
			href: $href,
			type: $type,
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
			type: $type,
			media: $media
		);
	}
}
add_action( 'wp_head', __NAMESPACE__ . '\preload_media', 1 );