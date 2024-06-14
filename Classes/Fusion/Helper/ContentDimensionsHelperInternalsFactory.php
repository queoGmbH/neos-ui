<?php

namespace Neos\Neos\Ui\Fusion\Helper;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;

/**
 * @deprecated ugly - we want to get rid of this by adding dimension infos in the Subgraph
 * @implements ContentRepositoryServiceFactoryInterface<ContentDimensionsHelperInternals>
 * @todo EEL helpers are still to be declared as internal
 */
class ContentDimensionsHelperInternalsFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentDimensionsHelperInternals
    {
        return new ContentDimensionsHelperInternals($serviceFactoryDependencies->contentDimensionSource);
    }
}
