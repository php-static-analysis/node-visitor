<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\PropertyRead;

class PropertyReadAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsPropertyReadPHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addPropertyReadAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @property-read string \$param\n */", $docText);
    }

    public function testAddsSeveralPropertyReadPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addPropertyReadAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @property-read string \$param\n * @property-read string \$param\n */", $docText);
    }

    public function testAddsMultiplePropertyReadPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addPropertyReadAttributesToNode($node);
        $this->addPropertyReadAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @property-read string \$param\n * @property-read string \$param\n */", $docText);
    }

    private function addPropertyReadAttributesToNode(Node\Stmt\Class_ $node, int $num = 1): void
    {
        $name = new Identifier('param');
        $value = new Node\Scalar\String_('string');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value, name: $name);
        }
        $attributeName = new FullyQualified(PropertyRead::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
