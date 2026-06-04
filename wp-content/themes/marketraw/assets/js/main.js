/* MarketRaw — JS Animation Suite */
( function () {
	'use strict';

	var isMobile = window.matchMedia( '(max-width: 768px)' ).matches;

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

	/* ═══════════════════════════════════════ 4 · MAGNETIC BUTTONS */
	if ( ! isMobile ) {
		document.querySelectorAll( '[data-magnetic]' ).forEach( function ( btn ) {
			var STRENGTH = 0.38;

			btn.addEventListener( 'mousemove', function ( e ) {
				var rect = btn.getBoundingClientRect();
				var cx   = rect.left + rect.width  / 2;
				var cy   = rect.top  + rect.height / 2;
				var dx   = e.clientX - cx;
				var dy   = e.clientY - cy;
				btn.style.transform = 'translate(' + ( dx * STRENGTH ) + 'px, ' + ( dy * STRENGTH ) + 'px)';
			} );
			btn.addEventListener( 'mouseleave', function () {
				btn.style.transition = 'transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
				btn.style.transform  = 'translate(0, 0)';
				setTimeout( function () { btn.style.transition = ''; }, 500 );
			} );
			btn.addEventListener( 'mouseenter', function () {
				btn.style.transition = 'transform 0.12s ease';
			} );
		} );
	}

	/* ═══════════════════════════════════════ 5 · 3D CARD TILT */
	if ( ! isMobile ) {
		document.querySelectorAll( '[data-tilt]' ).forEach( function ( card ) {
			var MAX_ROT = 9;

			card.addEventListener( 'mouseenter', function () {
				card.style.transition = 'transform 0.12s ease, box-shadow 0.3s ease';
			} );
			card.addEventListener( 'mousemove', function ( e ) {
				var rect  = card.getBoundingClientRect();
				var x     = e.clientX - rect.left;
				var y     = e.clientY - rect.top;
				var cx    = rect.width  / 2;
				var cy    = rect.height / 2;
				var rotY  = ( ( x - cx ) / cx ) * MAX_ROT;
				var rotX  = -( ( y - cy ) / cy ) * MAX_ROT;
				card.style.transform = 'perspective(700px) rotateX(' + rotX + 'deg) rotateY(' + rotY + 'deg) translateZ(8px)';
			} );
			card.addEventListener( 'mouseleave', function () {
				card.style.transition = 'transform 0.55s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease';
				card.style.transform  = 'perspective(700px) rotateX(0deg) rotateY(0deg) translateZ(0px)';
			} );
		} );
	}

	/* ═══════════════════════════════════════ 6 · COUNTER ANIMATION */
	function animateCount( el ) {
		var target  = parseInt( el.dataset.count, 10 );
		var suffix  = el.dataset.suffix  || '';
		var prefix  = el.dataset.prefix  !== undefined ? el.dataset.prefix : '';
		var DURATION = 1600;
		var startTime = null;

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

	/* ═══════════════════════════════════════ 7 · SPLIT TEXT REVEAL */
	function initSplitText() {
		document.querySelectorAll( '[data-split]' ).forEach( function ( el ) {
			var text  = el.textContent.trim();
			var words = text.split( ' ' );
			el.innerHTML = words.map( function ( word, i ) {
				return '<span class="split-word" style="transition-delay:' + ( i * 0.055 ) + 's">' + word + ' </span>';
			} ).join( '' );
		} );
	}
	initSplitText();

	/* ═══════════════════════════════════════ 8 · INTERSECTION OBSERVER */
	if ( 'IntersectionObserver' in window ) {

		/* General reveal */
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

		/* Split text reveal */
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

		/* Counter trigger */
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
		/* Fallback: show everything */
		document.querySelectorAll( '.reveal' ).forEach( function ( el ) { el.classList.add( 'is-visible' ); } );
		document.querySelectorAll( '.split-word' ).forEach( function ( el ) { el.classList.add( 'is-visible' ); } );
	}

	/* ═══════════════════════════════════════ 9 · HERO WORD CYCLE */
	var cycleEl = document.getElementById( 'hero-cycle-word' );
	if ( cycleEl ) {
		var words   = [ 'local', 'auténtico', 'granadino', 'único', 'nuestro' ];
		var current = 0;

		setInterval( function () {
			/* Leave animation */
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
			}, 320 );
		}, 2600 );
	}

	/* ═══════════════════════════════════════ 10 · HERO BLOB MOUSE PARALLAX */
	if ( ! isMobile ) {
		var blobPurple = document.getElementById( 'blob-purple' );
		var blobOrange = document.getElementById( 'blob-orange' );
		var blobCyan   = document.getElementById( 'blob-cyan' );

		var blobMouseX = 0, blobMouseY = 0;
		var blobCurrX  = 0, blobCurrY  = 0;

		document.addEventListener( 'mousemove', function ( e ) {
			blobMouseX = ( e.clientX / window.innerWidth  - 0.5 ) * 60;
			blobMouseY = ( e.clientY / window.innerHeight - 0.5 ) * 60;
		} );

		( function animBlobs() {
			blobCurrX += ( blobMouseX - blobCurrX ) * 0.04;
			blobCurrY += ( blobMouseY - blobCurrY ) * 0.04;

			if ( blobPurple ) blobPurple.style.transform = 'translate(' + ( blobCurrX * 0.6 ) + 'px,' + ( blobCurrY * 0.6 ) + 'px)';
			if ( blobOrange ) blobOrange.style.transform = 'translate(' + ( -blobCurrX * 0.4 ) + 'px,' + ( -blobCurrY * 0.4 ) + 'px)';
			if ( blobCyan   ) blobCyan.style.transform   = 'translate(' + ( blobCurrX * 0.3 ) + 'px,' + ( blobCurrY * 0.8 ) + 'px)';

			requestAnimationFrame( animBlobs );
		} )();
	}

	/* ═══════════════════════════════════════ 11 · HERO CARDS MOUSE PARALLAX */
	if ( ! isMobile ) {
		var heroVisual = document.getElementById( 'hero-visual' );
		if ( heroVisual ) {
			var heroCards = heroVisual.querySelectorAll( '.hero-card[data-depth]' );

			document.addEventListener( 'mousemove', function ( e ) {
				var mx = ( e.clientX / window.innerWidth  - 0.5 );
				var my = ( e.clientY / window.innerHeight - 0.5 );

				heroCards.forEach( function ( card ) {
					var depth = parseFloat( card.dataset.depth ) || 0.03;
					var tx    = mx * depth * 200;
					var ty    = my * depth * 200;
					/* Combine with existing float animation offset via CSS var */
					card.style.marginLeft = tx + 'px';
					card.style.marginTop  = ty + 'px';
				} );
			} );
		}
	}

	/* ═══════════════════════════════════════ 12 · SCROLL PARALLAX GRID */
	var heroSection = document.getElementById( 'hero' );
	if ( heroSection && ! isMobile ) {
		window.addEventListener( 'scroll', function () {
			var y = window.scrollY;
			if ( y < 900 ) {
				heroSection.style.backgroundPositionY = ( y * 0.15 ) + 'px';
			}
		}, { passive: true } );
	}

} )();
