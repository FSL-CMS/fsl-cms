<?php

class BaseControl extends Control
{
	public function createTemplate()
	{
		$template = parent::createTemplate();

		$texy = new MyTexy();
		$texy2 = new MyTexy;

		$texy->addHandler('script', array($this, 'scriptHandler'));
		$texy->addHandler('image', array($this, 'videoHandler'));

		$template->registerHelper('texy', array($texy, 'process'));
		$template->registerHelper('texy2', array($texy2, 'process'));

		$datum = new Datum();
		$template->registerHelper('datum', array($datum, 'date'));

		$template->registerHelper('zvetsPrvni', array($this, 'zvetsPrvni'));

		return $template;
	}
}