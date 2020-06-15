<?php

require_once __DIR__ . "/../vendor/autoload.php";

use srag\DIC\H5P\DICTrait;
use srag\Plugins\H5P\Utils\H5PTrait;

/**
 * Class ilH5PPageComponentPlugin
 *
 * @author studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class ilH5PPageComponentPlugin extends ilPageComponentPlugin
{

    use DICTrait;
    use H5PTrait;

    const PLUGIN_CLASS_NAME = ilH5PPlugin::class;
    const PLUGIN_ID = "pchfp";
    const PLUGIN_NAME = "H5PPageComponent";
    /**
     * @var self|null
     */
    protected static $instance = null;


    /**
     * ilH5PPageComponentPlugin constructor
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @return self
     */
    public static function getInstance() : self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * @inheritDoc
     */
    public function getPluginName() : string
    {
        return self::PLUGIN_NAME;
    }


    /**
     * @inheritDoc
     */
    public function isValidParentType(/*string*/ $a_type) : bool
    {
        // Allow in all parent types
        return true;
    }


    /**
     * @inheritDoc
     */
    public function onClone(/*array*/ &$properties, /*string*/ $plugin_version)/*: void*/
    {
        $old_content_id = intval($properties["content_id"]);

        $h5p_content = self::h5p()->contents()->getContentById(intval($old_content_id));

        $h5p_content_copy = self::h5p()->contents()->cloneContent($h5p_content);

        self::h5p()->contents()->storeContent($h5p_content_copy);

        self::h5p()->contents()->editor()->storageCore()->copyPackage($h5p_content_copy->getContentId(), $h5p_content->getContentId());

        $properties["content_id"] = $h5p_content_copy->getContentId();

        if (ilSession::get(ilH5PPlugin::PLUGIN_NAME . "_cut_old_content_id_" . $old_content_id)) {
            ilSession::clear(ilH5PPlugin::PLUGIN_NAME . "_cut_old_content_id_" . $old_content_id);

            $this->onDelete(["content_id" => $old_content_id], $plugin_version);
        }
    }


    /**
     * @inheritDoc
     */
    public function onDelete(/*array*/ $properties, /*string*/ $plugin_version)/*: void*/
    {
        if (self::dic()->ctrl()->getCmd() !== "moveAfter") {
            if (self::dic()->ctrl()->getCmd() !== "cut") {
                $h5p_content = self::h5p()->contents()->getContentById(intval($properties["content_id"]));

                if ($h5p_content !== null) {
                    self::h5p()->contents()->editor()->show()->deleteContent($h5p_content);
                }
            } else {
                ilSession::set(ilH5PPlugin::PLUGIN_NAME . "_cut_old_content_id_" . intval($properties["content_id"]), true);
            }
        }
    }
}
