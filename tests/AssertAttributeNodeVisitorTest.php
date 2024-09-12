<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Assert;

class AssertAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsAssertPHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert string \$param\n */", $docText);
    }

    public function testAddsSeveralAssertPHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert string \$param\n * @assert string \$param\n */", $docText);
    }

    public function testAddsMultipleAssertPHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertAttributesToNode($node);
        $this->addAssertAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert string \$param\n * @assert string \$param\n */", $docText);
    }

    public function testAddsAssertPHPDocToParam(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertAttributeToParamNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert string \$param\n */", $docText);
    }

    private function addAssertAttributesToNode(Node\Stmt\ClassMethod $node, int $num = 1): void
    {
        $name = new Identifier('param');
        $value = new Node\Scalar\String_('string');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value, name: $name);
        }
        $attributeName = new FullyQualified(Assert::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }

    private function addAssertAttributeToParamNode(Node\Stmt\ClassMethod $node): void
    {
        $var = new Node\Expr\Variable('param');
        $parameter = new Node\Param($var);
        $value = new Node\Scalar\String_('string');
        $args = [new Node\Arg($value)];
        $attributeName = new FullyQualified(Assert::class);
        $attribute = new Attribute($attributeName, $args);
        $parameter->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
        $node->params = [$parameter];
    }
}
