<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\ParamOut;

class ParamOutAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsParamOutPHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamOutAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param-out string \$param\n */", $docText);
    }

    public function testAddsSeveralParamOutPHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamOutAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param-out string \$param\n * @param-out string \$param\n */", $docText);
    }

    public function testAddsMultipleParamOutPHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamOutAttributesToNode($node);
        $this->addParamOutAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param-out string \$param\n * @param-out string \$param\n */", $docText);
    }

    public function testAddsParamOutPHPDocToParam(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamOutAttributeToParamNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param-out string \$param\n */", $docText);
    }

    private function addParamOutAttributesToNode(Node\Stmt\ClassMethod $node, int $num = 1): void
    {
        $name = new Identifier('param');
        $value = new Node\Scalar\String_('string');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value, name: $name);
        }
        $attributeName = new FullyQualified(ParamOut::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }

    private function addParamOutAttributeToParamNode(Node\Stmt\ClassMethod $node): void
    {
        $var = new Node\Expr\Variable('param');
        $parameter = new Node\Param($var);
        $value = new Node\Scalar\String_('string');
        $args = [new Node\Arg($value)];
        $attributeName = new FullyQualified(ParamOut::class);
        $attribute = new Attribute($attributeName, $args);
        $parameter->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
        $node->params = [$parameter];
    }
}
