<?php

declare(strict_types=1);

use srag\Plugins\H5P\Content\IContent;

/**
 * @author       Thibeau Fuhrer <thf@studer-raimann.ch>
 * @noinspection AutoloadingIssuesInspection
 */
class ilH5PPageComponentImporter extends ilPageComponentPluginImporter
{
    /**
     * @var ilH5PContainer|null
     */
    protected $h5p_container = null;

    /**
     * @inheritDoc
     */
    public function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping): void
    {
        $container = $this->getContainer();

        // has to be initialized here because getImportDirectory() will
        // be initialized after the object is constructed.
        $content_ids = (new ilH5PContentImporter(
            $container->getKernelFramework(),
            $container->getKernelValidator(),
            $container->getKernelStorage(),
            $container->getKernel(),
            $this->getImportDirectory(),
            IContent::PARENT_TYPE_OBJECT
        ))->import($a_xml, 0);

        if (!empty($content_ids)) {
            $imported_page_cont_id = self::getPCMapping($a_id, $a_mapping);
            self::$pc_properties[$imported_page_cont_id]['content_id'] = $content_ids[0];
        }
    }

    protected function getContainer(): ilH5PContainer
    {
        if (null === $this->h5p_container) {
            global $DIC;
            /** @var $component_factory ilComponentFactory */
            $component_factory = $DIC['component.factory'];
            /** @var $plugin ilH5PPlugin */
            $plugin = $component_factory->getPlugin(ilH5PPlugin::PLUGIN_ID);

            $this->h5p_container = $plugin->getContainer();
        }

        return $this->h5p_container;
    }
}
