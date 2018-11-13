<?php

namespace srag\Plugins\H5PPageComponent\Job;

use Exception;
use ilCronJob;
use ilCronJobResult;
use ilH5PPlugin;
use ilPageContent;
use ilPageObject;
use ilPageObjectFactory;
use ilPCPlugged;
use srag\DIC\H5P\DICTrait;
use srag\Plugins\H5P\Content\Content;
use srag\Plugins\H5P\Utils\H5PTrait;

/**
 * Class PageComponentJob
 *
 * @package    srag\Plugins\H5PPageComponent\Job
 *
 * @author     studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 *
 * @deprecated since ILIAS 5.3
 */
class PageComponentJob extends ilCronJob {

	use DICTrait;
	use H5PTrait;
	/**
	 * @var string
	 *
	 * @deprecated since ILIAS 5.3
	 */
	const CRON_JOB_ID = ilH5PPlugin::PLUGIN_ID . "_page_component";
	/**
	 * @var string
	 *
	 * @deprecated since ILIAS 5.3
	 */
	const PLUGIN_CLASS_NAME = ilH5PPlugin::class;


	/**
	 * PageComponentJob constructor
	 *
	 * @deprecated since ILIAS 5.3
	 */
	public function __construct() {

	}


	/**
	 * Get id
	 *
	 * @return string
	 *
	 * @deprecated since ILIAS 5.3
	 */
	public function getId() {
		return self::CRON_JOB_ID;
	}


	/**
	 * @return string
	 *
	 * @deprecated since ILIAS 5.3
	 */
	public function getTitle() {
		return ilH5PPlugin::PLUGIN_NAME . ": " . self::plugin()->translate(self::CRON_JOB_ID, ilH5PPlugin::LANG_MODULE_CRON);
	}


	/**
	 * @return string
	 *
	 * @deprecated since ILIAS 5.3
	 */
	public function getDescription() {
		return self::plugin()->translate(self::CRON_JOB_ID . "_description", ilH5PPlugin::LANG_MODULE_CRON) . "<br><br>" . self::plugin()
				->translate(self::CRON_JOB_ID . "_description_deprecated", ilH5PPlugin::LANG_MODULE_CRON);
	}


	/**
	 * Is to be activated on "installation"
	 *
	 * @return boolean
	 *
	 * @deprecated since ILIAS 5.3
	 */
	public function hasAutoActivation() {
		return (!self::version()->is53());
	}


	/**
	 * Can the schedule be configured?
	 *
	 * @return boolean
	 *
	 * @deprecated since ILIAS 5.3
	 */
	public function hasFlexibleSchedule() {
		return true;
	}


	/**
	 * Get schedule type
	 *
	 * @return int
	 *
	 * @deprecated since ILIAS 5.3
	 */
	public function getDefaultScheduleType() {
		return self::SCHEDULE_TYPE_DAILY;
	}


	/**
	 * Get schedule value
	 *
	 * @return int|array
	 *
	 * @deprecated since ILIAS 5.3
	 */
	public function getDefaultScheduleValue() {
		return NULL;
	}


	/**
	 * Run job
	 *
	 * @return ilCronJobResult
	 *
	 * @deprecated since ILIAS 5.3
	 */
	public function run() {
		$result = new ilCronJobResult();

		if (!self::version()->is53()) {
			$h5p_contents = Content::getContentsByObject(NULL, "page");
			$page_component_contents_in_use = $this->getPageComponentContentsInUse();

			foreach ($h5p_contents as $h5p_content) {
				if (!in_array($h5p_content->getContentId(), $page_component_contents_in_use)) {
					self::h5p()->show_editor()->deleteContent($h5p_content, false);
				}
			}

			$result->setStatus(ilCronJobResult::STATUS_OK);
		} else {
			$result->setStatus(ilCronJobResult::STATUS_NO_ACTION);
			$result->setMessage(self::plugin()->translate(self::CRON_JOB_ID . "_description_deprecated", ilH5PPlugin::LANG_MODULE_CRON));
		}

		return $result;
	}


	/**
	 * @return int[]
	 *
	 * @deprecated since ILIAS 5.3
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
