<?php
 /**
  * Souradnice input control
  *
  * @author    Milan Pála
  */

 //require_once(LIBS_DIR.'/Nette/Forms/Controls/TextInput.php');

 class SouradniceInput extends /*Nette\Forms\*/TextInput
 {
   private $sirkaInput;
   private $delkaInput;

	 /**
    * Konstruktor
    *
    * @access public
    *
    * @param string $label label
    * @param int $cols šířka elementu input
    * @param int $maxLenght parametr maximální počet znaků
    */
   public function __construct($label, $sirkaInput, $delkaInput)
   {
	parent::__construct($label);
	$this->sirkaInput = $sirkaInput;
	$this->delkaInput = $delkaInput;
   }

   /**
    * Vrácení hodnoty pole
    *
    * @access public
    *
    * @return mixed
    */
   public function getValue()
   {
	if (strlen($this->value))
	{
	  $tmp = explode(' ', $this->value);
	  $date = explode('.', $tmp[0]);

	  // Formát pro databázi: Y-m-d H:i:s
	  // Doplněny zavináče (nemají-li pole přesný počet prvků, docházelo k varování)
	  return @$date[2].'-'.@$date[1].'-'.@$date[0].' '.@$tmp[1];
	}

	return $this->value;
   }

   /**
    * Nastavení hodnoty pole
    *
    * @access public
    *
    * @param string $value hodnota
    *
    * @return void
    */
   public function setValue($value)
   {
	$value = preg_replace('~([0-9]{4})-([0-9]{2})-([0-9]{2})~', '$3.$2.$1', $value);

	parent::setValue($value);
   }

   /**
    * Generování HTML elementu
    *
    * @access public
    *
    * @return Html
    */
   public function getControl()
   {
	$control = parent::getControl();


	$el = Html::el('div');
	$el->create('div')->id('mapa')->class('vyber-polohy');
	$el->create('div')->add('<script type="text/javascript" src="http://api4.mapy.cz/loader.js"></script>
<script type="text/javascript">Loader.load();</script>
<script lang="text/javascript">
	var delka = JAK.gel("'.$this->delkaInput->getHtmlId().'").value != 0 ? JAK.gel("'.$this->delkaInput->getHtmlId().'").value : 132620048;
	var sirka = JAK.gel("'.$this->sirkaInput->getHtmlId().'").value != 0 ? JAK.gel("'.$this->sirkaInput->getHtmlId().'").value : 137499856;
	var center = SMap.Coords.fromPP(delka, sirka);
	var m = new SMap(JAK.gel("mapa"), center, 13);
	m.addDefaultLayer(SMap.DEF_OPHOTO);
	m.addDefaultLayer(SMap.DEF_BASE).enable();
	m.addDefaultControls();
	var mouse = new SMap.Control.Mouse(SMap.MOUSE_PAN | SMap.MOUSE_WHEEL | SMap.MOUSE_ZOOM); /* Ovládání myší */
	m.addControl(mouse);

	function move(e) {
		var coords = m.getCenter();
		JAK.gel("'.$this->delkaInput->getHtmlId().'").value = coords.toPP()[0];
		JAK.gel("'.$this->sirkaInput->getHtmlId().'").value = coords.toPP()[1];
	}

	var signals = m.getSignals();
	signals.addListener(window, "map-redraw", move);

	var layer = new SMap.Layer.HUD();
	m.addLayer(layer);
	layer.enable();

	var crosshair = JAK.mel("div");
	var img = JAK.mel("img", {}, {left:"-16px", top:"-16px", position:"absolute"});
	img.src = SMap.CONFIG.img + "/crosshair.gif";
	crosshair.appendChild(img);
	layer.addItem(crosshair, {left:"50%", top:"50%"});

	var layerSwitch = new SMap.Control.Layer();
	layerSwitch.addDefaultLayer(SMap.DEF_BASE);
	layerSwitch.addDefaultLayer(SMap.DEF_OPHOTO);
	m.addControl(layerSwitch,{left:"8px", top:"9px"});
</script>
');

	return $el;
   }
 }
?>