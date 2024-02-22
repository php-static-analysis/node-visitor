<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\TemplateImplements;

class TemplateImplementsAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsTemplateImplementsPHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateImplementsAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template-implements TemplateInterface<int>\n */", $docText);
    }

    public function testAddsSeveralTemplateImplementsPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateImplementsAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template-implements TemplateInterface<int>\n * @template-implements TemplateInterface<int>\n */", $docText);
    }

    public function testAddsMultipleTemplateImplementsPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateImplementsAttributesToNode($node);
        $this->addTemplateImplementsAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template-implements TemplateInterface<int>\n * @template-implements TemplateInterface<int>\n */", $docText);
    }

    private function addTemplateImplementsAttributesToNode(Node\Stmt\Class_ $node, int $num = 1): void
    {
        $value = new Node\Scalar\String_('TemplateInterface<int>');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value);
        }
        $attributeName = new FullyQualified(TemplateImplements::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
