$(document).ready(function() {
	'use strict';

	/**
	 * Ratings
	 */
	if ($('.rating-item').length !== 0) {
		$('.rating-item').each(function() {
			$(this).raty({
		        starType: 'i',
		        starOn: 'fa fa-star',
		        starHalf: 'fa fa-star-half-o',
		        starOff: 'fa fa-star-o'
			});
		});
	}

    /**
     * Hero particles
     */
    var el = $('#hero-particles');
    if (el.length) {
        particlesJS.load('hero-particles', '/assets/particles.json', function() {});
    }

	/**
	 * Hero unit animation
	 */
	$('body').addClass('loaded');

	/**
	 * Toggle navigation
	 */
	$('.navigation-toggle').on('click', function(e) {
		e.preventDefault();

		$('body').toggleClass('navigation-open');
	});

	/**
	 * Cart Forms
	 */

	$('form.input-group').submit(function() {
		$(this).ajaxSubmit({
			target: '#cart-results',
			url: 'https://rdcable.ru/cart'
		});
		return false;
	});

	$('#filter').ajaxForm({
	  target: '#header-results',
	  url: 'https://rdcable.ru/search/results2'
	});

	$('#keywords').on('keyup', function() {
	  $( "#filter" ).submit();
		$('#header-results').show();
	});

	$('#header-search').click(function() {
	  $('#filter').ajaxFormUnbind();
	  $('#filter').submit();
	});

	$(".search-toggle").click(function(){
		$('.app-search').show();
		$('.app-search input').focus();
	});

	$(".srh-btn").click(function(){
		$('.app-search').toggle(200);
		$('#header-results').toggle(200);
	});

	/**
	 * Listing detail toolbar
	 */
	if ($('.listing-toolbar')) {
		$('.listing-detail-section').each(function() {
			var title = $(this).data('title');
			var id = $(this).attr('id');
			$('.listing-toolbar .nav').append('<li class="nav-item"><a href="#' + id + '" class="nav-link">' + title + '</a></li>')
		});

		$('body').scrollspy({target: '.listing-toolbar', offset: 60})

        $('.listing-toolbar .nav-link').on('click', function(e) {
            e.preventDefault();
            var id = $(this).attr('href');
            $.scrollTo(id, 1000, {axis: 'y', offset: -60});
        });
	}

    /**
     * Slider
     */
    var sliders = document.getElementsByClassName('price-slider');
	for ( var i = 0; i < sliders.length; i++ ) {
		var slider = sliders[i];
		if (slider) {
			noUiSlider.create(slider, {
				start: [200, 400],
				connect: true,
				tooltips: [wNumb({ prefix: '$', decimals: 0, thousand: '.' }), wNumb({ prefix: '$', decimals: 0, thousand: '.' })],
				step: 10,
				range: {
					'min': 0,
					'max': 600
				}
			});
		}
	}

    /**
     * Chart
     */
	if ($('.chart').length) {
        new Chartist.Line('.chart', {
            labels: [1, 2, 3, 4, 5, 6, 7, 8],
            series: [
                [0.2, 1.2, 1.0, 1.5, 0.5, 1.2, 2.0, 1.7, 2.5, 0.5, 1.2, 2.2, 1.9, 1.3, 1.1, 0.9, 0.5, 1.2, 1.7],
                [0.5, 1.0, 2.0, 1.0, 2.5, 0.5, 2.5, 1.3, 1.0, 1.8, 1.0, 1.5, 1.0, 1.4, 1.6, 2.4, 1.3, 1.8, 1.2],
            ]
        }, {
            low: 0,
            showArea: true,
            showLine: false,
            showPoint: false,
            fullWidth: true,
            axisX: {
                showLabel: false,
                showGrid: false
            }
        });
    }

    /**
     * Custom radio & checkbox
     */
    $('input[type=checkbox]').wrap('<div class="checkbox-wrapper"/>');
    $('.checkbox-wrapper').append('<span class="indicator"></span>');

    $('input[type=radio]').wrap('<div class="radio-wrapper"/>');
    $('.radio-wrapper').append('<span class="indicator"></span>');

	/**
	 * SVG
	 */
	$('.svg').inlineSVG();

	/**
	 * Scrollbar
	 */
	$('.map-results-list, .map-results-detail, .hero-promo-items-wrapper').TrackpadScrollEmulator();

	/**
	 * Side
	 */
	$('.header-toggle, .side-overlay').on('click', function() {
		$('body').toggleClass('side-open');
	});

	$('.admin-header-sidebar-toggle').on('click', function() {
		$('body').toggleClass('admin-sidebar-minimal');
	});






	/**
	 * Map list
	 */
	$('.map-results-content.clickable .listing-row-medium').on('click', function(e) {
		if ($(this).hasClass('active')) {
			$(this).removeClass('active');
			$('.map-results').removeClass('expanded');
		} else {
			var el = $(this);
			el.parent().find('.listing-row-medium').removeClass('active');
			el.addClass('active');

			if ($('.map-results').hasClass('expanded')) {
				$('.map-results').removeClass('expanded');
				setTimeout(function() {
					$('.map-results').addClass('expanded');
				}, 600);
			} else {
				$('.map-results').addClass('expanded');
			}
		}
	});

	/**
	 * Map results toggle
	 */
	$('.map-results-toggle').on('click', function() {
		if ($('.map-results').hasClass('expanded')) {
			$('.map-results').removeClass('expanded').find('.listing-row-medium').removeClass('active');
		} else {
			if ($('.map-results').hasClass('compressed')) {
				$('.map-results').removeClass('compressed');
			} else {
				$('.map-results').addClass('compressed');
			}
		};
	});
});
