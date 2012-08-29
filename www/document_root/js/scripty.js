$(document).ready(function()
	{
		$(".hodnoceni").rating({
			showCancel: false
		}).closest('form').find('input[type=submit]').hide();
		$(".hodnoceni").bind("change", function()
		{
			$.post(
				$(this).closest('form').attr('action'),
				$(this).closest('form').serialize(),
				function(data)
				{
					dat = eval(data);
					$('.hodnoceni').eq(0).val(dat.hodnoceni);
					//alert($('#frmhodnoceniForm-hodnoceni').eq(0).attr("disabled"));
					$('#frmhodnoceniForm-hodnoceni').eq(0).attr("disabled", "disabled");
					//alert($('#frmhodnoceniForm-hodnoceni').eq(0).attr("disabled"));
					//alert($('.hodnoceni').eq(0).attr('disabled'));
					$(".hodnoceni").rating({
						showCancel: false,
						disabled: true
					});
				}
				);
		});

		$('.handle').css('cursor','n-resize');
		$('.handle *').show();
		$('.sortable').sortable({
			cursor: 'move',
			handle: $('.handle'),
			helper: 'clone'
		});
		$('.sortable').sortable( "option", "helper", 'clone' );
		$('.sortable').bind('sortupdate', function(event, ui)
		{
			$(this).children('tr').each( function(i) {
				$(this).find('input.poradi').val(i+1);
			} );
		//$('.sortable .poradi').removeClass('hidden-js');
		});

		//$('input.datepicker').datepicker({ duration: 'fast' });

		$('input.datetimepicker').datetimepicker({
			duration: 'normal',
			changeMonth: true,
			changeYear: true,
			yearRange: '1900:2100',
			currentText: 'Dnes',
			closeText: 'OK',
			showOn: "button",
			buttonText: "Kalendář",
			timeText: "Čas",
			hourText: "Hodiny",
			minuteText: "Minuty"
		});
		/* Automaticky se změní volba na ruční určení data při jeho změně. */
		$('.datetimepicker').change(function() {
			var $radios = $(this).closest('fieldset').find('input:radio');
			$radios.each(function(){
				if($(this).val() == 'datum_zverejneni') $(this).attr('checked', true);
			});
		});

		//odkazyVkotve.init();

		// run texyla
		$("textarea.texyla").texyla();

		$(".tablesorter").tablesorter();

		$('.slideshow').cycle({
			fx:	 'fade',
			timeout:  30000
		});

		$("a.zvetsenina").fancybox({
			'type' : 'image',
			'titlePosition' : 'inside'
		});
		$("a.vyber-z-galerie").live('click', function(e)
		{
			$.fancybox({
				'titleShow' : false,
				'href' : $(this).attr('href'),
				'autoScale' : true,
				'scrolling' : 'auto'
			});
			return false;
		});
		$("a.commentEdit").live('click', function(e)
		{
			$.fancybox({
				'titleShow' : false,
				'href' : $(this).attr('href'),
				'autoScale' : true,
				'scrolling' : 'auto'
			});
			return false;
		});
		$("#frm-diskuze-upravitKomentarForm").live('submit', function(e){
			$.fancybox.close();
			return true;
		});

		$("a.fancybox").live('click', function(e){
			//$.fancybox.close();
			$.fancybox({
				'titleShow' : false,
				'href' : $(this).attr('href'),
				'autoScale' : true,
				'scrolling' : 'auto'
			});
			return false;
		});

		$("#vyber-z-galerie").live('submit', function(e){
			var fotky = new Array();
			$(this).find('input:checked').each(function(){
				fotky.push($(this).val());
			});
			$('input.src').val(fotky.toString());
			$.fancybox.close();
			return false;
		});

		$('#pridatFotkyForm').live('submit', function(e){
			var $input = $(this).find('input');
			var filesToUpload = $input[0].files;
			for(var i=0; i<filesToUpload.length; i++)
			{
				var file = filesToUpload[i];
				//if( !file.type.match(/image.*/) ) continue;
				//var img = document.createElement("img");
				//img.src = window.URL.createObjectURL(file);
				var reader = new FileReader();
				var $form = $(this);
				reader.onload = function(e) {
					var img = document.createElement('img');
					var popisek = document.createElement('h2');
					var nahled = document.createElement('div');
					img.src = e.target.result;
					popisek.innerHTML = file.name;
					//nahled.appendChild(popisek);
					nahled.appendChild(img);
					alert(file.name);
					$form.find('.nahledy').append(nahled);
					var request = new XMLHttpRequest();
					request.open('post', $form.attr('action'));
					request.send();
				//$.post($form.attr('action'), , callback, type)
				}
				reader.readAsDataURL(file);
			}
			return false;
		});

		$('.komentar-form').slideUp();
		$('.commentAdd').live('click', function(e){
			$('#komentarForm-'+$(this).attr('id')).slideToggle();
			return false;
		});

		$('a.delete').live('click', function(e){
			return window.confirm('Opravdu smazat?');
		});

		$('.tisk').live('click', function(e){
			$('link[type="text/css"]').each(function(){
				if( $(this).attr('media') == 'print' ) $(this).clone().attr('media', 'screen').appendTo($('head'));
				else $(this).attr('media', 'mmm');
			});
			setTimeout(function(){
				window.print();
			}, 1000);
			$(this).remove();
			return false;
		});

		$('.breadcrumbs form select').each(function(){
			$(this).width($(this).width()+20);
		});

		//$('.breadcrumbs form select').chosen();
		//$('.breadcrumbs form select.combobox').combobox();

		$('.breadcrumbs form select').live('change', function(){
			window.location.href = $(this).val();
		});
	});

var odkazyVkotve =
{
	soucasnyOdkaz : "",

	init : function()
	{
		this.kontrolaKotvy();
		setTimeout("odkazyVkotve.kontrolaKotvy()", 200);
	},

	kontrolaKotvy : function()
	{
		if( location.hash != this.soucasnyOdkaz )
		{
			this.provedOdkaz();
			this.soucasnyOdkaz = location.hash;
		}
		setTimeout("odkazyVkotve.kontrolaKotvy()", 200)
	},

	provedOdkaz : function()
	{
		$.post(location.pathname + location.hash.substr(1), $.nette.success);
	}
}

jQuery.texyla.setDefaults({
	streamMakro: "[* stream:%var% *]"
});

jQuery.texyla.addWindow("stream", {
	createContent: function () {
		var el = jQuery(
			"<div><form><div>" +
			'<label>Odkaz na Stream video<br>' +
			'<input type="text" size="35" class="key">' +
			"</label></div>" +
			"</form></div>"
			);

		el.find(".key").bind("keyup change", function () {
			var val = this.value;
			var key = "";

			if (val.substr(0, 7) == "http://") {
				var res = val.match("/video/([a-zA-Z0-9_-]+)");
				if (res) key = res[1];
				else {
					res = val.match("/uservideo/([a-zA-Z0-9_-]+)");
					if (res) key = res[1];
					else {alert('Adresa videa nebyla rozpoznána.'); res = '---'; }
				}
			} else {
				key = val;
			}

			jQuery(this).data("key", key);

		});

		return el;
	},

	action: function (el) {
		var txt = this.expand(this.options.streamMakro, el.find(".key").data("key"));
		this.texy.update().replace(txt);
	},

	dimensions: [320, 300]
});

jQuery.texyla.setDefaults({
	facebookMakro: "[* facebook:%var% *]"
});

jQuery.texyla.addWindow("facebook", {
	createContent: function () {
		var el = jQuery(
			"<div><form><div>" +
			'<label>Odkaz na Facebook video<br>' +
			'<input type="text" size="35" class="key">' +
			"</label>" +
			'</div>' +
			"</form></div>"
			);

		el.find(".key").bind("keyup change", function () {
			var val = this.value;
			var key = "";

			if (val.substr(0, 7) == "http://") {
				var res = val.match("[?&]v=([a-zA-Z0-9_-]+)");
				if (res) key = res[1];
			} else {
				key = val;
			}

			jQuery(this).data("key", key);

		});

		return el;
	},

	action: function (el) {
		var txt = this.expand(this.options.youtubeMakro, el.find(".key").data("key"));
		this.texy.update().replace(txt);
	},

	dimensions: [320, 300]
});

jQuery.texyla.addWindow("prubezneVysledky", {
	createContent: function () {
		var el = jQuery(
			"<div><p>Vloží průběžné výsledky po uvedeném závodě.</p><form><div>" +
			'<label>Vyberte závod</label></div>' +
			'<div><select>'+$('.odkazy-na-zavody').eq(0).html()+'</select></div>' +
			"</form></div>"
			);
		return el;
	},

	action: function (el) {
		var txt = el.find('select').eq(0).val();
		this.texy.update().replace("{{prubezne-poradi:"+txt+"}}");
	},

	dimensions: [400, 230]
});

jQuery.texyla.addStrings("cs", {
	// stream
	btn_stream: "Stream",
	win_stream: "Stream",
	streamUrl: "Vlo\u017ete adresu videa nebo jeho ID",

	// facebook
	btn_facebook: "Facebook video",
	win_facebook: "Facebook video",
	facebookUrl: "Vlo\u017ete adresu videa nebo jeho ID",

	btn_prubezneVysledky: "Průběžné výsledky",
	win_prubezneVysledky: "Průběžné výsledky"
});


(function( $ ){

	/**
 * Zkrátí tabulku na požadovaný počet řádků
 * Nefunguje kvůli nemožnosti měnit výšku těla tabulky
 */
	$.fn.zkraceniTabulky = function() {

		// there's no need to do $(this) because
		// "this" is already a jquery object

		// $(this) would be the same as $($('#element'));
		var vyska = 0;
		$tbody = this.find('tbody');
		$tbody.find('tr').slice(0, 10).each(function(){
			vyska += $(this).height();
		});
		//alert(vyska);
		$tbody.height(vyska);
		$tbody.css('overflow', 'auto');

	};
})( jQuery );
