/**
 * Memberful paywall banner - measure-based full-bleed.
 *
 * Pure CSS can't make a banner span the visible viewport when the post column
 * is offset by a sidebar (classic themes with non-centered content). This
 * script measures the banner's parent against documentElement.clientWidth and
 * applies inline margin/width so the banner reaches both viewport edges
 * exactly. Runs synchronously from a footer enqueue so first paint is correct
 * (no flash of off-center content).
 */
( function () {
	'use strict';

	function measure( banner ) {
		var parent = banner.parentElement;
		if ( ! parent ) {
			return;
		}

		// Reset any previous measurement so parent rect reflects natural flow.
		banner.style.marginInlineStart = '';
		banner.style.marginInlineEnd = '';
		banner.style.width = '';

		var viewportWidth = document.documentElement.clientWidth;
		var parentRect = parent.getBoundingClientRect();

		var startMargin = -parentRect.left;
		var endMargin = parentRect.right - viewportWidth;

		banner.style.marginInlineStart = startMargin + 'px';
		banner.style.marginInlineEnd = endMargin + 'px';
		banner.style.width = viewportWidth + 'px';
	}

	function init() {
		var banners = document.querySelectorAll( '.memberful-paywall--banner' );
		if ( ! banners.length ) {
			return;
		}

		banners.forEach( function ( banner ) {
			measure( banner );

			var pending = null;
			var schedule = function () {
				if ( pending !== null ) {
					return;
				}
				pending = window.requestAnimationFrame( function () {
					pending = null;
					measure( banner );
				} );
			};

			window.addEventListener( 'resize', schedule, { passive: true } );

			if ( typeof ResizeObserver !== 'undefined' && banner.parentElement ) {
				new ResizeObserver( schedule ).observe( banner.parentElement );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();