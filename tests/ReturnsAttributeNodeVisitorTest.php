<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Returns;

class ReturnsAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsReturnPHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addReturnsAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @return string\n */", $docText);
    }

    private function addReturnsAttributeToNode(Node\Stmt\ClassMethod $node): void
    {
        $args = [
            new Node\Arg(new Node\Scalar\String_('string'))
        ];
        $attributeName = new FullyQualified(Returns::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }
}
