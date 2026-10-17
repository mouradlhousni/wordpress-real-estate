/**
 * Global behaviour:
 * - accessible mobile navigation toggle;
 * - type chips that filter an already-rendered card grid (progressive enhancement);
 * - previous/next buttons for horizontal scroll-snap rails.
 */
( function () {
	'use strict';

	const reducedMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;

	/* ------------------------------------------------------------ *
	 * Mobile navigation
	 * ------------------------------------------------------------ */
	const toggle = document.querySelector( '.nav-toggle' );
	const nav = document.getElementById( 'primary-nav' );

	if ( toggle && nav ) {
		const setOpen = function ( open ) {
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			nav.classList.toggle( 'is-open', open );
		};

		toggle.addEventListener( 'click', function () {
			setOpen( toggle.getAttribute( 'aria-expanded' ) !== 'true' );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if (
				event.key === 'Escape' &&
				nav.classList.contains( 'is-open' )
			) {
				setOpen( false );
				toggle.focus();
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if (
				nav.classList.contains( 'is-open' ) &&
				! nav.contains( event.target ) &&
				! toggle.contains( event.target )
			) {
				setOpen( false );
			}
		} );
	}

	/* ------------------------------------------------------------ *
	 * Card filter chips (home "Featured homes")
	 * ------------------------------------------------------------ */
	document
		.querySelectorAll( '[data-card-filter]' )
		.forEach( function ( group ) {
			const grid = document.getElementById(
				group.getAttribute( 'data-card-filter' )
			);
			if ( ! grid ) {
				return;
			}

			group.hidden = false;

			group.addEventListener( 'click', function ( event ) {
				const chip = event.target.closest( 'button[data-type]' );
				if ( ! chip ) {
					return;
				}
				const type = chip.getAttribute( 'data-type' );

				group
					.querySelectorAll( 'button[data-type]' )
					.forEach( function ( button ) {
						button.setAttribute(
							'aria-pressed',
							button === chip ? 'true' : 'false'
						);
					} );

				grid.querySelectorAll( '[data-type]' ).forEach(
					function ( card ) {
						card.hidden =
							!! type &&
							card.getAttribute( 'data-type' ) !== type;
					}
				);
			} );
		} );

	/* ------------------------------------------------------------ *
	 * Scroll rails
	 * ------------------------------------------------------------ */
	document.querySelectorAll( '[data-rail-nav]' ).forEach( function ( navEl ) {
		const rail = document.getElementById(
			navEl.getAttribute( 'data-rail-nav' )
		);
		if ( ! rail ) {
			return;
		}

		const prev = navEl.querySelector( '[data-rail-prev]' );
		const next = navEl.querySelector( '[data-rail-next]' );

		function update() {
			const max = rail.scrollWidth - rail.clientWidth;
			navEl.hidden = max < 4;
			prev.disabled = rail.scrollLeft <= 4;
			next.disabled = rail.scrollLeft >= max - 4;
		}

		function step( direction ) {
			const item = rail.firstElementChild;
			const distance = item
				? item.getBoundingClientRect().width + 24
				: rail.clientWidth * 0.8;
			rail.scrollBy( {
				left: direction * distance,
				behavior: reducedMotion ? 'auto' : 'smooth',
			} );
		}

		prev.addEventListener( 'click', function () {
			step( -1 );
		} );
		next.addEventListener( 'click', function () {
			step( 1 );
		} );
		rail.addEventListener( 'scroll', update, { passive: true } );
		window.addEventListener( 'resize', update );
		update();
	} );
} )();
