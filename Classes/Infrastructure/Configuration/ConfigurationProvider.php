<?php

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Ui\Infrastructure\Configuration;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Domain\Model\WorkspaceClassification;
use Neos\Neos\Domain\Service\WorkspaceService;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;
use Neos\Neos\Service\UserService;
use Neos\Neos\Ui\Domain\InitialData\CacheConfigurationVersionProviderInterface;
use Neos\Neos\Ui\Domain\InitialData\ConfigurationProviderInterface;

/**
 * @internal
 */
#[Flow\Scope("singleton")]
final class ConfigurationProvider implements ConfigurationProviderInterface
{
    #[Flow\Inject]
    protected UserService $userService;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    #[Flow\Inject]
    protected ConfigurationManager $configurationManager;

    #[Flow\Inject]
    protected WorkspaceService $workspaceService;

    #[Flow\Inject]
    protected ContentRepositoryAuthorizationService $contentRepositoryAuthorizationService;

    #[Flow\Inject]
    protected CacheConfigurationVersionProviderInterface $cacheConfigurationVersionProvider;

    public function getConfiguration(
        ContentRepository $contentRepository,
        UriBuilder $uriBuilder,
    ): array {
        return [
            'nodeTree' => $this->configurationManager->getConfiguration(
                ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                'Neos.Neos.userInterface.navigateComponent.nodeTree',
            ),
            'structureTree' => $this->configurationManager->getConfiguration(
                ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                'Neos.Neos.userInterface.navigateComponent.structureTree',
            ),
            'allowedTargetWorkspaces' => $this->getAllowedTargetWorkspaces($contentRepository),
            'endpoints' => [
                'nodeTypeSchema' => $uriBuilder->reset()
                    ->setCreateAbsoluteUri(true)
                    ->uriFor(
                        actionName: 'nodeTypeSchema',
                        controllerArguments: [
                            'version' =>
                                $this->cacheConfigurationVersionProvider
                                    ->getCacheConfigurationVersion(),
                        ],
                        controllerName: 'Backend\\Schema',
                        packageKey: 'Neos.Neos',
                    ),
                'translations' => $uriBuilder->reset()
                    ->setCreateAbsoluteUri(true)
                    ->uriFor(
                        actionName: 'xliffAsJson',
                        controllerArguments: [
                            'locale' =>
                                $this->userService
                                    ->getInterfaceLanguage(),
                            'version' =>
                                $this->cacheConfigurationVersionProvider
                                    ->getCacheConfigurationVersion(),
                        ],
                        controllerName: 'Backend\\Backend',
                        packageKey: 'Neos.Neos',
                    ),
            ]
        ];
    }

    /**
     * @return array<string,array{name:string,title:string,readonly:bool}>
     */
    private function getAllowedTargetWorkspaces(ContentRepository $contentRepository): array
    {
        $result = [];
        foreach ($contentRepository->findWorkspaces() as $workspace) {
            $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepository->id, $workspace->workspaceName);
            if (!in_array($workspaceMetadata->classification, [WorkspaceClassification::ROOT, WorkspaceClassification::SHARED], true)) {
                continue;
            }
            $workspacePermissions = $this->contentRepositoryAuthorizationService->getWorkspacePermissions($contentRepository->id, $workspace->workspaceName, $this->securityContext->getRoles(), $this->userService->getBackendUser()?->getId());
            if ($workspacePermissions->read === false) {
                continue;
            }
            $result[$workspace->workspaceName->value] = [
                'name' => $workspace->workspaceName->value,
                'title' => $workspaceMetadata->title->value,
                'readonly' => !$workspacePermissions->write,
            ];
        }
        return $result;
    }
}
