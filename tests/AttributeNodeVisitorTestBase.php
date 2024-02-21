<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Internal;
use PhpStaticAnalysis\Attributes\IsReadOnly;
use PhpStaticAnalysis\NodeVisitor\AttributeNodeVisitor;
use PHPUnit\Framework\TestCase;

class AttributeNodeVisitorTestBase extends TestCase
{
    protected const UNTOUCHED = "/**\n * untouched\n */";

    protected AttributeNodeVisitor $nodeVisitor;

    public function setUp(): void
    {
        $this->nodeVisitor = new AttributeNodeVisitor();
    }

    protected function setDocComment(Node $node, string $text): void
    {
        $docComment = new Doc(
            $text,
        );
        $node->setDocComment($docComment);
    }

    protected function getDocText(Node $node): string
    {
        $docComment = $node->getDocComment();
        $docText = '';
        if ($docComment instanceof Doc) {
            $docText = $docComment->getText();
        }
        return $docText;
    }

    protected function addIsReadOnlyAttributeToNode(
        Node\Stmt\ClassMethod | Node\Stmt\Property $node
    ): void {
        $attributeName = new FullyQualified(IsReadOnly::class);
        $attribute = new Attribute($attributeName);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }
}
