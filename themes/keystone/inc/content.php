<?php
/**
 * Editorial content of the home page (fictitious, filterable).
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

/**
 * Client testimonials.
 *
 * @return array<int, array<string, string>>
 */
function keystone_theme_testimonials(): array {
	$testimonials = array(
		array(
			'quote'  => __( 'We were buying from London without being able to visit every weekend. Sofia filmed each viewing, flagged a damp problem nobody else mentioned and negotiated eight percent off the asking price.', 'keystone' ),
			'name'   => 'Hannah & Tom Whitaker',
			'detail' => __( 'Bought a townhouse in Lisbon', 'keystone' ),
		),
		array(
			'quote'  => __( 'Lucía read the architect’s plans better than our own surveyor. Six months later, the renovation came in on budget, exactly as she predicted.', 'keystone' ),
			'name'   => 'Jonas Berglund',
			'detail' => __( 'Bought an apartment in Barcelona', 'keystone' ),
		),
		array(
			'quote'  => __( 'Our company needed a Paris office in five weeks. Marc shortlisted three, handled the lease in French and English, and we moved in on schedule.', 'keystone' ),
			'name'   => 'Priya Raman',
			'detail' => __( 'Leased offices in Paris', 'keystone' ),
		),
	);

	/**
	 * Filters the home page testimonials.
	 *
	 * @param array<int, array<string, string>> $testimonials Testimonials.
	 */
	return apply_filters( 'keystone_theme_testimonials', $testimonials );
}

/**
 * Key figures of the agency that are not derived from content.
 *
 * @return array<int, array<string, string>>
 */
function keystone_theme_agency_facts(): array {
	$facts = array(
		array(
			'value' => '2009',
			'label' => __( 'Independent since', 'keystone' ),
		),
		array(
			'value' => '48h',
			'label' => __( 'From first call to first viewing', 'keystone' ),
		),
	);

	/**
	 * Filters the agency key figures shown on the home page.
	 *
	 * @param array<int, array<string, string>> $facts Facts (value, label).
	 */
	return apply_filters( 'keystone_theme_agency_facts', $facts );
}
