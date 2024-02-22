<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Mixin;

class MixinAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsMixinPHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addMixinAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @mixin A\n */", $docText);
    }

    public function testAddsSeveralMixinPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addMixinAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @mixin A\n * @mixin A\n */", $docText);
    }

    public function testAddsMultipleMixinPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addMixinAttributesToNode($node);
        $this->addMixinAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @mixin A\n * @mixin A\n */", $docText);
    }

    private function addMixinAttributesToNode(Node\Stmt\Class_ $node, int $num = 1): void
    {
        $value = new Node\Scalar\String_('A');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value);
        }
        $attributeName = new FullyQualified(Mixin::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
