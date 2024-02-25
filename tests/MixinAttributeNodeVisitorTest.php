<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use other\A;
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
        $this->assertEquals("/**\n * @mixin other\A\n */", $docText);
    }

    public function testAddsSeveralMixinPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addMixinAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @mixin other\A\n * @mixin other\A\n */", $docText);
    }

    public function testAddsMultipleMixinPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addMixinAttributesToNode($node);
        $this->addMixinAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @mixin other\A\n * @mixin other\A\n */", $docText);
    }

    private function addMixinAttributesToNode(Node\Stmt\Class_ $node, int $num = 1): void
    {
        $class = new Node\Name(A::class);
        $value = new Node\Expr\ClassConstFetch($class, 'class');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value);
        }
        $attributeName = new FullyQualified(Mixin::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}

namespace other;

class A
{
}
