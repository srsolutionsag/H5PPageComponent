<?php declare(strict_types=1);

use srag\Plugins\H5P\Content\ContentImporter;
use srag\Plugins\H5P\Content\Content;

/**
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 * @noinspection AutoloadingIssuesInspection
 */
class ilH5PPageComponentImporter extends ilPageComponentPluginImporter
{
    /**
     * @inheritDoc
     */
    public function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping)/* : void*/
    {
        $content_ids = (new ContentImporter(
            $this->getImportDirectory(),
            Content::PARENT_TYPE_PAGE
        ))->import($a_xml, 0);

        if (!empty($content_ids)) {
            $imported_page_cont_id = self::getPCMapping($a_id, $a_mapping);
            self::$pc_properties[$imported_page_cont_id]['content_id'] = $content_ids[0];
        }
    }
}