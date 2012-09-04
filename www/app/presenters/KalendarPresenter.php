<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Presenter kalendáře
 *
 * @author	Milan Pála
 */
class KalendarPresenter extends BasePresenter
{
	/** Název kalendáře obsahující všechny závody ligy */
	private static $nazevKalendare;
	/** Popis kalendáře obsahující všechny závody ligy */
	private static $popisKalendare;
	/** Zkratka názvu ligy */
	private static $zkratkaLigy;

	protected function startup()
	{
		parent::startup();

		self::$nazevKalendare = self::$liga['nazev'];
		self::$popisKalendare = self::$liga['zkratka'].' - kalendář všech závodů';
		self::$zkratkaLigy = self::$liga['zkratka'];
	}

	/**
	 * Vrátí kalendář obsahující všechny závody
	 */
	public function actionLiga()
	{
		$zavodyModel = new Zavody;
		$zavody = $zavodyModel->findAll();

		// set a (site) unique id
		$config = array("unique_id" => $this->getHttpRequest()->getUri()->getHost());

		// create a new calendar instance
		$v = new vcalendar($config);

		// define time zone
		$tz = "Europe/Prague";

		// required of some calendar software
		$v->setProperty("method", "PUBLISH");
		$v->setProperty("x-wr-calname", self::$nazevKalendare);
		$v->setProperty("X-WR-CALDESC", self::$popisKalendare);

		$v->setProperty("X-PUBLISHED-TTL", 'PT7D');
		$v->setProperty("X-WR-TIMEZONE", $tz);
		$xprops = array("X-LIC-LOCATION" => $tz);
		// create timezone component(-s) opt. 1
		iCalUtilityFunctions::createTimezone($v, $tz, $xprops);

		foreach ($zavody as $zavod)
		{
			$this->pripravZavod($v, $zavod);
		}

		// redirect calendar file to browser
		$v->returnCalendar();
		$this->terminate();
	}

	/**
	 * Vrátí kalendář daného závodu
	 * @param int $id ID závodu.
	 * @throws BadRequestException
	 */
	public function actionZavod($id)
	{
		$zavodyModel = new Zavody;
		$zavod = $zavodyModel->find($id)->fetch();

		if($zavod === false) throw new BadRequestException();

		// set a (site) unique id
		$config = array("unique_id" => $this->getHttpRequest()->getUri()->getHost());

		// create a new calendar instance
		$v = new vcalendar($config);

		// define time zone
		$tz = "Europe/Prague";

		// required of some calendar software
		$v->setProperty("method", "PUBLISH");

		$v->setProperty("X-PUBLISHED-TTL", 'PT7D');
		$v->setProperty("X-WR-TIMEZONE", $tz);
		$xprops = array("X-LIC-LOCATION" => $tz);
		// create timezone component(-s) opt. 1
		iCalUtilityFunctions::createTimezone($v, $tz, $xprops);

		$this->pripravZavod($v, $zavod);

		// redirect calendar file to browser
		$v->returnCalendar();
		$this->terminate();
	}

	/**
	 * Vloží do kalendáře jeden závod.
	 * @param type $kalendar Objekt kalendáře.
	 * @param DibiRow $zavod Informace o závodu získané z DB.
	 */
	protected function pripravZavod(&$kalendar, $zavod)
	{
			$vevent = & $kalendar->newComponent("vevent");
			// create an event calendar component
			$start = array("year" => substr($zavod->datum, 0, 4), "month" => substr($zavod->datum, 5, 2), "day" => substr($zavod->datum, 8, 2), "hour" => substr($zavod->datum, 11, 2), "min" => substr($zavod->datum, 14, 2), "sec" => 0);
			$vevent->setProperty("dtstart", $start);

			$end = array("year" => substr($zavod->datum, 0, 4), "month" => substr($zavod->datum, 5, 2), "day" => substr($zavod->datum, 8, 2), "hour" => substr($zavod->datum, 11, 2) + 5, "min" => substr($zavod->datum, 14, 2), "sec" => 0);
			$vevent->setProperty("dtend", $end);

			$vevent->setProperty("LOCATION", $zavod->misto);

			$vevent->setProperty("summary", self::$zkratkaLigy.' '.$zavod->nazev);
			$vevent->setProperty("description", 'Oficiální stránka závodu s možností rezervace startovního pořadí a dalšími podrobnostmi na '.$this->getHttpRequest()->getUri()->getScheme().'://'.$this->getHttpRequest()->getUri()->getHost().$this->link('Zavody:zavod', $zavod->id));
			$vevent->setProperty("URL", $this->getHttpRequest()->getUri()->getScheme().'://'.$this->getHttpRequest()->getUri()->getHost().$this->link('Zavody:zavod', $zavod->id));
	}

}
