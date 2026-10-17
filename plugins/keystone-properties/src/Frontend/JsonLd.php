<?php
/**
 * Structured data for property pages.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Frontend;

use Keystone\Properties\PostTypes;
use Keystone\Properties\PropertyRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Prints schema.org RealEstateListing + Offer JSON-LD on single property pages.
 */
final class JsonLd {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_head', array( $this, 'print_property' ), 20 );
	}

	/**
	 * Outputs the JSON-LD block.
	 *
	 * @return void
	 */
	public function print_property(): void {
		if ( ! is_singular( PostTypes::PROPERTY ) ) {
			return;
		}

		$data = self::build( get_queried_object_id() );

		if ( ! $data ) {
			return;
		}

		printf(
			"<script type=\"application/ld+json\">%s</script>\n",
			wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP )
		);
	}

	/**
	 * Builds the structured data array for a property.
	 *
	 * @param int $post_id Property ID.
	 * @return array<string, mixed>
	 */
	public static function build( int $post_id ): array {
		$property = PropertyRepository::to_array( $post_id );

		if ( ! $property ) {
			return array();
		}

		$status   = $property['status']['slug'] ?? '';
		$currency = keystone_currency();
		$images   = array();

		foreach ( array_merge( array( (int) get_post_thumbnail_id( $post_id ) ), $property['gallery'] ) as $image_id ) {
			$url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : false;
			if ( $url ) {
				$images[] = $url;
			}
		}

		$offer = array(
			'@type'         => 'Offer',
			'price'         => $property['price'],
			'priceCurrency' => $currency['code'],
			'availability'  => 'sold' === $status ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
			'businessFunction' => 'for-rent' === $status ? 'http://purl.org/goodrelations/v1#LeaseOut' : 'http://purl.org/goodrelations/v1#Sell',
			'url'           => $property['link'],
		);

		if ( 'for-rent' === $status ) {
			$offer['priceSpecification'] = array(
				'@type'         => 'UnitPriceSpecification',
				'price'         => $property['price'],
				'priceCurrency' => $currency['code'],
				'unitCode'      => 'MON',
			);
		}

		$accommodation = array(
			'@type'          => self::accommodation_type( $property['type']['slug'] ?? '' ),
			'name'           => $property['title'],
			'floorSize'      => array(
				'@type'    => 'QuantitativeValue',
				'value'    => $property['surface'],
				'unitCode' => 'MTK',
			),
			'numberOfRooms'  => $property['bedrooms'] + $property['bathrooms'],
			'address'        => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $property['address'],
				'addressLocality' => $property['city']['name'] ?? '',
			),
		);

		if ( $property['bedrooms'] ) {
			$accommodation['numberOfBedrooms'] = $property['bedrooms'];
		}

		if ( $property['bathrooms'] ) {
			$accommodation['numberOfBathroomsTotal'] = $property['bathrooms'];
		}

		if ( $property['year_built'] ) {
			$accommodation['yearBuilt'] = $property['year_built'];
		}

		if ( $property['latitude'] || $property['longitude'] ) {
			$accommodation['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => $property['latitude'],
				'longitude' => $property['longitude'],
			);
		}

		if ( $property['features'] ) {
			$accommodation['amenityFeature'] = array_map(
				static fn( $feature ) => array(
					'@type' => 'LocationFeatureSpecification',
					'name'  => $feature,
					'value' => true,
				),
				$property['features']
			);
		}

		$data = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'RealEstateListing',
			'@id'           => $property['link'] . '#listing',
			'url'           => $property['link'],
			'name'          => $property['title'],
			'description'   => $property['excerpt'],
			'datePosted'    => $property['date'],
			'image'         => $images,
			'offers'        => $offer,
			'about'         => $accommodation,
		);

		$agent = PropertyRepository::agent( $property['agent'] );
		if ( $agent ) {
			$data['offers']['offeredBy'] = array(
				'@type'     => 'RealEstateAgent',
				'name'      => $agent['name'],
				'telephone' => $agent['phone'],
				'email'     => $agent['email'],
				'url'       => $agent['link'],
				'parentOrganization' => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
					'url'   => home_url( '/' ),
				),
			);
		}

		/**
		 * Filters the JSON-LD data of a property.
		 *
		 * @param array<string, mixed> $data    Structured data.
		 * @param int                  $post_id Property ID.
		 */
		return (array) apply_filters( 'keystone_property_json_ld', $data, $post_id );
	}

	/**
	 * Maps our property types to schema.org Accommodation subtypes.
	 *
	 * @param string $type_slug Type slug.
	 * @return string
	 */
	private static function accommodation_type( string $type_slug ): string {
		$map = array(
			'apartment' => 'Apartment',
			'house'     => 'SingleFamilyResidence',
			'villa'     => 'House',
			'office'    => 'Accommodation',
		);

		return $map[ $type_slug ] ?? 'Accommodation';
	}
}
