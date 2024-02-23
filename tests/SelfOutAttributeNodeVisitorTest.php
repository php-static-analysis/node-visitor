<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\SelfOut;

class SelfOutAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsReturnPHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addSelfOutAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @self-out self<T>\n */", $docText);
    }

    private function addSelfOutAttributeToNode(Node\Stmt\ClassMethod $node): void
    {
        $args = [
            new Node\Arg(new Node\Scalar\String_('self<T>'))
        ];
        $attributeName = new FullyQualified(SelfOut::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }
}
