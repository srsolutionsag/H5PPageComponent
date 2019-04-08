<?php

use srag\DIC\H5P\DICStatic;
use srag\DIC\H5P\DICTrait;
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
	const PLUGIN_CLASS_NAME = ilH5PPlugin::class;
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
		parent::__construct();
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

		if (!$form->storeForm()) {
			self::output()->output($form);

			return;
		}

		$h5p_content = self::h5p()->show_editor()->createContent($form);

		$h5p_content->setParentType(Content::PARENT_TYPE_PAGE);
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

		self::output()->output($form);
	}


	/**
	 *
	 */
	public function update() {
		$form = $this->getEditorForm();

		if (!$form->storeForm()) {
			self::output()->output($form);

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
	    DICStatic::clearCache(); // Workaround fix learning module override global template
	    
		$h5p_content = Content::getContentById($a_properties["content_id"]);

		self::dic()->ctrl()->setParameterByClass(H5PActionGUI::class, "ref_id", filter_input(INPUT_GET, "ref_id")); // Fix async url

		if ($h5p_content !== null) {
			return self::h5p()->show_content()->getH5PContent($h5p_content);
		} else {
			return self::plugin()->translate("content_not_exists") . "<br>";
		}
	}
}
