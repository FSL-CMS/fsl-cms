<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;
use Nette\Application\ForbiddenRequestException;

define('FACEBOOK_APP_ID', '119774764702049');
define('FACEBOOK_SECRET', 'e3a689dd77880081fb9b4380da243f0f');

/**
 * Presenter uživatelů
 *
 * @author	Milan Pála
 */
class UzivatelePresenter extends BasePresenter
{

	/** @persistent */
	public $backlink = '';

	protected $model;

	protected function startup()
	{
		$this->model = $this->presenter->context->uzivatele;
		parent::startup();
	}

	public function actionDefault()
	{
		if( !$this->user->isAllowed('uzivatele', 'view') )
		{
			throw new ForbiddenRequestException();
		}
	}

	public function renderDefault()
	{
		$this->template->uzivatele = array();

		$this->template->uzivatele['muze_editovat'] = $this->user->isAllowed('uzivatele', 'edit');
		$this->template->uzivatele['muze_pridavat'] = $this->user->isAllowed('uzivatele', 'add');
		$this->template->uzivatele['uzivatele'] = $this->model->findAll()->fetchAll();
		foreach( $this->template->uzivatele['uzivatele'] as &$uzivatel )
		{
			$uzivatel['muze_mazat'] = false;
		}

		$this->setTitle('Správa uživatelů');
	}

	public function actionUzivatel($id = 0)
	{
		if( $id == 0 ) $this->redirect('default');

		if( !$this->model->find($id)->fetch() ) throw new BadRequestException('Hledaný uživatel neexistuje');
	}


	public function renderUzivatel($id)
	{
		$this->template->uzivatel = $this->model->find($id)->fetch();

		$clankyModel = $this->presenter->context->clanky;
		$this->template->uzivatel['clanky'] = $clankyModel->findByAutor($id);

          $this->template->uzivatel['muze_editovat'] = $this->user->isAllowed('uzivatele', 'edit') || ($this->user->getIdentity() !== NULL && $this->template->uzivatel['id'] == $this->user->getIdentity()->id);

		$this->setTitle('Uživatel '.$this->template->uzivatel['jmeno'].' '.$this->template->uzivatel['prijmeni']);
  	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('uzivatele', 'add') ) throw new ForbiddenRequestException();

		$this->setView('edit');
	}

	public function actionEdit($id = 0)
	{
		parent::actionEdit($id);

		if( $id != 0 && !$this->model->find($id)->fetch() ) throw new BadRequestException();
	}

	public function renderEdit($id = 0, $backlink = NULL, $facebook = false)
	{
		if( $id != 0 ) $this['editForm']->setDefaults($this->model->find($id)->fetch());

		$this->template->facebook = $facebook;
		if( $facebook == true )
		{
			$apiKey = '755802d99e8b08b04bea91d60a5f235e';
			$apiSecret = 'e3a689dd77880081fb9b4380da243f0f';

			include_once LIBS_DIR."/facebook-platform/php/facebook.php";

			$fb = new Facebook($apiKey, $apiSecret);

			$fbUserId = $fb->user;

			$this['editForm']['facebook']['facebookId']->setDefaultValue($fbUserId);
			if( !empty($fbUserId) ) $this['editForm']['facebook']->addCheckbox('jeFacebook', 'Účet svázán s Facebook účtem')->setValue(true);
		}

		if($id == 0) $this->setTitle('Přidání nového uživatele');
		else $this->setTitle('Úprava informací o uživateli');
	}

	public function createComponentEditForm()
	{
		$id = (int)$this->getParam('id', 0);
		$facebook = (int)$this->getParam('facebook', false);

		$form = new RequestButtonReceiver($this, 'editForm');
		$uzivatele = $this->context->uzivatele;
		$sbory = $this->context->sbory;
		$funkce_rady = $this->context->funkceRady;
		$backlink = $this->getApplication()->storeRequest();

		$form->addGroup('Informace o uživateli');
		$form->addText('jmeno', 'Jméno')
			->addRule(Form::FILLED, 'Je nutné vyplnit jméno.')
			->addRule(Form::MAX_LENGTH, 'Jméno může mít maximálně %d znaků.', 50);
		$form->addText('prijmeni', 'Příjmení')
			->addRule(Form::FILLED, 'Je nutné vyplnit příjmení.')
			->addRule(Form::MAX_LENGTH, 'Příjmení může mít maximálně %d znaků.', 50);
		$form->addText('email', 'E-mail')
			->setOption('description', 'Používá se pro přihlašování.')
			->addRule(Form::FILLED, 'Je nutné vyplnit e-mail.')
			->addRule(Form::MAX_LENGTH, 'E-mail může mít maximálně %d znaků.', 50)
			->addCondition(Form::FILLED)
				->addRule(Form::EMAIL, 'Špatný tvar e-mailové adresy.');
		$form->addSelect('id_sboru', 'Sbor', $sbory->findAlltoSelect()->fetchPairs('id', 'nazev'))
			->setPrompt('Vyberte sbor')
			->addRule(Form::FILLED, 'Je nutné vybrat příslušnost ke sboru.')
			->setOption('description', $form->addRequestButton('addSbory', 'Přidat nový', 'Sbory:add'));
		if( $this->user->isAllowed('funkcerady', 'edit') )
		{
			$form->addSelect('id_funkce', 'Funkce v radě', array(0=>'žádná')+$funkce_rady->findAllToSelect()->fetchPairs('id', 'nazev'))
				->setOption('description', $form->addRequestButton('addFunkceRady', 'Přidat novou', 'FunkceRady:edit'));
		}
		$form->addTexylaTextArea('kontakt', 'Kontakt');

		$form->addGroup('Přihlašovací informace');
		if( $facebook == false )
		{
			$opravneni = array('user' => 'běžný uživatel', 'author' => 'autor', 'admin' => 'správce');

			$form->addPassword('heslo1', 'Nové heslo')
				->addCondition(Form::FILLED)
					->addRule(Form::MIN_LENGTH, 'Heslo by mělo mít aspoň %d znaků.', 5);
			$form->addPassword('heslo2', 'Kontrola hesla')
				->addConditionOn($form['heslo1'], Form::VALID)
					->addRule(Form::EQUAL, 'Hesla se musí shodovat.', $form['heslo1']);
			if( $this->user->isInRole('admin') && $this->user->isLoggedIn() )
			{
				$form->addSelect('opravneni', 'Oprávnění', $opravneni)
					->setDefaultValue('user')
					->addRule(Form::FILLED, 'Uživatelské oprávnění musí být vyplněno.');
			}

			if( $this->getAction() == 'add' ) $form['heslo1']->addRule(Form::FILLED, 'Je nutné vyplnit heslo.');
			if( $this->getAction() == 'add' ) $form['heslo2']->addRule(Form::FILLED, 'Je nutné vyplnit heslo.');
		}
		else
		{
			$fb = $form->addContainer('facebook');
			$fb->addHidden('facebookId');
			$fb->addTextArea('foo', '')
				->setValue('Přihlašování probíhá prostřednictvím účtu na Facebooku. Přihlašovací jméno i heslo není potřeba.')
				->setDisabled('true');
		}

		$form->addGroup('Uložení');

		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(FALSE);
		$form->addRequestButtonBack('back', 'Vrátit se zpět');

		$form->onSuccess[] = array($this, 'editFormSubmitted');

		return $form;
	}

	public function editFormSubmitted(Nette\Application\UI\Form $form)
	{
		$id = (int) $this->getParam('id');

		if( $form['cancel']->isSubmittedBy() )
		{
		}
		elseif( $form['save']->isSubmittedBy() )
		{
			$dataDoDB = array( 'jmeno' => $form['jmeno']->value, 'prijmeni' => $form['prijmeni']->value, 'id_sboru%i' => (int)$form['id_sboru']->value, 'kontakt' => $form['kontakt']->value, 'email' => $form['email']->value );

			if( isset($form['id_funkce']) ) $dataDoDB['id_funkce'] = $form['id_funkce']->value;

			if( isset($form['heslo1']->value) && !empty($form['heslo1']->value) ) $dataDoDB['heslo'] = md5($form['heslo1']->value);

			if( $this->user->isInRole('admin') && $this->user->isLoggedIn() )
			{
				if( isset($form['opravneni']->value) ) $dataDoDB['opravneni'] = $form['opravneni']->value;
			}

			try
			{
				if( $id == 0 )
				{
					try
					{
						$this->model->insert($dataDoDB);
						$id = $this->model->lastInsertedId();
					}
					catch(RegistredAccountException $e)
					{
						$this->flashMessage('Použitá emailová adresa již byla zaregistrována.', 'error');
						$this->redirect('Uzivatele:alreadyRegistredAccount', $dataDoDB['email']);
					}
				}
				else
				{
					$this->model->update($id, $dataDoDB);
				}

				if( $this->getAction() == 'edit' && $this->jeAutor($id) )
				{
					$this->user->getIdentity()->setName($form['jmeno']->value.' '.$form['prijmeni']->value);
					$this->user->getIdentity()->id_sboru = $dataDoDB['id_sboru%i'];
				}
				$this->flashMessage('Informace o uživateli byly úspěšně uloženy.', 'ok');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Nepodařilo se uložit informace o uživateli.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}
		}

		$this->getApplication()->restoreRequest($this->backlink);

		if($id != 0) $this->redirect('Uzivatele:uzivatel', $id);
		else $this->redirect('Uzivatele:default');
	}

	public function actionDelete($id, $force = 0)
	{
		try
		{
			if( $force == 0 && $this->user->getIdentity()->id == $id ) throw new RestrictionException('Chcete smazat sám sebe.');
			$this->model->delete($id);
			$this->flashMessage('Uživatel byl odstraněn.', 'ok');
			$this->redirect('default');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Uživatele se nepodařilo odstranit.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			$this->redirect('default');
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage().' "Přesto smazat!":'.$this->link('delete', array('id' => $id, 'force' => 1)), 'error');
			$this->redirect('default');
		}
	}

	public function renderAlreadyRegistredAccount($email)
	{
		$this->setTitle('Chyba při přihlášení');
		$this->template->email = $email;
	}

	protected function createComponentZapomenuteUdajeForm($name)
	{
		$form = new Nette\Application\UI\Form($this, $name);

		$form->getElementPrototype()->class('ajax');

		$form->addText('email', 'Email zadaný při registraci')
			->addRule(Form::FILLED, 'Je nutné vyplnit email.');

		$form->addSubmit('send', 'Vytvořit nové heslo');

		$form->onSuccess[] = array($this, 'zapomenuteUdajeFormSubmitted');
	}

	public function zapomenuteUdajeFormSubmitted($form)
	{
		try
		{
			$uzivatel = $this->model->findByEmail($form['email']->value)->fetch();
			if( !$uzivatel ) throw new UserNotFoundException('Uživatel s tímto emailem nebyl nalezen. "Registrovat se!":'.$this->getPresenter()->link('Uzivatele:add'));

			$heslo = $this->model->noveHeslo($form['email']->value);

			$mail = new Mail;
			$mail->setFrom('zapomenuteheslo@'.$_SERVER['SERVER_NAME']);
			$mail->addTo($form['email']->value);
			$mail->setBody('Nové heslo pro účet '.$form['email']->value.' je '.$heslo.'.');
			$mail->send();

			$this->boxFlashMessage('Nové heslo bylo úspěšně odesláno na email *'.$form['email']->value.'*.', 'ok');
			$this->invalidateControl('obnoveniHesla');
		}
		catch (UserNotFoundException $e)
		{
			$this->boxFlashMessage($e->getMessage(), 'warning');
			$this->invalidateControl('obnoveniHesla');
		}
		catch (SmtpException $e)
		{
			$this->boxFlashMessage('Nepodařilo se odeslat email s novým heslem.', 'error');
			$this->invalidateControl('obnoveniHesla');
		}
		catch (Exception $e)
		{
			$this->boxFlashMessage('Heslo se nepodařilo obnovit.', 'error');
			Debug::processException($e);
			$this->invalidateControl('obnoveniHesla');
		}
	}

	public function renderZapomenuteUdaje($email = '')
	{
		if( $email != '') $this['zapomenuteUdajeForm']['email']->setValue($email);
		$this->getPresenter()->setLayout(null);
	}

	public function actionRegistrovatFacebook()
	{
		// Create our Application instance (replace this with your appId and secret).
		$facebook = new Facebook(array(
			'appId'  => FACEBOOK_APP_ID,
			'secret' => FACEBOOK_SECRET,
		));

		// Get User ID
		$user = $facebook->getUser();

		// We may or may not have this data based on whether the user is logged in.
		//
		// If we have a $user id here, it means we know the user is logged into
		// Facebook, but we don't know if the access token is valid. An access
		// token is invalid if the user logged out of Facebook.
		$userProfile = NULL;
		if ($user)
		{
			try {
			// Proceed knowing you have a logged in user who's authenticated.
				$userProfile = $facebook->api('/me');

				$uzivatel = $this->model->findByEmail($userProfile['email'])->fetch();

				if( $uzivatel !== false && !empty($uzivatel->facebookId) )
				{
					if( $uzivatel->facebookId == $userProfile['id'] ) $this->prihlas(array('facebookId' => $userProfile['id']));
					else
					{
						$this->model->update($uzivatel->id, array('facebookId' => $userProfile['id']));
						$this->prihlas(array('facebookId' => $userProfile['id']));
					}
				}
				elseif( $uzivatel !== false && empty($uzivatel->facebookId) )
				{
					$this->model->update($uzivatel->id, array('facebookId' => $userProfile['id']));
					$this->flashMessage('Uživatelský účet *'.$uzivatel->email.'* byl spojen s účtem na Facebooku.', 'ok');
					$this->prihlas(array('facebookId' => $userProfile['id']));
				}
				else
				{
					$dataDoDB = array('email' => $userProfile['email'], 'jmeno' => $userProfile['firstName'], 'prijmeni' => $userProfile['lastName'], 'facebookId' => $userProfile['id'], 'pohlavi' => ($userProfile['gender'] == 'male' ? 'muz' : 'zena'));
					$this->model->insert($dataDoDB);
					$id = $this->model->insertedId();
					$this->redirect('Uzivatele:edit', $id);
				}
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Nepodařilo se provést úpravu údajů uživatelského účtu.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}
			catch (FacebookApiException $e)
			{
				$this->flashMessage('Nepodařilo se přihlásit pomocí Facebook účtu.', 'warning');
				$user = null;
			}
		}
		else
		{
			$this->flashMessage('Nepodařilo se načíst údaje Facebook účtu.', 'warning');
		}
	}

}
