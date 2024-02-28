<?php


use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Immutable;
use test\PhpStaticAnalysis\NodeVisitor\AttributeNodeVisitorTestBase;

class ImmutableAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsImmutablePHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addImmutableAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @immutable\n */", $docText);
    }

    private function addImmutableAttributeToNode(Node\Stmt\Class_ $node): void
    {
        $attributeName = new FullyQualified(Immutable::class);
        $attribute = new Attribute($attributeName);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }
}
