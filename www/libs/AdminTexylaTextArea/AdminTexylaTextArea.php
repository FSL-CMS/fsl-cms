<?php

class AdminTexylaTextArea extends Nette\Forms\Controls\TextArea
{

	public $souvisejici;
	public $id_souvisejiciho;

	/**
	 * @param  string  label
	 * @param  int  width of the control
	 * @param  int  maximum number of characters the user may enter
	 */
	public function __construct($label = NULL, $cols = NULL, $rows = NULL, $souvisejici, $id_souvisejiciho)
	{
		if( $cols === NULL ) $cols = 65;
		if( $rows === NULL ) $rows = 10;
		parent::__construct($label, $cols, $rows);

		$this->souvisejici = $souvisejici;
		$this->id_souvisejiciho = $id_souvisejiciho;
	}


	/**
	 * Generates control's HTML element.
	 * @return Html
	 */
	public function getControl()
	{
		$control = parent::getControl();
		$control->class = 'admintexyla';

		$zavodyModel = Nette\Environment::getService('zavody');
		$zavody = $zavodyModel->findAllToSelect();

		$options = '';
		foreach($zavody as $v)
		{
			$options .= '<option value="'.$v->id.'">'.$v->nazev.', '.datum::date($v->datum, 0, 0, 0).'</option>';
		}

		$el = Nette\Utils\Html::el();
		$el->add($control);
		$el->create('input')->type('hidden')->name('souvisejici')->value($this->souvisejici);
		$el->create('input')->type('hidden')->name('id_souvisejiciho')->value($this->id_souvisejiciho);
		$el->add('<select class="odkazy-na-zavody">'.$options.'</select>' );

		return $el;
	}

}