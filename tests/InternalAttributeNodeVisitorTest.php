<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Internal;
use PhpStaticAnalysis\NodeVisitor\AttributeNodeVisitor;

class InternalAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsInternalPHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addInternalAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @internal\n */", $docText);
    }

    public function testAddsInternalPHPDocWithNamespace(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addInternalAttributeToNode($node, true);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @internal A\B\n */", $docText);
    }

    public function testDoesNotAddToolPrefixToAnnotationIfNotPsalm(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->nodeVisitor = new AttributeNodeVisitor('phpstan');
        $this->addInternalAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @internal\n */", $docText);
    }

    public function testDoesNotAddToolPrefixToAnnotationIfNoNamespace(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->nodeVisitor = new AttributeNodeVisitor('phpstan');
        $this->addInternalAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @internal\n */", $docText);
    }

    public function testAddsToolPrefixToAnnotation(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->nodeVisitor = new AttributeNodeVisitor('psalm');
        $this->addInternalAttributeToNode($node, true);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @psalm-internal A\B\n */", $docText);
    }

    private function addInternalAttributeToNode(Node\Stmt\Class_ $node, bool $addNamespace = false): void
    {
        $args = [];
        if ($addNamespace) {
            $args[] = new Node\Arg(new Node\Scalar\String_('A\B'));
        }
        $attributeName = new FullyQualified(Internal::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }
}
