// Floating contact widget: open/close the card, greeting bubble once per visit.
( function () {
	var fab = document.querySelector( '.ep-fab' );
	if ( ! fab ) {
		return;
	}
	var toggle = fab.querySelector( '.ep-fab__toggle' );
	var bubble = fab.querySelector( '.ep-fab__bubble' );
	var seenKey = 'epFabBubbleSeen';

	function hideBubble() {
		if ( bubble ) {
			bubble.classList.remove( 'is-visible' );
		}
		try { sessionStorage.setItem( seenKey, '1' ); } catch ( e ) {}
	}
	function set( open ) {
		fab.classList.toggle( 'is-open', open );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		if ( open ) {
			hideBubble();
		}
	}

	toggle.addEventListener( 'click', function () {
		set( ! fab.classList.contains( 'is-open' ) );
	} );
	document.addEventListener( 'click', function ( e ) {
		if ( ! fab.contains( e.target ) ) {
			set( false );
		}
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && fab.classList.contains( 'is-open' ) ) {
			set( false );
			toggle.focus();
		}
	} );

	// Greeting bubble: once per browser session, after a short delay, hides itself after a while.
	if ( bubble ) {
		var seen = false;
		try { seen = !! sessionStorage.getItem( seenKey ); } catch ( e ) {}
		bubble.querySelector( '.ep-fab__bubble-close' ).addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			hideBubble();
		} );
		bubble.addEventListener( 'click', function () {
			set( true );
		} );
		if ( ! seen ) {
			bubble.hidden = false;
			setTimeout( function () {
				if ( ! fab.classList.contains( 'is-open' ) ) {
					bubble.classList.add( 'is-visible' );
				}
			}, 4000 );
			setTimeout( hideBubble, 16000 );
		}
	}
}() );
