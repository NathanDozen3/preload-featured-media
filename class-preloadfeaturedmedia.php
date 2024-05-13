<?php
/**
 * WordPress plugin to automatically preload images on pages, posts, and custom post types.
 *
 * @package           NathanDozen3\PreloadFeaturedMedia
 * @author            Nathan Johnson
 * @copyright         2024 Nathan Johnson
 * @license           GPL-2.0-or-later
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
	 * Singleton instance.
	 *
	 * @var self
	 *
	 * @since 0.1.0
	 */
	private static ?self $instance = null;


	/**
	 * Private constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}


	/**
	 * Print the <link>.
	 *
	 * @param string $rel           Defines the relationship between a linked resource and the current document.
	 * @param string $fetchpriority Represents a hint given to the browser on how it should prioritize the fetch of the image relative to other images.
	 * @param string $as_attr       The type of resource.
	 * @param string $href          The path to the resource.
	 * @param string $type          The MIME type of the resource.
	 * @param string $media         Optional. The media query for the resource.
	 * @param string $imagesrcset   Optional. The image srcset for the resource.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function print_link(
		string $rel,
		string $fetchpriority,
		string $as_attr,
		string $href,
		string $type,
		string $media = '',
		string $imagesrcset = '',
	): void {
		printf(
			'<link rel="%1$s" fetchpriority="%2$s" as="%3$s" href="%4$s" type="%5$s" media="%6$s" imagesrcset="%7$s">',
			esc_attr( $rel ),
			esc_attr( $fetchpriority ),
			esc_attr( $as_attr ),
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
	 * @param int                              $thumbnail_id   The post thumbnail ID.
	 * @param string                           $thumbnail_size The post thumbnail size.
	 * @param string                           $imagesrcset    The post thumbnail srcset.
	 * @param array<string, array<string,int>> $sizes          Array of sizes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function preload_media(
		int $thumbnail_id,
		string $thumbnail_size,
		string $imagesrcset,
		array $sizes,
	): void {

		$type = get_post_mime_type( $thumbnail_id );
		$href = wp_get_attachment_image_url( $thumbnail_id, $thumbnail_size );

		if ( ! $type || ! $href ) {
			return;
		}

		if ( $imagesrcset ) {
			$this->print_link(
				rel: 'preload',
				fetchpriority: 'high',
				as_attr: 'image',
				href: $href,
				type: $type,
				imagesrcset: $imagesrcset,
			);
			return;
		}

		$last_size = 0;
		$count     = count( $sizes );
		$n         = 0;

		foreach ( $sizes as $size => $atts ) {
			$min   = '';
			$max   = '';
			$media = '';
			if ( 0 !== $last_size ) {
				$sz     = $last_size + 1;
				$media .= "(min-width: {$sz}px)";
			}
			$last_size = $atts['width'];

			if ( $n++ !== $count - 1 ) {
				if ( '' !== $media ) {
					$media .= ' and ';
				}
				$media .= "(max-width: {$atts[ 'width' ]}px)";
			}

			$this->print_link(
				rel: 'preload',
				fetchpriority: 'high',
				as_attr: 'image',
				href: (string) wp_get_attachment_image_url( $thumbnail_id, $size ),
				type: $type,
				media: $media
			);
		}
	}


	/**
	 * Get singleton instance of PreloadFeaturedMedia object.
	 *
	 * @since 0.1.0
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( ! self::$instance instanceof PreloadFeaturedMedia ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Maybe preload the featured media.
	 *
	 * When hooked to `wp_head`, this function will output a `<link rel="preload">` element in the `<head>`.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function preflight_preload_featured_media(): void {
		if ( current_action() !== 'wp_head' ) {
			return;
		}

		$post_id = (int) get_the_ID();

		if ( is_archive() ) {
			$post_id = -1;
		}

		if ( is_home() ) {
			$page_for_posts = get_option( 'page_for_posts' );
			if ( ! is_integer( $page_for_posts ) ) {
				$page_for_posts = 0;
			}
			$post_id = intval( $page_for_posts );
		}

		/**
		 * Filters the thumbnail ID
		 *
		 * Returning a falsy value will short-circuit printing the `<link>` element.
		 *
		 * @param int|false $thumbnail_id
		 * @param int       $post_id
		 *
		 * @since 0.1.0
		 */
		$thumbnail_id = (int) apply_filters(
			'nathandozen3_preloadfeaturedmedia_thumbnail_id',
			get_post_thumbnail_id( $post_id ),
			$post_id
		);

		/**
		 * Filters the thumbnail size
		 *
		 * @param string|false $thumbnail_size
		 * @param int          $thumbnail_id
		 *
		 * @since 0.1.0
		 */
		$thumbnail_size = (string) apply_filters(
			'nathandozen3_preloadfeaturedmedia_thumbnail_size',
			'full',
			$thumbnail_id
		);

		/**
		 * Filters the image srcset.
		 *
		 * Manually edit the imagesrcset property or return a falsy value to use media queries.
		 *
		 * @param string|false $image_srcset
		 * @param int          $thumbnail_id
		 *
		 * @since 0.1.0
		 */
		$imagesrcset = (string) apply_filters(
			'nathandozen3_preloadfeaturedmedia_imagesrcset',
			wp_get_attachment_image_srcset( $thumbnail_id, $thumbnail_size ),
			$thumbnail_id
		);

		/**
		 * Filters the media sizes
		 *
		 * @param array $image_subsizes
		 * @param int   $thumbnail_id
		 *
		 * @since 0.1.0
		 */
		$sizes = (array) apply_filters(
			'nathandozen3_preloadfeaturedmedia_sizes',
			wp_get_registered_image_subsizes(),
			$thumbnail_id
		);

		$this->preload_media(
			thumbnail_id: $thumbnail_id,
			thumbnail_size: $thumbnail_size,
			imagesrcset: $imagesrcset,
			sizes: $sizes,
		);
	}
}
