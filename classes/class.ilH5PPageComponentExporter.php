<?php

declare(strict_types=1);

use srag\Plugins\H5P\Content\IContentRepository;

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
     * @var H5PCore
     */
    protected $h5p_kernel;

    /**
     * cannot initialize ContentExporter here because the
     * directories are not yet determined.
     */
    public function init(): void
    {
        $container = ilH5PPlugin::getInstance()->getContainer();

        $this->content_repository = $container->getRepositoryFactory()->content();
        $this->h5p_kernel = $container->getKernel();
    }

    /**
     * @inheritDoc
     */
    public function getXmlRepresentation($a_entity, $a_schema_version, $a_id): string
    {
        // at this point, the working directory does not yet exist.
        ilUtil::makeDir($this->getAbsoluteExportDirectory());

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