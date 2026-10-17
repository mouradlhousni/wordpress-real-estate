<?php
/**
 * WP-CLI commands.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Cli;

use Keystone\Properties\Cache;
use Keystone\Properties\PostTypes;
use Keystone\Properties\PropertyRepository;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Manage Keystone Realty demo content.
 */
final class Command {

	private const SEED_FLAG = '_keystone_seeded';
	private const MENU_NAME = 'Keystone Primary';

	/**
	 * Image pool built during a seed run: type => attachment IDs.
	 *
	 * @var array<string, array<int, int>>
	 */
	private array $pool = array();

	/**
	 * Seeds a complete demo site: agents, properties, generated images, pages and menu.
	 *
	 * Running it again replaces previously seeded content (it never touches other content).
	 *
	 * ## OPTIONS
	 *
	 * [--properties=<number>]
	 * : Number of properties to create (max 24).
	 * ---
	 * default: 24
	 * ---
	 *
	 * [--images=<number>]
	 * : Size of the generated image pool shared by listings (min 8). Lower it for faster runs, e.g. in Playground.
	 * ---
	 * default: 16
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp keystone seed
	 *     wp keystone seed --images=8
	 *
	 * @param array<int, string>    $args       Positional args.
	 * @param array<string, string> $assoc_args Options.
	 * @return void
	 */
	public function seed( $args, $assoc_args ): void {
		$start = microtime( true );

		if ( ! ImageGenerator::is_supported() ) {
			WP_CLI::error( 'The GD extension is required to generate demo images.' );
		}

		$property_count = max( 1, min( 24, (int) ( $assoc_args['properties'] ?? 24 ) ) );
		$image_count    = max( 8, min( 40, (int) ( $assoc_args['images'] ?? 16 ) ) );

		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Attribute seeded content to the first administrator (WP-CLI runs without a user).
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);
		if ( $admins ) {
			wp_set_current_user( (int) $admins[0] );
		}

		wp_defer_term_counting( true );
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'limit_image_sizes' ) );

		$this->purge();
		PostTypes::ensure_default_terms();

		WP_CLI::log( 'Generating demo images…' );
		$this->build_image_pool( $image_count );

		WP_CLI::log( 'Creating cities and agents…' );
		$cities = $this->create_cities();
		$agents = $this->create_agents();

		WP_CLI::log( 'Creating properties…' );
		$created = $this->create_properties( $property_count, $agents );

		$this->create_pages_and_menu();
		$this->configure_site();

		remove_filter( 'intermediate_image_sizes_advanced', array( $this, 'limit_image_sizes' ) );
		wp_defer_term_counting( false );
		Cache::flush();
		flush_rewrite_rules( false );

		WP_CLI::success(
			sprintf(
				'Seeded %1$d agents, %2$d properties in %3$d cities and %4$d images in %5$.1fs.',
				count( array_unique( $agents ) ),
				$created,
				count( $cities ),
				array_sum( array_map( 'count', $this->pool ) ),
				microtime( true ) - $start
			)
		);
	}

	/**
	 * Deletes every piece of seeded content (properties, agents, pages, images, menu).
	 *
	 * ## EXAMPLES
	 *
	 *     wp keystone reset
	 *
	 * @return void
	 */
	public function reset(): void {
		$deleted = $this->purge();
		Cache::flush();
		WP_CLI::success( sprintf( 'Deleted %d seeded items.', $deleted ) );
	}

	/**
	 * Exports every published property as JSON for the static site's client-side search.
	 *
	 * URLs are written relative to the site root (e.g. "property/sunlit-loft/") so the export
	 * works from any domain or sub-folder; the front-end resolves them against the home link.
	 *
	 * ## OPTIONS
	 *
	 * [--output=<file>]
	 * : Destination file. Prints to STDOUT when omitted.
	 *
	 * ## EXAMPLES
	 *
	 *     wp keystone export-json --output=dist/data/properties.json
	 *
	 * @subcommand export-json
	 *
	 * @param array<int, string>    $args       Positional args.
	 * @param array<string, string> $assoc_args Options.
	 * @return void
	 */
	public function export_json( $args, $assoc_args ): void {
		$items = array();
		$page  = 1;

		do {
			$query = new \WP_Query(
				array(
					'post_type'      => PostTypes::PROPERTY,
					'post_status'    => 'publish',
					'posts_per_page' => 100,
					'paged'          => $page,
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			);

			foreach ( $query->posts as $post ) {
				$items[] = $this->static_item( PropertyRepository::to_array( $post ) );
			}

			++$page;
		} while ( $page <= $query->max_num_pages );

		$json = (string) wp_json_encode(
			array(
				'generated' => gmdate( 'c' ),
				'total'     => count( $items ),
				'items'     => $items,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		if ( empty( $assoc_args['output'] ) ) {
			WP_CLI::line( $json );
			return;
		}

		$file = $assoc_args['output'];
		wp_mkdir_p( dirname( $file ) );

		if ( false === file_put_contents( $file, $json ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- CLI export to a local path.
			WP_CLI::error( "Could not write {$file}." );
		}

		WP_CLI::success( sprintf( 'Exported %1$d properties to %2$s.', count( $items ), $file ) );
	}

	/**
	 * Keeps the fields the static search, cards and map need, with root-relative URLs.
	 *
	 * @param array<string, mixed> $item Normalized property.
	 * @return array<string, mixed>
	 */
	private function static_item( array $item ): array {
		$home     = trailingslashit( home_url() );
		$relative = static fn( $url ) => is_string( $url ) && str_starts_with( $url, $home ) ? substr( $url, strlen( $home ) ) : $url;

		$keep = array_intersect_key(
			$item,
			array_flip( array( 'id', 'title', 'link', 'date', 'price', 'price_formatted', 'bedrooms', 'bathrooms', 'surface', 'latitude', 'longitude', 'featured', 'city', 'type', 'status', 'image', 'photo_count', 'agent_brief' ) )
		);

		$keep['link'] = $relative( $keep['link'] );

		foreach ( array( 'city', 'type', 'status' ) as $term ) {
			if ( is_array( $keep[ $term ] ) ) {
				$keep[ $term ]['link'] = $relative( $keep[ $term ]['link'] );
			}
		}

		if ( is_array( $keep['agent_brief'] ) ) {
			$keep['agent_brief']['avatar'] = $relative( $keep['agent_brief']['avatar'] );
		}

		if ( is_array( $keep['image'] ) ) {
			$keep['image']['src']    = $relative( $keep['image']['src'] );
			$keep['image']['srcset'] = str_replace( $home, '', $keep['image']['srcset'] );
		}

		return $keep;
	}

	/**
	 * Keeps only the image sizes the theme actually uses (faster seeding, smaller uploads).
	 *
	 * @param array<string, array<string, mixed>> $sizes Sizes.
	 * @return array<string, array<string, mixed>>
	 */
	public function limit_image_sizes( $sizes ): array {
		return array_intersect_key( $sizes, array_flip( array( 'thumbnail', 'medium', 'medium_large', 'large', 'keystone-card', 'keystone-portrait' ) ) );
	}

	/**
	 * Removes previously seeded content.
	 *
	 * @return int Number of deleted objects.
	 */
	private function purge(): int {
		$deleted = 0;
		$types   = array( PostTypes::PROPERTY, PostTypes::AGENT, 'page', 'attachment' );

		do {
			$ids = get_posts(
				array(
					'post_type'      => $types,
					'post_status'    => 'any',
					'posts_per_page' => 100,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'meta_key'       => self::SEED_FLAG, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				)
			);

			foreach ( $ids as $id ) {
				if ( 'attachment' === get_post_type( $id ) ) {
					/*
					 * Delete files from the *relative* path first: under WP-CLI on Windows, ABSPATH uses
					 * forward slashes ("C:/…"), which core's path_is_absolute() rejects, so sub-sizes would be orphaned.
					 */
					wp_delete_attachment_files(
						$id,
						wp_get_attachment_metadata( $id ),
						get_post_meta( $id, '_wp_attachment_backup_sizes', true ),
						(string) get_post_meta( $id, '_wp_attached_file', true )
					);
					wp_delete_attachment( $id, true );
				} else {
					wp_delete_post( $id, true );
				}
				++$deleted;
			}
		} while ( $ids );

		$menu = wp_get_nav_menu_object( self::MENU_NAME );
		if ( $menu ) {
			wp_delete_nav_menu( $menu->term_id );
		}

		return $deleted;
	}

	/**
	 * Generates the shared pool of exterior/interior images.
	 *
	 * @param int $total Total images (excluding agent portraits).
	 * @return void
	 */
	private function build_image_pool( int $total ): void {
		$generator = new ImageGenerator();
		$types     = array( 'villa', 'apartment', 'house', 'office' );
		$rooms     = array( 'living', 'kitchen', 'bedroom' );
		$exteriors = max( count( $types ), (int) round( $total * 0.6 ) );
		$interiors = max( count( $rooms ), $total - $exteriors );
		$progress  = WP_CLI\Utils\make_progress_bar( 'Images', $exteriors + $interiors );

		$this->pool = array( 'interior' => array() );

		for ( $i = 0; $i < $exteriors; $i++ ) {
			$type = $types[ $i % count( $types ) ];
			$id   = $this->generate_attachment(
				fn( $path ) => $this->demo_photo( "exterior/{$type}", count( $this->pool[ $type ] ?? array() ), $path ) ?? $generator->exterior( $type, 101 + $i, $path ),
				"keystone-{$type}-" . ( $i + 1 ) . '.jpg',
				/* translators: %s: property type. */
				sprintf( __( 'Exterior view of a %s', 'keystone-properties' ), $type )
			);
			if ( $id ) {
				$this->pool[ $type ][] = $id;
			}
			$progress->tick();
		}

		for ( $i = 0; $i < $interiors; $i++ ) {
			$room = $rooms[ $i % count( $rooms ) ];
			$id   = $this->generate_attachment(
				fn( $path ) => $this->demo_photo( 'interior', $i, $path ) ?? $generator->interior( $room, 301 + $i, $path ),
				"keystone-{$room}-" . ( $i + 1 ) . '.jpg',
				/* translators: %s: room name. */
				sprintf( __( 'Interior: %s', 'keystone-properties' ), $room )
			);
			if ( $id ) {
				$this->pool['interior'][] = $id;
			}
			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * Creates city terms with a description and a cover image.
	 *
	 * @return array<string, int> slug => term ID.
	 */
	private function create_cities(): array {
		$cities = array();
		$i      = 0;

		foreach ( SeedData::cities() as $slug => $city ) {
			$existing = term_exists( $slug, PostTypes::TAX_CITY );
			$term     = $existing ? $existing : wp_insert_term( $city['name'], PostTypes::TAX_CITY, array( 'slug' => $slug ) );

			if ( is_wp_error( $term ) ) {
				WP_CLI::warning( $term->get_error_message() );
				continue;
			}

			$term_id = (int) $term['term_id'];
			wp_update_term( $term_id, PostTypes::TAX_CITY, array( 'description' => $city['intro'] ) );

			$covers = array_merge( $this->pool['villa'] ?? array(), $this->pool['apartment'] ?? array(), $this->pool['house'] ?? array() );
			if ( $covers ) {
				update_term_meta( $term_id, 'keystone_city_image', $covers[ $i % count( $covers ) ] );
			}

			$cities[ $slug ] = $term_id;
			++$i;
		}

		return $cities;
	}

	/**
	 * Creates agents with generated portraits.
	 *
	 * @return array<string, int> city slug => agent ID.
	 */
	private function create_agents(): array {
		$generator = new ImageGenerator();
		$by_city   = array();

		foreach ( SeedData::agents() as $i => $agent ) {
			$agent_id = wp_insert_post(
				array(
					'post_type'    => PostTypes::AGENT,
					'post_status'  => 'publish',
					'post_title'   => $agent['name'],
					'post_content' => $agent['bio'],
					'menu_order'   => $i,
					'meta_input'   => array(
						'keystone_position' => $agent['position'],
						'keystone_phone'    => $agent['phone'],
						'keystone_email'    => $agent['email'],
						self::SEED_FLAG     => 1,
					),
				),
				true
			);

			if ( is_wp_error( $agent_id ) ) {
				WP_CLI::warning( $agent_id->get_error_message() );
				continue;
			}

			$photo = $this->generate_attachment(
				fn( $path ) => $this->demo_photo( 'agents', $i, $path ) ?? $generator->portrait( 7 + $i * 3, $path ),
				'keystone-agent-' . sanitize_title( $agent['name'] ) . '.jpg',
				/* translators: %s: agent name. */
				sprintf( __( 'Portrait of %s', 'keystone-properties' ), $agent['name'] ),
				$agent_id
			);

			if ( $photo ) {
				set_post_thumbnail( $agent_id, $photo );
			}

			foreach ( $agent['cities'] as $city ) {
				$by_city[ $city ] = (int) $agent_id;
			}
		}

		return $by_city;
	}

	/**
	 * Creates the listings.
	 *
	 * @param int                $count  How many.
	 * @param array<string, int> $agents City slug => agent ID.
	 * @return int Number created.
	 */
	private function create_properties( int $count, array $agents ): int {
		$cities     = SeedData::cities();
		$features   = SeedData::features();
		$paragraphs = SeedData::type_paragraphs();
		$closings   = SeedData::closing_lines();
		$rows       = array_slice( SeedData::properties(), 0, $count );
		$progress   = WP_CLI\Utils\make_progress_bar( 'Properties', count( $rows ) );
		$created    = 0;
		$used       = array();

		foreach ( $rows as $i => $row ) {
			list( $title, $city_slug, $type, $status, $price, $beds, $baths, $surface, $year, $featured ) = $row;

			$city = $cities[ $city_slug ];

			// Deterministic feature selection.
			$pool   = $features[ $type ];
			$picked = array();
			for ( $f = 0; $f < 5 + ( $i % 3 ); $f++ ) {
				$picked[] = $pool[ ( $i * 3 + $f * 2 ) % count( $pool ) ];
			}
			$picked = array_values( array_unique( $picked ) );

			// Coordinates scattered inland around the city centre (golden-angle spiral).
			$angle = $i * 2.39996;
			$dist  = 0.004 + ( $i % 5 ) * 0.003;
			$lat   = round( $city['lat'] + abs( sin( $angle ) ) * $dist, 6 );
			$lng   = round( $city['lng'] + cos( $angle ) * $dist * 1.3, 6 );

			$address = sprintf( $city['format'], 3 + ( $i * 37 ) % 180, $city['streets'][ $i % count( $city['streets'] ) ], $city['codes'][ $i % count( $city['codes'] ) ] );

			$content = implode(
				"\n\n",
				array(
					$paragraphs[ $type ][ $i % 2 ],
					$city['intro'],
					sprintf( 'Highlights include %s.', $this->human_list( array_map( 'strtolower', $picked ) ) ),
					$closings[ $i % count( $closings ) ],
				)
			);

			$excerpt = 'office' === $type
				? sprintf( '%1$d m² of office space in %2$s with %3$s.', $surface, $city['name'], strtolower( $picked[0] ) )
				: sprintf( '%1$d-bedroom %2$s of %3$d m² in %4$s, with %5$s and %6$s.', $beds, $type, $surface, $city['name'], strtolower( $picked[0] ), strtolower( $picked[1] ?? $picked[0] ) );

			$property_id = wp_insert_post(
				array(
					'post_type'    => PostTypes::PROPERTY,
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_content' => $content,
					'post_excerpt' => $excerpt,
					// Spread publication dates so "Newest first" is meaningful.
					'post_date'    => gmdate( 'Y-m-d H:i:s', time() - ( count( $rows ) - $i ) * DAY_IN_SECONDS * 2 ),
					'meta_input'   => array(
						'keystone_price'      => $price,
						'keystone_bedrooms'   => $beds,
						'keystone_bathrooms'  => $baths,
						'keystone_surface'    => $surface,
						'keystone_year_built' => $year,
						'keystone_address'    => $address,
						'keystone_latitude'   => $lat,
						'keystone_longitude'  => $lng,
						'keystone_features'   => $picked,
						'keystone_agent'      => $agents[ $city_slug ] ?? 0,
						'keystone_featured'   => $featured,
						self::SEED_FLAG       => 1,
					),
				),
				true
			);

			if ( is_wp_error( $property_id ) ) {
				WP_CLI::warning( $property_id->get_error_message() );
				continue;
			}

			wp_set_object_terms( $property_id, $city_slug, PostTypes::TAX_CITY );
			wp_set_object_terms( $property_id, $type, PostTypes::TAX_TYPE );
			wp_set_object_terms( $property_id, $status, PostTypes::TAX_STATUS );

			// Featured image: rotate through the type's exteriors. Gallery: other exteriors + interiors.
			$exteriors     = $this->pool[ $type ] ?? array();
			$used[ $type ] = ( $used[ $type ] ?? -1 ) + 1;

			if ( $exteriors ) {
				$cover = $exteriors[ $used[ $type ] % count( $exteriors ) ];
				set_post_thumbnail( $property_id, $cover );

				$gallery   = array_values( array_diff( $exteriors, array( $cover ) ) );
				$interiors = $this->pool['interior'];
				$offset    = $i % max( 1, count( $interiors ) );
				$gallery   = array_merge(
					array_slice( $gallery, 0, 1 ),
					array_slice( array_merge( array_slice( $interiors, $offset ), array_slice( $interiors, 0, $offset ) ), 0, 'office' === $type ? 2 : 4 )
				);
				update_post_meta( $property_id, 'keystone_gallery', $gallery );
			}

			++$created;
			$progress->tick();
		}

		$progress->finish();

		return $created;
	}

	/**
	 * Creates the About and Contact pages and the primary menu.
	 *
	 * @return void
	 */
	private function create_pages_and_menu(): void {
		$about = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'About',
				'post_name'    => 'about',
				'post_content' => implode(
					"\n\n",
					array(
						'<!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">Keystone Realty is an independent agency helping buyers, sellers and tenants across Southern Europe since 2009.</p><!-- /wp:paragraph -->',
						'<!-- wp:paragraph --><p>We work with a deliberately small portfolio so that every listing receives senior attention, honest pricing advice and professional presentation. Our multilingual agents live in the neighbourhoods they sell.</p><!-- /wp:paragraph -->',
						'<!-- wp:heading --><h2 class="wp-block-heading">Find your next home</h2><!-- /wp:heading -->',
						'<!-- wp:keystone/property-search /-->',
						'<!-- wp:heading --><h2 class="wp-block-heading">Hand-picked listings</h2><!-- /wp:heading -->',
						'<!-- wp:keystone/featured-properties {"count":3} /-->',
					)
				),
				'meta_input'   => array( self::SEED_FLAG => 1 ),
			)
		);

		$contact = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Contact',
				'post_name'    => 'contact',
				'post_content' => implode(
					"\n\n",
					array(
						'<!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">Every listing has a dedicated agent: the fastest way to reach us is the contact form on the property page.</p><!-- /wp:paragraph -->',
						'<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Lisbon</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Avenida da Liberdade 110<br>+351 210 000 101</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Barcelona</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Passeig de Gràcia 55<br>+34 930 000 202</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Paris</h3><!-- /wp:heading --><!-- wp:paragraph --><p>18 Rue du Bac<br>+33 1 00 00 03 03</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->',
						'<!-- wp:paragraph --><p>All names, phone numbers and addresses on this demo site are fictitious.</p><!-- /wp:paragraph -->',
					)
				),
				'meta_input'   => array( self::SEED_FLAG => 1 ),
			)
		);

		$menu_id = wp_create_nav_menu( self::MENU_NAME );
		if ( is_wp_error( $menu_id ) ) {
			return;
		}

		$items = array(
			array( __( 'Properties', 'keystone-properties' ), get_post_type_archive_link( PostTypes::PROPERTY ) ),
			array( __( 'Agents', 'keystone-properties' ), get_post_type_archive_link( PostTypes::AGENT ) ),
		);

		foreach ( $items as $position => $item ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'    => $item[0],
					'menu-item-url'      => $item[1],
					'menu-item-type'     => 'custom',
					'menu-item-status'   => 'publish',
					'menu-item-position' => $position + 1,
				)
			);
		}

		foreach ( array( $about, $contact ) as $position => $page_id ) {
			if ( $page_id && ! is_wp_error( $page_id ) ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object-id' => $page_id,
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
						'menu-item-position'  => $position + 3,
					)
				);
			}
		}

		$locations = get_theme_mod( 'nav_menu_locations', array() );
		foreach ( array_keys( get_registered_nav_menus() ) as $location ) {
			$locations[ $location ] = $menu_id;
		}
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Site identity and pretty permalinks.
	 *
	 * @return void
	 */
	private function configure_site(): void {
		update_option( 'blogname', 'Keystone Realty' );
		update_option( 'blogdescription', 'Exceptional homes across Southern Europe' );

		if ( ! get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
		}
	}

	/**
	 * Writes the n-th real demo photo of a folder (cycling) to a JPEG file, resized for the web.
	 *
	 * @param string $folder Folder relative to assets/demo/, e.g. "exterior/villa".
	 * @param int    $index  Index of the photo (wraps around).
	 * @param string $path   Target file path.
	 * @return string|null Path of the written JPEG, or null when the folder has no photo.
	 */
	private function demo_photo( string $folder, int $index, string $path ): ?string {
		$files = array_filter(
			(array) glob( KEYSTONE_PROPERTIES_DIR . 'assets/demo/' . $folder . '/*' ),
			static fn( $file ) => (bool) preg_match( '/\.(jpe?g|png|webp)$/i', (string) $file )
		);

		if ( ! $files ) {
			return null;
		}

		$files = array_values( $files );
		sort( $files );
		$editor = wp_get_image_editor( $files[ $index % count( $files ) ] );

		if ( is_wp_error( $editor ) ) {
			return null;
		}

		$editor->resize( 1600, 1600 );
		$editor->set_quality( 82 );
		$saved = $editor->save( $path . '.jpg', 'image/jpeg' );

		return is_wp_error( $saved ) ? null : $saved['path'];
	}

	/**
	 * Generates an image file and registers it as an attachment.
	 *
	 * @param callable $draw      Receives the temp path; returns bool, or the path of the file it wrote instead.
	 * @param string   $filename  Target file name.
	 * @param string   $alt       Alt text.
	 * @param int      $parent_id Parent post.
	 * @return int Attachment ID or 0.
	 */
	private function generate_attachment( callable $draw, string $filename, string $alt, int $parent_id = 0 ): int {
		$tmp    = wp_tempnam( $filename );
		$result = $draw( $tmp );

		if ( is_string( $result ) && $result !== $tmp ) {
			wp_delete_file( $tmp );
			$tmp = $result;
		}

		if ( ! $result ) {
			wp_delete_file( $tmp );
			return 0;
		}

		$upload = wp_upload_bits( $filename, null, (string) file_get_contents( $tmp ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local temp file.
		wp_delete_file( $tmp );

		if ( ! empty( $upload['error'] ) ) {
			WP_CLI::warning( $upload['error'] );
			return 0;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => $alt,
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$parent_id
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return 0;
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		update_post_meta( $attachment_id, self::SEED_FLAG, 1 );

		return (int) $attachment_id;
	}

	/**
	 * "a, b and c".
	 *
	 * @param array<int, string> $items Items.
	 * @return string
	 */
	private function human_list( array $items ): string {
		if ( count( $items ) < 2 ) {
			return implode( '', $items );
		}

		$last = array_pop( $items );

		return implode( ', ', $items ) . ' and ' . $last;
	}
}
