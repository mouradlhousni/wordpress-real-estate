<?php
/**
 * Custom, sortable admin list columns.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Admin;

use Keystone\Properties\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Adds price / city / status columns (all sortable) and taxonomy filters to the property list,
 * and useful columns to the lead list.
 */
final class Columns {

	/**
	 * Sortable column => taxonomy for term-name sorting.
	 */
	private const TERM_SORTS = array(
		'keystone_city'   => PostTypes::TAX_CITY,
		'keystone_status' => PostTypes::TAX_STATUS,
	);

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		$property = PostTypes::PROPERTY;

		add_filter( "manage_{$property}_posts_columns", array( $this, 'property_columns' ) );
		add_action( "manage_{$property}_posts_custom_column", array( $this, 'render_property_column' ), 10, 2 );
		add_filter( "manage_edit-{$property}_sortable_columns", array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_price' ) );
		add_filter( 'posts_clauses', array( $this, 'sort_by_term' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'taxonomy_filters' ) );

		$lead = PostTypes::LEAD;
		add_filter( "manage_{$lead}_posts_columns", array( $this, 'lead_columns' ) );
		add_action( "manage_{$lead}_posts_custom_column", array( $this, 'render_lead_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'lead_row_actions' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Property list columns.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function property_columns( $columns ): array {
		$new = array();

		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['keystone_thumb'] = '<span class="screen-reader-text">' . esc_html__( 'Image', 'keystone-properties' ) . '</span>';
			}

			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['keystone_price']    = __( 'Price', 'keystone-properties' );
				$new['keystone_city']     = __( 'City', 'keystone-properties' );
				$new['keystone_status']   = __( 'Status', 'keystone-properties' );
				$new['keystone_type']     = __( 'Type', 'keystone-properties' );
				$new['keystone_agent']    = __( 'Agent', 'keystone-properties' );
				$new['keystone_featured'] = __( 'Featured', 'keystone-properties' );
			}
		}

		return $new;
	}

	/**
	 * Renders a property column cell.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_property_column( $column, $post_id ): void {
		switch ( $column ) {
			case 'keystone_thumb':
				echo get_the_post_thumbnail( $post_id, 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped markup.
				break;

			case 'keystone_price':
				$status = get_the_terms( $post_id, PostTypes::TAX_STATUS );
				$slug   = is_array( $status ) && $status ? $status[0]->slug : '';
				echo esc_html( keystone_format_price( (float) get_post_meta( $post_id, 'keystone_price', true ), $slug ) );
				break;

			case 'keystone_city':
				$this->term_links( $post_id, PostTypes::TAX_CITY );
				break;

			case 'keystone_status':
				$this->term_links( $post_id, PostTypes::TAX_STATUS );
				break;

			case 'keystone_type':
				$this->term_links( $post_id, PostTypes::TAX_TYPE );
				break;

			case 'keystone_agent':
				$agent = (int) get_post_meta( $post_id, 'keystone_agent', true );
				echo $agent ? esc_html( get_the_title( $agent ) ) : '—';
				break;

			case 'keystone_featured':
				if ( get_post_meta( $post_id, 'keystone_featured', true ) ) {
					printf( '<span class="dashicons dashicons-star-filled" aria-hidden="true"></span><span class="screen-reader-text">%s</span>', esc_html__( 'Featured', 'keystone-properties' ) );
				}
				break;
		}
	}

	/**
	 * Sortable columns.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function sortable_columns( $columns ): array {
		$columns['keystone_price']  = 'keystone_price';
		$columns['keystone_city']   = 'keystone_city';
		$columns['keystone_status'] = 'keystone_status';

		return $columns;
	}

	/**
	 * Numeric price sort that keeps listings without a price.
	 *
	 * @param \WP_Query $query Query.
	 * @return void
	 */
	public function sort_by_price( $query ): void {
		if ( ! $this->is_property_list( $query ) || 'keystone_price' !== $query->get( 'orderby' ) ) {
			return;
		}

		$query->set(
			'meta_query',
			array(
				'relation'       => 'OR',
				'keystone_price' => array(
					'key'  => 'keystone_price',
					'type' => 'NUMERIC',
				),
				array(
					'key'     => 'keystone_price',
					'compare' => 'NOT EXISTS',
				),
			)
		);
		$query->set( 'orderby', array( 'keystone_price' => $this->order( $query ) ) );
	}

	/**
	 * Sorts by the name of a taxonomy term (city / status) with a single LEFT JOIN.
	 *
	 * @param array<string, string> $clauses SQL clauses.
	 * @param \WP_Query             $query   Query.
	 * @return array<string, string>
	 */
	public function sort_by_term( $clauses, $query ): array {
		$orderby = $query->get( 'orderby' );

		if ( ! $this->is_property_list( $query ) || ! is_string( $orderby ) || ! isset( self::TERM_SORTS[ $orderby ] ) ) {
			return $clauses;
		}

		global $wpdb;

		$clauses['join'] .= $wpdb->prepare(
			" LEFT JOIN (
				SELECT tr.object_id, MIN(t.name) AS keystone_term_name
				FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
				INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
				GROUP BY tr.object_id
			) keystone_terms ON keystone_terms.object_id = {$wpdb->posts}.ID",
			self::TERM_SORTS[ $orderby ]
		);

		$clauses['orderby'] = 'keystone_terms.keystone_term_name ' . $this->order( $query ) . ", {$wpdb->posts}.post_date DESC";

		return $clauses;
	}

	/**
	 * City / status / type dropdown filters above the list.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public function taxonomy_filters( $post_type ): void {
		if ( PostTypes::PROPERTY !== $post_type ) {
			return;
		}

		foreach ( array( PostTypes::TAX_CITY, PostTypes::TAX_STATUS, PostTypes::TAX_TYPE ) as $taxonomy ) {
			$tax = get_taxonomy( $taxonomy );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter.
			$current = isset( $_GET[ $taxonomy ] ) ? sanitize_title( wp_unslash( $_GET[ $taxonomy ] ) ) : '';

			printf( '<label class="screen-reader-text" for="filter-%1$s">%2$s</label>', esc_attr( $taxonomy ), esc_html( $tax->labels->name ) );
			wp_dropdown_categories(
				array(
					'show_option_all' => $tax->labels->all_items,
					'taxonomy'        => $taxonomy,
					'name'            => $taxonomy,
					'id'              => 'filter-' . $taxonomy,
					'value_field'     => 'slug',
					'selected'        => $current,
					'hide_empty'      => false,
					'hierarchical'    => true,
				)
			);
		}
	}

	/**
	 * Lead list columns.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function lead_columns( $columns ): array {
		return array(
			'cb'                     => $columns['cb'] ?? '',
			'title'                  => __( 'Lead', 'keystone-properties' ),
			'keystone_lead_email'    => __( 'Email', 'keystone-properties' ),
			'keystone_lead_property' => __( 'Property', 'keystone-properties' ),
			'keystone_lead_agent'    => __( 'Agent', 'keystone-properties' ),
			'date'                   => __( 'Received', 'keystone-properties' ),
		);
	}

	/**
	 * Renders a lead column cell.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_lead_column( $column, $post_id ): void {
		if ( 'keystone_lead_email' === $column ) {
			$email = (string) get_post_meta( $post_id, 'keystone_lead_email', true );
			printf( '<a href="%1$s">%2$s</a>', esc_url( 'mailto:' . $email ), esc_html( $email ) );
		}

		if ( 'keystone_lead_property' === $column || 'keystone_lead_agent' === $column ) {
			$related = (int) get_post_meta( $post_id, $column, true );
			echo $related ? esc_html( get_the_title( $related ) ) : '—';
		}
	}

	/**
	 * Leads are read-only: drop "Quick Edit".
	 *
	 * @param array<string, string> $actions Row actions.
	 * @param \WP_Post              $post    Post.
	 * @return array<string, string>
	 */
	public function lead_row_actions( $actions, $post ): array {
		if ( PostTypes::LEAD === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}

	/**
	 * Admin CSS on list screens.
	 *
	 * @param string $hook_suffix Admin page.
	 * @return void
	 */
	public function enqueue( $hook_suffix ): void {
		$screen = get_current_screen();

		if ( 'edit.php' === $hook_suffix && $screen && in_array( $screen->post_type, array( PostTypes::PROPERTY, PostTypes::LEAD ), true ) ) {
			wp_enqueue_style( 'keystone-admin', KEYSTONE_PROPERTIES_URL . 'assets/css/admin.css', array(), KEYSTONE_PROPERTIES_VERSION );
		}
	}

	/**
	 * Echoes comma-separated, filterable term links.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @return void
	 */
	private function term_links( int $post_id, string $taxonomy ): void {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! is_array( $terms ) || ! $terms ) {
			echo '—';
			return;
		}

		$links = array();
		foreach ( $terms as $term ) {
			$url     = add_query_arg(
				array(
					'post_type' => PostTypes::PROPERTY,
					$taxonomy   => $term->slug,
				),
				admin_url( 'edit.php' )
			);
			$links[] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html( $term->name ) );
		}

		echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	}

	/**
	 * Whether a query is the main admin property list.
	 *
	 * @param \WP_Query $query Query.
	 * @return bool
	 */
	private function is_property_list( $query ): bool {
		return is_admin() && $query->is_main_query() && PostTypes::PROPERTY === $query->get( 'post_type' );
	}

	/**
	 * Safe ORDER direction.
	 *
	 * @param \WP_Query $query Query.
	 * @return string
	 */
	private function order( $query ): string {
		return 'ASC' === strtoupper( (string) $query->get( 'order' ) ) ? 'ASC' : 'DESC';
	}
}
