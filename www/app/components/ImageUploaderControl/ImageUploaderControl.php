<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Komponenta vykreslující přehled závodů do postraního menu
 *
 * @author	Milan Pála
  */
class ImageUploaderControl extends BaseControl
{
	private $model;

	private $id_souvisejiciho = NULL;

	public function __construct()
	{
		//$this->model = new Zavody;
		parent::__construct();
	}

	public function setId($id)
	{
		$this->id_souvisejiciho = $id;
	}

	public function handleUpload()
	{
		try
		{
			//$this->exit_status(print_r($_FILES, true));
		$allowed_ext = array('jpg','jpeg','png','gif');

		if(strtolower($_SERVER['REQUEST_METHOD']) != 'post'){
			$this->exit_status('Error! Wrong HTTP method!');
		}

		if(array_key_exists('pic',$_FILES) && $_FILES['pic']['error'] == 0 ) {

			$pic = $_FILES['pic'];

			if(!in_array($this->get_extension($pic['name']),$allowed_ext)){
				$this->exit_status('Jsou dovoleny pouze přípony '.implode(',',$allowed_ext).'.');
			}

			$fotka = new HttpUploadedFile($_FILES['pic']);
			$fotkaModel = new Fotky($fotka);
			$fotkaModel->id_autora = $this->getPresenter()->user->getIdentity()->id;

			$fotkaModel->uloz($this->id_souvisejiciho);

			$this->exit_status('ok');
		}
		}
		catch(Exception $e)
		{
			$this->exit_status($e->getMessage());
			$this->getPresenter()->flashMessage('Nepodařilo se uložit obrázek.', 'error');
			Debug::processException($e, true);
		}

	}

	private function exit_status($str){
		echo json_encode(array('status'=>$str));
		exit;
	}

	private function get_extension($file_name){
		$ext = explode('.', $file_name);
		$ext = array_pop($ext);
		return strtolower($ext);
	}

	public function render()
	{
		if($this->id_souvisejiciho == NULL) throw new ApplicationException('Je nutné nastavit ID alba, do kterého se bude ukládat.');
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/uploader.phtml');
		$template->render();
	}

}
