/**
 * "Contact the agent" form: client-side validation and REST submission without reload.
 * Without JavaScript the form still works through admin-post.php.
 */
( function () {
	'use strict';

	const config = window.keystoneLeadForm || {};

	function setError( form, name, message ) {
		const field = form.elements[ name ];
		const slot = form.querySelector( '[data-error-for="' + name + '"]' );

		if ( field && field.setAttribute ) {
			if ( message ) {
				field.setAttribute( 'aria-invalid', 'true' );
			} else {
				field.removeAttribute( 'aria-invalid' );
			}
		}

		if ( slot ) {
			slot.textContent = message || '';
		}
	}

	function clientValidate( form ) {
		let firstInvalid = null;

		[ 'name', 'email', 'phone', 'message' ].forEach( function ( name ) {
			const field = form.elements[ name ];
			if ( ! field ) {
				return;
			}
			const valid = field.checkValidity();
			setError( form, name, valid ? '' : field.validationMessage );
			if ( ! valid && ! firstInvalid ) {
				firstInvalid = field;
			}
		} );

		if ( firstInvalid ) {
			firstInvalid.focus();
			return false;
		}

		return true;
	}

	function setStatus( form, message, type ) {
		const status = form.querySelector( '[data-lead-status]' );
		if ( ! status ) {
			return;
		}
		status.textContent = message;
		status.className =
			'lead-form__status is-visible' + ( type ? ' is-' + type : '' );
	}

	function onSubmit( event ) {
		const form = event.currentTarget;

		if ( ! window.fetch || ! config.endpoint ) {
			return; // Let the browser post the form normally.
		}

		event.preventDefault();

		if ( ! clientValidate( form ) ) {
			return;
		}

		const button = form.querySelector( '[type="submit"]' );
		const label = button.textContent;
		button.disabled = true;
		button.textContent = config.sending || label;
		setStatus( form, '', '' );

		const payload = {};
		new window.FormData( form ).forEach( function ( value, key ) {
			payload[ key ] = value;
		} );
		payload.property_id = parseInt( payload.property_id, 10 );
		delete payload.action;

		window
			.fetch( config.endpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': config.restNonce || '',
				},
				credentials: 'same-origin',
				body: JSON.stringify( payload ),
			} )
			.then( function ( response ) {
				return response.json().then( function ( body ) {
					return { ok: response.ok, body };
				} );
			} )
			.then( function ( result ) {
				if ( result.ok ) {
					setStatus(
						form,
						result.body.message,
						result.body.demo ? 'info' : 'success'
					);
					if ( ! result.body.demo ) {
						form.reset();
					}
					return;
				}

				const fields =
					( result.body.data && result.body.data.fields ) || {};
				Object.keys( fields ).forEach( function ( name ) {
					setError( form, name, fields[ name ] );
				} );
				setStatus( form, result.body.message || config.error, 'error' );
			} )
			.catch( function () {
				setStatus( form, config.error, 'error' );
			} )
			.finally( function () {
				button.disabled = false;
				button.textContent = label;
			} );
	}

	document
		.querySelectorAll( '[data-keystone-lead-form]' )
		.forEach( function ( form ) {
			form.addEventListener( 'submit', onSubmit );
			form.addEventListener( 'input', function ( event ) {
				if (
					event.target.name &&
					event.target.getAttribute( 'aria-invalid' )
				) {
					setError( form, event.target.name, '' );
				}
			} );
		} );
} )();
