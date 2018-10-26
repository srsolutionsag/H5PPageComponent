<?php

require_once __DIR__ . "/../../../../Repository/RepositoryObject/H5P/vendor/autoload.php";
require_once __DIR__ . "/../vendor/autoload.php";

use srag\DIC\DICTrait;
use srag\Plugins\H5P\Content\Content;
use srag\Plugins\H5P\Content\Editor\EditContentFormGUI;
use srag\Plugins\H5P\Utils\H5PTrait;

/**
 * Class ilH5PPageComponentPluginGUI
 *
 * @author            studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 *
 * @ilCtrl_isCalledBy ilH5PPageComponentPluginGUI: ilPCPluggedGUI
 */
class ilH5PPageComponentPluginGUI extends ilPageComponentPluginGUI {

	use DICTrait;
	use H5PTrait;
	const PLUGIN_CLASS_NAME = ilH5PPageComponentPlugin::class;
	const CMD_CANCEL = "cancel";
	const CMD_CREATE = "create";
	const CMD_CREATE_PLUG = "create_plug";
	const CMD_EDIT = "edit";
	const CMD_INSERT = "insert";
	const CMD_UPDATE = "update";


	/**
	 * ilH5PPageComponentPluginGUI constructor
	 */
	public function __construct() {
		if (ILIAS_VERSION_NUMERIC >= "5.3") {
			parent::__construct();
		}
	}


	/**
	 *
	 */
	public function executeCommand() {
		$next_class = self::dic()->ctrl()->getNextClass($this);

		switch (strtolower($next_class)) {
			default:
				$cmd = self::dic()->ctrl()->getCmd();

				switch ($cmd) {
					case self::CMD_CANCEL:
					case self::CMD_CREATE;
					case self::CMD_EDIT:
					case self::CMD_INSERT;
					case self::CMD_UPDATE;
						$this->{$cmd}();
						break;

					default:
						break;
				}
				break;
		}
	}


	/**
	 * @return EditContentFormGUI
	 */
	protected function getEditorForm() {
		$properties = $this->getProperties();
		$h5p_content = Content::getContentById($properties["content_id"]);

		self::dic()->ctrl()->setParameterByClass(H5PActionGUI::class, "ref_id", filter_input(INPUT_GET, "ref_id")); // Fix async url

		$form = self::h5p()->show_editor()->getEditorForm($h5p_content, $this, self::CMD_CREATE_PLUG, self::CMD_UPDATE, self::CMD_CANCEL);

		//self::addCreationButton($form);

		return $form;
	}


	/**
	 *
	 */
	public function insert() {
		$this->edit();
	}


	/**
	 *
	 */
	public function create() {
		$form = $this->getEditorForm();

		$form->setValuesByPost();

		if (!$form->checkInput()) {
			self::plugin()->output($form);

			return;
		}

		$h5p_content = self::h5p()->show_editor()->createContent($form);

		$h5p_content->setParentType("page");
		$h5p_content->setObjId(0); // No id linked to page component required. Parent type is enough.

		$h5p_content->store();

		$properties = [
			"content_id" => $h5p_content->getContentId()
		];
		$this->createElement($properties);

		$this->returnToParent();
	}


	/**
	 *
	 */
	public function edit() {
		$form = $this->getEditorForm();

		self::plugin()->output($form);
	}


	/**
	 *
	 */
	public function update() {
		$form = $this->getEditorForm();

		$form->setValuesByPost();

		if (!$form->checkInput()) {
			self::plugin()->output($form);

			return;
		}

		$properties = $this->getProperties();
		$h5p_content = Content::getContentById($properties["content_id"]);

		self::h5p()->show_editor()->updateContent($h5p_content, $form);

		$this->updateElement($properties);

		$this->returnToParent();
	}


	/**
	 *
	 */
	public function cancel() {
		$this->returnToParent();
	}


	/**
	 * @param string $a_mode
	 * @param array  $a_properties
	 * @param string $plugin_version
	 *
	 * @return string
	 */
	public function getElementHTML($a_mode, array $a_properties, $plugin_version) {
		$h5p_content = Content::getContentById($a_properties["content_id"]);

		self::dic()->ctrl()->setParameterByClass(H5PActionGUI::class, "ref_id", filter_input(INPUT_GET, "ref_id")); // Fix async url

		if ($h5p_content !== NULL) {
			return self::h5p()->show_content()->getH5PContentIntegration($h5p_content);
		} else {
			return self::plugin()->translate("pchfp_content_not_exists");
		}
	}
}
