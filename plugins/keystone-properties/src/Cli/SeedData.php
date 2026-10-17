<?php
/**
 * Demo content for `wp keystone seed`.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Cli;

defined( 'ABSPATH' ) || exit;

/**
 * Realistic, hand-written demo dataset (all people, phones and emails are fictitious).
 */
final class SeedData {

	/**
	 * Cities: slug => name, centre coordinates, address format, streets and an intro line.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function cities(): array {
		return array(
			'lisbon'    => array(
				'name'    => 'Lisbon',
				'lat'     => 38.7139,
				'lng'     => -9.1394,
				'format'  => '%2$s %1$d, %3$s Lisbon',
				'codes'   => array( '1100-148', '1200-195', '1250-096', '1350-115' ),
				'streets' => array( 'Rua Augusta', 'Avenida da Liberdade', 'Rua do Alecrim', 'Travessa da Bica', 'Rua das Janelas Verdes' ),
				'intro'   => 'Lisbon pairs golden light and tiled façades with one of Europe’s most dynamic property markets.',
			),
			'barcelona' => array(
				'name'    => 'Barcelona',
				'lat'     => 41.3917,
				'lng'     => 2.1649,
				'format'  => '%2$s, %1$d, %3$s Barcelona',
				'codes'   => array( '08007', '08008', '08036', '08017' ),
				'streets' => array( 'Carrer de Mallorca', 'Passeig de Gràcia', 'Carrer del Rosselló', 'Rambla de Catalunya', 'Carrer de Provença' ),
				'intro'   => 'Barcelona blends Modernista architecture, Mediterranean beaches and a thriving tech scene.',
			),
			'paris'     => array(
				'name'    => 'Paris',
				'lat'     => 48.8606,
				'lng'     => 2.3376,
				'format'  => '%1$d %2$s, %3$s Paris',
				'codes'   => array( '75004', '75006', '75007', '75016' ),
				'streets' => array( 'Rue de Rivoli', 'Rue du Bac', 'Boulevard Saint-Germain', 'Rue Oberkampf', 'Avenue Montaigne' ),
				'intro'   => 'Paris remains a timeless address, where Haussmann buildings meet leafy boulevards and world-class culture.',
			),
			'nice'      => array(
				'name'    => 'Nice',
				'lat'     => 43.6966,
				'lng'     => 7.2656,
				'format'  => '%1$d %2$s, %3$s Nice',
				'codes'   => array( '06000', '06300', '06200' ),
				'streets' => array( 'Promenade des Anglais', 'Boulevard Victor Hugo', 'Rue de France', 'Avenue Jean Médecin', 'Boulevard du Mont Boron' ),
				'intro'   => 'Nice offers 300 days of sunshine, the azure Baie des Anges and the relaxed rhythm of the Riviera.',
			),
			'marbella'  => array(
				'name'    => 'Marbella',
				'lat'     => 36.5101,
				'lng'     => -4.8925,
				'format'  => '%2$s, %1$d, %3$s Marbella',
				'codes'   => array( '29600', '29602', '29660' ),
				'streets' => array( 'Avenida Ricardo Soriano', 'Calle Ancha', 'Camino de Camoján', 'Paseo Marítimo', 'Urbanización Sierra Blanca' ),
				'intro'   => 'Marbella combines Andalusian charm with the Costa del Sol’s most exclusive residential enclaves.',
			),
		);
	}

	/**
	 * Agents. `cities` drives which listings they get.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function agents(): array {
		return array(
			array(
				'name'     => 'Sofia Almeida',
				'position' => 'Senior Partner – Lisbon',
				'phone'    => '+351 210 000 101',
				'email'    => 'sofia.almeida@keystone-realty.example',
				'cities'   => array( 'lisbon' ),
				'bio'      => 'Sofia has spent fifteen years matching international buyers with Lisbon’s most characterful homes, from restored Alfama townhouses to contemporary riverside lofts. She speaks Portuguese, English and French, and is known for negotiating calmly and closing quickly.',
			),
			array(
				'name'     => 'Lucía Ferrer',
				'position' => 'Director – Spain',
				'phone'    => '+34 930 000 202',
				'email'    => 'lucia.ferrer@keystone-realty.example',
				'cities'   => array( 'barcelona', 'marbella' ),
				'bio'      => 'An architect by training, Lucía leads our Spanish desk between Barcelona and the Costa del Sol. Her eye for build quality and renovation potential has saved clients from costly surprises more than once.',
			),
			array(
				'name'     => 'Marc Delacroix',
				'position' => 'Head of Paris Office',
				'phone'    => '+33 1 00 00 03 03',
				'email'    => 'marc.delacroix@keystone-realty.example',
				'cities'   => array( 'paris' ),
				'bio'      => 'Marc knows every arrondissement by heart. He advises families, investors and companies on Paris acquisitions and leases, with a particular passion for Haussmann-era apartments and their original details.',
			),
			array(
				'name'     => 'Elena Rossi',
				'position' => 'Riviera Specialist',
				'phone'    => '+33 4 00 00 04 04',
				'email'    => 'elena.rossi@keystone-realty.example',
				'cities'   => array( 'nice' ),
				'bio'      => 'Born in Genoa and based in Nice for a decade, Elena specialises in sea-view apartments and Belle Époque villas along the Riviera. Clients value her discretion and her network of trusted local craftsmen.',
			),
		);
	}

	/**
	 * The 24 demo listings.
	 *
	 * Columns: title, city, type, status, price, beds, baths, surface m², year, featured.
	 *
	 * @return array<int, array<int, mixed>>
	 */
	public static function properties(): array {
		return array(
			array( 'Sunlit apartment with river views in Chiado', 'lisbon', 'apartment', 'for-sale', 685000, 2, 2, 98, 1910, true ),
			array( 'Renovated loft near Príncipe Real', 'lisbon', 'apartment', 'for-rent', 2400, 1, 1, 72, 1925, false ),
			array( 'Restored townhouse with patio in Alfama', 'lisbon', 'house', 'for-sale', 920000, 3, 2, 160, 1890, false ),
			array( 'Creative office space on Avenida da Liberdade', 'lisbon', 'office', 'for-rent', 6500, 0, 2, 240, 1998, false ),
			array( 'Contemporary villa in Restelo', 'lisbon', 'villa', 'sold', 2350000, 5, 4, 380, 2016, false ),
			array( 'Modernist apartment in the Eixample', 'barcelona', 'apartment', 'for-sale', 1150000, 3, 2, 145, 1902, true ),
			array( 'Bright two-bedroom by Passeig de Gràcia', 'barcelona', 'apartment', 'for-rent', 2900, 2, 2, 95, 1960, false ),
			array( 'Family house with garden in Sant Gervasi', 'barcelona', 'house', 'for-sale', 1890000, 4, 3, 260, 1975, false ),
			array( 'Open-plan office in the 22@ innovation district', 'barcelona', 'office', 'for-sale', 1320000, 0, 2, 410, 2012, false ),
			array( 'Hillside villa with sea views in Pedralbes', 'barcelona', 'villa', 'for-sale', 3750000, 6, 5, 520, 2008, true ),
			array( 'Haussmann apartment with balcony in Saint-Germain', 'paris', 'apartment', 'for-sale', 1480000, 3, 2, 118, 1880, true ),
			array( 'Furnished pied-à-terre in the Marais', 'paris', 'apartment', 'for-rent', 1750, 1, 1, 34, 1850, false ),
			array( 'Top-floor apartment with Eiffel Tower view', 'paris', 'apartment', 'sold', 2100000, 3, 2, 132, 1905, false ),
			array( 'Prestige office suite near Avenue Montaigne', 'paris', 'office', 'for-rent', 14000, 0, 2, 300, 1930, false ),
			array( 'Artist’s house with courtyard in Montmartre', 'paris', 'house', 'for-sale', 2650000, 4, 3, 210, 1912, false ),
			array( 'Sea-view apartment on the Promenade des Anglais', 'nice', 'apartment', 'for-sale', 890000, 2, 2, 86, 1965, true ),
			array( 'Belle Époque villa on Mont Boron', 'nice', 'villa', 'for-sale', 4200000, 6, 5, 450, 1905, true ),
			array( 'Old Town apartment steps from the flower market', 'nice', 'apartment', 'for-rent', 1600, 1, 1, 52, 1820, false ),
			array( 'Provençal house with olive grove', 'nice', 'house', 'for-sale', 1250000, 4, 3, 220, 1950, false ),
			array( 'Ground-floor office on Avenue Jean Médecin', 'nice', 'office', 'for-sale', 640000, 0, 1, 140, 1972, false ),
			array( 'Modern villa with infinity pool in Sierra Blanca', 'marbella', 'villa', 'for-sale', 5900000, 6, 6, 620, 2021, true ),
			array( 'Beachfront villa for long-term rental', 'marbella', 'villa', 'for-rent', 18000, 5, 5, 480, 2014, false ),
			array( 'Penthouse with wraparound terrace on the Golden Mile', 'marbella', 'apartment', 'for-sale', 1690000, 3, 3, 190, 2019, false ),
			array( 'Andalusian townhouse in the Old Town', 'marbella', 'house', 'sold', 720000, 3, 2, 150, 1960, false ),
		);
	}

	/**
	 * Feature pools per property type.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function features(): array {
		return array(
			'apartment' => array( 'Balcony', 'Elevator', 'Air conditioning', 'Hardwood floors', 'Concierge', 'Double glazing', 'Fitted kitchen', 'Storage room', 'Underfloor heating', 'City views' ),
			'house'     => array( 'Private garden', 'Garage', 'Fireplace', 'Terrace', 'Solar panels', 'Wine cellar', 'Home office', 'Patio', 'Air conditioning' ),
			'villa'     => array( 'Infinity pool', 'Sea views', 'Home cinema', 'Gym', 'Smart home system', 'Staff quarters', 'Outdoor kitchen', 'Landscaped garden', 'Double garage', 'Sauna' ),
			'office'    => array( 'Open-plan layout', 'Meeting rooms', 'Fibre internet', 'Reception area', '24/7 access', 'Bike storage', 'Kitchenette', 'Step-free access', 'Air conditioning' ),
		);
	}

	/**
	 * Opening paragraph per property type.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function type_paragraphs(): array {
		return array(
			'apartment' => array(
				'Behind a handsome façade, this apartment has been thoughtfully renovated to balance period character with modern comfort. Generous windows pour natural light across the living areas, while the layout keeps sleeping quarters quiet and private.',
				'A rare find in a sought-after building: high ceilings, well-proportioned rooms and a kitchen designed by someone who actually cooks. Everything is ready to move in, with nothing left to do but unpack.',
			),
			'house'     => array(
				'This house offers the space and privacy of a family home without giving up the energy of the city. Living spaces open onto outdoor areas that work as well for long lunches as for quiet mornings with a coffee.',
				'Carefully restored over the past few years, the house keeps its original charm — exposed beams, stone walls, traditional tiles — while bringing insulation, heating and wiring fully up to date.',
			),
			'villa'     => array(
				'Designed around light, views and the flow between inside and out, this villa delivers resort-style living all year round. Floor-to-ceiling glazing frames the landscape, and every bedroom enjoys its own bathroom.',
				'Set on a generous, fully landscaped plot, the villa combines architectural ambition with the kind of everyday practicality families appreciate: wide circulation, abundant storage and staff accommodation.',
			),
			'office'    => array(
				'A flexible workspace with the presence clients expect and the practicality teams need. The open-plan floor can be configured for collaboration, with enclosed rooms for focused work and confidential meetings.',
				'Efficient, bright and impeccably connected, these premises are ready for immediate occupation. Shared amenities and excellent transport links make it easy to attract and retain talent.',
			),
		);
	}

	/**
	 * Home-page testimonials are theme content; these are used for the "About" page.
	 *
	 * @return array<int, string>
	 */
	public static function closing_lines(): array {
		return array(
			'Viewings can be arranged at short notice, including by video call. Contact the listing agent for the full technical file and energy certificate.',
			'Our team can recommend independent lawyers, mortgage brokers and contractors to make your purchase as smooth as possible.',
			'Floor plans, energy performance certificate and building documents are available on request.',
		);
	}
}
