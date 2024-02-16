<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\PropertyWrite;

class PropertyWriteAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsPropertyWritePHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addPropertyWriteAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @property-write string \$param\n */", $docText);
    }

    public function testAddsSeveralPropertyWritePHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addPropertyWriteAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @property-write string \$param\n * @property-write string \$param\n */", $docText);
    }

    public function testAddsMultiplePropertyWritePHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addPropertyWriteAttributesToNode($node);
        $this->addPropertyWriteAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @property-write string \$param\n * @property-write string \$param\n */", $docText);
    }

    private function addPropertyWriteAttributesToNode(Node\Stmt\Class_ $node, int $num = 1): void
    {
        $name = new Identifier('param');
        $value = new Node\Scalar\String_('string');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value, name: $name);
        }
        $attributeName = new FullyQualified(PropertyWrite::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
