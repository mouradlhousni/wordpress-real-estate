/**
 * Live property search.
 *
 * Progressive enhancement of the server-rendered search page:
 * - filters, sort and pagination update results without a reload;
 * - the URL is kept in sync (shareable, back/forward work);
 * - loading state (aria-busy), polite announcement of the result count, empty state.
 *
 * Two interchangeable data sources share the exact same UI:
 * - dynamic site: the keystone/v1/properties REST endpoint;
 * - static export (<body data-static-build="1">): data/properties.json filtered in the browser.
 */
( function () {
	'use strict';

	const config = window.keystoneSearch || {};
	const root = document.querySelector( '[data-search-root]' );
	const form = document.querySelector( '[data-search-form]' );

	if ( ! root || ! form || ! window.fetch || ! window.URLSearchParams ) {
		return;
	}

	const i18n = config.i18n || {};
	const icons = config.icons || {};
	const results = root.querySelector( '[data-search-results]' );
	const count = root.querySelector( '[data-search-count]' );
	const empty = root.querySelector( '[data-search-empty]' );
	const pagination = root.querySelector( '[data-search-pagination]' );
	const sort = root.querySelector( '[data-search-sort]' );
	const viewToggle = root.querySelector( '[data-view-toggle]' );
	const perPage = config.perPage || 9;
	const FILTERS = [
		'city',
		'type',
		'status',
		'min_price',
		'max_price',
		'min_beds',
	];
	const isStatic =
		document.body.getAttribute( 'data-static-build' ) === '1' ||
		!! config.static;

	let controller = null;
	let staticData = null;

	document.documentElement.classList.add( 'js-search' );

	/* ------------------------------------------------------------ *
	 * State <-> URL
	 * ------------------------------------------------------------ */

	function readState( search ) {
		const params = new URLSearchParams( search );
		const state = {
			sort: params.get( 'sort' ) || config.sortDefault || 'newest',
			page: Math.max( 1, parseInt( params.get( 'pg' ), 10 ) || 1 ),
		};

		FILTERS.forEach( function ( key ) {
			const value = params.get( key );
			if ( value ) {
				state[ key ] = value;
			}
		} );

		// Taxonomy pages (/city/nice/) pre-select their term through the form.
		FILTERS.forEach( function ( key ) {
			if (
				! state[ key ] &&
				form.elements[ key ] &&
				form.elements[ key ].value
			) {
				state[ key ] = form.elements[ key ].value;
			}
		} );

		return state;
	}

	function formState() {
		const state = { sort: sort ? sort.value : 'newest', page: 1 };

		FILTERS.forEach( function ( key ) {
			const field = form.elements[ key ];
			if ( field && field.value ) {
				state[ key ] = field.value;
			}
		} );

		return state;
	}

	function syncForm( state ) {
		FILTERS.forEach( function ( key ) {
			if ( form.elements[ key ] ) {
				form.elements[ key ].value = state[ key ] || '';
			}
		} );
		if ( sort ) {
			sort.value = state.sort || 'newest';
		}
	}

	function stateToParams( state ) {
		const params = new URLSearchParams();

		FILTERS.forEach( function ( key ) {
			if ( state[ key ] ) {
				params.set( key, state[ key ] );
			}
		} );
		if ( state.sort && state.sort !== ( config.sortDefault || 'newest' ) ) {
			params.set( 'sort', state.sort );
		}
		if ( state.page > 1 ) {
			params.set( 'pg', String( state.page ) );
		}

		return params;
	}

	function stateToUrl( state ) {
		const query = stateToParams( state ).toString();
		const base = form.getAttribute( 'action' ) || window.location.pathname;

		return base.split( '?' )[ 0 ] + ( query ? '?' + query : '' );
	}

	/* ------------------------------------------------------------ *
	 * Data sources
	 * ------------------------------------------------------------ */

	function fetchRemote( state, signal ) {
		const params = new URLSearchParams();

		FILTERS.forEach( function ( key ) {
			if ( state[ key ] ) {
				params.set( key, state[ key ] );
			}
		} );
		params.set( 'sort', state.sort || 'newest' );
		params.set( 'page', String( state.page ) );
		params.set( 'per_page', String( perPage ) );

		const separator = config.endpoint.indexOf( '?' ) === -1 ? '?' : '&';

		return window
			.fetch( config.endpoint + separator + params.toString(), {
				signal,
				headers: { Accept: 'application/json' },
			} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				return response.json();
			} );
	}

	function loadStaticData( signal ) {
		if ( staticData ) {
			return Promise.resolve( staticData );
		}

		// The export rewrites every URL as relative, so the home link gives the site root wherever it is hosted.
		const home = document.querySelector( '.brand' );
		const base = home ? home.href : window.location.href;
		const resolve = function ( path ) {
			return path ? new URL( path, base ).href : path;
		};

		return window
			.fetch( resolve( 'data/properties.json' ), { signal } )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				return response.json();
			} )
			.then( function ( data ) {
				staticData = ( data.items || [] ).map( function ( item ) {
					item.link = resolve( item.link );
					if ( item.agent_brief && item.agent_brief.avatar ) {
						item.agent_brief.avatar = resolve(
							item.agent_brief.avatar
						);
					}
					if ( item.image ) {
						item.image.src = resolve( item.image.src );
						item.image.srcset = ( item.image.srcset || '' )
							.split( ',' )
							.map( function ( candidate ) {
								const parts = candidate.trim().split( /\s+/ );
								return parts[ 0 ]
									? resolve( parts[ 0 ] ) +
											' ' +
											( parts[ 1 ] || '' )
									: '';
							} )
							.filter( Boolean )
							.join( ', ' );
					}
					return item;
				} );
				return staticData;
			} );
	}

	// Mirrors PropertyRepository::query_args() so both modes return identical results.
	function filterStatic( items, state ) {
		const min = state.min_price ? Number( state.min_price ) : null;
		const max = state.max_price ? Number( state.max_price ) : null;
		const beds = state.min_beds ? Number( state.min_beds ) : 0;

		const matches = items.filter( function ( item ) {
			return (
				( ! state.city ||
					( item.city && item.city.slug === state.city ) ) &&
				( ! state.type ||
					( item.type && item.type.slug === state.type ) ) &&
				( ! state.status ||
					( item.status && item.status.slug === state.status ) ) &&
				( min === null || item.price >= min ) &&
				( max === null || item.price <= max ) &&
				item.bedrooms >= beds
			);
		} );

		const sorters = {
			newest( a, b ) {
				return a.date < b.date ? 1 : -1;
			},
			'price-asc'( a, b ) {
				return a.price - b.price;
			},
			'price-desc'( a, b ) {
				return b.price - a.price;
			},
			surface( a, b ) {
				return b.surface - a.surface;
			},
		};

		matches.sort( sorters[ state.sort ] || sorters.newest );

		const totalPages = Math.ceil( matches.length / perPage );
		const page = Math.min( state.page, Math.max( 1, totalPages ) );

		return {
			items: matches.slice( ( page - 1 ) * perPage, page * perPage ),
			total: matches.length,
			total_pages: totalPages,
			page,
			per_page: perPage,
		};
	}

	function getResults( state, signal ) {
		if ( isStatic ) {
			return loadStaticData( signal ).then( function ( items ) {
				return filterStatic( items, state );
			} );
		}

		return fetchRemote( state, signal );
	}

	/* ------------------------------------------------------------ *
	 * Rendering (keep in sync with template-parts/property-card.php)
	 * ------------------------------------------------------------ */

	function esc( value ) {
		return String( value === null || value === undefined ? '' : value )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	function plural( n, one, many ) {
		return ( n === 1 ? one : many )
			.replace( '%d', n )
			.replace( '%s', n.toLocaleString() );
	}

	function renderCard( item ) {
		const image = item.image
			? '<img class="property-card__img" src="' +
			  esc( item.image.src ) +
			  '" srcset="' +
			  esc( item.image.srcset ) +
			  '" sizes="(min-width: 1200px) 380px, (min-width: 700px) 50vw, 100vw" width="' +
			  esc( item.image.width ) +
			  '" height="' +
			  esc( item.image.height ) +
			  '" alt="' +
			  esc( item.image.alt ) +
			  '" loading="lazy" decoding="async">'
			: '';
		const photos =
			item.photo_count > 1
				? '<span class="property-card__photos">' +
				  ( icons.camera || '' ) +
				  esc( item.photo_count ) +
				  '<span class="screen-reader-text"> ' +
				  esc( i18n.photos ) +
				  '</span></span>'
				: '';
		const agent =
			item.agent_brief && item.agent_brief.name
				? '<span class="property-card__agent">' +
				  ( item.agent_brief.avatar
						? '<img src="' +
						  esc( item.agent_brief.avatar ) +
						  '" width="32" height="32" alt="" loading="lazy" decoding="async">'
						: '' ) +
				  '<span>' +
				  esc( item.agent_brief.name ) +
				  '</span></span>'
				: '';
		const badge = item.status
			? '<span class="badge badge--' +
			  esc( item.status.slug ) +
			  '">' +
			  esc( item.status.name ) +
			  '</span>'
			: '';
		const type = item.type
			? '<span class="property-card__type">' +
			  esc( item.type.name ) +
			  '</span>'
			: '';
		const specs = [];

		if ( item.bedrooms ) {
			specs.push(
				'<li>' +
					( icons.bed || '' ) +
					esc( plural( item.bedrooms, i18n.bedOne, i18n.bedMany ) ) +
					'</li>'
			);
		}
		if ( item.bathrooms ) {
			specs.push(
				'<li>' +
					( icons.bath || '' ) +
					esc(
						plural( item.bathrooms, i18n.bathOne, i18n.bathMany )
					) +
					'</li>'
			);
		}
		if ( item.surface ) {
			specs.push(
				'<li>' +
					( icons.area || '' ) +
					esc( Number( item.surface ).toLocaleString() + ' m²' ) +
					'</li>'
			);
		}

		return (
			'<article class="property-card" data-property-id="' +
			esc( item.id ) +
			'" data-type="' +
			esc( item.type ? item.type.slug : '' ) +
			'">' +
			'<div class="property-card__media">' +
			image +
			'<span class="property-card__go" aria-hidden="true">' +
			( icons.go || '' ) +
			'</span>' +
			photos +
			agent +
			'</div>' +
			'<div class="property-card__body">' +
			'<p class="property-card__meta">' +
			badge +
			type +
			'</p>' +
			'<p class="property-card__price">' +
			esc( item.price_formatted ) +
			'</p>' +
			'<h2 class="property-card__title"><a class="property-card__link" href="' +
			esc( item.link ) +
			'">' +
			esc( item.title ) +
			'</a></h2>' +
			'<p class="property-card__place">' +
			( icons.pin || '' ) +
			esc( item.city ? item.city.name : '' ) +
			'</p>' +
			'<ul class="specs">' +
			specs.join( '' ) +
			'</ul>' +
			'</div></article>'
		);
	}

	function renderPagination( data, state ) {
		if ( data.total_pages < 2 ) {
			pagination.innerHTML = '';
			return;
		}

		let html =
			'<nav class="pagination" aria-label="' +
			esc( i18n.pages ) +
			'"><ul>';
		for ( let i = 1; i <= data.total_pages; i++ ) {
			const url = stateToUrl( Object.assign( {}, state, { page: i } ) );
			html +=
				'<li><a href="' +
				esc( url ) +
				'" data-page="' +
				i +
				'"' +
				( i === data.page ? ' aria-current="page"' : '' ) +
				'>' +
				i +
				'</a></li>';
		}
		pagination.innerHTML = html + '</ul></nav>';
	}

	function render( data, state ) {
		results.innerHTML = data.items.map( renderCard ).join( '' );
		count.textContent = plural( data.total, i18n.countOne, i18n.countMany );
		empty.hidden = data.items.length > 0;
		renderPagination( data, state );

		document.dispatchEvent(
			new window.CustomEvent( 'keystone:results', {
				detail: { items: data.items },
			} )
		);
	}

	/* ------------------------------------------------------------ *
	 * Flow
	 * ------------------------------------------------------------ */

	function run( state, options ) {
		const opts = options || {};

		if ( controller ) {
			controller.abort();
		}
		controller = new window.AbortController();

		results.setAttribute( 'aria-busy', 'true' );

		if ( opts.push ) {
			window.history.pushState( state, '', stateToUrl( state ) );
		}

		return getResults( state, controller.signal )
			.then( function ( data ) {
				render( data, state );
				if ( opts.scroll ) {
					root.scrollIntoView( {
						behavior: window.matchMedia(
							'(prefers-reduced-motion: reduce)'
						).matches
							? 'auto'
							: 'smooth',
						block: 'start',
					} );
				}
			} )
			.catch( function ( error ) {
				if ( error.name !== 'AbortError' ) {
					count.textContent = i18n.error;
				}
			} )
			.finally( function () {
				results.setAttribute( 'aria-busy', 'false' );
			} );
	}

	form.addEventListener( 'change', function () {
		run( formState(), { push: true } );
	} );

	form.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		run( formState(), { push: true } );
	} );

	if ( sort ) {
		sort.addEventListener( 'change', function () {
			run( formState(), { push: true } );
		} );
	}

	pagination.addEventListener( 'click', function ( event ) {
		const link = event.target.closest( 'a[data-page]' );
		if ( ! link ) {
			return;
		}
		event.preventDefault();
		const state = Object.assign( formState(), {
			page: parseInt( link.getAttribute( 'data-page' ), 10 ),
		} );
		run( state, { push: true, scroll: true } );
	} );

	document.addEventListener( 'click', function ( event ) {
		const reset = event.target.closest( '[data-search-reset]' );
		if ( ! reset ) {
			return;
		}
		event.preventDefault();
		form.reset();
		FILTERS.forEach( function ( key ) {
			if ( form.elements[ key ] ) {
				form.elements[ key ].value = '';
			}
		} );
		if ( sort ) {
			sort.value = config.sortDefault || 'newest';
		}
		run( formState(), { push: true } );
		form.elements.city.focus();
	} );

	window.addEventListener( 'popstate', function () {
		const state = readState( window.location.search );
		syncForm( state );
		run( state );
	} );

	if ( viewToggle ) {
		const label = viewToggle.querySelector( '[data-view-toggle-label]' );
		viewToggle.addEventListener( 'click', function () {
			const showMap = root.getAttribute( 'data-view' ) !== 'map';
			root.setAttribute( 'data-view', showMap ? 'map' : 'list' );
			viewToggle.setAttribute(
				'aria-pressed',
				showMap ? 'true' : 'false'
			);
			label.textContent = showMap ? i18n.showList : i18n.showMap;
			document.dispatchEvent(
				new window.CustomEvent( 'keystone:map-visible' )
			);
		} );
	}

	// Static pages are identical for every URL: render the state requested by the query string.
	if ( isStatic ) {
		const initial = readState( window.location.search );
		syncForm( initial );
		run( initial );
	}

	window.history.replaceState( readState( window.location.search ), '' );
} )();
