<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Komponenta vykreslující Facebook Connect
 *
 * @author	Milan Pála
  */
class FacebookConnectControl extends BaseControl
{
	public $apiId = "119774764702049";
	private $apiSecret = "e3a689dd77880081fb9b4380da243f0f";

	public function handleXd_receiver()
	{
		
	}
	
	public function render()
	{
		$facebook = new Facebook(array(
			'appId'  => $this->apiId,
			'secret' => $this->apiSecret,
		));
		
		$this->template->fbconfig = array(
                'scope'         => 'email',
                'redirect_uri'  => $this->link('//facebookLogin!')
		);
		
		$this->template->facebook = $facebook;
		
		$uzivatele = new Uzivatele;
		if( $this->parent->user->getIdentity() !== NULL ) $uzivatel = $uzivatele->find($this->parent->user->getIdentity()->id)->fetch();

		if( $this->parent->user->isLoggedIn() !== false && $this->parent->user->getIdentity() !== NULL && !empty($uzivatel['facebookId']) ) $this->template->zobrazitLogin = false;
		else $this->template->zobrazitLogin = true;

		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/loginButton.phtml');

		$template->render();
	}
	
	public function handleFacebookLogin()
	{
		$uzivateleModel = new Uzivatele;
		// Create our Application instance (replace this with your appId and secret).
		$facebook = new Facebook(array(
			'appId'  => $this->apiId,
			'secret' => $this->apiSecret,
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
				
				$uzivatel = $uzivateleModel->findByEmail($userProfile['email'])->fetch();
				
				if( $uzivatel !== false && !empty($uzivatel->facebookId) )
				{
					if( $uzivatel->facebookId == $userProfile['id'] ) $this->getPresenter()->prihlas(array('facebookId' => $userProfile['id']));
					else
					{
						$uzivateleModel->update($uzivatel->id, array('facebookId' => $userProfile['id']));
						$this->getPresenter()->prihlas(array('facebookId' => $userProfile['id']));
					}
				}
				elseif( $uzivatel !== false && empty($uzivatel->facebookId) )
				{
					$uzivateleModel->update($uzivatel->id, array('facebookId' => $userProfile['id']));
					$this->getPresenter()->flashMessage('Uživatelský účet *'.$uzivatel->email.'* byl spojen s účtem na Facebooku.', 'ok');
					$this->getPresenter()->prihlas(array('facebookId' => $userProfile['id']));
				}
				else
				{
					$dataDoDB = array('email' => $userProfile['email'], 'jmeno' => $userProfile['firstName'], 'prijmeni' => $userProfile['lastName'], 'facebookId' => $userProfile['id'], 'pohlavi' => ($userProfile['gender'] == 'male' ? 'muz' : 'zena'));
					$uzivateleModel->insert($dataDoDB);
					$id = $uzivateleModel->insertedId();
					$this->redirect('Uzivatele:edit', $id);
				}
				
			}
			catch(DibiException $e)
			{
				$this->getPresenter()->flashMessage('Nepodařilo se provést úpravu údajů uživatelského účtu.', 'error');
				Debug::processException($e, true);
			}
			catch (FacebookApiException $e)
			{
				$this->getPresenter()->flashMessage('Nepodařilo se přihlásit pomocí Facebook účtu.', 'warning');
			}
		}
		else
		{
			$this->getPresenter()->flashMessage('Nepodařilo se načíst údaje Facebook účtu.', 'warning');
		}
		$this->redirect('this');
	}
}