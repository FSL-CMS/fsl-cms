/**
 * AJAX form plugin for jQuery
 *
 * @copyright  Copyright (c) 2009 Jan Marek
 * @license	MIT
 * @link	   http://nettephp.com/cs/extras/ajax-form
 * @version	0.1
 */

jQuery.fn.extend({
	ajaxSubmit: function (callback,e) {
		var form;
		var sendValues = {};

		// submit button
		if (this.is(":submit")) {
			form = this.parents("form");
			sendValues[this.attr("name")] = this.val() || "";

		// form
		} else if (this.is("form")) {
			form = this;

		// invalid element, do nothing
		} else {
			$.fancybox.hideLoading();
			return null;
		}

		if( e != undefined ) e.preventDefault();

		// validation
		if (form.get(0).onsubmit && !form.get(0).onsubmit()) {
			e.stopImmediatePropagation();
			//$.fancybox.hideLoading();
			return null;
		}

		if(form.data("ajaxSubmitCalled")==true) {
			//$.fancybox.hideLoading();
			return null;
		}

		form.data("ajaxSubmitCalled",true);

		// Tím, že zaregistruji ajaxové odeslání až teď, tak se provede jako poslední. (až po všem)
		form.one("submit",function(){
			// get values
			var values = form.serializeArray();

			for (var i = 0; i < values.length; i++) {
				var name = values[i].name;

				// multi
				if (name in sendValues) {
					var val = sendValues[name];

					if (!(val instanceof Array)) {
						val = [val];
					}

					val.push(values[i].value);
					sendValues[name] = val;
				} else {
					sendValues[name] = values[i].value;
				}
			}

			// send ajax request
			var ajaxOptions = {
				url: form.attr("action"),
				data: sendValues,
				type: form.attr("method") || "get"
			};

			ajaxOptions.complete = function(){
				form.data("ajaxSubmitCalled",false);
			}

			if (callback) {
				ajaxOptions.success = callback;
			}
			//$.fancybox.hideLoading();
			return jQuery.ajax(ajaxOptions);
		})

		if( e != undefined ) e.stopImmediatePropagation();
		form.submit();
		return null;
	},

	__submit: function(e) {
		$.fancybox.showLoading();
		$(this).ajaxSubmit(null,e);
	},

	__submitKomentar: function(e) {
		var $formular = $(this).closest('form');
		$(this).ajaxSubmit(null,e);
		$formular.closest('.komentar-form').slideUp();
	//$formular[0].reset();
	},

	enableAjaxSubmit: function() {
		this.bind("submit",this.__submit);
		$(":submit",this).bind("click",this.__submit);
	},
	enableAjaxSubmitKomentar: function() {
		this.bind("submit",this.__submitKomentar);
		$(":submit",this).bind("click",this.__submitKomentar);
	},
	disableAjaxSubmit: function() {
		this.unbind("submit",this.__submit);
		$(":submit",this).unbind("click",this.__submit);
	}
});


// po načtení stránky
$(function () {
	// odeslání na formulářích
	$("form.ajax").livequery(function(){
		$(this).enableAjaxSubmit();
	});

	$("form.ajax-komentar").livequery(function(){
		$(this).enableAjaxSubmitKomentar();
	});
});
