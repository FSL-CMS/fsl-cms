<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Komponenta vykreslující přehled závodů do postraního menu
 *
 * @author	Milan Pála
 */
class FileUploaderControl extends BaseControl
{

	private $allowedTypes = array('photos' => array('jpg'), 'images' => array('png', 'gif'), 'docs' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'zip', 'rar'));
	private $allowedMimeTypes = array('photos' => array('image\/jpeg'), 'images' => array('image\/png', 'image\/gif'), 'docs' => array('application', 'zip', 'rar'));
	private $types;

	/** Klíč do proměnné $_FILES */

	const UPLOADED_KEY = 'fileUploader';

	/**
	 *
	 * @var IFileUploaderFile
	 */
	private $souborModel = NULL;

	public function __construct()
	{
		parent::__construct();

		$this->types = array('photos');
	}

	public function setFileModel(IFileUploaderFileManager $model)
	{
		$this->souborModel = $model;
	}

	public function setType($typ)
	{
		$this->types = explode(',', $typ);
	}

	public function handleUpload()
	{
		try
		{
			if(strtolower($_SERVER['REQUEST_METHOD']) != 'post')
			{
				$this->exit_status('Error! Wrong HTTP method!');
			}

			if(array_key_exists(self::UPLOADED_KEY, $_FILES) && $_FILES[self::UPLOADED_KEY]['error'] == 0)
			{

				$pic = $_FILES[self::UPLOADED_KEY];

				$allowedTypes = array();
				foreach ($this->types as $val)
				{
					$allowedTypes = array_merge($allowedTypes, $this->allowedTypes[$val]);
				}

				if(!in_array($this->get_extension($pic['name']), $allowedTypes))
				{
					$this->exit_status('Jsou dovoleny pouze přípony ' . implode(', ', $allowedTypes) . '.');
				}

				$soubor = new Nette\Http\FileUpload($_FILES[self::UPLOADED_KEY]);
				$this->souborModel->save($soubor);

				$this->exit_status('ok');
			}
		}
		catch (Exception $e)
		{
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			$this->getPresenter()->flashMessage('Nepodařilo se uložit obrázek.', 'error');
			$this->exit_status($e->getMessage());
		}
	}

	private function exit_status($str)
	{
		echo json_encode(array('status' => $str));
		exit;
	}

	private function get_extension($file_name)
	{
		$ext = explode('.', $file_name);
		$ext = array_pop($ext);
		return strtolower($ext);
	}

	public function render()
	{
		if($this->souborModel === NULL) throw new ApplicationException('Je nutné nastavit model obstarávající ukládání nahraných souborů.');

		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/uploader.phtml');
		$template->uploadedKey = self::UPLOADED_KEY;
		$template->allowedMimeTypes = array();
		$template->allowedTypes = array();
		foreach ($this->types as $val)
		{
			$template->allowedMimeTypes = array_merge($template->allowedMimeTypes, $this->allowedMimeTypes[$val]);
			$template->allowedTypes = array_merge($template->allowedTypes, $this->allowedTypes[$val]);
		}
		$template->inputFileUploaderFormId = $this['inputFileUploaderForm']->getElementPrototype()->id;
		$template->render();
	}

	public function createComponentInputFileUploaderForm($name)
	{
		$f = new Nette\Application\UI\Form($this, $name);

		$f->addGroup('Nahrání souborů');
		$c = $f->addContainer(self::UPLOADED_KEY);
		$c->addUpload('0', 'Vyberte soubory k nahrání');
		$c->addUpload('1', 'Vyberte soubory k nahrání');
		$c->addUpload('2', 'Vyberte soubory k nahrání');

		$f->addSubmit('save', 'Nahrát soubory');
		$f->onSuccess[] = array($this, 'inputFileUploaderFormSubmitted');
	}

	public function inputFileUploaderFormSubmitted(Nette\Application\UI\Form $form)
	{
		$data = $form->getValues();

		foreach ($data[self::UPLOADED_KEY] AS $soubor)
		{
			try
			{
				if(!$soubor->isOk()) continue;
				$allowedTypes = array();
				foreach ($this->types as $val)
				{
					$allowedTypes = array_merge($allowedTypes, $this->allowedTypes[$val]);
				}

				if(!in_array($this->get_extension($soubor->getName()), $allowedTypes))
				{
					$this->getPresenter()->flashMessage('Jsou dovoleny pouze přípony ' . implode(', ', $allowedTypes) . '.', 'warning');
					continue;
				}
				$this->souborModel->save($soubor);
				$this->presenter->flashMessage('Soubor ' . $soubor->getName() . ' byl úspěšně uložen.', 'ok');
			}
			catch (Exception $e)
			{
				$this->presenter->flashMessage('Nepodařilo se uložit soubor ' . $soubor->getName() . '. Chyba: ' . $e->getMessage(), 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}
		}

		// Předáme data do šablony
		//$this->template->values = $data;
		if(!$this->presenter->isAjax()) $this->redirect('this');
	}

}
