<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\TemplateExtends;

class TemplateExtendsAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsTemplateExtendsPHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTemplateExtendsAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @template-extends TemplateClass<int>\n */", $docText);
    }

    private function addTemplateExtendsAttributeToNode(Node\Stmt\Class_ $node): void
    {
        $args = [
            new Node\Arg(new Node\Scalar\String_('TemplateClass<int>'))
        ];
        $attributeName = new FullyQualified(TemplateExtends::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
