<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Komponenta vykreslující vkládaná videa
 *
 * @author	Milan Pála
  */
class VideoControl extends BaseControl
{
	public function __construct()
	{
		parent::__construct();
	}

	public function renderYoutube($width = 450, $height = 300, $key)
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/youtube.phtml');
		$template->sirka = $width ?: 450;
		$template->vyska = $height ?: 300;
		$template->key = $key;
		return $template->render();
	}

	public function renderFacebook($width = 450, $height = 300, $key)
	{
		$template = clone $this->parent->template;
		$template->width = $width;
		$template->height = $height;
		$template->key = $key;
		$template->setFile(dirname(__FILE__) . '/facebook.phtml');
		return $template->render();
	}

	public function renderStream($width = 450, $height = 300, $key)
	{
		$template = clone $this->parent->template;
		$template->width = $width;
		$template->height = $height;
		$template->key = $key;
		$template->setFile(dirname(__FILE__) . '/stream.phtml');
		return $template->render();
	}
}