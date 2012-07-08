<?php

/**
 * @author Daniel Robenek
 * @license MIT
 */

// \Nette\Forms\FormContainer::extensionMethod("addJsonDependentSelectBox", "DependentSelectBox\JsonDependentSelectBox::formAddJsonDependentSelectBox");

class JsonDependentSelectBox extends DependentSelectBox {

	public static $jsonResoponseItems = array();

	public function submitButtonHandler($button) {
		parent::submitButtonHandler($button);
		if(Environment::getApplication()->getPresenter()->isAjax())
			$this->addJsonResponseItem($this);
	}
	
	protected function addJsonResponseItem($selectBox) {
		self::$jsonResoponseItems[] = $selectBox;
		if($selectBox instanceof DependentSelectBox)
			foreach($selectBox->childs as $child)
				$child->addJsonResponseItem($child);
	}
	
	public static function tryJsonResponse() {
		if(empty(self::$jsonResoponseItems))
			return;

		$payload = array(
			"type" => "JsonDependentSelectBoxResponse",
			"items" => array()
		);
		foreach(self::$jsonResoponseItems as $item) {
			$payload["items"][$item->getHtmlId()] = array(
				"selected" => $item->getValue(),
				"items" => $item->getItems()
			);
		}
		//$response = new JsonResponse($payload);
		
		//$presenter = Environment::getApplication()->getPresenter();
		$response = new JsonResponse($payload);
		Environment::getApplication()->getPresenter()->terminate($response);
	}

	public static function formAddJsonDependentSelectBox($_this, $name, $label, $parents, $dataCallback) {
		return $_this[$name] = new JsonDependentSelectBox($label, $parents, $dataCallback);
	}

}