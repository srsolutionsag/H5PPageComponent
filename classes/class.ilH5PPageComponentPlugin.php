<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use srag\Plugins\H5P\CI\Rector\DICTrait\Replacement\VersionComparator;
use srag\Plugins\H5P\Content\IContentRepository;
use srag\Plugins\H5P\Content\IContent;
use srag\Plugins\H5P\ArrayBasedRequestWrapper;
use srag\Plugins\H5P\RequestHelper;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\DI\Container;

/**
 * This plugin can only be installed if the H5P main plugin is available on the
 * file-system.
 *
 * However, the plugin may be installed before the main plugin is installed, in
 * which case we need to disable the plugins functionality to prevent fatals.
 * This is why in this class and the GUI class we need to check if the main plugin
 * has been installed.
 *
 * @author       Thibeau Fuhrer <thibeau@sr.solutions>
 * @noinspection AutoloadingIssuesInspection
 */
class ilH5PPageComponentPlugin extends ilPageComponentPlugin
{
    public const PLUGIN_NAME = "H5PPageComponent";
    public const PLUGIN_ID = "pchfp";

    public const PROPERTY_CONTENT_ID = 'content_id';

    protected const H5P_MAIN_AUTOLOAD = __DIR__ . "/../../../../Repository/RepositoryObject/H5P/vendor/autoload.php";

    /**
     * @var ilH5PPlugin
     */
    protected $h5p_plugin;

    /**
     * @var ilH5PContainer
     */
    protected $h5p_container;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var ilCtrl
     */
    protected $ctrl;

    /**
     * @throws LogicException if the main plugin is not available (not found in file-system).
     */
    public function __construct(
        \ilDBInterface $db,
        \ilComponentRepositoryWrite $component_repository,
        string $id
    ) {
        global $DIC;
        parent::__construct($db, $component_repository, $id);

        if (!file_exists(self::H5P_MAIN_AUTOLOAD)) {
            throw new LogicException("You cannot use this plugin without installing the main plugin first.");
        }

        require_once self::H5P_MAIN_AUTOLOAD;

        /** @var $component_factory ilComponentFactory */
        $component_factory = $DIC['component.factory'];
        /** @var $plugin ilH5PPlugin */
        $this->h5p_plugin = $component_factory->getPlugin(ilH5PPlugin::PLUGIN_ID);

        $this->h5p_container = $this->h5p_plugin->getContainer();

        if ($DIC->offsetExists('http') && $DIC->offsetExists('ilCtrl')) {
            $this->request = $DIC->http()->request();
            $this->ctrl = $DIC->ctrl();
        }
    }

    /**
     * @inheritDoc
     */
    public function isValidParentType($a_type): bool
    {
        return true;
    }

    /**
     * Exchanges the default renderer instead of the main plugin, if it is available,
     * installed but not active.
     *
     * This needs to be done because this plugins should still be usable even if the
     * main plugin is inactive. Since renderers are only exchanged if a plugin is
     * active, we need to exchange the renderer here to cover this scenario.
     *
     * @inheritDoc
     */
    public function exchangeUIRendererAfterInitialization(Container $dic): Closure
    {
        if ($this->isMainPluginInstalled() && !$this->isMainPluginActive()) {
            return $this->h5p_plugin->exchangeUIRendererAfterInitialization($dic);
        }

        return parent::exchangeUIRendererAfterInitialization($dic);
    }

    /**
     * @inheritDoc
     */
    public function onClone(array &$a_properties, string $a_plugin_version): void
    {
        if (!$this->isMainPluginInstalled()) {
            return;
        }

        $content_id = (int) $a_properties[self::PROPERTY_CONTENT_ID];

        $content = $this->h5p_container->getRepositoryFactory()->content()->getContent($content_id);

        if (null === $content) {
            return;
        }

        $content_clone = $this->h5p_container->getRepositoryFactory()->content()->cloneContent($content);

        $this->h5p_container->getRepositoryFactory()->content()->storeContent($content_clone);
        $this->h5p_container->getKernelStorage()->copyPackage(
            $content_clone->getContentId(),
            $content->getContentId()
        );

        $a_properties[self::PROPERTY_CONTENT_ID] = $content_clone->getContentId();

        if ($this->isContentIdCut($content_id)) {
            $this->markContentIdAsPasted($content_id);
            $this->onDelete([self::PROPERTY_CONTENT_ID => $content_id], $a_plugin_version);
        }
    }

    /**
     * @inheritDoc
     */
    public function onDelete(
        array $a_properties,
        string $a_plugin_version,
        bool $move_operation = false
    ): void {
        if (!$this->isMainPluginInstalled()) {
            return;
        }

        $content_id = (int) $a_properties[self::PROPERTY_CONTENT_ID];

        if (!$move_operation) {
            if (null !== $content = $this->h5p_container->getRepositoryFactory()->content()->getContent($content_id)) {
                $this->deleteContent($content);
            }
        } else {
            $this->markContentIdAsCut($content_id);
        }
    }

    protected function deleteContent(IContent $content): void
    {
        $this->h5p_container->getKernelStorage()->deletePackage([
            'id' => $content->getContentId(),
            'slug' => $content->getSlug()
        ]);
    }

    protected function markContentIdAsPasted(int $content_id): void
    {
        ilSession::clear(self::PLUGIN_ID . '_cut_old_content_id_' . $content_id);
    }

    protected function markContentIdAsCut(int $content_id): void
    {
        ilSession::set(self::PLUGIN_ID . '_cut_old_content_id_' . $content_id, true);
    }

    protected function isContentIdCut(int $content_id): bool
    {
        return (true === ilSession::get(self::PLUGIN_ID . '_cut_old_content_id_' . $content_id));
    }

    private function isMainPluginInstalled(): bool
    {
        return $this
            ->h5p_container
            ->getRepositoryFactory()
            ->general()
            ->isMainPluginInstalled();
    }

    private function isMainPluginActive(): bool
    {
        return $this->h5p_plugin->isActive();
    }
}
