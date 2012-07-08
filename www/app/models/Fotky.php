<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model fotografií
 *
 * @author	Milan Pála
 */
class Fotky extends BaseSoubory implements IUdrzba
{

	public function __construct(HttpUploadedFile $soubor = NULL)
	{
		parent::__construct($soubor);
	}

	public function find($id)
	{
		return parent::find($id)->select('id_souvisejiciho AS id_fotogalerie');
	}

	public function findAll()
	{
		return parent::findAll()->where(array('souvisejici' => 'fotogalerie'));
	}

	public function findBySouvisejici($id, $souvisejici = 'fotogalerie')
	{
		return parent::findBySouvisejici($id, $souvisejici)->select('id_souvisejiciho AS id_fotogalerie')->orderBy('[soubory].[soubor], [soubory].[pripona]');
	}

	public function findByFotogalerie($id)
	{
		return $this->findBySouvisejici($id);
	}

	public function uloz($id_souvisejiciho, $souvisejici = 'fotogalerie')
	{
		$fotka = $this->soubor->getImage()->resize(800, 600);

		$nahled = $this->soubor->getImage()->resize(180, 135);

		$casti = pathinfo( $this->soubor->getName() );
		$this->insert(array('souvisejici' => $souvisejici, 'id_souvisejiciho' => $id_souvisejiciho, 'soubor' => $casti['filename'], 'pripona' => $casti['extension'], 'id_autora' => $this->id_autora, 'datum_pridani%sql' => 'NOW()'));
		$id_souboru = $this->lastInsertedId();

		if( $fotka->save($this->cestaKsouborum.$id_souboru.'.'.$casti['extension']) == false ) throw new Exception('Soubor '.$this->soubor->getName().' se nepodařilo uložit.');
		if( $nahled->save($this->cestaKsouborum.'nahled/'.$id_souboru.'.'.$casti['extension']) == false ) throw new Exception('Soubor '.$this->soubor->getName().' se nepodařilo uložit.');

		return $id_souboru;
	}

	/**
	 *
	 * @param type $id ID fotogalerie
	 * @return type
	 */
	public function findRandomFromGalerie($id)
	{
		return $this->findByFotogalerie($id)->orderBy('RAND()');
	}

	public function deleteByFotogalerie($id)
	{
		return parent::delete(NULL)->clause('where', true)->where('[soubory].[souvisejici] = "fotogalerie" AND [soubory].[id_souvisejiciho] = %i', $id)->execute();
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		$data = $this->constructUri($id, $data);
		$urlsModel = new Urls;
		$urlsModel->setUrl('Fotky', 'fotka', $id, $data['uri']);

		return $ret;
	}

	public function update($id, array $data)
	{
		parent::update($id, $data)->execute();
		$data = $this->constructUri($id, $data);
		$urlsModel = new Urls;
		$urlsModel->setUrl('Fotky', 'fotka', $id, $data['uri']);

	}

	public function delete($id)
	{
		return parent::delete($id)->execute();
	}

	public function findIdByUri($uri, $column)
	{
		return $this->connection->select('[id]')
			   ->from($this->table)
				->where('%n = %s', $column, $uri);
	}

	private function constructUri($id, $data)
	{
		if( isset($data['soubor']) && isset($data['pripona']) && isset($data['id_souvisejiciho']) )
		{
			$urlsModel = new Urls;
			$url = (array)$urlsModel->findUrlByPresenterAndActionAndParam('Fotogalerie', 'fotogalerie', $data['id_souvisejiciho'])->fetch();
			$data['uri'] = $url['url'].$id.'-'.Texy::webalize($data['soubor']).'-'.Texy::webalize($data['pripona']);
		}
		return $data;
	}

	public function udrzba()
	{
		$fotky = $this->findAll();
		foreach($fotky as $fotka)
		{
			$this->update($fotka['id'], array('soubor' => $fotka['soubor'], 'pripona' => $fotka['pripona'], 'id_souvisejiciho' => $fotka['id_souvisejiciho']));
		}
	}
}
