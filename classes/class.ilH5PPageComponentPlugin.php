<?php

require_once __DIR__ . "/../../../../Repository/RepositoryObject/H5P/vendor/autoload.php";
require_once __DIR__ . "/../vendor/autoload.php";

use srag\DIC\DICTrait;

/**
 * Class ilH5PPageComponentPlugin
 */
class ilH5PPageComponentPlugin extends ilPageComponentPlugin {

	use DICTrait;
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
	 * @return bool
	 */
	protected function beforeUninstall() {
		// Nothing to delete
		return true;
	}
}
