<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use other\RequireClass;
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
        $this->assertEquals("/**\n * @require-extends other\RequireClass\n */", $docText);
    }

    private function addRequireExtendsAttributeToNode(Node\Stmt\Trait_ $node): void
    {
        $class = new Node\Name(RequireClass::class);
        $args = [
            new Node\Arg(new Node\Expr\ClassConstFetch($class, 'class'))
        ];
        $attributeName = new FullyQualified(RequireExtends::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}

namespace other;

class RequireClass
{
}
