<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Template;

class TemplateAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsTemplatePHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template T\n */", $docText);
    }

    public function testAddsTemplateWithTypePHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateAttributeToNode($node, true);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template T of Exception\n */", $docText);
    }

    public function testAddsMultipleTemplatePHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateAttributeToNode($node);
        $this->addTemplateAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template T\n * @template T\n */", $docText);
    }

    private function addTemplateAttributeToNode(Node\Stmt\Class_ $node, bool $addType = false): void
    {
        $args = [
            new Node\Arg(new Node\Scalar\String_('T'))
        ];
        if ($addType) {
            $args[] = new Node\Arg(new Node\Scalar\String_(Exception::class));
        }
        $attributeName = new FullyQualified(Template::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
