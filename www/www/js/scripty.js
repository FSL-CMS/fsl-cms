$(document).ready(function() {
	// Hodnocení kontrol
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

	$('.datetimepicker').datetimepicker({
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
	/*$('.datetimepicker').change(function() {
			var $radios = $(this).closest('fieldset').find('input:radio');
			$radios.each(function(){
				if($(this).val() == 'datum_zverejneni') $(this).attr('checked', true);
			});
		});*/

	//odkazyVkotve.init();

	$(".tablesorter").tablesorter();

	$('.slideshow').cycle({
		fx:	 'fade',
		timeout:  30000
	});

	$("a.zvetsenina").fancybox({
		type : 'image',
		helpers : {
			title : {
				type : 'inside'
			}
		},
		afterLoad : function() {
			this.title = this.title + '<div class="fb-like" data-href="'+this.href+'" data-send="false" data-layout="button_count" data-width="100" data-show-faces="false"></div>';
		}
	});

	$('.komentar-form').slideUp();

	$('.tisk').on('click', function(e){
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

	$('.breadcrumbs form select').on('change', function(){
		window.location.href = $(this).val();
	});
});


$(document).on('click', '.commentAdd', function(){
	$('#komentarForm-'+$(this).attr('id')).slideToggle();
	return false;
});

$(document).on('click', 'a.delete', function(){
	return window.confirm('Opravdu smazat?');
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

$(document).on('click', 'a.vyber-z-galerie, a.commentEdit, a.fancybox', function(e)
{
	$.fancybox.open(
		[{
			href : $(this).attr('href')
		}],
		{
			autoResize : true,
			scrolling : 'auto',
			helpers: {
				title:  null
			},
			type: 'ajax'
		}
		);
	return false;
});


$(document).on('submit', '#frm-diskuze-upravitKomentarForm', function(e){
	$.fancybox.close();
	return true;
});

$(document).on("submit", "#vyber-z-galerie", function(e) {
	var fotky = new Array();
	$(this).find('input:checked').each(function(){
		fotky.push($(this).val());
	});
	// texyla input
	$('input.src').val(fotky.toString());
	$.fancybox.close();
	return false;
});

$(document).on('submit', '#pridatFotkyForm', function(e){
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
