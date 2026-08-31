/**
 * MPRO Forms — drag-and-drop form builder.
 *
 * No build step: plain ES2017 that every browser WordPress 6.5 supports can run.
 * The canvas owns an array of field objects; every render rebuilds the list from
 * that array, and the array is serialised into a hidden input on submit.
 */
( function () {
	'use strict';

	var config = window.mproBuilder || {};
	var LABELS = config.labels || {};
	var TYPES = config.types || [];
	var MAX_FIELDS = 50;

	var canvas = document.getElementById( 'mpro-canvas' );
	var palette = document.getElementById( 'mpro-palette' );
	var input = document.getElementById( 'mpro-fields-input' );
	var form = document.getElementById( 'mpro-builder-form' );

	if ( ! canvas || ! input || ! form ) {
		return;
	}

	var fields = parse( input.value );
	var dragIndex = null;

	function parse( value ) {
		try {
			var parsed = JSON.parse( value );
			return Array.isArray( parsed ) ? parsed : [];
		} catch ( error ) {
			return [];
		}
	}

	function typeConfig( type ) {
		for ( var i = 0; i < TYPES.length; i++ ) {
			if ( TYPES[ i ].type === type ) {
				return TYPES[ i ];
			}
		}
		return { type: type, label: type, icon: 'editor-textcolor', hasOptions: false };
	}

	function makeField( type ) {
		var meta = typeConfig( type );
		var field = {
			type: type,
			name: '',
			label: meta.label || LABELS.newFieldLabel,
			required: false,
			options: [],
			help: '',
			placeholder: '',
			width: 'full'
		};

		if ( meta.hasOptions ) {
			field.options = 'scale' === type ? [ '1', '2', '3', '4', '5' ] : [ 'Option 1', 'Option 2' ];
		}

		return field;
	}

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( undefined !== text && null !== text ) {
			node.textContent = text;
		}
		return node;
	}

	function labelled( labelText, control, id ) {
		var wrap = el( 'p', 'mpro-card__control' );
		var label = el( 'label', null, labelText );
		label.setAttribute( 'for', id );
		control.id = id;
		wrap.appendChild( label );
		wrap.appendChild( control );
		return wrap;
	}

	function textInput( value, onChange ) {
		var node = document.createElement( 'input' );
		node.type = 'text';
		node.className = 'widefat';
		node.value = value || '';
		node.addEventListener( 'input', function () {
			onChange( node.value );
		} );
		return node;
	}

	function renderCard( field, index ) {
		var meta = typeConfig( field.type );
		var card = el( 'div', 'mpro-card' );
		card.setAttribute( 'draggable', 'true' );
		card.setAttribute( 'data-index', String( index ) );

		var head = el( 'div', 'mpro-card__head' );
		var handle = el( 'span', 'mpro-card__handle dashicons dashicons-move' );
		handle.setAttribute( 'aria-hidden', 'true' );
		head.appendChild( handle );

		var icon = el( 'span', 'mpro-card__icon dashicons dashicons-' + meta.icon );
		icon.setAttribute( 'aria-hidden', 'true' );
		head.appendChild( icon );

		head.appendChild( el( 'strong', 'mpro-card__type', meta.label ) );
		head.appendChild( el( 'span', 'mpro-card__summary', field.label || '' ) );

		var tools = el( 'span', 'mpro-card__tools' );
		tools.appendChild( toolButton( 'arrow-up-alt2', LABELS.moveUp, function () {
			move( index, index - 1 );
		} ) );
		tools.appendChild( toolButton( 'arrow-down-alt2', LABELS.moveDown, function () {
			move( index, index + 1 );
		} ) );
		tools.appendChild( toolButton( 'trash', LABELS.removeField, function () {
			if ( window.confirm( LABELS.confirmRemove ) ) {
				fields.splice( index, 1 );
				render();
			}
		} ) );
		head.appendChild( tools );
		card.appendChild( head );

		var body = el( 'div', 'mpro-card__body' );
		var prefix = 'mpro-field-' + index + '-';

		body.appendChild( labelled( LABELS.label, textInput( field.label, function ( value ) {
			field.label = value;
			var summary = card.querySelector( '.mpro-card__summary' );
			if ( summary ) {
				summary.textContent = value;
			}
		} ), prefix + 'label' ) );

		if ( 'section' !== field.type ) {
			body.appendChild( labelled( LABELS.name, textInput( field.name, function ( value ) {
				field.name = value;
			} ), prefix + 'name' ) );

			body.appendChild( labelled( LABELS.placeholder, textInput( field.placeholder, function ( value ) {
				field.placeholder = value;
			} ), prefix + 'placeholder' ) );
		}

		body.appendChild( labelled( LABELS.help, textInput( field.help, function ( value ) {
			field.help = value;
		} ), prefix + 'help' ) );

		if ( meta.hasOptions ) {
			var options = document.createElement( 'textarea' );
			options.className = 'widefat';
			options.rows = 4;
			options.value = ( field.options || [] ).join( '\n' );
			options.addEventListener( 'input', function () {
				field.options = options.value.split( '\n' ).map( function ( line ) {
					return line.trim();
				} ).filter( function ( line ) {
					return '' !== line;
				} );
			} );
			body.appendChild( labelled( LABELS.options, options, prefix + 'options' ) );
		}

		if ( 'section' !== field.type ) {
			var flags = el( 'p', 'mpro-card__flags' );

			var requiredLabel = el( 'label' );
			var required = document.createElement( 'input' );
			required.type = 'checkbox';
			required.checked = !! field.required;
			required.addEventListener( 'change', function () {
				field.required = required.checked;
			} );
			requiredLabel.appendChild( required );
			requiredLabel.appendChild( document.createTextNode( ' ' + LABELS.required ) );
			flags.appendChild( requiredLabel );

			var widthLabel = el( 'label', 'mpro-card__width' );
			widthLabel.appendChild( document.createTextNode( LABELS.width + ' ' ) );
			var width = document.createElement( 'select' );
			[ [ 'full', LABELS.widthFull ], [ 'half', LABELS.widthHalf ] ].forEach( function ( pair ) {
				var option = document.createElement( 'option' );
				option.value = pair[ 0 ];
				option.textContent = pair[ 1 ];
				if ( field.width === pair[ 0 ] ) {
					option.selected = true;
				}
				width.appendChild( option );
			} );
			width.addEventListener( 'change', function () {
				field.width = width.value;
			} );
			widthLabel.appendChild( width );
			flags.appendChild( widthLabel );

			body.appendChild( flags );
		}

		card.appendChild( body );

		card.addEventListener( 'dragstart', function ( event ) {
			dragIndex = index;
			card.classList.add( 'is-dragging' );
			event.dataTransfer.effectAllowed = 'move';
			// Firefox requires data to be set for a drag to start.
			event.dataTransfer.setData( 'text/plain', String( index ) );
		} );

		card.addEventListener( 'dragend', function () {
			dragIndex = null;
			card.classList.remove( 'is-dragging' );
			clearDropMarkers();
		} );

		card.addEventListener( 'dragover', function ( event ) {
			event.preventDefault();
			card.classList.add( 'is-drop-target' );
		} );

		card.addEventListener( 'dragleave', function () {
			card.classList.remove( 'is-drop-target' );
		} );

		card.addEventListener( 'drop', function ( event ) {
			event.preventDefault();
			event.stopPropagation();
			card.classList.remove( 'is-drop-target' );

			var newType = event.dataTransfer.getData( 'mpro/type' );
			if ( newType ) {
				insert( makeField( newType ), index );
				return;
			}

			if ( null !== dragIndex ) {
				move( dragIndex, index );
			}
		} );

		return card;
	}

	function toolButton( icon, label, onClick ) {
		var button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'mpro-card__tool';
		button.title = label;
		button.setAttribute( 'aria-label', label );
		button.innerHTML = '<span class="dashicons dashicons-' + icon + '" aria-hidden="true"></span>';
		button.addEventListener( 'click', onClick );
		return button;
	}

	function clearDropMarkers() {
		var targets = canvas.querySelectorAll( '.is-drop-target' );
		for ( var i = 0; i < targets.length; i++ ) {
			targets[ i ].classList.remove( 'is-drop-target' );
		}
	}

	function move( from, to ) {
		if ( from === to || to < 0 || to >= fields.length ) {
			return;
		}
		var moved = fields.splice( from, 1 )[ 0 ];
		fields.splice( to, 0, moved );
		render();
	}

	function insert( field, at ) {
		if ( fields.length >= MAX_FIELDS ) {
			window.alert( LABELS.maxFields );
			return;
		}
		if ( undefined === at || at < 0 || at > fields.length ) {
			at = fields.length;
		}
		fields.splice( at, 0, field );
		render();
	}

	function render() {
		canvas.innerHTML = '';

		if ( ! fields.length ) {
			canvas.appendChild( el( 'p', 'mpro-canvas__empty', LABELS.empty ) );
			sync();
			return;
		}

		fields.forEach( function ( field, index ) {
			canvas.appendChild( renderCard( field, index ) );
		} );

		sync();
	}

	function sync() {
		input.value = JSON.stringify( fields );
	}

	canvas.addEventListener( 'dragover', function ( event ) {
		event.preventDefault();
	} );

	canvas.addEventListener( 'drop', function ( event ) {
		event.preventDefault();
		clearDropMarkers();

		var newType = event.dataTransfer.getData( 'mpro/type' );
		if ( newType ) {
			insert( makeField( newType ) );
			return;
		}

		if ( null !== dragIndex ) {
			move( dragIndex, fields.length - 1 );
		}
	} );

	if ( palette ) {
		palette.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '.mpro-palette__item' );
			if ( button ) {
				insert( makeField( button.getAttribute( 'data-mpro-type' ) ) );
			}
		} );

		palette.addEventListener( 'dragstart', function ( event ) {
			var button = event.target.closest( '.mpro-palette__item' );
			if ( ! button ) {
				return;
			}
			dragIndex = null;
			event.dataTransfer.effectAllowed = 'copy';
			event.dataTransfer.setData( 'mpro/type', button.getAttribute( 'data-mpro-type' ) );
			event.dataTransfer.setData( 'text/plain', button.getAttribute( 'data-mpro-type' ) );
		} );
	}

	form.addEventListener( 'submit', sync );

	render();
}() );
