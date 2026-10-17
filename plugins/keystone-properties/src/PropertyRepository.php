<?php
/**
 * Read model for properties and agents.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties;

defined( 'ABSPATH' ) || exit;

/**
 * Builds normalized arrays for properties and agents and runs filtered, cached searches.
 *
 * Shared by the REST API, the theme templates, the Gutenberg blocks, JSON-LD and the static export,
 * so every surface shows exactly the same data.
 */
final class PropertyRepository {

	public const MAX_PER_PAGE = 24;

	/**
	 * Allowed sort keys mapped to WP_Query arguments.
	 */
	public const SORTS = array(
		'newest'     => array(
			'orderby' => 'date',
			'order'   => 'DESC',
		),
		'price-asc'  => array(
			'orderby'  => 'meta_value_num',
			'meta_key' => 'keystone_price', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'    => 'ASC',
		),
		'price-desc' => array(
			'orderby'  => 'meta_value_num',
			'meta_key' => 'keystone_price', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'    => 'DESC',
		),
		'surface'    => array(
			'orderby'  => 'meta_value_num',
			'meta_key' => 'keystone_surface', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'    => 'DESC',
		),
	);

	/**
	 * Normalizes raw filters (from REST, URL or block attributes).
	 *
	 * @param array<string, mixed> $raw Raw filters.
	 * @return array<string, mixed>
	 */
	public static function normalize_filters( array $raw ): array {
		$sort = isset( $raw['sort'] ) ? sanitize_key( (string) $raw['sort'] ) : 'newest';

		return array(
			'city'      => isset( $raw['city'] ) ? sanitize_title( (string) $raw['city'] ) : '',
			'type'      => isset( $raw['type'] ) ? sanitize_title( (string) $raw['type'] ) : '',
			'status'    => isset( $raw['status'] ) ? sanitize_title( (string) $raw['status'] ) : '',
			'min_price' => isset( $raw['min_price'] ) && '' !== $raw['min_price'] ? max( 0, (float) $raw['min_price'] ) : null,
			'max_price' => isset( $raw['max_price'] ) && '' !== $raw['max_price'] ? max( 0, (float) $raw['max_price'] ) : null,
			'min_beds'  => isset( $raw['min_beds'] ) ? max( 0, (int) $raw['min_beds'] ) : 0,
			'featured'  => ! empty( $raw['featured'] ),
			'exclude'   => isset( $raw['exclude'] ) ? array_map( 'absint', (array) $raw['exclude'] ) : array(),
			'sort'      => array_key_exists( $sort, self::SORTS ) ? $sort : 'newest',
			'page'      => isset( $raw['page'] ) ? max( 1, (int) $raw['page'] ) : 1,
			'per_page'  => isset( $raw['per_page'] ) ? min( self::MAX_PER_PAGE, max( 1, (int) $raw['per_page'] ) ) : 9,
		);
	}

	/**
	 * Runs a filtered, paginated search. Results are cached until any property changes.
	 *
	 * @param array<string, mixed> $raw_filters Raw filters.
	 * @return array{items: array<int, array<string, mixed>>, total: int, total_pages: int, page: int, per_page: int}
	 */
	public static function search( array $raw_filters ): array {
		$filters = self::normalize_filters( $raw_filters );

		return Cache::remember(
			'search:' . wp_json_encode( $filters ),
			static function () use ( $filters ) {
				$query = new \WP_Query( self::query_args( $filters ) );
				$items = array_map( array( self::class, 'to_array' ), $query->posts );

				return array(
					'items'       => $items,
					'total'       => (int) $query->found_posts,
					'total_pages' => (int) $query->max_num_pages,
					'page'        => $filters['page'],
					'per_page'    => $filters['per_page'],
				);
			}
		);
	}

	/**
	 * Translates normalized filters into WP_Query arguments.
	 *
	 * @param array<string, mixed> $filters Normalized filters.
	 * @return array<string, mixed>
	 */
	public static function query_args( array $filters ): array {
		$args = array(
			'post_type'              => PostTypes::PROPERTY,
			'post_status'            => 'publish',
			'posts_per_page'         => $filters['per_page'],
			'paged'                  => $filters['page'],
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => true,
			'update_post_meta_cache' => true,
		);

		$args = array_merge( $args, self::SORTS[ $filters['sort'] ] );

		$tax_query = array();
		$tax_map   = array(
			'city'   => PostTypes::TAX_CITY,
			'type'   => PostTypes::TAX_TYPE,
			'status' => PostTypes::TAX_STATUS,
		);

		foreach ( $tax_map as $filter => $taxonomy ) {
			if ( '' !== $filters[ $filter ] ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $filters[ $filter ],
				);
			}
		}

		if ( $tax_query ) {
			$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$meta_query = array();

		if ( null !== $filters['min_price'] || null !== $filters['max_price'] ) {
			$meta_query[] = array(
				'key'     => 'keystone_price',
				'value'   => array( $filters['min_price'] ?? 0, $filters['max_price'] ?? PHP_INT_MAX ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}

		if ( $filters['min_beds'] > 0 ) {
			$meta_query[] = array(
				'key'     => 'keystone_bedrooms',
				'value'   => $filters['min_beds'],
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		if ( $filters['featured'] ) {
			$meta_query[] = array(
				'key'   => 'keystone_featured',
				'value' => '1',
			);
		}

		if ( $meta_query ) {
			$args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		if ( $filters['exclude'] ) {
			$args['post__not_in'] = $filters['exclude']; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		}

		return $args;
	}

	/**
	 * Normalized representation of one property.
	 *
	 * @param \WP_Post|int $post Post or ID.
	 * @return array<string, mixed>
	 */
	public static function to_array( $post ): array {
		$post = get_post( $post );

		if ( ! $post instanceof \WP_Post ) {
			return array();
		}

		$id     = $post->ID;
		$status = self::first_term( $id, PostTypes::TAX_STATUS );
		$price  = (float) get_post_meta( $id, 'keystone_price', true );
		$thumb  = (int) get_post_thumbnail_id( $id );
		$photos = array_unique( array_filter( array_merge( array( $thumb ), array_map( 'absint', (array) get_post_meta( $id, 'keystone_gallery', true ) ) ) ) );
		$agent  = self::agent_brief( (int) get_post_meta( $id, 'keystone_agent', true ) );

		return array(
			'id'              => $id,
			'title'           => get_the_title( $post ),
			'slug'            => $post->post_name,
			'link'            => get_permalink( $post ),
			'excerpt'         => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'date'            => get_post_time( 'c', true, $post ),
			'price'           => $price,
			'price_formatted' => keystone_format_price( $price, $status['slug'] ?? '' ),
			'bedrooms'        => (int) get_post_meta( $id, 'keystone_bedrooms', true ),
			'bathrooms'       => (int) get_post_meta( $id, 'keystone_bathrooms', true ),
			'surface'         => (float) get_post_meta( $id, 'keystone_surface', true ),
			'year_built'      => (int) get_post_meta( $id, 'keystone_year_built', true ),
			'address'         => (string) get_post_meta( $id, 'keystone_address', true ),
			'latitude'        => (float) get_post_meta( $id, 'keystone_latitude', true ),
			'longitude'       => (float) get_post_meta( $id, 'keystone_longitude', true ),
			'features'        => (array) get_post_meta( $id, 'keystone_features', true ),
			'gallery'         => array_map( 'absint', (array) get_post_meta( $id, 'keystone_gallery', true ) ),
			'agent'           => (int) get_post_meta( $id, 'keystone_agent', true ),
			'agent_brief'     => $agent,
			'photo_count'     => count( $photos ),
			'featured'        => (bool) get_post_meta( $id, 'keystone_featured', true ),
			'city'            => self::first_term( $id, PostTypes::TAX_CITY ),
			'type'            => self::first_term( $id, PostTypes::TAX_TYPE ),
			'status'          => $status,
			'image'           => $thumb ? self::image( $thumb, 'keystone-card' ) : null,
		);
	}

	/**
	 * Normalized representation of one agent.
	 *
	 * @param \WP_Post|int $post Post or ID.
	 * @return array<string, mixed>|null
	 */
	public static function agent( $post ): ?array {
		$post = $post ? get_post( $post ) : null;

		if ( ! $post instanceof \WP_Post || PostTypes::AGENT !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		$photo = (int) get_post_thumbnail_id( $post );

		return array(
			'id'       => $post->ID,
			'name'     => get_the_title( $post ),
			'link'     => get_permalink( $post ),
			'position' => (string) get_post_meta( $post->ID, 'keystone_position', true ),
			'phone'    => (string) get_post_meta( $post->ID, 'keystone_phone', true ),
			'email'    => (string) get_post_meta( $post->ID, 'keystone_email', true ),
			'bio'      => wp_strip_all_tags( $post->post_content ),
			'photo'    => $photo ? self::image( $photo, 'keystone-portrait' ) : null,
		);
	}

	/**
	 * Name and avatar of an agent, for the overlay of property cards.
	 *
	 * @param int $agent_id Agent post ID.
	 * @return array{name: string, avatar: string}|null
	 */
	public static function agent_brief( int $agent_id ): ?array {
		$post = $agent_id ? get_post( $agent_id ) : null;

		if ( ! $post instanceof \WP_Post || PostTypes::AGENT !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		$avatar = wp_get_attachment_image_src( (int) get_post_thumbnail_id( $post ), 'thumbnail' );

		return array(
			'name'   => get_the_title( $post ),
			'avatar' => $avatar ? $avatar[0] : '',
		);
	}

	/**
	 * Image data (URL + responsive srcset) for an attachment.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size          Image size.
	 * @return array<string, mixed>|null
	 */
	public static function image( int $attachment_id, string $size ): ?array {
		$src = wp_get_attachment_image_src( $attachment_id, $size );

		if ( ! $src ) {
			return null;
		}

		return array(
			'id'     => $attachment_id,
			'src'    => $src[0],
			'width'  => (int) $src[1],
			'height' => (int) $src[2],
			'srcset' => (string) wp_get_attachment_image_srcset( $attachment_id, $size ),
			'alt'    => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		);
	}

	/**
	 * First term of a taxonomy for a post, as {slug, name, link}.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @return array<string, string>|null
	 */
	public static function first_term( int $post_id, string $taxonomy ): ?array {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! is_array( $terms ) || ! $terms ) {
			return null;
		}

		$term = reset( $terms );
		$link = get_term_link( $term );

		return array(
			'slug' => $term->slug,
			'name' => $term->name,
			'link' => is_wp_error( $link ) ? '' : $link,
		);
	}
}
