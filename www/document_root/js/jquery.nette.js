/**
 * AJAX Nette Framwork plugin for jQuery
 *
 * @copyright  Copyright (c) 2009, 2010 Jan Marek
 * @copyright  Copyright (c) 2009, 2010 David Grudl
 * @license    MIT
 * @link       http://nettephp.com/cs/extras/jquery-ajax
 */

/*
if (typeof jQuery != 'function') {
	alert('jQuery was not loaded');
}
*/

(function($) {

	$.nette = {
		success: function(payload)
		{
			// redirect
			if (payload.redirect) {
				window.location.href = payload.redirect;
				return;
			}

			// state
			if (payload.state) {
				$.nette.state = payload.state;
			}

			// snippets
			if (payload.snippets) {
				for (var i in payload.snippets) {
					if( i == 'snippet--flashes' )
					{
					}
					$.nette.updateSnippet(i, payload.snippets[i]);
				}
				$('.pnotify').each( function() {
					$.pnotify({
						pnotify_title: "Upozornění",
						pnotify_text: $(this).html(),
						pnotify_nonblock: true,
						pnotify_mouse_reset: false,
						pnotify_addclas: $(this).attr('class')
					});
				});
			}

			if(payload.form)
			{
				$('form#'+payload.form).find(':input').first().select();
			}
			$.dependentselectbox.hideSubmits();
			$.fancybox.hideActivity();
		},

		updateSnippet: function(id, html)
		{
			$('#' + id).fadeOut('slow', function(){
				$('#' + id).html(html).fadeIn('slow');
			});
		},

		// create animated spinner
		createSpinner: function(id)
		{
			return this.spinner = $('<div><a href="">provést ručně</a></div>').attr('id', id ? id : 'ajax-spinner').ajaxStart(function() {
				//$(this).show();

			}).ajaxStop(function() {
				$(this).hide().css({
					position: 'fixed',
					left: '50%',
					top: '50%'
				});

			}).appendTo('body').hide();
		},

		// current page state
		state: null,

		// spinner element
		spinner: null
	};


})(jQuery);



jQuery(function($) {

	$.ajaxSetup({
		success: $.nette.success,
		dataType: 'json',
		error: $.nette.error
	});

	$.nette.createSpinner();
	//this.ajax.ajaxStop(function() {$.fancybox.hideActivity(); });

	// apply AJAX unobtrusive way
	$('a.ajax').live('click', function(event) {
		$.fancybox.showActivity();
		event.preventDefault();
		if ($.active) return;

		/*window.location.hash = this.href.substr(this.href.indexOf('?'));
		odkazyVkotve.soucasnyOdkaz = location.hash;*/
		$.post(this.href, $.nette.success);

		/*$.nette.spinner.css({
			position: 'fixed'
			//left: event.pageX,
			//top: event.pageY
		});*/

		//$.nette.spinner.children('a').attr('href', this.href);

		//return false;
	});

});
