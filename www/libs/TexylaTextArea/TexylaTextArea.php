<?php

class TexylaTextArea extends Nette\Forms\Controls\TextArea
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
		$control = parent::getControl();
		$control->class = 'texyla';

		$zavodyModel = Nette\Environment::getService('zavody');
		$zavody = $zavodyModel->findAllToSelect();

		$options = '';
		foreach($zavody as $v)
		{
			$options .= '<option value="'.$v->id.'">'.$v->nazev.', '.datum::date($v->datum, 0, 0, 0).'</option>';
		}

		$el = Nette\Utils\Html::el();
		$el->add($control);
		$el->add('<select class="odkazy-na-zavody">'.$options.'</select>' );

		return $el;
	}

}