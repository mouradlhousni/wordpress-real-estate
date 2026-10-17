/**
 * Accessible lightbox for the property gallery.
 *
 * Built on the native <dialog> element: modal focus containment, Escape to close and
 * an inert page come for free. Adds arrow-key navigation, a live photo counter and
 * focus restoration to the element that opened it. Without JavaScript, thumbnails
 * simply link to the full-size images.
 */
( function () {
	'use strict';

	const i18n = window.keystoneGallery || {};
	const gallery = document.querySelector( '[data-gallery]' );

	if ( ! gallery || typeof window.HTMLDialogElement !== 'function' ) {
		return;
	}

	const dataNode = gallery.querySelector( '[data-gallery-data]' );
	const photos = dataNode ? JSON.parse( dataNode.textContent || '[]' ) : [];

	if ( ! photos.length ) {
		return;
	}

	const icon = function ( path ) {
		return (
			'<svg class="icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
			path +
			'</svg>'
		);
	};

	const dialog = document.createElement( 'dialog' );
	dialog.className = 'lightbox';
	dialog.setAttribute( 'aria-label', i18n.dialog || 'Photo gallery' );
	dialog.innerHTML =
		'<div class="lightbox__inner">' +
		'<div class="lightbox__bar"><p class="lightbox__counter" aria-live="polite"></p>' +
		'<button type="button" class="lightbox__button" data-action="close">' +
		icon( '<path d="M6 6l12 12M18 6 6 18"/>' ) +
		'<span class="screen-reader-text"></span></button></div>' +
		'<div class="lightbox__stage"><img alt="" decoding="async"></div>' +
		'<div class="lightbox__nav">' +
		'<button type="button" class="lightbox__button lightbox__button--prev" data-action="prev">' +
		icon( '<path d="m9 6 6 6-6 6"/>' ) +
		'<span class="screen-reader-text"></span></button>' +
		'<button type="button" class="lightbox__button" data-action="next">' +
		icon( '<path d="m9 6 6 6-6 6"/>' ) +
		'<span class="screen-reader-text"></span></button>' +
		'</div></div>';
	document.body.appendChild( dialog );

	dialog.querySelector(
		'[data-action="close"] .screen-reader-text'
	).textContent = i18n.close || 'Close';
	dialog.querySelector(
		'[data-action="prev"] .screen-reader-text'
	).textContent = i18n.previous || 'Previous';
	dialog.querySelector(
		'[data-action="next"] .screen-reader-text'
	).textContent = i18n.next || 'Next';

	const img = dialog.querySelector( 'img' );
	const counter = dialog.querySelector( '.lightbox__counter' );
	const single = photos.length < 2;
	let index = 0;
	let opener = null;

	dialog.querySelector( '.lightbox__nav' ).hidden = single;

	function show( next ) {
		index = ( next + photos.length ) % photos.length;
		const photo = photos[ index ];

		img.src = photo.src;
		img.srcset = photo.srcset || '';
		img.sizes = '100vw';
		img.width = photo.width;
		img.height = photo.height;
		img.alt = photo.alt || '';
		counter.textContent = ( i18n.counter || 'Photo %1$d of %2$d' )
			.replace( '%1$d', index + 1 )
			.replace( '%2$d', photos.length );

		// Warm the cache for the next photo.
		const preload = new window.Image();
		preload.src = photos[ ( index + 1 ) % photos.length ].src;
	}

	function open( start, trigger ) {
		opener = trigger || dialog.ownerDocument.activeElement;
		show( start );
		dialog.showModal();
		document.documentElement.style.overflow = 'hidden';
		dialog.querySelector( '[data-action="close"]' ).focus();
	}

	dialog.addEventListener( 'close', function () {
		document.documentElement.style.overflow = '';
		if ( opener && opener.focus ) {
			opener.focus();
		}
	} );

	dialog.addEventListener( 'click', function ( event ) {
		const button = event.target.closest( '[data-action]' );

		if ( button ) {
			const action = button.getAttribute( 'data-action' );
			if ( action === 'close' ) {
				dialog.close();
			} else {
				show( index + ( action === 'next' ? 1 : -1 ) );
			}
			return;
		}

		// Click on the dark backdrop area (not the photo) closes.
		if (
			event.target === dialog ||
			event.target.classList.contains( 'lightbox__stage' )
		) {
			dialog.close();
		}
	} );

	dialog.addEventListener( 'keydown', function ( event ) {
		if ( single ) {
			return;
		}
		if ( event.key === 'ArrowRight' ) {
			event.preventDefault();
			show( index + 1 );
		} else if ( event.key === 'ArrowLeft' ) {
			event.preventDefault();
			show( index - 1 );
		}
	} );

	// Simple swipe support.
	let touchX = null;
	dialog.addEventListener(
		'touchstart',
		function ( event ) {
			touchX = event.touches[ 0 ].clientX;
		},
		{ passive: true }
	);
	dialog.addEventListener( 'touchend', function ( event ) {
		if ( touchX === null || single ) {
			return;
		}
		const delta = event.changedTouches[ 0 ].clientX - touchX;
		if ( Math.abs( delta ) > 50 ) {
			show( index + ( delta < 0 ? 1 : -1 ) );
		}
		touchX = null;
	} );

	gallery.addEventListener( 'click', function ( event ) {
		const link = event.target.closest( '[data-gallery-index]' );
		if ( link ) {
			event.preventDefault();
			open(
				parseInt( link.getAttribute( 'data-gallery-index' ), 10 ) || 0,
				link
			);
		}
	} );

	const all = gallery.querySelector( '[data-gallery-open]' );
	if ( all ) {
		all.hidden = false;
		all.addEventListener( 'click', function () {
			open( 0, all );
		} );
	}
} )();
