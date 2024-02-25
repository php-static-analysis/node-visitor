<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Throws;

class ThrowsAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsThrowsPHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addThrowsAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @throws Exception\n */", $docText);
    }

    public function testAddsSeveralThrowsPHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addThrowsAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @throws Exception\n * @throws Exception\n */", $docText);
    }

    public function testAddsMultipleThrowsPHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addThrowsAttributesToNode($node);
        $this->addThrowsAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @throws Exception\n * @throws Exception\n */", $docText);
    }

    private function addThrowsAttributesToNode(Node\Stmt\ClassMethod $node, int $num = 1): void
    {
        $exception = new Node\Name(Exception::class);
        $value = new Node\Expr\ClassConstFetch($exception, 'class');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value);
        }
        $attributeName = new FullyQualified(Throws::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
