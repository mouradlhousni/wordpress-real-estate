/**
 * Leaflet + OpenStreetMap maps.
 *
 * Leaflet (~150 kB) is injected only when a map container actually becomes visible,
 * so it never weighs on pages or viewports that do not show a map.
 * Markers are price pills; on the search page they follow live results and highlight
 * when the matching card is hovered or focused.
 */
( function () {
	'use strict';

	const config = window.keystoneMap || {};
	const containers = Array.prototype.slice.call(
		document.querySelectorAll( '[data-map]' )
	);

	if ( ! containers.length ) {
		return;
	}

	let leafletPromise = null;

	function loadLeaflet() {
		if ( window.L ) {
			return Promise.resolve( window.L );
		}

		if ( ! leafletPromise ) {
			leafletPromise = new Promise( function ( resolve, reject ) {
				const css = document.createElement( 'link' );
				css.rel = 'stylesheet';
				css.href = config.leafletCss;
				document.head.appendChild( css );

				const script = document.createElement( 'script' );
				script.src = config.leafletJs;
				script.async = true;
				script.onload = function () {
					resolve( window.L );
				};
				script.onerror = reject;
				document.head.appendChild( script );
			} );
		}

		return leafletPromise;
	}

	function esc( value ) {
		const div = document.createElement( 'div' );
		div.textContent =
			value === null || value === undefined ? '' : String( value );
		return div.innerHTML;
	}

	function createMap( L, el ) {
		const map = L.map( el, {
			scrollWheelZoom: false,
			zoomControl: true,
		} );

		L.tileLayer( config.tiles, {
			maxZoom: 19,
			attribution: config.attribution,
		} ).addTo( map );

		const layer = L.layerGroup().addTo( map );
		const markers = {};

		function setMarkers( list ) {
			layer.clearLayers();
			Object.keys( markers ).forEach( function ( key ) {
				delete markers[ key ];
			} );

			const bounds = [];

			list.forEach( function ( item ) {
				const icon = L.divIcon( {
					className: 'map-pin-icon',
					html:
						'<span class="map-pin">' +
						esc( item.price ) +
						'</span>',
					iconSize: null,
				} );

				const marker = L.marker( [ item.lat, item.lng ], {
					icon,
					keyboard: true,
					title: item.title,
					alt: item.title + ', ' + item.price,
					riseOnHover: true,
				} );

				marker.bindPopup(
					'<div class="map-popup">' +
						( item.image
							? '<img src="' +
							  esc( item.image ) +
							  '" width="88" height="88" alt="" loading="lazy">'
							: '' ) +
						'<div class="map-popup__body"><strong>' +
						esc( item.price ) +
						'</strong><a href="' +
						esc( item.link ) +
						'">' +
						esc( item.title ) +
						'</a>' +
						( item.place
							? '<span>' + esc( item.place ) + '</span>'
							: '' ) +
						'</div></div>',
					{ minWidth: 260, maxWidth: 320 }
				);

				marker.addTo( layer );
				markers[ item.id ] = marker;
				bounds.push( [ item.lat, item.lng ] );
			} );

			if ( bounds.length === 1 ) {
				map.setView(
					bounds[ 0 ],
					parseInt( el.getAttribute( 'data-map-zoom' ), 10 ) || 14
				);
			} else if ( bounds.length ) {
				map.fitBounds( bounds, { padding: [ 48, 48 ], maxZoom: 14 } );
			} else {
				map.setView( [ 43, 3 ], 4 );
			}
		}

		function highlight( id, active ) {
			const marker = markers[ id ];
			const pin =
				marker &&
				marker.getElement() &&
				marker.getElement().querySelector( '.map-pin' );
			if ( pin ) {
				pin.classList.toggle( 'is-active', active );
				marker.setZIndexOffset( active ? 1000 : 0 );
			}
		}

		setMarkers(
			JSON.parse( el.getAttribute( 'data-map-markers' ) || '[]' )
		);

		return { map, setMarkers, highlight };
	}

	function toMarkers( items ) {
		return items
			.filter( function ( item ) {
				return item.latitude || item.longitude;
			} )
			.map( function ( item ) {
				return {
					id: item.id,
					lat: item.latitude,
					lng: item.longitude,
					title: item.title,
					price: item.price_formatted,
					link: item.link,
					place: item.city ? item.city.name : '',
					image: item.image ? item.image.src : '',
				};
			} );
	}

	containers.forEach( function ( el ) {
		let instance = null;
		let pending = null;

		function init() {
			if ( instance || el.offsetParent === null ) {
				return;
			}

			loadLeaflet().then( function ( L ) {
				if ( instance ) {
					return;
				}
				instance = createMap( L, el );
				if ( pending ) {
					instance.setMarkers( pending );
					pending = null;
				}
			} );
		}

		// Follow live search results.
		document.addEventListener( 'keystone:results', function ( event ) {
			const list = toMarkers( event.detail.items || [] );
			el.setAttribute( 'data-map-markers', JSON.stringify( list ) );
			if ( instance ) {
				instance.setMarkers( list );
			} else {
				pending = list;
			}
		} );

		// The mobile "Show map" toggle reveals a container that was hidden.
		document.addEventListener( 'keystone:map-visible', function () {
			init();
			if ( instance ) {
				window.setTimeout( function () {
					instance.map.invalidateSize();
				}, 50 );
			}
		} );

		// Card hover/focus highlights the matching pin.
		[ 'mouseover', 'focusin' ].forEach( function ( type ) {
			document.addEventListener( type, function ( event ) {
				const card =
					event.target.closest &&
					event.target.closest( '[data-property-id]' );
				if ( card && instance ) {
					instance.highlight(
						card.getAttribute( 'data-property-id' ),
						true
					);
				}
			} );
		} );
		[ 'mouseout', 'focusout' ].forEach( function ( type ) {
			document.addEventListener( type, function ( event ) {
				const card =
					event.target.closest &&
					event.target.closest( '[data-property-id]' );
				if ( card && instance ) {
					instance.highlight(
						card.getAttribute( 'data-property-id' ),
						false
					);
				}
			} );
		} );

		if ( 'IntersectionObserver' in window ) {
			const observer = new window.IntersectionObserver(
				function ( entries ) {
					if ( entries.some( ( entry ) => entry.isIntersecting ) ) {
						init();
						if ( instance || el.offsetParent !== null ) {
							observer.disconnect();
						}
					}
				},
				{ rootMargin: '200px' }
			);
			observer.observe( el );
		} else {
			init();
		}
	} );
} )();
