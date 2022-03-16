<?php declare(strict_types=1);

use srag\Plugins\H5P\Utils\H5PTrait;
use srag\Plugins\H5P\Content\ContentExporter;

/**
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 * @noinspection AutoloadingIssuesInspection
 */
class ilH5PPageComponentExporter extends ilPageComponentPluginExporter
{
    use H5PTrait;

    /**
     * cannot initialize ContentExporter here because the
     * directories are not yet determined.
     */
    public function init()/* : void*/
    {
    }

    /**
     * @inheritDoc
     */
    public function getXmlRepresentation($a_entity, $a_schema_version, $a_id) : string
    {
        // at this point, the working directory does not yet exist.
        ilUtil::makeDir($this->getAbsoluteExportDirectory());

        $content_id = (int) self::$pc_properties[$a_id]['content_id'];

        return (new ContentExporter(
            new ilXmlWriter(),
            $this->getAbsoluteExportDirectory(),
            $this->getRelativeExportDirectory()
        ))->exportSingle(
            self::h5p()->contents()->getContentById($content_id)
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidSchemaVersions($a_entity) : array
    {
        return [];
    }
}