<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Param;

class ParamAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsParamPHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param string \$param\n */", $docText);
    }

    public function testAddsSeveralParamPHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param string \$param\n * @param string \$param\n */", $docText);
    }

    public function testAddsMultipleParamPHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamAttributesToNode($node);
        $this->addParamAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param string \$param\n * @param string \$param\n */", $docText);
    }

    public function testAddsParamPHPDocToParam(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamAttributeToParamNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param string \$param\n */", $docText);
    }

    private function addParamAttributesToNode(Node\Stmt\ClassMethod $node, int $num = 1): void
    {
        $name = new Identifier('param');
        $value = new Node\Scalar\String_('string');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value, name: $name);
        }
        $attributeName = new FullyQualified(Param::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }

    private function addParamAttributeToParamNode(Node\Stmt\ClassMethod $node): void
    {
        $var = new Node\Expr\Variable('param');
        $parameter = new Node\Param($var);
        $value = new Node\Scalar\String_('string');
        $args = [new Node\Arg($value)];
        $attributeName = new FullyQualified(Param::class);
        $attribute = new Attribute($attributeName, $args);
        $parameter->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
        $node->params = [$parameter];
    }
}
