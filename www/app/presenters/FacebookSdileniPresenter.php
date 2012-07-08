<?php

/**
 * Description of FacebookSdileni
 *
 * @author Milan
 */
class FacebookSdileniPresenter extends BasePresenter
{

	public function startup()
	{
		parent::startup();
	}

	public function actionDefault()
	{
		/* echo 'Inicializace';
		  $facebook = new FacebookDriver();

		  print_r($facebook->message(
		  array('message' => 'Testovací zpráva',
		  'link' => 'http://www.podripskaliga.cz/',
		  'picture' => 'http://www.podripskaliga.cz/images/logo-facebook-nahled.png',
		  'name' => 'Logo PHL',
		  'description' => 'Testovací automatická zpráva ze stránek PHL.')
		  ));
		  echo 'Konec';
		  $this->terminate(); */

		try
		{
			$facebook = new Facebook(array(
					  'appId' => '351226354949001',
					  'secret' => '017e920dd36ae0638fb767cb6dc5752f',
				   ));

			print_r($facebook);

			// Get User ID
			$user = $facebook->getUser();
			//print_r($user);

			$par['scope'] = "publish_stream";
			if($user)
			{

				$data = array('message' => 'Testovací zpráva');

				$page = $facebook->api('/321953414503815/feed', 'post', $data);

				var_dump($page);
			}
			else
			{
				$loginUrl = $facebook->getLoginUrl($par);
				print_r($loginUrl);
			}
		}
		catch (Exception $e)
		{
			print_r($e->getMessage());
		}

		$this->terminate();
	}

}
