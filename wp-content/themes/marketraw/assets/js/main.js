/* MarketRaw — Editorial Motion Suite */
( function () {
	'use strict';

	var isMobile      = window.matchMedia( '(max-width: 768px)' ).matches;
	var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* ═══════════════════════════════════════ 1 · HERO LINE REVEAL */
	/* Las líneas del titular entran con máscara cuando el DOM está listo. */
	requestAnimationFrame( function () {
		requestAnimationFrame( function () {
			document.body.classList.add( 'is-ready' );
		} );
	} );

	/* ═══════════════════════════════════════ 2 · SCROLL PROGRESS */
	var progressBar = document.getElementById( 'scroll-progress' );
	if ( progressBar ) {
		window.addEventListener( 'scroll', function () {
			var scrolled = document.documentElement.scrollTop;
			var total    = document.documentElement.scrollHeight - document.documentElement.clientHeight;
			progressBar.style.width = ( ( scrolled / total ) * 100 ) + '%';
		}, { passive: true } );
	}

	/* ═══════════════════════════════════════ 3 · NAV SCROLL + MOBILE */
	var header    = document.getElementById( 'site-header' );
	var navToggle = document.getElementById( 'nav-toggle' );
	var siteNav   = document.getElementById( 'site-nav' );

	if ( header ) {
		window.addEventListener( 'scroll', function () {
			header.classList.toggle( 'scrolled', window.scrollY > 50 );
		}, { passive: true } );
	}
	if ( navToggle && siteNav ) {
		navToggle.addEventListener( 'click', function () {
			var expanded = navToggle.getAttribute( 'aria-expanded' ) === 'true';
			navToggle.setAttribute( 'aria-expanded', String( ! expanded ) );
			navToggle.classList.toggle( 'is-active' );
			siteNav.classList.toggle( 'is-open' );
			document.body.style.overflow = expanded ? '' : 'hidden';
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( siteNav.classList.contains( 'is-open' )
			     && ! siteNav.contains( e.target )
			     && ! navToggle.contains( e.target ) ) {
				navToggle.setAttribute( 'aria-expanded', 'false' );
				navToggle.classList.remove( 'is-active' );
				siteNav.classList.remove( 'is-open' );
				document.body.style.overflow = '';
			}
		} );
	}

	/* ═══════════════════════════════════════ 4 · COUNTER ANIMATION */
	function animateCount( el ) {
		var target  = parseInt( el.dataset.count, 10 );
		var suffix  = el.dataset.suffix  || '';
		var prefix  = el.dataset.prefix  !== undefined ? el.dataset.prefix : '';
		var DURATION = 1600;
		var startTime = null;

		if ( reducedMotion ) {
			el.textContent = prefix + target + suffix;
			return;
		}

		function easeOut( t ) { return 1 - Math.pow( 1 - t, 3 ); }

		function step( timestamp ) {
			if ( ! startTime ) startTime = timestamp;
			var progress = Math.min( ( timestamp - startTime ) / DURATION, 1 );
			var current  = Math.round( easeOut( progress ) * target );
			el.textContent = prefix + current + suffix;
			if ( progress < 1 ) requestAnimationFrame( step );
		}

		requestAnimationFrame( step );
	}

	/* ═══════════════════════════════════════ 5 · SPLIT TEXT REVEAL */
	function initSplitText() {
		document.querySelectorAll( '[data-split]' ).forEach( function ( el ) {
			var text  = el.textContent.trim();
			var words = text.split( ' ' );
			el.innerHTML = words.map( function ( word, i ) {
				return '<span class="split-word" style="transition-delay:' + ( i * 0.045 ) + 's">' + word + ' </span>';
			} ).join( '' );
		} );
	}
	if ( ! reducedMotion ) initSplitText();

	/* ═══════════════════════════════════════ 6 · INTERSECTION OBSERVER */
	if ( 'IntersectionObserver' in window && ! reducedMotion ) {

		/* Reveal general */
		var revealObs = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					revealObs.unobserve( entry.target );
				}
			} );
		}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' } );

		document.querySelectorAll( '.reveal' ).forEach( function ( el ) {
			revealObs.observe( el );
		} );

		/* Split text */
		var splitObs = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.querySelectorAll( '.split-word' ).forEach( function ( word ) {
						word.classList.add( 'is-visible' );
					} );
					splitObs.unobserve( entry.target );
				}
			} );
		}, { threshold: 0.3 } );

		document.querySelectorAll( '[data-split]' ).forEach( function ( el ) {
			splitObs.observe( el );
		} );

		/* Counters */
		var counterObs = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					animateCount( entry.target );
					counterObs.unobserve( entry.target );
				}
			} );
		}, { threshold: 0.5 } );

		document.querySelectorAll( '[data-count]' ).forEach( function ( el ) {
			counterObs.observe( el );
		} );

	} else {
		/* Fallback / reduced motion: mostrar todo */
		document.querySelectorAll( '.reveal' ).forEach( function ( el ) { el.classList.add( 'is-visible' ); } );
		document.querySelectorAll( '.split-word' ).forEach( function ( el ) { el.classList.add( 'is-visible' ); } );
		document.querySelectorAll( '[data-count]' ).forEach( function ( el ) {
			var prefix = el.dataset.prefix !== undefined ? el.dataset.prefix : '';
			el.textContent = prefix + el.dataset.count + ( el.dataset.suffix || '' );
		} );
	}

	/* ═══════════════════════════════════════ 7 · HERO WORD CYCLE */
	var cycleEl = document.getElementById( 'hero-cycle-word' );
	if ( cycleEl && ! reducedMotion ) {
		var words   = [ 'local', 'auténtico', 'granadino', 'único', 'nuestro' ];
		var current = 0;

		setInterval( function () {
			cycleEl.classList.add( 'is-leaving' );

			setTimeout( function () {
				current = ( current + 1 ) % words.length;
				cycleEl.textContent = words[ current ];
				cycleEl.classList.remove( 'is-leaving' );
				cycleEl.classList.add( 'is-entering' );

				requestAnimationFrame( function () {
					requestAnimationFrame( function () {
						cycleEl.classList.remove( 'is-entering' );
					} );
				} );
			}, 380 );
		}, 3000 );
	}

	/* ═══════════════════════════════════════ 8 · HERO PLATES — PARALLAX SUTIL */
	/* Las láminas se separan ligeramente al hacer scroll, según su profundidad. */
	var heroVisual = document.getElementById( 'hero-visual' );
	if ( heroVisual && ! isMobile && ! reducedMotion ) {
		var plates  = heroVisual.querySelectorAll( '.hero-plate[data-depth]' );
		var ticking = false;

		window.addEventListener( 'scroll', function () {
			if ( ticking ) return;
			ticking = true;
			requestAnimationFrame( function () {
				var y = window.scrollY;
				if ( y < 950 ) {
					plates.forEach( function ( plate ) {
						var depth = parseFloat( plate.dataset.depth ) || 0.03;
						plate.style.marginTop = ( -y * depth * 4 ) + 'px';
					} );
				}
				ticking = false;
			} );
		}, { passive: true } );
	}

} )();
