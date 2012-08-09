<?php

class TexylaTextArea extends /*Nette\Forms\*/TextArea
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

		$el = Html::el();
		$el->add($control);
		$el->add('
<script type="text/javascript">
$.texyla.setDefaults({
	texyCfg: "guestbook",
	previewPath: "'.$presenter->link('').'",
	baseDir: "'.Environment::getVariable('baseUri').'",
	iconPath: "'.Environment::getVariable('baseUri').'" + "js/texyla/icons/%var%.png",
	toolbar: [
		"h2", "h3", "h4",
		null,
		"bold", "italic",
		null,
		"center", ["left", "right"],
		null,
		"ul", "ol",
		null,
		{type: "label", text: "Vložit"}, "link", "table",
		null,
		"blockquote",
	],
	bottomLeftToolbar: []
});
</script>');

		return $el;
	}

}