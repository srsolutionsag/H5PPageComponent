<?php
require_once __DIR__ . "/../../../../Repository/RepositoryObject/H5P/vendor/autoload.php";
require_once __DIR__ . "/../vendor/autoload.php";

/**
 * H5P Page Component Plugin
 */
class ilH5PPageComponentPlugin extends ilPageComponentPlugin {

	use srag\DIC\DICTrait;
	const PLUGIN_CLASS_NAME = self::class;
	const PLUGIN_ID = "pchfp";
	const PLUGIN_NAME = "H5PPageComponent";
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
	 *
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
	 * @return bool
	 */
	protected function beforeUninstall() {
		// Nothing to delete
		return true;
	}
}
