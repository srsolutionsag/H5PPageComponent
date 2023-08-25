<?php

declare(strict_types=1);

use srag\Plugins\H5P\Content\Form\ImportContentFormProcessor;
use srag\Plugins\H5P\Content\Form\ImportContentFormBuilder;
use srag\Plugins\H5P\Content\Form\EditContentFormBuilder;
use srag\Plugins\H5P\Content\Form\EditContentFormProcessor;
use srag\Plugins\H5P\Content\ContentEditorHelper;
use srag\Plugins\H5P\Content\ContentEditorData;
use srag\Plugins\H5P\Content\IContent;
use srag\Plugins\H5P\Content\Form\ContentPostProcessor;
use srag\Plugins\H5P\Content\Form\IPostProcessorAware;
use srag\Plugins\H5P\Form\IFormBuilder;
use srag\Plugins\H5P\ArrayBasedRequestWrapper;
use srag\Plugins\H5P\IRepositoryFactory;
use srag\Plugins\H5P\IRequestParameters;
use srag\Plugins\H5P\TemplateHelper;
use srag\Plugins\H5P\RequestHelper;
use srag\Plugins\H5P\ITranslator;
use srag\Plugins\H5P\IContainer;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Factory as ComponentFactory;
use srag\Plugins\H5P\Settings\IGeneralSettings;

/**
 * This GUI handles all requests that concern the CRUD operations of H5P content
 * embedded in a COPage object.
 *
 * The architecture of COPages is very limited in what plugins can do because it
 * uses ilCtrl's getHTML() method to render the GUI. This leads to very long and
 * fragile ilCtrl paths which disallow us to use this GUI as we would like to.
 *
 * Link targets to this GUI can only ever be built with the commands 'insert',
 * 'create', 'edit', and 'update', otherwise requests will magically fail.
 *
 * @author            Thibeau Fuhrer <thibeau@sr.solutions>
 * @ilCtrl_isCalledBy ilH5PPageComponentPluginGUI: ilPCPluggedGUI
 * @noinspection      AutoloadingIssuesInspection
 */
class ilH5PPageComponentPluginGUI extends ilPageComponentPluginGUI
{
    use ilH5PTargetHelper;
    use ilH5PRequestObject;
    use ContentEditorHelper;
    use TemplateHelper;
    use RequestHelper;

    public const CMD_CONTENT_CREATE = 'create';
    public const CMD_CONTENT_UPDATE = 'update';

    protected const IMPORT_CONTENT_FALG = 'import_content';
    protected const EXPORT_CONTENT_FLAG = 'export_content';

    /**
     * @var IContainer
     */
    protected $h5p_container;

    /**
     * @var IRepositoryFactory
     */
    protected $repositories;

    /**
     * @var ITranslator
     */
    protected $translator;

    /**
     * @var ComponentFactory
     */
    protected $components;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Boolean flag which determines whether or not $this->object is located in the workspace.
     * If true, the object-id (obj_id) must be used over the reference-id (ref_id).
     *
     * @var bool
     */
    protected $in_workspace;

    /**
     * ATTENTION: this property cannot be initialized in the constructur due to missing
     * information about the parent object. $this->initParentObject() needs to be called
     * before using this property.
     *
     * @var ilObject
     */
    protected $object;

    /**
     * @var ilToolbarGUI
     */
    protected $toolbar;

    /**
     * @throws LogicException if there was no parent object id in the request.
     */
    public function __construct()
    {
        global $DIC;
        parent::__construct();

        $this->h5p_container = ilH5PPlugin::getInstance()->getContainer();
        $this->repositories = $this->h5p_container->getRepositoryFactory();
        $this->translator = $this->h5p_container->getTranslator();

        $this->post_request = new ArrayBasedRequestWrapper(
            $DIC->http()->request()->getParsedBody()
        );

        // we cannot use $DIC->http()->request()->getQueryParams() here,
        // because in ILIAS LM objects the $_GET variable will be provided
        // with the parent ref-id manually, at a latter point in time.
        $this->get_request = new ArrayBasedRequestWrapper(
            $_GET
        );

        $this->components = $DIC->ui()->factory();
        $this->template = $DIC->ui()->mainTemplate();
        $this->renderer = $DIC->ui()->renderer();
        $this->request = $DIC->http()->request();
        $this->refinery = $DIC->refinery();
        $this->toolbar = $DIC->toolbar();
        $this->ctrl = $DIC->ctrl();
    }

    /**
     * @inheritDoc
     */
    public function executeCommand(): void
    {
        $command = $this->ctrl->getCmd();

        switch ($command) {
            case ilPageComponentPlugin::CMD_INSERT:
            case ilPageComponentPlugin::CMD_EDIT:
            case self::CMD_CONTENT_CREATE:
            case self::CMD_CONTENT_UPDATE:
                $this->abortIfMainPluginNotInstalled();
                $this->initParentObject();
                $this->{$command}();
                break;

            default:
                throw new LogicException("command '$command' not found.");
        }
    }

    /**
     * @inheritDoc
     */
    public function getElementHTML($a_mode, array $a_properties, $plugin_version): string
    {
        if (!$this->repositories->general()->isMainPluginInstalled()) {
            return '';
        }

        $content_id = $a_properties[ilH5PPageComponentPlugin::PROPERTY_CONTENT_ID] ?? null;
        $content = (null !== $content_id) ? $this->repositories->content()->getContent((int) $content_id) : null;

        $component = (null === $content) ?
            $this->components->messageBox()->failure($this->translator->txt('content_not_exists')) :
            $this->h5p_container->getComponentFactory()->content($content)->withLoadingMessage(
                $this->translator->txt('content_loading')
            );

        return $this->wrapHtml($this->renderer->render($component));
    }

    /**
     * This is our entry-point for the creating OR importing a new H5P content.
     *
     * If $this->shouldImportContent() returns true, we render the import form,
     * otherwise we render the create form. The toolbar will hold an additional
     * button to switch between the two forms.
     *
     * Both form MUST submit to the 'create' command which must handle both scenarios
     * as well, otherwise the request will dissappear.
     *
     * @inheritDoc
     */
    public function insert(): void
    {
        $this->abortIfMainPluginNotInstalled();
        $this->initParentObject();

        $import = $this->shouldImportContent();

        if ($this->areImportsAllowed()) {
            $this->addImportOrCreateButton($import);
        } elseif ($import) {
            $this->redirectPermissionDenied();
        }

        ($import) ?
            $this->render($this->getImportContentForm()) :
            $this->render($this->getEditContentForm(self::CMD_CONTENT_CREATE));
    }

    /**
     * Processes the import or create form, depending on the request.
     * This is the counter-part of the insert() method.
     *
     * @inheritDoc
     */
    public function create(): void
    {
        $this->abortIfMainPluginNotInstalled();
        $this->initParentObject();

        $import = $this->shouldImportContent();

        if ($this->areImportsAllowed()) {
            $this->addImportOrCreateButton($import);
        } elseif ($import) {
            $this->redirectPermissionDenied();
        }

        $processor = ($import) ?
            $this->getImportContentFormProcessor() :
            $this->getEditContentFormProcessor($this->getEditContentForm(self::CMD_CONTENT_CREATE));

        $this->runFormProcessor($processor);
    }

    /**
     * This is our entry-point for the edditing OR exporting an H5P content.
     *
     * In both scenarios we render the edit form. If $this->shouldExportContent()
     * returns true however, we will deliver the H5P content as a file as well.
     *
     * The form will submit to the 'update' command which processes it.
     *
     * @inheritDoc
     */
    public function edit(): void
    {
        $this->abortIfMainPluginNotInstalled();
        $this->initParentObject();

        if (null === ($content = $this->getContentFor($this->getProperties()))) {
            $this->redirectObjectNotFound();
        }

        if ($this->shouldExportContent()) {
            $this->exportContent($content->getContentId());
        }

        $this->addExportButton($content);

        $this->render($this->getEditContentForm(self::CMD_CONTENT_UPDATE, $content));
    }

    /**
     * Processes the edit form. This is the counter-part of the edit() method.
     */
    protected function update(): void
    {
        $this->abortIfMainPluginNotInstalled();
        $this->initParentObject();

        if (null === ($content = $this->getContentFor($this->getProperties()))) {
            $this->redirectObjectNotFound();
        }

        $this->addExportButton($content);

        $this->runFormProcessor(
            $this->getEditContentFormProcessor(
                $this->getEditContentForm(self::CMD_CONTENT_UPDATE, $content)
            ),
            $content
        );
    }

    /**
     * Exports and delivers the H5P content as a file.
     */
    protected function exportContent(int $content_id): void
    {
        $content_data = $this->h5p_container->getKernel()->loadContent($content_id);
        $this->h5p_container->getKernel()->filterParameters($content_data);

        $export_file = IContainer::H5P_STORAGE_DIR . "/exports/" . $content_data["slug"] . "-" . $content_data["id"] . ".h5p";

        ilFileDelivery::deliverFileAttached($export_file, $content_data["slug"] . ".h5p");
    }

    /**
     * Adds a button to the toolbar to switch between the import and create form.
     * The button will be the opposite of the given $import flag.
     */
    protected function addImportOrCreateButton(bool $import): void
    {
        $import = !$import;

        $this->toolbar->addComponent(
            $this->components->button()->standard(
                $this->translator->txt((($import) ? 'import_content' : 'add_content')),
                $this->getLinkTarget(self::class, ilPageComponentPlugin::CMD_INSERT, [
                    self::IMPORT_CONTENT_FALG => (int) $import
                ])
            )
        );
    }

    protected function addExportButton(IContent $content): void
    {
        $this->toolbar->addComponent(
            $this->components->button()->standard(
                $this->translator->txt('export_content'),
                $this->getLinkTarget(self::class, ilPageComponentPlugin::CMD_EDIT, [
                    self::EXPORT_CONTENT_FLAG => 1,
                ])
            )
        );
    }

    /**
     * Executes the given form processor and registers an additional post-processor,
     * which calles either $this->createElement() or $this->updateElement() depending
     * on the given content.
     */
    protected function runFormProcessor(IPostProcessorAware $form_processor, IContent $content = null): void
    {
        $post_processor = new ContentPostProcessor(
            ilH5PPageComponentPlugin::PLUGIN_ID,
            function (array $content_data) use ($content): void {
                $data[ilH5PPageComponentPlugin::PROPERTY_CONTENT_ID] = $content_data['id'] ?? null;

                (null !== $content) ? $this->updateElement($data) : $this->createElement($data);
            }
        );

        $form_processor = $form_processor->withPostProcessor($post_processor);

        if ($form_processor->processForm()) {
            $this->returnToParent();
        }

        $this->render($form_processor->getProcessedForm());
    }

    protected function getEditContentForm(string $command, IContent $content = null): Form
    {
        $builder = new EditContentFormBuilder(
            $this->translator,
            $this->components->input()->container()->form(),
            $this->components->input()->field(),
            $this->h5p_container->getComponentFactory(),
            $this->refinery,
            (null !== $content) ? $this->getContentEditorData(
                $content->getContentId()
            ) : null
        );

        return $builder->getForm(
            $this->getFormAction(self::class, $command)
        );
    }

    protected function getEditContentFormProcessor(Form $edit_form): IPostProcessorAware
    {
        return new EditContentFormProcessor(
            $this->repositories->content(),
            $this->repositories->library(),
            $this->h5p_container->getKernel(),
            $this->h5p_container->getEditor(),
            $this->request,
            $edit_form,
            ($this->in_workspace) ? $this->object->getId() : $this->object->getRefId(),
            $this->object->getType(),
            $this->in_workspace
        );
    }

    protected function getImportContentForm(): Form
    {
        $builder = new ImportContentFormBuilder(
            $this->translator,
            $this->components->input()->container()->form(),
            $this->components->input()->field(),
            $this->refinery,
            new ilH5PUploadHandlerGUI()
        );

        return $builder->getForm(
            $this->getFormAction(self::class, self::CMD_CONTENT_CREATE, [
                self::IMPORT_CONTENT_FALG => 1,
            ])
        );
    }

    protected function getImportContentFormProcessor(): IPostProcessorAware
    {
        return new ImportContentFormProcessor(
            $this->h5p_container->getFileUploadCommunicator(),
            $this->h5p_container->getKernelValidator(),
            $this->h5p_container->getKernelStorage(),
            $this->h5p_container->getKernel(),
            $this->request,
            $this->getImportContentForm(),
            ($this->in_workspace) ? $this->object->getId() : $this->object->getRefId(),
            $this->object->getType(),
            $this->in_workspace
        );
    }

    /**
     * This method exists because this controller may be called in other contexts than
     * the repository (like portfolio or workspace), which do not allow to retrieve the
     * parent object during construction, because the page will be provided at a later
     * point.
     */
    protected function initParentObject(): void
    {
        $repository_object = $this->getRequestedRepositoryObject($this->get_request);
        $workspace_object = $this->getWorkspaceParentObject();

        if (null !== $repository_object) {
            $query_parameter = IRequestParameters::REF_ID;
            $ilias_id = $repository_object->getRefId();
            $this->object = $repository_object;
            $this->in_workspace = false;
        } elseif (null !== $workspace_object) {
            $query_parameter = IRequestParameters::OBJ_ID;
            $ilias_id = $workspace_object->getId();
            $this->object = $workspace_object;
            $this->in_workspace = true;
        } else {
            $this->redirectObjectNotFound();
            return;
        }

        $this->ctrl->setParameterByClass(ilH5PAjaxEndpointGUI::class, $query_parameter, $ilias_id);
    }

    /**
     * ATTENTION: please only use this method if the parent object cannot be determined
     * via request.
     */
    protected function getWorkspaceParentObject(): ?ilObject
    {
        /** @var $parent_gui ilPCPluggedGUI */
        $parent_gui = $this->getPCGUI();
        /** @var $page ilPageObject */
        $page = $parent_gui->getPage();

        if ($page instanceof ilPortfolioPage) {
            $obj_id = $page->getPortfolioId();
        } else {
            $obj_id = $page->getParentId();
        }

        $object = ilObjectFactory::getInstanceByObjId($obj_id, false);

        return ($object) ?: null;
    }

    protected function getContentFor(array $properties): ?IContent
    {
        $content_id = $properties[ilH5PPageComponentPlugin::PROPERTY_CONTENT_ID] ?? null;
        if (null !== $content_id) {
            return $this->repositories->content()->getContent((int) $content_id);
        }

        return null;
    }

    /**
     * Wraps the given HTML in a div with a margin-top of 25px.
     *
     * This is used to add spacing between continous H5P contents and provides
     * the page-editor with a clickable area to select the content.
     */
    protected function wrapHtml(string $html): string
    {
        return '<div style="margin-top: 25px;">' . $html . '</div>';
    }

    protected function redirectWithFailure(string $message): void
    {
        ilUtil::sendFailure($message, true);

        // it's possible the PC GUI has not been set yet, in which case
        // we have no choice than redirecting to the repository root.
        if (null === $this->getPCGUI()) {
            $this->ctrl->redirectToURL(ilLink::_getLink(1));
        } else {
            $this->returnToParent();
        }
    }

    protected function redirectObjectNotFound(): void
    {
        $this->redirectWithFailure($this->translator->txt('object_not_found'));
    }

    protected function redirectPermissionDenied(): void
    {
        $this->redirectWithFailure($this->translator->txt('permission_denied'));
    }

    protected function abortIfMainPluginNotInstalled(): void
    {
        if (!$this->repositories->general()->isMainPluginInstalled()) {
            ilUtil::sendFailure('You must install the H5P plugin before you can create H5P contents.', true);
            $this->returnToParent();
        }
    }

    protected function areImportsAllowed(): bool
    {
        $option = $this->repositories->settings()->getGeneralSettingValue(IGeneralSettings::SETTING_ALLOW_H5P_IMPORTS);
        if (null !== $option) {
            return (bool) $option;
        }

        return false;
    }

    protected function shouldImportContent(): bool
    {
        return 0 < $this->getRequestedInteger($this->get_request, self::IMPORT_CONTENT_FALG);
    }

    protected function shouldExportContent(): bool
    {
        return 0 < $this->getRequestedInteger($this->get_request, self::EXPORT_CONTENT_FLAG);
    }

    protected function getKernel(): \H5PCore
    {
        return $this->h5p_container->getKernel();
    }
}
