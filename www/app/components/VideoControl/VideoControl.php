<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
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

	public function renderYoutube($width = 560, $height = 315, $key)
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/youtube.phtml');
		$template->sirka = $width ?: 560;
		$template->vyska = $height ?: 315;
		$template->key = $key;
		return $template->render();
	}

	public function renderYoutubePlaylist($width = 560, $height = 315, $key)
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/youtubePlaylist.phtml');
		$template->sirka = $width ?: 560;
		$template->vyska = $height ?: 315;
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

	public function renderStream($width = 560, $height = 343, $key)
	{
		$template = clone $this->parent->template;
		$template->sirka = $width ?: 560;
		$template->vyska = $height ?: 343;
		$template->key = $key;
		$template->setFile(dirname(__FILE__) . '/stream.phtml');
		return $template->render();
	}
}
