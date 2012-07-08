<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */

/**
 * Model anket
 *
 * @author	Milan Pála
 */
class FacebookDriver extends BaseModel
{

	/**
	 * @var The page id to edit
	 */
	private $page_id = '321953414503815';

	/**
	 * @var The back-end service for page's wall
	 */
	private $post_url = 'http://www.podripskaliga.cz/';
	private $facebook;
	private $page_access_token = '351226354949001|KWgOghov8MEcl0gj1LjvqLqlQrU';

	/**
	 * Constructor, sets the url's
	 */
	public function __construct()
	{
		$this->post_url = 'https://graph.facebook.com/' . $this->page_id . '/feed';
		$this->facebook = new Facebook(array(
				  'appId' => '351226354949001',
				  'secret' => '017e920dd36ae0638fb767cb6dc5752f',
			   ));
		//echo $this->facebook->getLogoutUrl();
	}

	public function getUser()
	{
		return $this->facebook->getUser();
	}

	public function getLoginUrl()
	{
		$par['scope'] = 'publish_stream,manage_pages';
		$par['display'] = 'page';
		return $this->facebook->getLoginUrl($par);
	}

	public function getLogoutUrl()
	{
		return $this->facebook->getLogoutUrl();
	}

	/**
	 * Manages the POST message to post an update on a page wall
	 *
	 * @param array $data
	 * @return string the back-end response
	 * @private
	 */
	public function publishLink($data, $page = true)
	{
		if($this->getUser() == 0) throw new FacebookNeedLoginException();

		$data['picture'] = 'http://www.podripskaliga.cz/images/logo-facebook-nahled.png';
		$data['description'] = 'Podřipská hasičská liga v požárním útoku';

		if($page == true)
		{
			$page_info = $this->facebook->api('/' . $this->page_id . '/?fields=access_token');
			if(!empty($page_info['access_token']))
			{
				$data['access_token'] = $page_info['access_token'];
				return $this->facebook->api($this->page_id . '/links', 'post', $data);
			}
			else
			{
				throw new FacebookNeedLogoutException();
			}
		} else return $this->facebook->api($this->page_id . '/links', 'post', $data);
	}

}
