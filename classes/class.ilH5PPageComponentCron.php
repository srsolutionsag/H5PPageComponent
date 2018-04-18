<?php
require_once __DIR__ . "/../../../../Repository/RepositoryObject/H5P/vendor/autoload.php";
require_once __DIR__ . "/../vendor/autoload.php";

/**
 * H5P page component cronjob
 *
 * Called in @see ilH5PCron
 */
class ilH5PPageComponentCron {

	/**
	 * @var ilDB
	 */
	protected $db;
	/**
	 * @var ilH5P
	 */
	protected $h5p;


	/**
	 *
	 */
	public function __construct() {

	}


	/**
	 *
	 */
	public function run() {
		global $DIC;

		$this->db = $DIC->database();
		$this->h5p = ilH5P::getInstance();

		$this->deleteDeletedPageComponentContents();
	}


	/**
	 *
	 */
	protected function deleteDeletedPageComponentContents() {
		$h5p_contents = ilH5PContent::getContentsByObject(NULL, "page");
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
		$result = $this->db->query("SELECT page_id, parent_type FROM page_object");

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
