// ESPAM PLUS — small front-end behaviours (no dependencies):
// header state, mobile menu, section reveal.
( function () {
	var body = document.body;
	var reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	// Compact header after scrolling.
	var ticking = false;
	function onScroll() {
		ticking = false;
		body.classList.toggle( 'is-scrolled', window.scrollY > 24 );
	}
	window.addEventListener( 'scroll', function () {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( onScroll );
		}
	}, { passive: true } );
	onScroll();

	// Mobile menu.
	var nav = document.querySelector( '.ep-nav--main' );
	var toggle = nav && nav.querySelector( '.ep-nav__toggle' );
	if ( toggle ) {
		var setMenu = function ( open ) {
			nav.classList.toggle( 'is-open', open );
			body.classList.toggle( 'ep-menu-open', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		};
		toggle.addEventListener( 'click', function () {
			setMenu( ! nav.classList.contains( 'is-open' ) );
		} );
		nav.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '.ep-nav__panel a' ) ) {
				setMenu( false );
			}
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && nav.classList.contains( 'is-open' ) ) {
				setMenu( false );
				toggle.focus();
			}
		} );
	}

	// Pause the marquee while it is off screen (no animation work for nothing).
	var marquee = document.querySelector( '.ep-marquee' );
	if ( marquee && 'IntersectionObserver' in window ) {
		new IntersectionObserver( function ( entries ) {
			marquee.classList.toggle( 'is-offscreen', ! entries[ 0 ].isIntersecting );
		} ).observe( marquee );
	}

	// Supply network: stop its looping animation (SVG + CSS) while off screen.
	var net = document.querySelector( '.ep-net' );
	if ( net && 'IntersectionObserver' in window ) {
		var netSvg = net.querySelector( 'svg' );
		new IntersectionObserver( function ( entries ) {
			var visible = entries[ 0 ].isIntersecting;
			net.classList.toggle( 'is-paused', ! visible );
			if ( netSvg && netSvg.pauseAnimations ) {
				visible ? netSvg.unpauseAnimations() : netSvg.pauseAnimations();
			}
		} ).observe( net );
	}

	// Stagger index for category tiles.
	document.querySelectorAll( '.ep-cats--grid li' ).forEach( function ( li, i ) {
		li.style.setProperty( '--i', i );
	} );

	// Reveal sections once as they enter the viewport.
	var sections = document.querySelectorAll( '.ep-reveal' );
	if ( reduce || ! ( 'IntersectionObserver' in window ) ) {
		sections.forEach( function ( el ) { el.classList.add( 'is-in' ); } );
	} else {
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-in' );
					io.unobserve( entry.target );
				}
			} );
		}, { rootMargin: '0px 0px -10% 0px', threshold: 0.05 } );
		sections.forEach( function ( el ) { io.observe( el ); } );
	}
}() );
