<?php
declare(strict_types=1);

namespace KaufmannDigital\EmailEditing\Fusion\Helper;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\ProtectedContextAwareInterface;

class FallbackValueHelper implements ProtectedContextAwareInterface
{

    /**
     * Reads a property directly from the EmailLayout node.
     *
     * Use this as a default value in Component.*.fusion when the component is called without
     * explicit props (e.g. as part of another component). Does not consider intermediate
     * container nodes (Column, Section).
     *
     * Usage: `fontColor = ${FallbackValue.getFallbackValue(node, 'headlineFontColor')}`
     *
     * @param NodeInterface $contextNode Any node within the email tree
     * @param string $propertyName Property name on the EmailLayout node
     * @return mixed Property value from EmailLayout, or null on error
     */
    public function getFallbackValue(NodeInterface $contextNode, string $propertyName): mixed
    {
        try {
            $emailLayout = $this->getEmailLayoutByContextNode($contextNode);
            return $emailLayout->getProperty($propertyName);

        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Resolves the background color for MjmlElementRenderer based on the container hierarchy.
     * Priority: Column.backgroundColor → Section.backgroundColor → EmailLayout.columnBackgroundColor → EmailLayout.sectionBackgroundColor
     *
     * Always use this in htmlRendering.renderer (MjmlElementRenderer) of a content element
     * so that the backend preview renders the correct background context.
     *
     * Usage: `backgroundColor = ${FallbackValue.contentBackgroundColor(node)}`
     *
     * @param NodeInterface $node The content node being rendered
     * @return mixed Resolved background color, or null if none is set
     */
    public function contentBackgroundColor(NodeInterface $node): mixed
    {
        $q = new FlowQuery([$node]);

        // Column
        $parentBackgroundNode = $q->parents('[instanceof KaufmannDigital.EmailEditing:Content.Column][backgroundColor!=""]')->get(0);
        if ($parentBackgroundNode instanceof NodeInterface
            && !empty($parentBackgroundNode->getProperty('backgroundColor'))
        ) {
            return $parentBackgroundNode->getProperty('backgroundColor');
        }

        // Section
        $parentBackgroundNode = $q->parents('[instanceof KaufmannDigital.EmailEditing:Content.Section][backgroundColor!=""]')->get(0);
        if ($parentBackgroundNode instanceof NodeInterface
            && !empty($parentBackgroundNode->getProperty('backgroundColor'))
        ) {
            return $parentBackgroundNode->getProperty('backgroundColor');
        }

        // EmailLayout
        $emailNode = $q->parents('[instanceof Neos.Neos:Document]')->get(0);
        if ($emailNode->getNodeType()->getName() === 'KaufmannDigital.EmailEditing:Document.Email') {
            $emailNode = $emailNode->getProperty('emailLayout');
        }

        if (!$emailNode instanceof NodeInterface) {
            return null;
        }

        return $emailNode->getProperty('columnBackgroundColor') ?: $emailNode->getProperty('sectionBackgroundColor');
    }

    /**
     * Resolves a font color based on the container hierarchy.
     * Priority: Column.[layoutPropertyName] → Section.[layoutPropertyName] → EmailLayout.[layoutPropertyName]
     *
     * Use this as the default value in Component.*.fusion for `fontColor` when no node-level
     * override exists. Allows per-Section or per-Column font color overrides, e.g. when a
     * Section has a dark background but the global font color is also dark.
     *
     * Usage: `fontColor = ${FallbackValue.contentFontColor(node, 'textFontColor')}`
     *        `fontColor = ${FallbackValue.contentFontColor(node, 'headlineFontColor')}`
     *
     * @param NodeInterface $node The content node being rendered
     * @param string $layoutPropertyName Property name to check on Column, Section and EmailLayout
     * @return mixed Resolved font color, or null if none is set
     */
    public function contentFontColor(NodeInterface $node, string $layoutPropertyName): mixed
    {
        $q = new FlowQuery([$node]);

        // Column
        $parentNode = $q->parents('[instanceof KaufmannDigital.EmailEditing:Content.Column][' . $layoutPropertyName . '!=""]')->get(0);
        if ($parentNode instanceof NodeInterface && !empty($parentNode->getProperty($layoutPropertyName))) {
            return $parentNode->getProperty($layoutPropertyName);
        }

        // Section
        $parentNode = $q->parents('[instanceof KaufmannDigital.EmailEditing:Content.Section][' . $layoutPropertyName . '!=""]')->get(0);
        if ($parentNode instanceof NodeInterface && !empty($parentNode->getProperty($layoutPropertyName))) {
            return $parentNode->getProperty($layoutPropertyName);
        }

        // EmailLayout fallback
        return $this->getFallbackValue($node, $layoutPropertyName);
    }

    /**
     * Combines a node-level property override with the full container hierarchy (via contentFontColor).
     * Priority: Node.[localPropertyName] → Column.[layoutPropertyName] → Section.[layoutPropertyName] → EmailLayout.[layoutPropertyName]
     *
     * Use this in Content.*.fusion (componentProps) for content elements that have their own
     * overridable font color property. Extends determineValue by also considering Column and
     * Section as intermediate fallback sources.
     *
     * Usage: `fontColor = ${FallbackValue.determineContentValue(node, 'fontColor', 'textFontColor')}`
     *
     * @param NodeInterface $node The content node
     * @param string $localPropertyName Property name on the content node itself
     * @param string $layoutPropertyName Property name to check on Column, Section and EmailLayout
     * @return mixed Resolved value following the full hierarchy, or null
     */
    public function determineContentValue(NodeInterface $node, string $localPropertyName, string $layoutPropertyName): mixed
    {
        $localPropertyValue = $node->getProperty($localPropertyName);
        if ($localPropertyValue !== null && $localPropertyValue !== '') {
            return $localPropertyValue;
        }

        return $this->contentFontColor($node, $layoutPropertyName);
    }

    /**
     * Combines a node-level property override with a direct EmailLayout fallback (no container hierarchy).
     * Priority: Node.[localPropertyName] → EmailLayout.[layoutPropertyName]
     *
     * Use this in Content.*.fusion (componentProps) for properties that are overridable per node
     * but do not require a container intermediate value (e.g. fontSize, padding, textAlign).
     * Note: 0 is not treated as empty so that padding can be explicitly reset to 0.
     *
     * Usage: `fontSize = ${FallbackValue.determineValue(node, 'fontSize', 'headlineFontSize')}`
     *
     * @param NodeInterface $node The content node
     * @param string $localPropertyName Property name on the content node itself
     * @param string $layoutPropertyName Property name on the EmailLayout node
     * @return mixed Resolved value, or null if neither is set
     */
    public function determineValue(NodeInterface $node, string $localPropertyName, string $layoutPropertyName): mixed
    {
        $localPropertyValue = $node->getProperty($localPropertyName);
        if ($localPropertyValue !== null && $localPropertyValue !== '') { // empty() would match 0 (to reset paddings etc.) as empty
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


    /**
     * @param NodeInterface $contextNode
     * @return NodeInterface|null
     */
    private function getEmailLayoutByContextNode(NodeInterface $contextNode): ?NodeInterface
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
