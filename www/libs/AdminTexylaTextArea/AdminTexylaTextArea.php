<?php

class AdminTexylaTextArea extends /*Nette\Forms\*/TextArea
{

	/**
	 * @param  string  label
	 * @param  int  width of the control
	 * @param  int  maximum number of characters the user may enter
	 */
	public function __construct($label = NULL, $cols = NULL, $rows = NULL)
	{
		if( $cols === NULL ) $cols = 65;
		if( $rows === NULL ) $rows = 10;
		parent::__construct($label, $cols, $rows);
	}


	/**
	 * Generates control's HTML element.
	 * @return Html
	 */
	public function getControl()
	{
		$presenter = $this->getParent()->getParent();

		$control = parent::getControl();
		$control->class = 'texyla';

		$zavodyModel = new Zavody;
		$zavody = $zavodyModel->findAllToSelect();

		$options = '';
		foreach($zavody as $v)
		{
			$options .= '<option value="'.$v->id.'">'.$v->nazev.', '.datum::date($v->datum, 0, 0, 0).'</option>';
		}

		$el = Html::el();
		$el->add($control);
		$el->add('
<script type="text/javascript">

	// Okno obrázku
	$.texyla.addWindow("img", {
		createContent: function () {
			return $(
				"<div><p><a href='."'".$presenter->link('Galerie:vyber')."'".' class='."'vyber-z-galerie'".'>Vybrat fotku uloženou v galerii</a><br />" +
					"<a href='."'".$presenter->link('Soubory:vyber', array('souvisejici' => $presenter->getName(), 'id_souvisejiciho' => $presenter->getParam('id', 0)))."'".' class='."'vyber-z-galerie'".'>Vybrat přiložený obrázek</a></p>" +
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
						"<option value='."'".'<'."'>".'" + this.lng.imgAlignLeft + "</option>" +
						"<option value='."'".'>'."'>".'" + this.lng.imgAlignRight + "</option>" +
						"<option value='."'".'<>'."'>".'" + this.lng.imgAlignCenter + "</option>" +
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

$.texyla.setDefaults({
	texyCfg: "admin",
	previewPath: "'.$presenter->link('Texyla:preview').'",
	baseDir: "'.Environment::getVariable('baseUri').'",
	iconPath: "'.Environment::getVariable('baseUri').'" + "js/texyla/icons/%var%.png",
	toolbar: [
		"h2", "h3", "h4",
		null,
		"bold", "italic", "del",
		null,
		"center", ["left", "right"],
		null,
		"ul", "ol",
		null,
		{type: "label", text: "Vložit"}, "link", "img", "table",
		null,
		"youtube", ["stream", "facebook"],
		null,
		"blockquote",
		null,
		{type: "label", text: "Ostatní"}, ["sup", "sub", "acronym", "hr"],
		null,
		{type: "label", text: "Vložit speciální obsah"}, ["prubezneVysledky"]
	]
});

</script>
<select class="odkazy-na-zavody">'.$options.'</select>' );

		return $el;
	}

}