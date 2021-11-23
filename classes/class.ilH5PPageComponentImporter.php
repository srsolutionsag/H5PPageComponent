<?php declare(strict_types=1);

/**
 * Class ilH5PPageComponentImporter
 *
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 */
class ilH5PPageComponentImporter extends ilPageComponentPluginImporter
{
    /**
     * @inheritDoc
     */
    public function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping) : void
    {
        // workaround for H5P content that was exported before
        // exports were prohibited. The loop beneath replaces all
        // properties and leads to an empty XML tag being added.
        foreach (self::$pc_properties as $key => $values) {
            self::$pc_properties[$key] = [];
        }
    }
}