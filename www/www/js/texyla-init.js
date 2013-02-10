$.texyla.setDefaults({
	baseDir: '{{$baseUri}}js/texyla',
	previewPath: '{{$previewPath}}',
	filesPath: '{{$filesPath}}',
	filesUploadPath: '{{$filesUploadPath}}',
	filesMkDirPath: '{{$filesMkDirPath}}',
	filesRenamePath: '{{$filesRenamePath}}',
	filesDeletePath: '{{$filesDeletePath}}'
});

$(function () {
	$(".admintexyla").texyla({
		toolbar: [
			'h2', 'h3', 'h4',
			null,
			"bold", "italic", "del",
			null,
			'center', ['left', 'right'],
			null,
			'ul', 'ol', ["olAlphabetSmall", "olAlphabetBig", "olRomans", "olRomansSmall"],
			null,
			{ type: "label", text: "Vložit"}, 'link', 'img', 'table', 'symbol',
			null,
			"youtube", ["stream", "facebook"],
			null,
			'blockquote',
			null,
			{type: "label", text: "Ostatní"}, ["sup", "sub", "acronym", "hr"],
			null,
			{type: "label", text: "Vložit speciální obsah"}, ["prubezneVysledky", "mapaStranek", "kontakty"]

		],
		texyCfg: "admin",
		bottomLeftToolbar: ['edit', 'preview'],
		buttonType: "span",
		tabs: true
	});

	$(".texyla").texyla({
		toolbar: [
			'h2', 'h3', 'h4',
			null,
			"bold", "italic", "del",
			null,
			'center', ['left', 'right'],
			null,
			'ul', 'ol', ["olAlphabetSmall", "olAlphabetBig", "olRomans", "olRomansSmall"],
			null,
			{ type: "label", text: "Vložit"}, 'link', 'img', 'table', 'symbol',
			null,
			"youtube", ["stream", "facebook"],
			null,
			'blockquote',
			null,
			{type: "label", text: "Ostatní"}, ["sup", "sub", "acronym", "hr"]
		],
		texyCfg: "admin",
		bottomLeftToolbar: ['edit', 'preview'],
		buttonType: "span",
		tabs: true
	});

});

// Okno obrázku
$.texyla.addWindow("img", {
	createContent: function () {
		return $(
			'<div><p><a href="{{$galerieVyberPath}}" class="vyber-z-galerie">Vybrat fotku uloženou v galerii</a><br />' +
				'<a href="{{$souboryVyberPath}}" class="vyber-z-galerie">Vybrat přiložený obrázek</a></p>' +
			"<table><tbody><tr>" +
				// Adresa
				"<th><label>" + this.lng.imgSrc + "</label></th>" +
				"<td><input type=text class=src></td>" +
			"</tr><tr>" +
				// Alt
				"<th><label>" + this.lng.imgAlt + "</label></th>" +
				"<td><input type=text class=alt></td>" +
			"</tr><tr>" +
				// Zarovnání
				"<th><label>" + this.lng.imgAlign + "</label></th>" +
				"<td><select class=align>" +
					"<option value=*>" + this.lng.imgAlignNone + "</option>" +
					'<option value="<">' + this.lng.imgAlignLeft + "</option>" +
					'<option value=">">' + this.lng.imgAlignRight + "</option>" +
					'<option value=".<>">' + this.lng.imgAlignCenter + "</option>" +
				"</select></td>" +
			"</tr><tr>" +
				// Zarovnání
				"<th><label>Velikost</label></th>" +
				"<td><select class=velikost>" +
					"<option value=nahled>malý náhled</option>" +
					"<option value=velka>velká</option>" +
				"</select></td>" +
			"</tr></tbody></table></div>"
		);
	},

	action: function (el) {
			if( el.find(".velikost").val() == "velka" ) velikost = "velka:"+el.find(".src").val();
			else velikost = el.find(".src").val();
		this.texy.img(
			velikost,
			el.find(".alt").val(),
			el.find(".align").val()
		);
	},

	dimensions: [350, 380]
});

// Vloží odkaz na video ze Stream
$.texyla.setDefaults({
	streamMakro: "[* stream:%var% *]"
});

$.texyla.addWindow("stream", {
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

	dimensions: [400, 200]
});

$.texyla.addWindow("link", {
	dimensions: [350, 200],

	createContent: function () {
		return jQuery(
			'<div><table><tbody><tr>' +
				'<th><label>' + this.lng.linkText + '</label></th>' +
				'<td><input type="text" class="link-text" value="' + this.texy.trimSelect().text() + '"></td>' +
			'</tr><tr>' +
				'<th><label>' + this.lng.linkUrl + '</label></th>' +
				'<td><input type="text" class="link-url" value="http://"></td>' +
			'</tr></tbody></table></div>'
		);
	},

	action: function (el) {
		var txt = el.find(".link-text").val();
		txt = txt == '' ? '' : '"' + txt + '":';
		this.texy.replace(txt + el.find(".link-url").val());
	}
});

// Vloží odkaz na video z Facebooku
$.texyla.setDefaults({
	facebookMakro: "[* facebook:%var% *]"
});

$.texyla.addWindow("facebook", {
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

	dimensions: [400, 200]
});

// Vloží makro s výsledky ze závodů
$.texyla.addWindow("prubezneVysledky", {
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

	dimensions: [500, 300]
});

// Vloží makro na kontakty {{kontakty}}
$.texyla.addButton("kontakty", function () {
    this.texy.update().replace("{{kontakty}}");
});

// Vloží makro na mapu stránek {{mapaStranek}}
$.texyla.addButton("mapaStranek", function () {
    this.texy.update().replace("{{mapaStranek}}");
});

$.texyla.addStrings("cs", {
	// stream
	btn_stream: "Stream",
	win_stream: "Stream",
	streamUrl: "Vlo\u017ete adresu videa nebo jeho ID",

	// facebook
	btn_facebook: "Facebook video",
	win_facebook: "Facebook video",
	facebookUrl: "Vlo\u017ete adresu videa nebo jeho ID",

	btn_prubezneVysledky: "Průběžné výsledky po závodu",
	win_prubezneVysledky: "Průběžné výsledky po závodu",

	btn_mapaStranek: "Mapa stránek",
	win_mapaStranek: "Mapa stránek",
	btn_kontakty: "Kontakty",
	win_kontakty: "Kontakty"
});