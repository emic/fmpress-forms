/**
 * Event is fired when the whole page has loaded.
 */
window.addEventListener( 'load', function() {
	/**
	 * Toggle display of database user names.
	 *
	 * @param {string} driverId
	 */
	const showHideDatabaseUsername = ( driverId ) => {
		const selector = 'label[for="databaseUsername"]';
		const el = document.querySelector( selector );
		if ( null === el ) {
			return;
		}

		if ( '1' === driverId ) {
			// FilMaker Server.
			el.parentNode.style.display = 'block';
		} else if ( '2' === driverId ) {
			// FilMaker Cloud.
			el.parentNode.style.display = 'none';
		}
	};

	/**
	 * Toggle password labels.
	 *
	 * @param {string} driverId
	 */
	const setLabelForSetDatabasePassword = ( driverId ) => {
		const selector = 'label[for="databasePassword"]';
		const el = document.querySelector( selector );
		if ( null === el ) {
			return;
		}

		if ( '1' === driverId ) {
			el.textContent = el.dataset.labelforserver;
		} else if ( '2' === driverId ) {
			el.textContent = el.dataset.labelforcloud;
		}
	};

	/**
	 * Toggle button labels.
	 *
	 * @param {string} driverId
	 */
	const setButtonForDatabasePassword = ( driverId ) => {
		const selector = 'setDatabasePassword';
		const el = document.getElementById( selector );
		if ( null === el ) {
			return;
		}

		if ( '1' === driverId ) {
			el.textContent = el.dataset.labelforserver;
		} else if ( '2' === driverId ) {
			el.textContent = el.dataset.labelforcloud;
		}
	};

	/**
	 * Run when driver is changed.
	 */
	const changeDriver = () => {
		const idText = 'driver';
		const el = document.getElementById( idText );

		showHideDatabaseUsername( el.value );
		setLabelForSetDatabasePassword( el.value );
		setButtonForDatabasePassword( el.value );
	};

	/**
	 * Display password entry area.
	 */
	const showDataSourcePasswordInput = () => {
		const selector = 'div[data-aria="setDatabasePassword"]';
		const el = document.querySelector( selector );
		if ( null !== el ) {
			el.style.display = 'block';
			hideSetPasswordButton();
		}
	};

	/**
	 * Hide password entry area.
	 */
	const cancelDatasourcePasswordInput = () => {
		const selector = 'div[data-aria="setDatabasePassword"]';
		const el = document.querySelector( selector );
		if ( null !== el ) {
			el.style.display = 'none';
			showSetPasswordButton();
		}
	};

	/**
	 * Show button.
	 */
	const showSetPasswordButton = () => {
		const idText = 'setDatabasePassword';
		const el = document.getElementById( idText );
		if ( null !== el ) {
			el.style.display = 'block';
		}
	};

	/**
	 * Hide button.
	 */
	const hideSetPasswordButton = () => {
		const idText = 'setDatabasePassword';
		const el = document.getElementById( idText );
		if ( null !== el ) {
			el.style.display = 'none';
		}
	};

	/**
	 * Toggle the attributes of the password entry field.
	 */
	const switchDataSourceInputType = () => {
		const idText = 'databasePassword';
		const el = document.getElementById( idText );
		if ( el.type === 'text' ) {
			el.type = 'password';
			switchDataSourceButtonText( 'show' );
		} else {
			el.type = 'text';
			switchDataSourceButtonText( 'hide' );
		}
	};

	/**
	 * Toggle button labels and icons.
	 *
	 * @param {string} action
	 */
	const switchDataSourceButtonText = ( action ) => {
		// Text.
		const buttonText = document.querySelector( '#hideDatabasePassword .text' );
		// Icon.
		const buttonIcon = document.querySelector( '#hideDatabasePassword .dashicons' );
		// Toggle.
		if ( action === 'show' ) {
			buttonText.textContent = 'Show';
			buttonIcon.className = 'dashicons dashicons-visibility';
		} else {
			buttonText.textContent = 'Hide';
			buttonIcon.className = 'dashicons dashicons-hidden';
		}
	};

	/**
	 * Connection testing of data source.
	 *
	 * @param {Object} e event
	 */
	const connectionTest = ( e ) => {
		e.preventDefault();

		if ( ! isReadyForWpAjax ) {
			return;
		}

		const data = {
			action: 'connection_test',
			wp_post_id: getQueryVar( 'post' ),
			// eslint-disable-next-line no-undef
			fmpress_ajax_nonce: localize.fmpressAjaxNonce,
		};

		// eslint-disable-next-line no-undef
		const xhr = new XMLHttpRequest();
		// eslint-disable-next-line no-undef
		xhr.open( 'POST', ajaxurl );
		const array = [];
		Object.keys( data ).forEach( ( element ) =>
			array.push(
				encodeURIComponent( element ) + '=' + encodeURIComponent( data[ element ] )
			)
		);
		const body = array.join( '&' );
		xhr.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
		xhr.send( body );

		xhr.onload = () => {
			if ( '' !== xhr.response ) {
				showMessage( JSON.parse( xhr.response ) );
			}
		};
		xhr.onerror = () => {
			// eslint-disable-next-line no-console
			console.error( xhr.status );
			// eslint-disable-next-line no-console
			console.error( xhr.response );
		};
	};

	/**
	 * Getting URL parameters.
	 *
	 * @param {string} name
	 * @param {string} url
	 * @return {string} URL argument value
	 */
	const getQueryVar = ( name, url ) => {
		if ( ! url ) {
			url = window.location.href;
		}
		name = name.replace( /[\[\]]/g, '\\$&' );
		const regex = new RegExp( '[?&]' + name + '(=([^&#]*)|&|#|$)' ),
			results = regex.exec( url );
		if ( ! results ) {
			return null;
		}
		if ( ! results[ 2 ] ) {
			return '';
		}
		return decodeURIComponent( results[ 2 ].replace( /\+/g, ' ' ) );
	};

	/**
	 * Show messages.
	 *
	 * @param {string} messages
	 */
	const showMessage = ( messages ) => {
		if ( ! messages ) {
			return;
		}
		let message = '';
		for ( let i = 0; i < messages.length; i++ ) {
			message += messages[ i ] + '\n';
		}
		// eslint-disable-next-line no-alert
		window.alert( message );
	};

	/**
	 * Determine if WordPress Ajax is ready
	 *
	 * @return {boolean} True if WordPress Ajax is ready
	 */
	const isReadyForWpAjax = () => {
		if ( typeof ajaxurl === 'undefined' || typeof localize === 'undefined' ) {
			return false;
		}

		return true;
	};

	/**
	 * Set Events.
	 */
	const setEvents = () => {
		/**
		 * Set event on button to display password entry area.
		 */
		const setEventToSetDatabasePassword = () => {
			const idText = 'setDatabasePassword';
			const el = document.getElementById( idText );
			if ( null !== el ) {
				el.addEventListener( 'click', showDataSourcePasswordInput, false );
			}
		};
		setEventToSetDatabasePassword();

		/**
		 * Set event on button to hide password entry area.
		 */
		const setEventToCancelDatabasePassword = () => {
			const idText = 'cancelDatabasePassword';
			const el = document.getElementById( idText );
			if ( null !== el ) {
				el.addEventListener( 'click', cancelDatasourcePasswordInput, false );
			}
		};
		setEventToCancelDatabasePassword();

		/**
		 * Set event on button to test connection of data source.
		 */
		const setEventToConnectionTest = () => {
			const idText = 'connectionTest';
			const el = document.getElementById( idText );
			if ( null !== el ) {
				el.addEventListener( 'click', connectionTest, false );
			}
		};
		setEventToConnectionTest();

		/**
		 * Set event on button to toggle password display.
		 */
		const setEventToHideDatabasePassword = () => {
			const idText = 'hideDatabasePassword';
			const el = document.getElementById( idText );
			if ( null !== el ) {
				el.addEventListener( 'click', switchDataSourceInputType, false );
			}
		};
		setEventToHideDatabasePassword();

		/**
		 * Set event to be executed when driver is changed.
		 */
		const setEventToChangeDriver = () => {
			const idText = 'driver';
			const el = document.getElementById( idText );
			if ( null !== el ) {
				const driverId = el.value;
				el.addEventListener( 'change', { name: driverId, handleEvent: changeDriver }, false );
			}
		};
		setEventToChangeDriver();
	};

	/**
	 * initialization process.
	 */
	const init = () => {
		// Check submission type
		if ( ! document.body.classList.contains( 'post-type-connect_datasource' ) ) {
			return;
		} else if ( ! document.body.classList.contains( 'post-php' ) && ! document.body.classList.contains( 'post-new-php' ) ) {
			return;
		}

		setEvents();
		changeDriver();
	};
	init();
}, false );
