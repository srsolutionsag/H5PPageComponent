<?php declare(strict_types=1);

/* Copyright (c) 2021 Thibeau Fuhrer <thf@studer-raimann.ch> Extended GPL, see docs/LICENSE */

/**
 * Class ilH5PPageComponentExporter
 *
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 */
class ilH5PPageComponentExporter extends ilPageComponentPluginExporter
{
    /**
     * This method removes all plugged H5P contents and therefore
     * prohibits H5P content to be exported.
     *
     * That is done, because exporting an H5P object that is plugged
     * into a COPage object only means, that the page contains a
     * reference to that object. If this page is imported, then the
     * H5P content of an entirely different page will be shown and
     * could even be edited - and because it's only referenced the
     * changes affect BOTH places the content is plugged into.
     */
    public function init() : void
    {
        (new ilH5PExportHelper(
            $this->exp->manifest_writer->xmlStr,
            $this->exp->export_run_dir
        ))->removePluggedContents();
    }

    /**
     * @inheritDoc
     */
    public function getValidSchemaVersions($a_entity) : array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getXmlRepresentation($a_entity, $a_schema_version, $a_id) : void
    {

    }
}