<?php

require_once __DIR__ . "/../../../../Repository/RepositoryObject/H5P/vendor/autoload.php";
require_once __DIR__ . "/../vendor/autoload.php";

use srag\Plugins\H5P\Content\Content;
use srag\Plugins\H5P\Utils\H5PTrait;

/**
 * Class ilH5PPageComponentPlugin
 *
 * @author studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class ilH5PPageComponentPlugin extends ilPageComponentPlugin {

	use H5PTrait;
	const PLUGIN_ID = "pchfp";
	const PLUGIN_NAME = "H5PPageComponent";
	const PLUGIN_CLASS_NAME = ilH5PPlugin::class;
	/**
	 * @var self|null
	 */
	protected static $instance = NULL;


	/**
	 * @return self
	 */
	public static function getInstance() {
		if (self::$instance === NULL) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * ilH5PPageComponentPlugin constructor
	 */
	public function __construct() {
		parent::__construct();
	}


	/**
	 * @return string
	 */
	public function getPluginName() {
		return self::PLUGIN_NAME;
	}


	/**
	 * @param string $a_type
	 *
	 * @return bool
	 */
	public function isValidParentType($a_type) {
		// Allow in all parent types
		return true;
	}


	/**
	 * @param array  $properties
	 * @param string $plugin_version
	 *
	 * @since ILIAS 5.3
	 */
	public function onDelete($properties, $plugin_version) {
		if (self::dic()->ctrl()->getCmd() !== "moveAfter") {
			if (self::dic()->ctrl()->getCmd() !== "cut") {
				$h5p_content = Content::getContentById($properties["content_id"]);

				if ($h5p_content !== NULL) {
					self::h5p()->show_editor()->deleteContent($h5p_content);
				}
			} else {
				ilSession::set(ilH5PPlugin::PLUGIN_NAME . "_cut_old_content_id_" . $properties["content_id"], true);
			}
		}
	}


	/**
	 * @param array  $properties
	 * @param string $plugin_version
	 *
	 * @since ILIAS 5.3
	 */
	public function onClone(&$properties, $plugin_version) {
		$old_content_id = $properties["content_id"];

		$h5p_content = Content::getContentById($old_content_id);

		/**
		 * @var Content $h5p_content_copy
		 */

		$h5p_content_copy = $h5p_content->copy();

		$h5p_content_copy->store();

		self::h5p()->storage()->copyPackage($h5p_content_copy->getContentId(), $h5p_content->getContentId());

		$properties["content_id"] = $h5p_content_copy->getContentId();

		if (ilSession::get(ilH5PPlugin::PLUGIN_NAME . "_cut_old_content_id_" . $old_content_id)) {
			ilSession::clear(ilH5PPlugin::PLUGIN_NAME . "_cut_old_content_id_" . $old_content_id);

			$this->onDelete([ "content_id" => $old_content_id ], $plugin_version);
		}
	}
}
