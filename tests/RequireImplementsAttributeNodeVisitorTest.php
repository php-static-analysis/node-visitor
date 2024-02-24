<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\RequireImplements;

class RequireImplementsAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsRequireImplementsPHPDoc(): void
    {
        $node = new Node\Stmt\Trait_('Test');
        $this->addRequireImplementsAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @require-implements RequireInterface\n */", $docText);
    }

    public function testAddsSeveralRequireImplementsPHPDocs(): void
    {
        $node = new Node\Stmt\Trait_('Test');
        $this->addRequireImplementsAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @require-implements RequireInterface\n * @require-implements RequireInterface\n */", $docText);
    }

    public function testAddsMultipleRequireImplementsPHPDocs(): void
    {
        $node = new Node\Stmt\Trait_('Test');
        $this->addRequireImplementsAttributesToNode($node);
        $this->addRequireImplementsAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @require-implements RequireInterface\n * @require-implements RequireInterface\n */", $docText);
    }

    private function addRequireImplementsAttributesToNode(Node\Stmt\Trait_ $node, int $num = 1): void
    {
        $value = new Node\Scalar\String_('RequireInterface');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value);
        }
        $attributeName = new FullyQualified(RequireImplements::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
