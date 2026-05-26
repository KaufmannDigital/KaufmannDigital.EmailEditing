<?php
declare(strict_types=1);

namespace KaufmannDigital\EmailEditing\Fusion\Helper;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\ProtectedContextAwareInterface;

class FallbackValueHelper implements ProtectedContextAwareInterface
{


    public function getFallbackValue(NodeInterface $contextNode, string $propertyName)
    {
        try {
            $emailLayout = $this->getEmailLayoutByContextNode($contextNode);
            return $emailLayout->getProperty($propertyName);

        } catch (\Exception $exception) {
            return null;
        }
    }

    public function contentBackgroundColor(NodeInterface $node)
    {
        $q = new FlowQuery([$node]);
        //Check parent contents

        //Column
        $parentBackgroundNode = $q->parents('[instanceof KaufmannDigital.EmailEditing:Content.Column][backgroundColor!=""]')->get(0);
        if ($parentBackgroundNode instanceof NodeInterface
            && !empty($parentBackgroundNode->getProperty('backgroundColor'))
        ) {
            return $parentBackgroundNode->getProperty('backgroundColor');
        }

        //Section
        $parentBackgroundNode = $q->parents('[instanceof KaufmannDigital.EmailEditing:Content.Section][backgroundColor!=""]')->get(0);
        if ($parentBackgroundNode instanceof NodeInterface
            && !empty($parentBackgroundNode->getProperty('backgroundColor'))
        ) {
            return $parentBackgroundNode->getProperty('backgroundColor');
        }

        //Email
        $emailNode = $q->parents('[instanceof Neos.Neos:Document]')->get(0);
        if ($emailNode->getNodeType()->getName() === 'KaufmannDigital.EmailEditing:Document.Email') {
            $emailNode = $emailNode->getProperty('emailLayout');
        }

        if (!$emailNode instanceof NodeInterface) {
            return null;
        }


        return $emailNode->getProperty('columnBackgroundColor') ?: $emailNode->getProperty('sectionBackgroundColor');

    }

    public function determineValue(NodeInterface $node, string $localPropertyName, string $layoutPropertyName)
    {
        $localPropertyValue = $node->getProperty($localPropertyName);
        if ($localPropertyValue !== null && $localPropertyValue !== '') { //empty() would match 0 (to reset paddings etc.) as empty
            return $localPropertyValue;
        }

        $q = new FlowQuery([$node]);
        $emailNode = $q->parents('[instanceof Neos.Neos:Document]')->get(0);
        if ($emailNode->getNodeType()->getName() === 'KaufmannDigital.EmailEditing:Document.Email') {
            $emailNode = $emailNode->getProperty('emailLayout');
        }

        $layoutPropertyValue = $emailNode->getProperty($layoutPropertyName);
        if ($layoutPropertyValue !== null && $layoutPropertyValue !== '') {
            return $layoutPropertyValue;
        }

        return null;
    }


    private function getEmailLayoutByContextNode(NodeInterface $contextNode) : ?NodeInterface
    {
        try {
            if ($contextNode->getNodeType()->isOfType('KaufmannDigital.EmailEditing:Document.EmailLayout')) {
                return $contextNode;
            } else if ($contextNode->getNodeType()->isOfType('KaufmannDigital.EmailEditing:Document.Email')) {
                return $contextNode->getProperty('emailLayout');
            } else {
                $q = new FlowQuery([$contextNode]);
                $documentNode = $q->parents('[instanceof KaufmannDigital.EmailEditing:Document.Email],[instanceof KaufmannDigital.EmailEditing:Document.EmailLayout]')->get(0);
                if ($documentNode->getNodeType()->isOfType('KaufmannDigital.EmailEditing:Document.EmailLayout')) {
                    return $documentNode;
                }
                return $documentNode->getProperty('emailLayout');
            }
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function allowsCallOfMethod($methodName): true
    {
        return true;
    }
}
