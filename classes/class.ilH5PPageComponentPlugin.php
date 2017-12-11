<?php
require_once "Services/COPage/classes/class.ilPageComponentPlugin.php";

/**
 * H5P Page Component Plugin
 */
class ilH5PPageComponentPlugin extends ilPageComponentPlugin {

	/**
	 * @var H5PPageComponentPlugin
	 */
	protected static $instance = NULL;


	/**
	 * @return H5PPageComponentPlugin
	 */
	static function getInstance() {
		if (self::$instance === NULL) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	const ID = "pchfp";


	public function __construct() {
		parent::__construct();

		global $DIC;
	}


	/**
	 * @return string
	 */
	function getPluginName() {
		return "H5PPageComponent";
	}


	/**
	 * @param string $a_type
	 *
	 * @return bool
	 */
	function isValidParentType($a_type) {
		// TODO isValidParentType

		return true;

		$supports_parent_types = [];

		return in_array($a_type, $supports_parent_types);
	}
}
