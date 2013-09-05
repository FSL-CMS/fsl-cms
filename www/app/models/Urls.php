<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */
use Nette\Caching\Cache;

/**
 * Model URL
 *
 * @author	Milan Pála
 */
class Urls extends BaseModel
{

	/** @var string */
	protected $table = 'urls';
	private $cache = null;

	public function getCache()
	{
		if($this->cache === null)
		{
			$cacheStorage = Nette\Environment::getService('cacheStorage');
			$this->cache = new Cache($cacheStorage, 'urlsRouter');
		}

		return $this->cache;
	}

	public function find($id)
	{
		return $this->findAll()->where('[id] = %i', $id);
	}

	public function findAll()
	{
		return $this->connection
						->select('*')
						->from($this->table);
	}

	public function findUrlByPresenterAndAction($presenter, $action)
	{
		//return $this->findAll()->where('[redirect] IS NULL AND [presenter] = %s AND [action] = %s AND [param] IS NULL', $presenter, $action)->fetch();
		$self = $this;
		$value = $this->getCache()->load(md5($presenter . $action), function() use ($self, $presenter, $action)
				{
					$value = $self->findAll()->where('[redirect] IS NULL AND [presenter] = %s AND [action] = %s AND [param] IS NULL', $presenter, $action)->fetch();
					$self->getCache()->save(md5($presenter . $action), $value);
					return $value;
				});

		return $value;
	}

	/**
	 * 
	 * @param string $presenter
	 * @param string $action
	 * @param int|string $param
	 * @return array
	 */
	public function findUrlByPresenterAndActionAndParam($presenter, $action, $param)
	{
		//return $this->findAll()->where('[redirect] IS NULL AND [presenter] = %s AND [action] = %s AND [param] = %i', $presenter, $action, $param)->fetch();
		$self = $this;
		$value = $this->getCache()->load(md5($presenter . $action . $param), function() use ($self, $presenter, $action, $param)
				{
					$value = $self->findAll()->where('[redirect] IS NULL AND [presenter] = %s AND [action] = %s AND [param] = %i', $presenter, $action, $param)->fetch();
					$self->getCache()->save(md5($presenter . $action . $param), $value);
					return $value;
				});

		return $value;
	}

	public function findByUrl($url)
	{
		return $this->findAll()->where('[redirect] IS NULL AND [url] = %s', $url);
	}

	public function findRedirectedByUrl($url)
	{
		return $this->findAll()->where('[redirect] IS NOT NULL AND [url] = %s', $url);
	}

	public function insert(array $data)
	{
		return parent::insert($data)->execute(dibi::IDENTIFIER);
	}

	public function update($id, array $data)
	{
		return parent::update($id, $data)->execute();
	}

	public function setUrl($presenter, $action, $param, $url)
	{
		$this->connection->begin();
		try
		{
			// provede doplnění pouze pokud nové URL zatím není v DB
			$byUrl = false;
			$byRequest = false;
			if(($byUrl = $this->findByUrl($url)->fetch()) == false || ($byRequest = $this->findUrlByPresenterAndActionAndParam($presenter, $action, $param)) == false || $byUrl['id'] != $byRequest['id'])
			{
				$redByUrl = $this->findRedirectedByUrl($url)->fetch();
				if($byUrl == false && $redByUrl == false) // nové URL, které ještě nebylo v DB
				{
					$this->insert(array('presenter%s' => $presenter, 'action%s' => $action, 'param%i' => $param, 'url%s' => $url));
					$id = $this->connection->insertId();
					$this->connection->query('UPDATE %n SET [redirect] = %i WHERE [presenter] = %s AND [action] = %s AND [param] = %i AND [id] != %i', $this->table, $id, $presenter, $action, $param, $id);
				}
				else // už stejné URL bylo zadáno, ale bylo časem přesměrováno
				{
					if($redByUrl != false) $this->update($redByUrl['id'], array('redirect%in' => 0));
					//if($byUrl != false) $this->update($byUrl['id'], array('redirect%in' => 0));
					$this->connection->query('UPDATE %n SET [redirect] = %i WHERE [presenter] = %s AND [action] = %s AND [param] = %i AND [id] != %i', $this->table, $redByUrl['id'], $presenter, $action, $param, $redByUrl['id']);
				}
			}
			$this->connection->commit();
			$this->getCache()->remove(md5($presenter.$action.$param));
		}
		catch (DibiException $e)
		{
			$this->connection->rollback();
			$this->flashMessage('Nepodařilo se nastavit URL adresu položky.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
	}

}
