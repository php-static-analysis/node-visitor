<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Property;

class PropertyAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsPropertyPHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addPropertyAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @property string \$param\n */", $docText);
    }

    public function testAddsSeveralPropertyPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addPropertyAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @property string \$param\n * @property string \$param\n */", $docText);
    }

    public function testAddsMultiplePropertyPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addPropertyAttributesToNode($node);
        $this->addPropertyAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @property string \$param\n * @property string \$param\n */", $docText);
    }

    public function testAddsVarPHPDocForPropertyAttribute(): void
    {
        $node = new Node\Stmt\Property(0, []);
        $this->addPropertyAttributeToPropertyNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @var string\n */", $docText);
    }

    private function addPropertyAttributesToNode(Node\Stmt\Class_ $node, int $num = 1): void
    {
        $name = new Identifier('param');
        $value = new Node\Scalar\String_('string');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value, name: $name);
        }
        $attributeName = new FullyQualified(Property::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }

    private function addPropertyAttributeToPropertyNode(Node\Stmt\Property $node): void
    {
        $args = [
            new Node\Arg(new Node\Scalar\String_('string'))
        ];
        $attributeName = new FullyQualified(Property::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }
}
