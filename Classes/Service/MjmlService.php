<?php
declare(strict_types=1);

namespace KaufmannDigital\EmailEditing\Service;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Annotations as Flow;

class MjmlService
{

    #[Flow\Inject]
    protected ContextFactoryInterface $contextFactory;

    #[Flow\InjectConfiguration(path: 'mjmlRendering.fusionPaths')]
    protected array $mjmlFusionIncludes;

    public function getMjmlSourceForEmail(NodeInterface $emailNode, ControllerContext $controllerContext)
    {

        $fusionView = new \Neos\Fusion\View\FusionView();
        $fusionView->setControllerContext($controllerContext);
        $fusionView->setFusionPathPatterns($this->mjmlFusionIncludes);
        $fusionView->setFusionPath('MJMLRenderer');


        //We need to get Live-Node, to prevent rendering of backend-specific HTML-elements
        $context = $this->contextFactory->create();
        $liveNode = $context->getNode($emailNode->getPath());

        $fusionView->assignMultiple([
            'emailNode' => $liveNode,
        ]);

        $rendered = $fusionView->render();

        #TODO: Maybe better switch to ContentCollectionRenderer, to prevent div getting generated?
        $rendered = str_replace(['<div class="neos-contentcollection">', '</div>'], '', $rendered);

        return sprintf('<mjml>%s</mjml>', $rendered);
    }
}
