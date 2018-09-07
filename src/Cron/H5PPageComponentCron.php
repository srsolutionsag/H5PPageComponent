<?php

namespace srag\Plugins\H5PPageComponent\Cron;

use Exception;
use ilH5PPageComponentPlugin;
use ilPageContent;
use ilPageObject;
use ilPageObjectFactory;
use ilPCPlugged;
use srag\DIC\DICTrait;
use srag\Plugins\H5P\ActiveRecord\H5PContent;
use srag\Plugins\H5P\H5P\H5P;

/**
 * Class H5PPageComponentCron
 *
 * Called in @see H5PCron
 *
 * @package srag\Plugins\H5PPageComponent\Cron
 * @author  studer + raimann ag <support-custom1@studer-raimann.ch>
 */
class H5PPageComponentCron {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilH5PPageComponentPlugin::class;
	/**
	 * @var H5P
	 */
	protected $h5p;


	/**
	 * H5PPageComponentCron constructor
	 */
	public function __construct() {

	}


	/**
	 *
	 */
	public function run() {
		$this->h5p = H5P::getInstance();

		$this->deleteDeletedPageComponentContents();
	}


	/**
	 *
	 */
	protected function deleteDeletedPageComponentContents() {
		$h5p_contents = H5PContent::getContentsByObject(NULL, "page");
		$page_component_contents_in_use = $this->getPageComponentContentsInUse();

		foreach ($h5p_contents as $h5p_content) {
			if (!in_array($h5p_content->getContentId(), $page_component_contents_in_use)) {
				$this->h5p->show_editor()->deleteContent($h5p_content, false);
			}
		}
	}


	/**
	 * @return int[]
	 */
	protected function getPageComponentContentsInUse() {
		$result = self::dic()->database()->query("SELECT page_id, parent_type FROM page_object");

		$page_component_contents_in_use = [];
		while (($page_component = $result->fetchAssoc()) !== false) {
			/**
			 * @var ilPageObject $page_obj
			 */

			$page_obj = ilPageObjectFactory::getInstance($page_component["parent_type"], $page_component["page_id"]);
			$page_obj->buildDom();
			$page_obj->addHierIDs();

			foreach ($page_obj->getHierIds() as $hier_id) {
				try {
					/**
					 * @var ilPageContent $content_obj
					 */

					$content_obj = $page_obj->getContentObject($hier_id);

					if ($content_obj instanceof ilPCPlugged) {
						$properties = $content_obj->getProperties();

						if (isset($properties["content_id"])) {
							$page_component_contents_in_use[] = $properties["content_id"];
						};
					}
				} catch (Exception $ex) {
				}
			}
		}

		return $page_component_contents_in_use;
	}
}
