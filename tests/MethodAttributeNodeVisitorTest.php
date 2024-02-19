<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Method;

class MethodAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsMethodPHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addMethodAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @method void setString(string \$text)\n */", $docText);
    }

    public function testAddsSeveralMethodPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addMethodAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @method void setString(string \$text)\n * @method void setString(string \$text)\n */", $docText);
    }

    public function testAddsMultipleMethodPHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addMethodAttributesToNode($node);
        $this->addMethodAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @method void setString(string \$text)\n * @method void setString(string \$text)\n */", $docText);
    }

    private function addMethodAttributesToNode(Node\Stmt\Class_ $node, int $num = 1): void
    {
        $value = new Node\Scalar\String_('void setString(string $text)');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value);
        }
        $attributeName = new FullyQualified(Method::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
