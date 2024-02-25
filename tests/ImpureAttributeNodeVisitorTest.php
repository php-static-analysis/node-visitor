<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Impure;

class ImpureAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsImpurePHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addImpureAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @impure\n */", $docText);
    }

    private function addImpureAttributeToNode(Node\Stmt\ClassMethod $node): void
    {
        $attributeName = new FullyQualified(Impure::class);
        $attribute = new Attribute($attributeName);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }
}
