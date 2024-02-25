<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Pure;

class PureAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsPurePHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addPureAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @pure\n */", $docText);
    }

    private function addPureAttributeToNode(Node\Stmt\ClassMethod $node): void
    {
        $attributeName = new FullyQualified(Pure::class);
        $attribute = new Attribute($attributeName);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }
}
