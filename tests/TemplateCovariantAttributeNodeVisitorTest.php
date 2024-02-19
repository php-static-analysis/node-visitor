<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\TemplateCovariant;

class TemplateCovariantAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsTemplateCovariantPHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateCovariantAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template-covariant T\n */", $docText);
    }

    public function testAddsTemplateCovariantWithTypePHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateCovariantAttributeToNode($node, true);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template-covariant T of Exception\n */", $docText);
    }

    public function testAddsMultipleTemplateCovariantPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateCovariantAttributeToNode($node);
        $this->addTemplateCovariantAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template-covariant T\n * @template-covariant T\n */", $docText);
    }

    private function addTemplateCovariantAttributeToNode(Node\Stmt\Class_ $node, bool $addType = false): void
    {
        $args = [
            new Node\Arg(new Node\Scalar\String_('T'))
        ];
        if ($addType) {
            $args[] = new Node\Arg(new Node\Scalar\String_(Exception::class));
        }
        $attributeName = new FullyQualified(TemplateCovariant::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
