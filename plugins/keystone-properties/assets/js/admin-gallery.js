/**
 * Property gallery picker: opens the media library, renders thumbnails,
 * supports drag & drop ordering and keeps a hidden input of attachment IDs in sync.
 *
 * @param {Function} $    jQuery.
 * @param {Object}   wp   WordPress globals (wp.media).
 * @param {Object}   i18n Localized strings.
 */
( function ( $, wp, i18n ) {
	'use strict';

	$( function () {
		const $box = $( '[data-keystone-gallery]' );

		if ( ! $box.length || ! wp || ! wp.media ) {
			return;
		}

		const $input = $box.find( '[data-keystone-gallery-input]' );
		const $list = $box.find( '[data-keystone-gallery-list]' );
		let frame;

		function sync() {
			const ids = $list
				.children()
				.map( function () {
					return $( this ).data( 'id' );
				} )
				.get();
			$input.val( ids.join( ',' ) );
		}

		function addItem( attachment ) {
			const sizes = attachment.sizes || {};
			const thumb = sizes.thumbnail || sizes.full;

			if ( ! thumb ) {
				return;
			}

			const $item = $( '<li class="keystone-gallery__item"></li>' ).attr(
				'data-id',
				attachment.id
			);
			$( '<img alt="">' ).attr( 'src', thumb.url ).appendTo( $item );
			$(
				'<button type="button" class="keystone-gallery__remove">&times;</button>'
			)
				.attr( 'aria-label', i18n.remove )
				.appendTo( $item );
			$list.append( $item );
		}

		$list.sortable( { update: sync, tolerance: 'pointer' } );

		$list.on( 'click', '.keystone-gallery__remove', function () {
			$( this ).closest( 'li' ).remove();
			sync();
		} );

		$box.on( 'click', '[data-keystone-gallery-add]', function ( event ) {
			event.preventDefault();

			if ( ! frame ) {
				frame = wp.media( {
					title: i18n.title,
					button: { text: i18n.button },
					library: { type: 'image' },
					multiple: 'add',
				} );

				frame.on( 'select', function () {
					const existing = $input.val().split( ',' ).map( Number );

					frame
						.state()
						.get( 'selection' )
						.each( function ( model ) {
							const attachment = model.toJSON();
							if ( existing.indexOf( attachment.id ) === -1 ) {
								addItem( attachment );
							}
						} );
					sync();
				} );
			}

			frame.open();
		} );
	} );
} )( window.jQuery, window.wp, window.keystoneGallery || {} );
