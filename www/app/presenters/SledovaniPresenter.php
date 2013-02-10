<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Presenter sledování
 *
 * @author	Milan Pála
 */
class SledovaniPresenter extends SecuredPresenter
{
	/** @persistent */
	public $backlink = '';

	protected $model = NULL;

	protected function startup()
	{
		$this->model = $this->context->sledovani;
		parent::startup();
	}

	public function renderDefault()
	{
		$this->template->sledovani = array();

		$this->template->sledovani['muze_pridat'] = $this->user->isAllowed('sledovani', 'add');
		$this->template->sledovani['muze_editovat'] = $this->user->isAllowed('sledovani', 'edit');
		$this->template->sledovani['muze_mazat'] = $this->user->isAllowed('sledovani', 'delete');
		$this->template->sledovani['sledovani'] = $this->model->findAll();

		$this->setTitle('Správa sledování');
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Místo bylo úspěšně odstraněno.');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Místo se nepodařilo odstranit.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'error');
		}

		if( $this->isAjax() ) $this->invalidateControl('mista');
		else $this->redirect('this');
	}
}
