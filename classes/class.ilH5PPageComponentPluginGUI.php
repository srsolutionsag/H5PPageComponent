<?php

require_once __DIR__ . "/../vendor/autoload.php";

use srag\DIC\H5P\DICTrait;
use srag\Plugins\H5P\Content\Content;
use srag\Plugins\H5P\Content\Editor\EditContentFormGUI;
use srag\Plugins\H5P\Content\Editor\ImportContentFormGUI;
use srag\Plugins\H5P\Utils\H5PTrait;

/**
 * Class ilH5PPageComponentPluginGUI
 *
 * @author            studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 *
 * @ilCtrl_isCalledBy ilH5PPageComponentPluginGUI: ilPCPluggedGUI
 */
class ilH5PPageComponentPluginGUI extends ilPageComponentPluginGUI
{

    use DICTrait;
    use H5PTrait;

    const CMD_CANCEL = "cancel";
    const CMD_CREATE = "create";
    const CMD_CREATE_PLUG = "create_plug";
    const CMD_EDIT = "edit";
    const CMD_EXPORT = "export";
    const CMD_INSERT = "insert";
    const CMD_UPDATE = "update";
    const PARAM_IMPORT = "h5p_import";
    const PLUGIN_CLASS_NAME = ilH5PPlugin::class;


    /**
     * ilH5PPageComponentPluginGUI constructor
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     *
     */
    public function cancel()/*:void*/
    {
        $this->returnToParent();
    }


    /**
     * @inheritDoc
     */
    public function create()/*:void*/
    {
        if ($this->shouldImport()) {
            $form = $this->getImportContentForm();
        } else {
            $form = $this->getEditorForm();
        }

        if (!$form->storeForm()) {
            self::output()->output($form);

            return;
        }

        if ($this->shouldImport()) {
            $h5p_content = self::h5p()->contents()->editor()->show()->importContent($form);
        } else {
            $h5p_content = self::h5p()->contents()->editor()->show()->createContent($form->getLibrary(), $form->getParams(), $form);
        }

        if (!$h5p_content) {
            self::output()->output($form);

            return;
        }

        $h5p_content->setParentType(Content::PARENT_TYPE_PAGE);
        $h5p_content->setObjId(0); // No id linked to page component required. Parent type is enough.

        self::h5p()->contents()->storeContent($h5p_content);

        $properties = [
            "content_id" => $h5p_content->getContentId()
        ];
        $this->createElement($properties);

        $this->returnToParent();
    }


    /**
     * @inheritDoc
     */
    public function edit()/*:void*/
    {
        $form = $this->getEditorForm();

        self::output()->output($form);
    }


    /**
     * @inheritDoc
     */
    public function executeCommand()/*:void*/
    {
        $next_class = self::dic()->ctrl()->getNextClass($this);

        switch (strtolower($next_class)) {
            default:
                $cmd = self::dic()->ctrl()->getCmd();

                switch ($cmd) {
                    case self::CMD_CANCEL:
                    case self::CMD_CREATE:
                    case self::CMD_EDIT:
                    case self::CMD_EXPORT:
                    case self::CMD_INSERT:
                    case self::CMD_UPDATE:
                        $this->{$cmd}();
                        break;

                    default:
                        break;
                }
                break;
        }
    }


    /**
     *
     */
    public function export()/*:void*/
    {
        $properties = $this->getProperties();
        $h5p_content = self::h5p()->contents()->getContentById(intval($properties["content_id"]));

        self::h5p()->contents()->editor()->show()->exportContent($h5p_content);
    }


    /**
     * @inheritDoc
     */
    public function getElementHTML(/*string*/ $a_mode, array $a_properties, /*string*/ $plugin_version) : string
    {
        // Workaround fix learning module override global template
        self::dic()->dic()->offsetUnset("tpl");
        self::dic()->dic()->offsetSet("tpl", $GLOBALS["tpl"]);

        $h5p_content = self::h5p()->contents()->getContentById(intval($a_properties["content_id"]));

        if ($h5p_content !== null) {
            return self::h5p()->contents()->show()->getH5PContent($h5p_content);
        } else {
            return self::plugin()->translate("content_not_exists") . "<br>";
        }
    }


    /**
     * @inheritDoc
     */
    public function insert()/*:void*/
    {
        if ($this->shouldImport()) {
            $form = $this->getImportContentForm();
        } else {
            $form = $this->getEditorForm();
        }

        self::output()->output($form);
    }


    /**
     *
     */
    public function update()/*:void*/
    {
        $form = $this->getEditorForm();

        if (!$form->storeForm()) {
            self::output()->output($form);

            return;
        }

        $properties = $this->getProperties();
        $h5p_content = self::h5p()->contents()->getContentById(intval($properties["content_id"]));

        self::h5p()->contents()->editor()->show()->updateContent($h5p_content, $form->getParams(), $form);

        $this->updateElement($properties);

        $this->returnToParent();
    }


    /**
     * @return EditContentFormGUI
     */
    protected function getEditorForm() : EditContentFormGUI
    {
        $properties = $this->getProperties();
        $h5p_content = self::h5p()->contents()->getContentById(intval($properties["content_id"]));

        if ($h5p_content !== null) {
            self::dic()->toolbar()->addComponent(self::dic()->ui()->factory()->button()->standard(self::plugin()
                ->translate("export_content"), self::dic()->ctrl()->getLinkTarget($this, self::CMD_EXPORT)));
        } else {
            self::dic()->ctrl()->setParameter($this, self::PARAM_IMPORT, true);
            self::dic()->toolbar()->addComponent(self::dic()->ui()->factory()->button()->standard(self::plugin()
                ->translate("import_content"), self::dic()->ctrl()->getLinkTarget($this, self::CMD_INSERT)));
            self::dic()->ctrl()->setParameter($this, self::PARAM_IMPORT, null);
        }

        $form = self::h5p()->contents()->editor()->factory()->newEditContentFormInstance($this, $h5p_content, self::CMD_CREATE_PLUG, self::CMD_UPDATE, self::CMD_CANCEL);

        //self::addCreationButton($form);

        return $form;
    }


    /**
     * @return ImportContentFormGUI
     */
    protected function getImportContentForm() : ImportContentFormGUI
    {
        self::dic()->ctrl()->saveParameter($this, self::PARAM_IMPORT);

        $form = self::h5p()->contents()->editor()->factory()->newImportContentFormInstance($this, self::CMD_CREATE, self::CMD_CANCEL);

        return $form;
    }


    /**
     * @return bool
     */
    protected function shouldImport() : bool
    {
        return boolval(filter_input(INPUT_GET, self::PARAM_IMPORT));
    }
}
