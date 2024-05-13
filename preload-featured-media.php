<?php
/**
 * @package           NathanDozen3\PreloadFeaturedMedia
 * @author            Nathan Johnson
 * @copyright         2024 Nathan Johnson
 * @license           GPL-2.0-or-later
 * 
 * @wordpress-plugin
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
 * Class used to implement the preloading of featured media.
 * 
 * @since 0.1.0
 */
final class PreloadFeaturedMedia {

	/**
	 * @var self Singleton instance.
	 */
	private static ?self $_instance = null;


	/**
	 * Private constructor.
	 */
	private function __construct(){}


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
	private function printLink(
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
	 * @param int $thumbnail_id
	 * @param string $thumbnail_size
	 * @param string $imagesrcset
	 * @param array $sizes
	 * 
	 * @return void
	 */
	private function preloadMedia( 
		int $thumbnail_id,
		string $thumbnail_size,
		string $imagesrcset,
		array $sizes,
	) : void {

		$type = get_post_mime_type( $thumbnail_id );
		$href = wp_get_attachment_image_url( $thumbnail_id, $thumbnail_size );

		if( ! $type || ! $href ) {
			return;
		}

		if( $imagesrcset ) {
			$this->printLink(
				rel: 'preload',
				fetchpriority: 'high',
				as: 'image',
				href: $href,
				type: $type,
				imagesrcset: $imagesrcset,
			);
			return;
		}

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

			$this->printLink(
				rel: 'preload',
				fetchpriority: 'high',
				as: 'image',
				href: wp_get_attachment_image_url( $thumbnail_id, $size ),
				type: $type,
				media: $media
			);
		}
	}


	/**
	 * Get singleton instance of PreloadFeaturedMedia object.
	 * 
	 * @return self
	 */
	public static function instance() : self {
		if( ! self::$_instance instanceof PreloadFeaturedMedia ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	/**
	 * Maybe preload the featured media.
	 *
	 * When hooked to `wp_head`, this function will output a `<link rel="preload">` element in the `<head>`.
	 */
	public function preflightPreloadFeaturedMedia() : void {
		if( current_action() !== 'wp_head' ) {
			return;
		}

		$post_id = (int) get_the_ID();
		is_archive() && $post_id = -1;
		is_home() && $post_id = (int) get_option( 'page_for_posts' );

		/**
		 * Filters the thumbnail ID
		 * 
		 * Returning a falsy value will short-circuit printing the `<link>` element.
		 * 
		 * @param int $post_id
		 * 
		 * @since 0.1.0
		 */
		$thumbnail_id = (int) apply_filters(
			'NathanDozen3\PreloadFeaturedMedia\thumbnail_id',
			get_post_thumbnail_id( $post_id ),
			$post_id
		);
		
		/**
		 * Filters the thumbnail size
		 * 
		 * @param int $thumbnail_id
		 * 
		 * @since 0.1.0
		 */
		$thumbnail_size = (string) apply_filters(
			'NathanDozen3\PreloadFeaturedMedia\thumbnail_size',
			'full',
			$thumbnail_id
		);

		/**
		 * Filters the image srcset.
		 * 
		 * Manually edit the imagesrcset property or return a falsy value to use media queries.
		 *
		 * @param int $thumbnail_id
		 * 
		 * @since 0.1.0
		 */
		$imagesrcset = (string) apply_filters(
			'NathanDozen3\PreloadFeaturedMedia\imagesrcset',
			wp_get_attachment_image_srcset( $thumbnail_id, $thumbnail_size ),
			$thumbnail_id
		);

		/**
		 * Filters the media sizes
		 * 
		 * @param int $thumbnail_id
		 * 
		 * @since 0.1.0
		 */
		$sizes = (array) apply_filters(
			'NathanDozen3\PreloadFeaturedMedia\sizes',
			wp_get_registered_image_subsizes(),
			$thumbnail_id
		);

		$this->preloadMedia(
			thumbnail_id: $thumbnail_id,
			thumbnail_size: $thumbnail_size,
			imagesrcset: $imagesrcset,
			sizes: $sizes,
		);
	}
}

/**
 * Add the preflightPreloadFeaturedMedia method to the wp_head hook.
 */
$preloadFeaturedMedia = PreloadFeaturedMedia::instance();
add_action( 'wp_head', [ $preloadFeaturedMedia, 'preflightPreloadFeaturedMedia' ], 1 );
