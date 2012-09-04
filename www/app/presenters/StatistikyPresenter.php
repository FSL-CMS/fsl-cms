<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Presenter statistik
 *
 * @author	Milan Pála
 */
class StatistikyPresenter extends BasePresenter
{

	private $model = NULL;

	protected function startup()
	{
		$this->model = new Vysledky;

		parent::startup();
	}

	public function renderDefault()
	{
		$this->template->statistikyMenu = array(
			'vitezoveRocniku' => 'Vítězové ročníků',
			'nejrychlejsiCasy' => 'Nejrychlejší časy',
			'nejlepeBodovanaDruzstva' => 'Nejlépe bodovaná družstva',
			'prumerneCasy' => 'Průměrné časy sezón',
			'nejrychlejsiDrahy' => 'Nejrychlejší dráhy',
			//'poradaneZavody' => 'Počty pořádaných závodů'
		);

		$this->setTitle('Statistiky ligy');
	}

	/**
	 * Připraví průměrné časy sezón
	 */
	public function renderPrumerneCasy()
	{
		// Průměrné časy sezón
		$this->template->prumerneCasySezon = $this->model->prumerneCasySezon()->fetchAssoc('soutez,kategorie,typ,id,=');

		// vytvoření pole pro záhlaví tabulky s průměrnými časy sezón
		$this->template->prumerneCasySezon_zahlavi = array();
		foreach($this->template->prumerneCasySezon as $soutez => $foobar )
		{
			foreach($foobar as $kategorie => $foo )
			{
				$this->template->prumerneCasySezon_zahlavi[$soutez][0][0] = array('nazev' => 'Sezóna', 'sirka' => 1, 'vyska'=> 2);
				$this->template->prumerneCasySezon_zahlavi[$soutez][0][$kategorie] = array( 'nazev' => $kategorie, 'sirka' => count($foo), 'vyska' => 1 );
				foreach( $foo as $terce => $bar )
				{
					$this->template->prumerneCasySezon_zahlavi[$soutez][1][$kategorie.$terce] = array( 'nazev' => $terce, 'sirka' => 1, 'vyska' => 1);
				}
			}
		}

		$tmp = $this->template->prumerneCasySezon;
		$this->template->prumerneCasySezon = array();
		$this->template->prumerneCasySezon_oddilu = array();
		$kategorie = array();
		foreach( $tmp as $sou => $foobar )
		{
			$j=0;
			foreach( $foobar as $kat => $foo )
			{
				foreach( $foo as $terce => $bar )
				{
					if( !isset($kategorie[$sou][$kat.$terce]) ) $kategorie[$sou][$kat.$terce] = $j++;
					$maximum = 10000;
					foreach( $bar as $cas )
					{
						$casy = explode( ',', $cas['casy'] );
						if( count($casy) > 2 )
						{
							sort($casy);
							$median = $casy[(int)((count($casy)+1)/2.0)-1];
							$cas['prumer'] = $median;
							if( $cas['prumer'] <= $maximum )
							{
								$maximum = $cas['prumer'];
								$cas['maximum'] = true;
							}
							else $cas['maximum'] = false;
						}
						$this->template->prumerneCasySezon[$sou][$cas['rok']][$kategorie[$sou][$kat.$terce]] = array( 'prumer' => sprintf( "%.2f", $cas['prumer'] ) );

					}
				}

			}
			$this->template->prumerneCasySezon_oddilu[$sou] = count($kategorie[$sou]);
		}
		$this->setTitle('Průměrné časy sezón');
	}

	/**
	 * Připraví přehled nejlépe bodovaných družstev
	 */
	public function renderNejlepeBodovanaDruzstva()
	{
		// družstva s nejvíce body
		$this->template->nejviceBodovanaDruzstva = $this->model->nejviceBodovanaDruzstva()->fetchAssoc('soutez,kategorie,id,=');
		foreach( $this->template->nejviceBodovanaDruzstva as $soutez => $bar )
		{
			foreach($bar as $kategorie => $foo )
			{
				$i = 0; $j=1;
				$predchozi_body = 0;
				foreach( $foo as $id => $foobar )
				{
					$body = explode( ',', $foobar['body'] );
					if(count($body)>2)
					{
						sort($body);
						$median = $body[(int)((count($body)+1)/2.0)-1];
						$this->template->nejviceBodovanaDruzstva[$soutez][$kategorie][$id]['prumer'] = $median;
					}

					if( $predchozi_body != $this->template->nejviceBodovanaDruzstva[$soutez][$kategorie][$id]['celkem_bodu'] ) { $i+=$j; $j=1; }
					$this->template->nejviceBodovanaDruzstva[$soutez][$kategorie][$id]['poradi'] = $i;
					$this->template->nejviceBodovanaDruzstva[$soutez][$kategorie][$id]['prumer'] = sprintf( "%.0f", $this->template->nejviceBodovanaDruzstva[$soutez][$kategorie][$id]['prumer'] );
					if( $predchozi_body == $this->template->nejviceBodovanaDruzstva[$soutez][$kategorie][$id]['celkem_bodu'] ) $j++;
					$predchozi_body = $this->template->nejviceBodovanaDruzstva[$soutez][$kategorie][$id]['celkem_bodu'];
				}
			}
		}

		$this->setTitle('Nejlépe bodovaná družstva');
	}

	protected function seradPodlePrumeru($a, $b)
	{
		if($b['cas'] > $a['cas'] ) return 1;
		elseif($b['cas'] < $a['cas'] ) return -1;
		else return 0;
	}

	/**
	 * Připraví přehled nejrychlejší závodních drah
	 */
	public function renderNejrychlejsiDrahy()
	{
		$this->setTitle('Nejrychlejší dráhy');

		$drahyByCas = $this->model->nejrychlejsiDrahyByCas()->fetchAssoc('soutez,kategorie,terce,#,=');
		$this->template->nejrychlejsiDrahyByCas = array();
		$j=0;
		$kategorie = array();
		foreach( $drahyByCas as $soutez => $foo)
		{
			foreach( $drahyByCas[$soutez] as $kategorie => $bar)
			{
				foreach( $drahyByCas[$soutez][$kategorie] as $terce => $bar )
				{
					if( !isset($kategorie[$soutez.$kategorie.$terce]) ) $kategorie[$soutez.$kategorie.$terce] = $j++;
					$i=1;
					foreach( $drahyByCas[$soutez][$kategorie][$terce] as $draha )
					{
						// neřadí podle mediánu
						/*$casy = explode( ',', $draha['casy'] );
						if( count($casy) > 2 )
						{
							sort($casy);
							$median = $casy[(int)((count($casy)+1)/2.0)-1];
							$draha['prumer'] = $median;
						}*/
						$this->template->nejrychlejsiDrahyByCas[$soutez][$kategorie][$terce][$i] = array( 'poradi' => $i, 'obec' => $draha['obec'], 'cas' => sprintf( "%.2f", $draha['prumer'] ), 'pocet' => $draha['pocet_casu'] );
						$i++;
					}
				}
			}
		}
	}

	/**
	 * Připraví statistiku nejrychlejších časů
	 */
	public function renderNejrychlejsiCasy()
	{
		$this->template->nejrychlejsiCasy = $this->model->nejlepsiCasy()->fetchAssoc('soutez,kategorie,typ,id,=');
		foreach( $this->template->nejrychlejsiCasy as $soutez => $foobar )
		{
			foreach( $foobar as $kategorie => $foo )
			{
				foreach( $foo as $terce => $bar )
				{
					$this['toc']->add($soutez.'-'.$kategorie.'-'.$terce, $soutez.' - '.$kategorie.' - '.$terce);
					$i = 1;
					foreach( $bar as $vysledkyKategorie => $foofoobar )
					{
						$this->template->nejrychlejsiCasy[$soutez][$kategorie][$terce][$vysledkyKategorie]['poradi'] = $i++;
						$this->template->nejrychlejsiCasy[$soutez][$kategorie][$terce][$vysledkyKategorie]['vysledny_cas'] = sprintf("%.2f", $this->template->nejrychlejsiCasy[$soutez][$kategorie][$terce][$vysledkyKategorie]['vysledny_cas']);
					}
				}
			}
		}

		$this->setTitle('Nejrychlejší časy');
	}

	public function renderVitezoveRocniku()
	{
		$vysledky = $this->model->findVitezeRocniku()->fetchAssoc('soutez,rok,kategorie,#,=');
          $this->template->vitezoveRocniku = array();
          $this->template->zahlavi = array();
		foreach($vysledky as $soutez => $vysledkySouteze)
		{
			$this->template->zahlavi[$soutez][0]['sezona'] = array('nazev' => 'Sezóna', 'sirka' => 1, 'vyska' => 1);
			$kategorie = array();
			foreach( $vysledkySouteze as $rok => $foo )
			{
				foreach( $foo as $kat => $bar )
				{
					$kategorie[$kat] = $kat;
					$this->template->zahlavi[$soutez][0][$kat] = array( 'nazev' => $kat, 'sirka' => 1, 'vyska' => 1 );
					$this->template->vitezoveRocniku[$soutez][$rok][$kat] = $bar[0];
				}
			}

			foreach( $this->template->vitezoveRocniku[$soutez] as $rok => $foo )
			{
				foreach( $kategorie as $kat )
				{
					if( !isset($this->template->vitezoveRocniku[$soutez][$rok][$kat]) ) $this->template->vitezoveRocniku[$soutez][$rok][$kat] = array();
				}
			}
		}

		$this->setTitle('Vítězové jednotlivých ročníků');
	}

	public function renderPoradaneZavody()
	{
		$rocniky = new Rocniky;
		$poradane = $rocniky->statistikyPoradani()->fetchAssoc('rok,soutez,=');

          $this->template->poradaneZavody_zahlavi = array();

		$this->template->poradaneZavody_zahlavi[0][0] = 'Rok';
		foreach($poradane as $rok => $foobar)
		{
			foreach($poradane[$rok] as $soutez => $foo)
			{
				$this->template->poradaneZavody_zahlavi[0][$soutez] = $soutez;
			}
		}
		$this->template->poradaneZavody = $poradane;

		$this->setTitle('Počty pořádáných závodů');
	}

	public function actionGrafPoradaneZavody()
	{
		$rocniky = new Rocniky;
		$poradane = $rocniky->statistikyPoradani()->fetchPairs('rok','pocet');

		$data = array();
		foreach($poradane as $rok => $pocet)
		{
			$data[] = array('nazev' => (string)$rok, 'hodnota' => (float)$pocet);
		}

  		$grafy[] = array('nazev' => 'Počty pořádaných závodů', 'sirka' => 650, 'vyska' => 400, 'rady' => array( array('typ' => 'Line', 'nazev' => 'Pořádané závody', 'hodnoty' => $data) ) );

          $this->getHttpResponse()->setHeader('Content-type', 'text/plain');

		echo json_encode($grafy);
		$this->terminate();
	}

	public function actionGrafVitezoveRocniku()
	{
		$vysledky = $this->model->findVitezeRocniku()->fetchAssoc('rok,kategorie,#,=');
          $data = array();
		foreach( $vysledky as $rok => $foo )
		{
			foreach( $foo as $kategorie => $bar )
			{
				if( !isset($data[$kategorie][$bar[0]['druzstvo']]) ) $data[$kategorie][$bar[0]['druzstvo']] = array('nazev' => $bar[0]['druzstvo'], 'hodnota' => 1);
				else $data[$kategorie][$bar[0]['druzstvo']]['hodnota']++;
    			}
		}

  		foreach( $data as $kategorie => $foo )
		{
			$data2 = array();
			foreach( $foo as $bar )
			{
				$data2[] = $bar;
			}
               $grafy[] = array('nazev' => 'Vítězové ročníků v kategorii '.$kategorie, 'sirka' => 550, 'vyska' => 400, 'rady' => array( array('typ' => 'Pie', 'nazev' => 'Vítězové', 'hodnoty' => $data2) ) );
		}
          $this->getHttpResponse()->setHeader('Content-type', 'text/plain');

		echo json_encode($grafy);
		$this->terminate();
	}

}
