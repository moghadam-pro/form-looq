/**
 * Form LOOQ — shared admin behaviour.
 *
 * Copy buttons, delete confirmations, and the unsaved-changes guard.
 * No dependencies and no build step.
 */
( function () {
	'use strict';

	var config = window.looqAdmin || {};

	function announce( message ) {
		var notice = document.createElement( 'div' );
		notice.className = 'notice notice-success is-dismissible looq-flash';
		notice.setAttribute( 'role', 'status' );
		notice.innerHTML = '<p></p>';
		notice.querySelector( 'p' ).textContent = message;

		var anchor = document.querySelector( '.looq-header' );
		if ( anchor && anchor.parentNode ) {
			anchor.parentNode.insertBefore( notice, anchor.nextSibling );
			window.setTimeout( function () {
				notice.remove();
			}, 3000 );
		}
	}

	function copyFrom( target ) {
		var field = document.getElementById( target );
		if ( ! field ) {
			return;
		}

		field.focus();
		field.select();

		var done = function () {
			announce( config.copied || 'Copied.' );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( field.value ).then( done, function () {
				announce( config.copyFailed || 'Copy failed.' );
			} );
			return;
		}

		try {
			document.execCommand( 'copy' );
			done();
		} catch ( error ) {
			announce( config.copyFailed || 'Copy failed.' );
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var copyButton = event.target.closest( '[data-looq-copy]' );
		if ( copyButton ) {
			event.preventDefault();
			copyFrom( copyButton.getAttribute( 'data-looq-copy' ) );
			return;
		}

		var confirmLink = event.target.closest( '[data-looq-confirm]' );
		if ( confirmLink && ! window.confirm( confirmLink.getAttribute( 'data-looq-confirm' ) ) ) {
			event.preventDefault();
		}
	} );

	// Warn before navigating away from a builder form with unsaved edits.
	var builderForm = document.getElementById( 'looq-builder-form' );

	if ( builderForm && config.confirmLeave ) {
		var dirty = false;
		var saving = false;

		builderForm.addEventListener( 'input', function () {
			dirty = true;
		} );

		builderForm.addEventListener( 'change', function () {
			dirty = true;
		} );

		builderForm.addEventListener( 'submit', function () {
			saving = true;
		} );

		window.addEventListener( 'beforeunload', function ( event ) {
			if ( ! dirty || saving ) {
				return undefined;
			}

			event.preventDefault();
			// Browsers show their own wording; a non-empty return value is what matters.
			event.returnValue = config.leaveWarning || '';
			return event.returnValue;
		} );
	}
}() );
