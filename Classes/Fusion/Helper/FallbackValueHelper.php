<?php
declare(strict_types=1);

namespace KaufmannDigital\EmailEditing\Fusion\Helper;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\ProtectedContextAwareInterface;

class FallbackValueHelper implements ProtectedContextAwareInterface {

    public function contentBackgroundColor(NodeInterface $node)
    {
        $q = new FlowQuery([$node]);
        //Check parent contents

        $parentBackgroundNode = $q->parents('[instanceof KaufmannDigital.EmailEditing:Content.Column][backgroundColor!=""]')->get(0);
        if ($parentBackgroundNode instanceof NodeInterface
            && !empty($parentBackgroundNode->getProperty('backgroundColor'))
        ) {
            return $parentBackgroundNode->getProperty('backgroundColor');
        }

        $emailNode = $q->parents('[instanceof Neos.Neos:Document]')->get(0);
        if ($emailNode->getNodeType()->getName() === 'KaufmannDigital.EmailEditing:Document.Email') {
            $emailNode = $emailNode->getProperty('emailLayout');
        }

        if (!$emailNode instanceof NodeInterface) {
            return null;
        }


        return $emailNode->getProperty('columnBackgroundColor') ?: $emailNode->getProperty('sectionBackgroundColor');

    }

    public function allowsCallOfMethod($methodName): true
    {
        return true;
    }
}

