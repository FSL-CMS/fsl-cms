<?php

/**
 * FormPollControl - part of PollControl plugin for Nette Framework for voting.
 * Uses links for realization of the vote.
 *
 * @copyright  Copyright (c) 2009 Ondřej Brejla
 * @license    New BSD License
 * @link       http://nettephp.com/cs/extras/poll-control
 * @package    Nette\Extras
 * @version    0.1
 */
class LinkPollControl extends PollControl {

	public function handleVote($id) {
        try {
            $this->model->vote($id);
            $this->flashMessage('Váš hlas byl uložen.');
        } catch (BadRequestException $ex) {
            // something to do, when user is not allowed to vote (ex. flash message, ...)
            $this->flashMessage('Již jste hlasoval(a).', 'warning');
        }
	   catch(DibiException $e)
	   {
		   $this->flashMessage('Nepodařilo se uložit hlas.', 'error');
		   Debug::processException($e, true);
	   }

        if (!$this->getPresenter()->isAjax()) {
            $this->redirect('this');
        } else $this->invalidateControl('this');
    }

	public function render($id = 0)
	{
		try
		{
			if( $this->model->oldId == 0 && $id != 0 ) $this->model = new PollControlModel($id);
			$this->template->setFile(dirname(__FILE__) . '/LinkPollControl.phtml');
			if( $this->model->id != 0 ) $this->template->render();
		}
		catch(DibiDriverException $e)
		{
			$this->flashMessage('Nepodařilo se načíst anketu', 'error');
			Dibi::processException($e, true);
		}
    }

}
