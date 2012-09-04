<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Presenter souborů
 *
 * @author	Milan Pála
 */
class SouboryPresenter extends BasePresenter
{
	private $model;

	protected function startup()
	{
		$this->model = new Soubory;
		parent::startup();
	}

	public function renderDefault()
	{
		$this->template->galerie = array();
		$this->template->galerie['galerie'] = $this->model->findAll()->fetchAll();

		$this->template->galerie['muze_pridavat'] = $this->user->isAllowed('fotogalerie', 'edit');

		$this->setTitle('Fotogalerie');

		//$this->zpracujClanky();
	}

	public function renderVyber($souvisejici, $id_souvisejiciho)
	{
		$this->getPresenter()->setLayout(false);

		$this->template->soubory = array();
		$this->template->soubory['soubory'] = $this->model->findBySouvisejici($id_souvisejiciho, $souvisejici)->fetchAssoc('id,=');
	}

	public function actionSoubor($id)
	{
		$soubor = $this->model->find($id)->fetch();

		if( $soubor === false ) throw new BadRequestException('Soubor nebyl nalezen.');

		$cestaKsouboru = APP_DIR.'/../data/'.$soubor['id'].'.'.$soubor['pripona'];

		if( !file_exists($cestaKsouboru) ) throw new BadRequestException('Soubor nebyl nalezen.');

		$rozmery = getimagesize($cestaKsouboru);
		session_cache_limiter('public');
		$last_modified_time = strtotime( $soubor['datum_pridani'] );

		$res = Environment::getHttpResponse();
		$req = Environment::getHttpRequest();

		switch ($soubor['pripona']) {
			case "pdf": $mime="application/pdf"; break;
			case "zip": $mime="application/zip"; break;
			case "docx": $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'; break;
			case "doc": $mime="application/vnd.ms-word"; break;
			case "xlsx": $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; break;
			case "xls": $mime="application/vnd.ms-excel"; break;
			case "pptx": $mime = 'application/vnd.openxmlformats-officedocument.presentationml.slideshow'; break;
			case "ppt": $mime="application/vnd.ms-powerpoint"; break;
			case "gif": $mime="image/gif"; break;
			case "png": $mime="image/png"; break;
			case "jpe":
			case "jpeg":
			case "jpg": $mime="image/jpg"; break;
			case "wmv": $mime="video/x-ms-wmv"; break;
			default: $mime="application/octet-stream";
		}

		$res->setHeader('Expires', gmdate("D, d M Y H:i:s", strtotime('+1 month') )." GMT");
		$res->setHeader('Cache-Control', 'max-age=86400');
		$res->setHeader('Pragma', 'public'); // Fix for IE - Content-Disposition
		$res->setHeader("Last-Modified", gmdate("D, d M Y H:i:s", $last_modified_time)." GMT");
		$res->setHeader('Content-Disposition', '; filename="'.$soubor['soubor'].'.'.$soubor['pripona'].'"');
		//$res->setHeader('Content-Description', 'File Transfer');
		$res->setHeader('Content-Transfer-Encoding', 'binary');
		$res->setHeader('Connection', 'close');
		$etag = md5($cestaKsouboru.filemtime($cestaKsouboru).filesize($cestaKsouboru)).'"';
		$res->setHeader('ETag', '"'.$etag.'"');
		$res->setHeader('Content-Length', filesize($cestaKsouboru));
		$res->setHeader('Content-Type', $mime );


		//$req->getHeader('If-None-Match')

		//if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && isset($_SERVER['HTTP_IF_NONE_MATCH']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag )
		if( strtotime($req->getHeader('If-Modified-Since')) == $last_modified_time && trim($req->getHeader('If-Match')) == $etag )
		{
			$res->setHeader("HTTP/1.1",  "304 Not Modified");
			$this->terminate();
		}
		else
		{
			$res->setHeader("HTTP/1.1", "200 OK");
		}
		$this->model->noveStazeni($soubor['id']);
		readfile($cestaKsouboru);
		$this->terminate();
	}

	/*public function actionNahled($id)
	{
		$fotka = $this->model->find($id)->fetch();

		if( $fotka === false ) throw new BadRequestException('Fotografie nebyla nalezena');

		$soubor = APP_DIR.'/../data/nahled/'.$fotka['id'].'.'.$fotka['pripona'];

		if( !file_exists($soubor) ) throw new BadRequestException('Fotografie nebyla nalezena');

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
	}*/

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
