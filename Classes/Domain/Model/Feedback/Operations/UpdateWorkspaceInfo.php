<?php
namespace Neos\Neos\Ui\Domain\Model\Feedback\Operations;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Neos\Ui\ContentRepository\Service\WorkspaceService as UiWorkspaceService;
use Neos\Neos\Ui\Domain\Model\AbstractFeedback;
use Neos\Neos\Ui\Domain\Model\FeedbackInterface;

/**
 * @internal
 */
class UpdateWorkspaceInfo extends AbstractFeedback
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var UiWorkspaceService
     */
    protected $uiWorkspaceService;

    /**
     * UpdateWorkspaceInfo constructor.
     *
     */
    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly WorkspaceName $workspaceName
    ) {
    }

    /**
     * Getter for WorkspaceName
     */
    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /**
     * Get the type identifier
     *
     * @return string
     */
    public function getType()
    {
        return 'Neos.Neos.Ui:UpdateWorkspaceInfo';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return sprintf('New workspace info available.');
    }

    /**
     * Checks whether this feedback is similar to another
     *
     * @param FeedbackInterface $feedback
     * @return boolean
     */
    public function isSimilarTo(FeedbackInterface $feedback)
    {
        if (!$feedback instanceof UpdateWorkspaceInfo) {
            return false;
        }
        $feedbackWorkspaceName = $feedback->getWorkspaceName();
        return $feedbackWorkspaceName !== null && $this->getWorkspaceName()->equals($feedbackWorkspaceName);
    }

    /**
     * Serialize the payload for this feedback
     *
     * @param ControllerContext $controllerContext
     * @return mixed
     */
    public function serializePayload(ControllerContext $controllerContext)
    {
        $contentRepository = $this->contentRepositoryRegistry->get($this->contentRepositoryId);
        $workspace = $contentRepository->findWorkspaceByName($this->workspaceName);
        if ($workspace === null) {
            return null;
        }
        $publishableNodes = $this->uiWorkspaceService->getPublishableNodeInfo($workspace->workspaceName, $contentRepository->id);
        return [
            'name' => $this->workspaceName->value,
            'totalNumberOfChanges' => count($publishableNodes),
            'publishableNodes' => $publishableNodes,
            'baseWorkspace' => $workspace->baseWorkspaceName?->value,
            'status' => $workspace->status->value,
        ];
    }
}
