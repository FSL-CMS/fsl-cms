<?php
/**
 * Select box control that allows single item selection.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @author	   Ondřej Hübsch, David Grudl
 * @package    Nette Extras
 * @license	   WTFPL
 *
 * @property-read mixed $rawValue
 * @property   array $items
 * @property-read mixed $selectedItem
 * @property-read bool $firstSkipped
 */
class AjaxSelectBox extends FormControl
{
	/** @deprecated */
	protected $items = array();

	/** @deprecated */
	protected $allowed = array();

	/** @deprecated */
	protected $skipFirst = FALSE;

	/** @deprecated */
	protected $useKeys = TRUE;

	/** @var SelectBox */
	private $parentSelect = NULL;


	/**
	 * @param  string  label
	 * @param  array   items from which to choose
	 * @param  int     number of rows that should be visible
	 */
	public function __construct($label = NULL, array $items = NULL,  $parentSelect = NULL, $size = NULL)
	{
		/** after private->protected: parent::__construct($label, $items, $size);*/

		/** @deprecated section */
		parent::__construct($label);
		$this->control->setName('select');
		$this->control->size = $size > 1 ? (int) $size : NULL;
		$this->control->onfocus = 'this.onmousewheel=function(){return false}';  // prevents accidental change in IE
		$this->label->onclick = 'document.getElementById(this.htmlFor).focus();return false';  // prevents deselect in IE 5 - 6
		if ($items !== NULL) {
			$this->setItems($items);
		}
		/** end of @deprecated section */
		
		$this->parentSelect = $parentSelect;
	}


	/**
	 * Returns selected item key.
	 * @return mixed
	 */
	public function getValue()
	{
		$allowed = $this->allowed;
		if ($this->skipFirst) {
			$allowed = array_slice($allowed, 1, count($allowed), TRUE);
		}
		return is_scalar($this->value) && isset($this->parentSelect->value) && isset($allowed[$this->parentSelect->value][$this->value]) ? $this->value : NULL;
	}

	/**
	 * Sets items from which to choose.
	 * @param  array
	 * @return SelectBox  provides a fluent interface
	 */
	public function setItems(array $items, $useKeys = TRUE)
	{
		$this->items = $items;
		$this->allowed = array();
		$this->useKeys = (bool) $useKeys;
		foreach ($items as $key => $value)
		{
			if(!is_array($value)) {
				throw new InvalidArgumentException("Bad format of items.");
			}
			
			$this->allowed[$key] = array();
			
			foreach($value as $key2 => $value2) {
			
				if (!is_array($value2)) {
					$value2 = array($key2 => $value2);
				}

				foreach ($value2 as $key3 => $value3) {
					if (!$this->useKeys) {
						if (!is_scalar($value3))	{
							throw new InvalidArgumentException("All items must be scalars.");
						}
						$key3 = $value3;
					}

					if (isset($this->allowed[$key][$key3])) {
						throw new InvalidArgumentException("Items contain duplication for key '$key3'.");
					}
					
					$this->allowed[$key][$key3] = $value3;
				}
			}
		}
		
		return $this;
	}

	/**
	 * Generates control's HTML element.
	 * @return Html
	 */
	public function getControl()
	{
		$this->setOption('rendered', TRUE);
		$selectedParent = isset($this->items[$this->parentSelect->value]) ? $this->parentSelect->value : NULL;

		if($selectedParent === NULL) {
			return NULL;
		}
		$control = clone $this->control;
		$control->name = $this->getHtmlName();
		$control->disabled = $this->disabled;
		$control->id = $this->getHtmlId();

		$selected = $this->getValue();
		$selected = is_array($selected) ? array_flip($selected) : array($selected => TRUE);
		$option = Html::el('option');
		if($selectedParent != NULL) {
			
			foreach($this->items[$selectedParent] as $key => $value) {
				if (!is_array($value)) {
					$value = array($key => $value);
					$dest = $control;

				} else {
					$dest = $control->create('optgroup')->label($key);
				}

				foreach ($value as $key2 => $value2) {
					if ($value2 instanceof Html) {
						$dest->add((string) $value2->selected(isset($selected[$key2])));

					} elseif ($this->useKeys) {
						$dest->add((string) $option->value($key2)->selected(isset($selected[$key2]))->setText($this->translate($value2)));

					} else {
						$dest->add((string) $option->selected(isset($selected[$value2]))->setText($this->translate($value2)));
					}
				}
			}
		}
		
		return $control;
	}

	/**
	 * Show select box control and label?
	 * @return bool
	 */
	public function showSelectBox()
	{
		if($this->parentSelect->value !== NULL)
			return TRUE;

		return FALSE;
	}

	/**
	 * Adds ajax select box control that allows single item selection.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   items from which to choose
	 * @param  int     number of rows that should be visible
	 * @return SelectBox
	 */
	public static function addAjaxSelect(FormContainer $_this, $name, $label = NULL, array $items = NULL, $parentSelect = NULL, $size = NULL)
	{
		return $_this[$name] = new AjaxSelectBox($label, $items, $parentSelect, $size);
	}


	/** @deprecated methods, until private -> protected change */
	
	/**
	 * Returns selected item key (not checked).
	 * @return mixed
	 * 
	 * @deprecated
	 */
	public function getRawValue()
	{
		return is_scalar($this->value) ? $this->value : NULL;
	}

	/**
	 * Ignores the first item in select box.
	 * @param  string
	 * @return SelectBox  provides a fluent interface
	 *
	 * @deprecated
	 */
	public function skipFirst($item = NULL)
	{
		if (is_bool($item)) {
			$this->skipFirst = $item;
		} else {
			$this->skipFirst = TRUE;
			if ($item !== NULL) {
				$this->items = array('' => $item) + $this->items;
				$this->allowed = array('' => '') + $this->allowed;
			}
		}
		return $this;
	}



	/**
	 * Is first item in select box ignored?
	 * @return bool
	 *
	 * @deprecated
	 */
	final public function isFirstSkipped()
	{
		return $this->skipFirst;
	}


	/**
	 * Are the keys used?
	 * @return bool
	 *
	 * @deprecated
	 */
	final public function areKeysUsed()
	{
		return $this->useKeys;
	}

	/**
	 * Returns items from which to choose.
	 * @return array
	 *
	 * @deprecated
	 */
	final public function getItems()
	{
		return $this->items;
	}

	/**
	 * Returns selected value.
	 * @return string
	 *
	 * @deprecated
	 */
	public function getSelectedItem()
	{
		if (!$this->useKeys) {
			return $this->getValue();

		} else {
			$value = $this->getValue();
			return $value === NULL ? NULL : $this->allowed[$value];
		}
	}

	/**
	 * Filled validator: has been any item selected?
	 * @param  IFormControl
	 * @return bool
	 *
	 * @deprecated
	 */
	public static function validateFilled(IFormControl $control)
	{
		$value = $control->getValue();
		return is_array($value) ? count($value) > 0 : $value !== NULL;
	}
}

FormContainer::extensionMethod('FormContainer::addAjaxSelect', array('AjaxSelectBox', 'addAjaxSelect'));