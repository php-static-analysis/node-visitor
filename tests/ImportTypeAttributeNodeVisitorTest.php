<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\ImportType;

class ImportTypeAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsImportTypePHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addImportTypeAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @import-type StringArray from StringClass\n */", $docText);
    }

    public function testAddsImportTypePHPDocWithoutArgumentName(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addImportTypeAttributesToNode($node, useArgumentName: false);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @import-type StringArray from StringClass\n */", $docText);
    }

    public function testAddsSeveralImportTypePHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addImportTypeAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @import-type StringArray from StringClass\n * @import-type StringArray from StringClass\n */", $docText);
    }

    public function testAddsMultipleImportTypePHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addImportTypeAttributesToNode($node);
        $this->addImportTypeAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @import-type StringArray from StringClass\n * @import-type StringArray from StringClass\n */", $docText);
    }

    private function addImportTypeAttributesToNode(Node\Stmt\Class_ $node, int $num = 1, bool $useArgumentName = true): void
    {
        $args = [];
        if ($useArgumentName) {
            $name = new Identifier('StringArray');
            $value = new Node\Scalar\String_('StringClass');
            for ($i = 0; $i < $num; $i++) {
                $args[] = new Node\Arg($value, name: $name);
            }
        } else {
            $value = new Node\Scalar\String_('StringArray from StringClass');
            for ($i = 0; $i < $num; $i++) {
                $args[] = new Node\Arg($value);
            }
        }
        $attributeName = new FullyQualified(ImportType::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }
}
