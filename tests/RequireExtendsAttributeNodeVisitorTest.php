<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\RequireExtends;

class RequireExtendsAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsRequireExtendsPHPDoc(): void
    {
        $node = new Node\Stmt\Trait_('Test');
        $this->addRequireExtendsAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @require-extends RequireClass\n */", $docText);
    }

    private function addRequireExtendsAttributeToNode(Node\Stmt\Trait_ $node): void
    {
        $args = [
            new Node\Arg(new Node\Scalar\String_('RequireClass'))
        ];
        $attributeName = new FullyQualified(RequireExtends::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
