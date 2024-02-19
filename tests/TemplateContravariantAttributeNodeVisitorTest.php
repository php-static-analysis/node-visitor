<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\TemplateContravariant;

class TemplateContravariantAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsTemplateContravariantPHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateContravariantAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template-contravariant T\n */", $docText);
    }

    public function testAddsTemplateContravariantWithTypePHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateContravariantAttributeToNode($node, true);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template-contravariant T of Exception\n */", $docText);
    }

    public function testAddsMultipleTemplateContravariantPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateContravariantAttributeToNode($node);
        $this->addTemplateContravariantAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template-contravariant T\n * @template-contravariant T\n */", $docText);
    }

    private function addTemplateContravariantAttributeToNode(Node\Stmt\Class_ $node, bool $addType = false): void
    {
        $args = [
            new Node\Arg(new Node\Scalar\String_('T'))
        ];
        if ($addType) {
            $args[] = new Node\Arg(new Node\Scalar\String_(Exception::class));
        }
        $attributeName = new FullyQualified(TemplateContravariant::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
