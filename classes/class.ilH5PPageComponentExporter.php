<?php

declare(strict_types=1);

use srag\Plugins\H5P\Content\IContentRepository;
use ILIAS\Filesystem\Filesystem;

/**
 * @author       Thibeau Fuhrer <thf@studer-raimann.ch>
 * @noinspection AutoloadingIssuesInspection
 */
class ilH5PPageComponentExporter extends ilPageComponentPluginExporter
{
    /**
     * @var IContentRepository
     */
    protected $content_repository;

    /**
     * @var Filesystem
     */
    protected $file_system;

    /**
     * @var H5PCore
     */
    protected $h5p_kernel;

    /**
     * cannot initialize ContentExporter here because the
     * directories are not yet determined.
     */
    public function init(): void
    {
        global $DIC;

        /** @var $component_factory ilComponentFactory */
        $component_factory = $DIC['component.factory'];
        /** @var $plugin ilH5PPlugin */
        $plugin = $component_factory->getPlugin(ilH5PPlugin::PLUGIN_ID);

        $this->file_system = $DIC->filesystem()->storage();
        $this->content_repository = $plugin->getContainer()->getRepositoryFactory()->content();
        $this->h5p_kernel = $plugin->getContainer()->getKernel();
    }

    /**
     * @inheritDoc
     */
    public function getXmlRepresentation($a_entity, $a_schema_version, $a_id): string
    {
        // at this point, the working directory does not yet exist.
        $this->file_system->createDir($this->getAbsoluteExportDirectory());

        $content_id = (int) self::$pc_properties[$a_id]['content_id'];

        if (null === ($content = $this->content_repository->getContent($content_id))) {
            return '';
        }

        return (new ilH5PContentExporter(
            $this->content_repository,
            new ilXmlWriter(),
            $this->h5p_kernel,
            $this->getAbsoluteExportDirectory(),
            $this->getRelativeExportDirectory()
        ))->exportSingle($content);
    }

    /**
     * @inheritDoc
     */
    public function getValidSchemaVersions($a_entity): array
    {
        return [];
    }
}