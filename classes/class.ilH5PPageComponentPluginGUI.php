<?php
require_once "Services/COPage/classes/class.ilPageComponentPluginGUI.php";
require_once "Customizing/global/plugins/Services/Repository/RepositoryObject/H5P/classes/H5P/class.ilH5P.php";

/**
 * H5P Page Component GUI
 *
 * @ilCtrl_isCalledBy ilH5PPageComponentPluginGUI: ilPCPluggedGUI
 */
class ilH5PPageComponentPluginGUI extends ilPageComponentPluginGUI {

	const CMD_CANCEL = "cancel";
	const CMD_CREATE = "create";
	const CMD_CREATE_PLUG = "create_plug";
	const CMD_EDIT = "edit";
	const CMD_INSERT = "insert";
	const CMD_UPDATE = "update";
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilH5P
	 */
	protected $h5p;
	/**
	 * Fix autocomplete (Defined in parent)
	 *
	 * @var ilH5PPageComponentPlugin
	 */
	protected $plugin;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;


	function __construct() {
		global $DIC;

		$this->ctrl = $DIC->ctrl();
		$this->h5p = ilH5P::getInstance();
		$this->tpl = $DIC->ui()->mainTemplate();
	}


	/**
	 *
	 */
	function executeCommand() {
		$next_class = $this->ctrl->getNextClass($this);

		switch ($next_class) {
			default:
				$cmd = $this->ctrl->getCmd();

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
	 * @param string $html
	 */
	protected function show($html) {
		if ($this->ctrl->isAsynch()) {
			echo $html;

			exit();
		} else {
			$this->tpl->setContent($html);
		}
	}


	/**
	 * @return ilPropertyFormGUI
	 */
	protected function getEditorForm() {
		$properties = $this->getProperties();
		$h5p_content = ilH5PContent::getContentById($properties["content_id"]);

		$this->ctrl->setParameterByClass(ilH5PActionGUI::class, "ref_id", filter_input(INPUT_GET, "ref_id")); // Fix async url

		$form = $this->h5p->show_editor()->getEditorForm($h5p_content, $this, self::CMD_CREATE_PLUG, self::CMD_UPDATE, self::CMD_CANCEL);

		//self::addCreationButton($form);

		return $form;
	}


	/**
	 *
	 */
	function insert() {
		$this->edit();
	}


	/**
	 *
	 */
	function create() {
		$form = $this->getEditorForm();

		$form->setValuesByPost();

		if (!$form->checkInput()) {
			$this->show($form->getHTML());

			return;
		}

		$h5p_content = $this->h5p->show_editor()->createContent($form);

		$h5p_content->setParentType("page");
		$h5p_content->setObjId(0); // No id linked to page component required. Parent type is enough.

		$h5p_content->update();

		$properties = [
			"content_id" => $h5p_content->getContentId()
		];
		$this->createElement($properties);

		$this->returnToParent();
	}


	/**
	 *
	 */
	function edit() {
		$form = $this->getEditorForm();

		$this->show($form->getHTML());
	}


	/**
	 *
	 */
	function update() {
		$form = $this->getEditorForm();

		$form->setValuesByPost();

		if (!$form->checkInput()) {
			$this->show($form->getHTML());

			return;
		}

		$properties = $this->getProperties();
		$h5p_content = ilH5PContent::getContentById($properties["content_id"]);

		$this->h5p->show_editor()->updateContent($h5p_content, $form);

		$this->updateElement($properties);

		$this->returnToParent();
	}


	/**
	 *
	 */
	function delete() {
		// The h5p page component contents will be deleted with the H5P cronjob

		/*
		$properties = $this->getProperties();
		$h5p_content = ilH5PContent::getContentById($properties["content_id"]);

		if ($h5p_content !== NULL) {
			$this->h5p->show_editor()->deleteContent($h5p_content);
		}
		*/
	}


	/**
	 *
	 */
	function copy() {
		// TODO Copy content on page component copy
	}


	/**
	 *
	 */
	function paste() {
		// TODO Paste content on page component paste
	}


	/**
	 *
	 */
	function cancel() {
		$this->returnToParent();
	}


	/**
	 * @param string $a_mode
	 * @param array  $a_properties
	 * @param string $plugin_version
	 *
	 * @return string
	 */
	function getElementHTML($a_mode, array $a_properties, $plugin_version) {
		$h5p_content = ilH5PContent::getContentById($a_properties["content_id"]);

		$this->ctrl->setParameterByClass(ilH5PActionGUI::class, "ref_id", filter_input(INPUT_GET, "ref_id")); // Fix async url

		if ($h5p_content !== NULL) {
			return $this->h5p->show_content()->getH5PContentIntegration($h5p_content);
		} else {
			return $this->plugin->txt("pchfp_content_not_exists");
		}
	}


	/**
	 * @param string $a_var
	 *
	 * @return string
	 */
	protected function txt($a_var) {
		return ilH5PPlugin::getInstance()->txt($a_var);
	}
}
