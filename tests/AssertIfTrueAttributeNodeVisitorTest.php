<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\AssertIfTrue;

class AssertIfTrueAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsAssertIfTruePHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertIfTrueAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert-if-true string \$param\n */", $docText);
    }

    public function testAddsSeveralAssertIfTruePHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertIfTrueAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert-if-true string \$param\n * @assert-if-true string \$param\n */", $docText);
    }

    public function testAddsMultipleAssertIfTruePHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertIfTrueAttributesToNode($node);
        $this->addAssertIfTrueAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert-if-true string \$param\n * @assert-if-true string \$param\n */", $docText);
    }

    public function testAddsAssertIfTruePHPDocToParam(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addAssertIfTrueAttributeToParamNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @assert-if-true string \$param\n */", $docText);
    }

    private function addAssertIfTrueAttributesToNode(Node\Stmt\ClassMethod $node, int $num = 1): void
    {
        $name = new Identifier('param');
        $value = new Node\Scalar\String_('string');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value, name: $name);
        }
        $attributeName = new FullyQualified(AssertIfTrue::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }

    private function addAssertIfTrueAttributeToParamNode(Node\Stmt\ClassMethod $node): void
    {
        $var = new Node\Expr\Variable('param');
        $parameter = new Node\Param($var);
        $value = new Node\Scalar\String_('string');
        $args = [new Node\Arg($value)];
        $attributeName = new FullyQualified(AssertIfTrue::class);
        $attribute = new Attribute($attributeName, $args);
        $parameter->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
        $node->params = [$parameter];
    }
}
