<?php
/**
 * WordPress plugin to automatically preload images on pages, posts, and custom post types.
 *
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

require __DIR__ . '/class-preloadfeaturedmedia.php';

/**
 * Add the preflight_preload_featured_media method to the `wp_head` hook.
 */
add_action( 'wp_head', array( PreloadFeaturedMedia::instance(), 'preflight_preload_featured_media' ), 1 );
