<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Type;

class TypeAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsVarPHPDoc(): void
    {
        $node = new Node\Stmt\Property(0, []);
        $this->addTypeAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @var string\n */", $docText);
    }

    public function testAddsReturnPHPDocWithTypeAttribute(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addTypeAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @return string\n */", $docText);
    }

    private function addTypeAttributeToNode(Node\Stmt\Property|Node\Stmt\ClassMethod $node): void
    {
        $args = [
            new Node\Arg(new Node\Scalar\String_('string'))
        ];
        $attributeName = new FullyQualified(Type::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }
}
