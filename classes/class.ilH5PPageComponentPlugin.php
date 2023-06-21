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
 * This is why in this class and the GUI class we need to check if the main has
 * been installed.
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
     * @var self|null
     */
    protected static $instance;

    /**
     * @var IContentRepository
     */
    protected $content_repository;

    /**
     * @var H5PStorage
     */
    protected $h5p_kernel_storage;

    /**
     * @var VersionComparator
     */
    protected $version_comparator;

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
    public function __construct()
    {
        global $DIC;
        parent::__construct();

        if (!file_exists(self::H5P_MAIN_AUTOLOAD)) {
            throw new LogicException("You cannot use this plugin without installing the main plugin first.");
        }

        if (!$this->isMainPluginLoaded()) {
            require_once self::H5P_MAIN_AUTOLOAD;
        }

        $container = ilH5PPlugin::getInstance()->getContainer();

        if ($container->areDependenciesAvailable()) {
            $this->content_repository = $container->getRepositoryFactory()->content();
            $this->h5p_kernel_storage = $container->getKernelStorage();
        }

        if ($DIC->offsetExists('http') && $DIC->offsetExists('ilCtrl')) {
            $this->request = $DIC->http()->request();
            $this->ctrl = $DIC->ctrl();
        }

        $this->version_comparator = new VersionComparator();

        self::$instance = $this;
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @inheritDoc
     */
    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * @inheritDoc
     */
    public function isValidParentType($a_type): bool
    {
        return true;
    }

    /**
     * Exchanges the default renderer if the main plugin is available but not active.
     * Otherwise, this plugin cannot render any H5P contents and could even lead to fatals
     * because we are using custom UI components, which arent renderable by default.
     *
     * @inheritDoc
     */
    public function exchangeUIRendererAfterInitialization(Container $dic): Closure
    {
        if ($this->isMainPluginLoaded() && !$this->isMainPluginActive()) {
            return ilH5PPlugin::getInstance()->exchangeUIRendererAfterInitialization($dic);
        }

        return parent::exchangeUIRendererAfterInitialization($dic);
    }

    /**
     * @inheritDoc
     */
    public function onClone(&$a_properties, $a_plugin_version): void
    {
        if (!$this->isMainPluginInstalled()) {
            return;
        }

        $content_id = (int) $a_properties[self::PROPERTY_CONTENT_ID];

        $content = $this->content_repository->getContent($content_id);

        if (null === $content) {
            return;
        }

        $content_clone = $this->content_repository->cloneContent($content);

        $this->content_repository->storeContent($content_clone);
        $this->h5p_kernel_storage->copyPackage(
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
    public function onDelete($a_properties, $a_plugin_version): void
    {
        if (!$this->isMainPluginInstalled()) {
            return;
        }

        $content_id = (int) $a_properties[self::PROPERTY_CONTENT_ID];

        if ($this->shouldDeleteContent()) {
            if (null !== $content = $this->content_repository->getContent($content_id)) {
                $this->deleteContent($content);
            }
        } else {
            $this->markContentIdAsCut($content_id);
        }
    }

    /**
     * Returns whether the content can be safely deleted from the
     * database or not.
     */
    protected function shouldDeleteContent(): bool
    {
        // see discussion in https://github.com/ILIAS-eLearning/ILIAS/pull/3990.
        if ($this->version_comparator->is7()) {
            $this->request->getBody()->rewind();
            $body = (!empty($_POST)) ?
                $this->request->getParsedBody() :
                json_decode($this->request->getBody()->getContents(), true);

            return (isset($body['action']) && 'delete' === $body['action']);
        }

        if ($this->version_comparator->is6()) {
            return ('cut' === $this->ctrl->getCmd());
        }

        return false;
    }

    protected function deleteContent(IContent $content): void
    {
        $this->h5p_kernel_storage->deletePackage([
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

    private function isMainPluginLoaded(): bool
    {
        return class_exists('ilH5PPlugin');
    }

    private function isMainPluginInstalled(): bool
    {
        return ilH5PPlugin::getInstance()->getContainer()->getRepositoryFactory()->general()->isMainPluginInstalled();
    }

    private function isMainPluginActive(): bool
    {
        return ilH5PPlugin::getInstance()->isActive();
    }
}
