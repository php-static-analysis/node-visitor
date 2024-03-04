<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\DefineType;

class DefineTypeAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsDefineTypePHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addDefineTypeAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @type StringArray string[]\n */", $docText);
    }

    public function testAddsDefineTypePHPDocWithoutArgumentName(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addDefineTypeAttributesToNode($node, useArgumentName: false);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @type StringArray string[]\n */", $docText);
    }

    public function testAddsSeveralDefineTypePHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addDefineTypeAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @type StringArray string[]\n * @type StringArray string[]\n */", $docText);
    }

    public function testAddsMultipleDefineTypePHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addDefineTypeAttributesToNode($node);
        $this->addDefineTypeAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @type StringArray string[]\n * @type StringArray string[]\n */", $docText);
    }

    private function addDefineTypeAttributesToNode(Node\Stmt\Class_ $node, int $num = 1, bool $useArgumentName = true): void
    {
        $args = [];
        if ($useArgumentName) {
            $name = new Identifier('StringArray');
            $value = new Node\Scalar\String_('string[]');
            for ($i = 0; $i < $num; $i++) {
                $args[] = new Node\Arg($value, name: $name);
            }
        } else {
            $value = new Node\Scalar\String_('StringArray string[]');
            for ($i = 0; $i < $num; $i++) {
                $args[] = new Node\Arg($value);
            }
        }
        $attributeName = new FullyQualified(DefineType::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
