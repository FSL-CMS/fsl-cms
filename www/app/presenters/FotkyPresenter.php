<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Presenter fotek
 *
 * @author	Milan Pála
 */
class FotkyPresenter extends BasePresenter
{
	private $model;

	protected function startup()
	{
		$this->model = new Fotky;
		parent::startup();
	}

	public function actionFotka($id)
	{
		$fotka = $this->model->find($id)->fetch();

		if( $fotka === false ) throw new BadRequestException('Fotografie nebyla nalezena');

		$soubor = APP_DIR.'/../data/'.$fotka['id'].'.'.$fotka['pripona'];

		if( !file_exists($soubor) ) throw new BadRequestException('Fotografie nebyla nalezena');

		$this->model->noveStazeni($id);

		$rozmery = getimagesize($soubor);
		session_cache_limiter('public');
		$last_modified_time = strtotime( $fotka['datum_pridani'] );

		$res = Environment::getHttpResponse();
		$req = Environment::getHttpRequest();

		$res->setHeader('Cache-Control', 'public');
		$res->setHeader('Pragma', 'public'); // Fix for IE - Content-Disposition
		$res->setHeader('Expires', gmdate("D, d M Y H:i:s", strtotime('+1 month') )." GMT");
		$res->setHeader("Last-Modified", gmdate("D, d M Y H:i:s", $last_modified_time)." GMT");
		$res->setHeader("Accept-Ranges", "bytes");
		$res->setHeader('Content-Type', $rozmery['mime'] );


		/*$etag = md5($soubor.filemtime($soubor).filesize($soubor)).'"';
		$res->setHeader('ETag', '"'.$etag);

		//if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && isset($_SERVER['HTTP_IF_NONE_MATCH']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag )
		if( strtotime($req->getHeader('If-Modified-Since')) == $last_modified_time && trim($req->getHeader('If-None-Match')) == $etag )
		{
			$res->setHeader("HTTP/1.1",  "304 Not Modified");
			$this->terminate();
		}
		else
		{
			$res->setHeader("HTTP/1.1", "200 OK");
			//throw new BadRequestException('milan');
		}*/

		//$res->setHeader('Content-Disposition', 'filename="'.$fotka['soubor'].'.'.$fotka['pripona'].'"');
		//$res->setHeader('Content-Description', 'File Transfer');
		//$res->setHeader('Content-Transfer-Encoding', 'binary');
		//$res->setHeader('Connection', 'close');
		$res->setHeader('Content-Length', filesize($soubor));

		//$req->getHeader('If-None-Match')

		readfile($soubor);
		exit();
	}

	public function actionNahled($id)
	{
		$fotka = $this->model->find($id)->fetch();

		if( $fotka === false ) throw new BadRequestException('Fotografie nebyla nalezena');

		$soubor = APP_DIR.'/../data/nahled/'.$fotka['id'].'.'.$fotka['pripona'];

		if( !file_exists($soubor) ) throw new BadRequestException('Fotografie nebyla nalezena');

		$this->model->noveStazeni($id);

		$rozmery = getimagesize($soubor);
		session_cache_limiter('public');
		$last_modified_time = strtotime( $fotka['datum_pridani'] );

		$res = Environment::getHttpResponse();
		$req = Environment::getHttpRequest();

		$res->setHeader('Expires', gmdate("D, d M Y H:i:s", strtotime('+1 month') )." GMT");
		$res->setHeader('Cache-Control', 'public');
		$res->setHeader('Pragma', 'public'); // Fix for IE - Content-Disposition
		$res->setHeader("Last-Modified", gmdate("D, d M Y H:i:s", $last_modified_time)." GMT");
		$res->setHeader('Content-Disposition', '; filename="'.$fotka['soubor'].'.'.$fotka['pripona'].'"');
		//$res->setHeader('Content-Description', 'File Transfer');
		$res->setHeader('Content-Transfer-Encoding', 'binary');
		$res->setHeader('Connection', 'close');
		$etag = md5($soubor.filemtime($soubor).filesize($soubor)).'"';
		$res->setHeader('ETag', '"'.$etag);
		$res->setHeader('Content-Length', filesize($soubor));
		$res->setHeader('Content-Type', $rozmery['mime'] );

		//$req->getHeader('If-None-Match')

		//if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && isset($_SERVER['HTTP_IF_NONE_MATCH']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag )
		if( strtotime($req->getHeader('If-Modified-Since')) == $last_modified_time && trim($req->getHeader('If-None-Match')) == $etag )
		{
			$res->setHeader("HTTP/1.1",  "304 Not Modified");
			$this->terminate();
		}
		else
		{
			$res->setHeader("HTTP/1.1", "200 OK");
			//throw new BadRequestException('milan');
		}
		readfile($soubor);
		$this->terminate();
	}

	public function renderEdit($id)
	{
		$this['editForm']->setDefaults($this->model->find($id)->fetch());

		$this->setTitle('Úprava fotky');
	}

	public function createComponentEditForm()
	{
		$form = new AppForm;
		$uzivatele = new Uzivatele;

		$form->addText('nazev', 'Popis fotky');
		$form->addSelect('id_autora', 'Autor', $uzivatele->findAllToSelect()->fetchPairs('id', 'uzivatel'));

		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(false);

		$form->onSubmit[] = array($this, 'editFormSubmitted');

		return $form;
	}

	public function editFormSubmitted(AppForm $form)
	{
		$id = (int)$this->getParam('id');

		if( $form['save']->isSubmittedBy() )
		{
			try
			{
				$data = array( 'nazev' => $form['nazev']->value, 'id_autora' => $form['id_autora']->value );
				$this->model->update($id, $data);

				$this->flashMessage('Informace o fotce byly úspěšně uloženy.');
			}
			catch(Exception $e)
			{
				$this->flashMessage('Nepodařilo se uložit informace o souboru "'.$file->getName().'".');
			}
		}

		$this->redirect('Fotogalerie:fotka', $id);
	}

	public function handleSmazat($id)
	{
		$fotka = $this->model->find($id)->fetch();

		$this->model->delete($id);
		$this->flashMessage('Fotka byla odstraněna.');
		$this->invalidateControl('flashes');
		$this->invalidateControl('galerie');

		//if(!$this->isAjax()) $this->redirect('Fotogalerie:fotogalerie', $fotka['id_fotogalerie']);
		//else $this->forward('Fotogalerie:fotogalerie', $fotka['id_fotogalerie']);
	}

}
