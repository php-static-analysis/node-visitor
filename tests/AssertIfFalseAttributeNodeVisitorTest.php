<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\AssertIfFalse;

class AssertIfFalseAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsAssertIfFalsePHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertIfFalseAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert-if-false string \$param\n */", $docText);
    }

    public function testAddsSeveralAssertIfFalsePHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertIfFalseAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert-if-false string \$param\n * @assert-if-false string \$param\n */", $docText);
    }

    public function testAddsMultipleAssertIfFalsePHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertIfFalseAttributesToNode($node);
        $this->addAssertIfFalseAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert-if-false string \$param\n * @assert-if-false string \$param\n */", $docText);
    }

    public function testAddsAssertIfFalsePHPDocToParam(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertIfFalseAttributeToParamNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert-if-false string \$param\n */", $docText);
    }

    private function addAssertIfFalseAttributesToNode(Node\Stmt\ClassMethod $node, int $num = 1): void
    {
        $name = new Identifier('param');
        $value = new Node\Scalar\String_('string');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value, name: $name);
        }
        $attributeName = new FullyQualified(AssertIfFalse::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }

    private function addAssertIfFalseAttributeToParamNode(Node\Stmt\ClassMethod $node): void
    {
        $var = new Node\Expr\Variable('param');
        $parameter = new Node\Param($var);
        $value = new Node\Scalar\String_('string');
        $args = [new Node\Arg($value)];
        $attributeName = new FullyQualified(AssertIfFalse::class);
        $attribute = new Attribute($attributeName, $args);
        $parameter->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
        $node->params = [$parameter];
    }
}
