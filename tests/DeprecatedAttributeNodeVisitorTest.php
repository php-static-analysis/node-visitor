<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Deprecated;

class DeprecatedAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsDeprecatedPHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addDeprecatedAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @deprecated\n */", $docText);
    }

    private function addDeprecatedAttributeToNode(Node\Stmt\Class_ $node, bool $addType = false): void
    {
        $attributeName = new FullyQualified(Deprecated::class);
        $attribute = new Attribute($attributeName);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }
}
