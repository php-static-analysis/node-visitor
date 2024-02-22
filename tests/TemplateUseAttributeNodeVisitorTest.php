<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\Param;
use PhpStaticAnalysis\Attributes\TemplateUse;

class TemplateUseAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
    public function testAddsTemplateUsePHPDoc(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTraitUsesToClass($node, ['test']);
        $this->addTemplateUseAttributeToNode($node, 'test');
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getUseDocText($node, 'test');
        $this->assertEquals("/**\n * @template-use test<int>\n */", $docText);
    }

    public function testAddsTemplateUsePHPDocWithFQN(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTraitUsesToClass($node, ['\PhpStaticAnalysis\NodeVisitor\test']);
        $this->addTemplateUseAttributeToNode($node, '\PhpStaticAnalysis\NodeVisitor\test');
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getUseDocText($node, '\PhpStaticAnalysis\NodeVisitor\test');
        $this->assertEquals("/**\n * @template-use \\PhpStaticAnalysis\\NodeVisitor\\test<int>\n */", $docText);
    }

    public function testAddsTemplateUsePHPDocWithPartialMatch(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTraitUsesToClass($node, ['\PhpStaticAnalysis\NodeVisitor\test']);
        $this->addTemplateUseAttributeToNode($node, 'NodeVisitor\test');
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getUseDocText($node, '\PhpStaticAnalysis\NodeVisitor\test');
        $this->assertEquals("/**\n * @template-use NodeVisitor\\test<int>\n */", $docText);
    }

    public function testDoesNotAddTemplateUsePHPDocWithoutFullMatch(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTraitUsesToClass($node, ['\PhpStaticAnalysis\NodeVisitor\test']);
        $this->addTemplateUseAttributeToNode($node, '\Other\NodeVisitor\test');
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getUseDocText($node, '\PhpStaticAnalysis\NodeVisitor\test');
        $this->assertEquals("", $docText);
    }

    public function testAddsTemplateUsePHPDocToTheRightTraitUse(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTraitUsesToClass($node, [
            '\Other\NodeVisitor\test',
            '\PhpStaticAnalysis\NodeVisitor\test'
        ]);
        $this->addTemplateUseAttributeToNode($node, '\PhpStaticAnalysis\NodeVisitor\test');
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getUseDocText($node, '\Other\NodeVisitor\test');
        $this->assertEquals("", $docText);
        $docText = $this->getUseDocText($node, '\PhpStaticAnalysis\NodeVisitor\test');
        $this->assertEquals("/**\n * @template-use \\PhpStaticAnalysis\\NodeVisitor\\test<int>\n */", $docText);
    }

    public function testAddsSeveralTemplateUsePHPDocs(): void
    {
        $node = new Node\Stmt\Class_('Test');
        $this->addTraitUsesToClass($node, [
            '\Other\template',
            '\PhpStaticAnalysis\NodeVisitor\test'
        ]);
        $this->addTemplateUseAttributeToNode($node, 'template');
        $this->addTemplateUseAttributeToNode($node, '\PhpStaticAnalysis\NodeVisitor\test');
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getUseDocText($node, '\Other\template');
        $this->assertEquals("/**\n * @template-use template<int>\n */", $docText);
        $docText = $this->getUseDocText($node, '\PhpStaticAnalysis\NodeVisitor\test');
        $this->assertEquals("/**\n * @template-use \\PhpStaticAnalysis\\NodeVisitor\\test<int>\n */", $docText);
    }

    #[Param(traitNames: 'string[]')]
    private function addTraitUsesToClass(Node\Stmt\Class_ $node, array $traitNames): void
    {
        foreach ($traitNames as $traitName) {
            $trait = new FullyQualified($traitName);
            $useTrait = new Node\Stmt\TraitUse([$trait]);
            $node->stmts[] = $useTrait;
        }
    }

    private function addTemplateUseAttributeToNode(Node\Stmt\Class_ $node, string $templateName): void
    {
        $value = new Node\Scalar\String_($templateName . '<int>');
        $arg = new Node\Arg($value);
        $attributeName = new FullyQualified(TemplateUse::class);
        $attribute = new Attribute($attributeName, [$arg]);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }

    private function getUseDocText(Node\Stmt\Class_ $node, string $traitName): string
    {
        $docText = '';
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if ($traitName == (string)$trait) {
                        $docComment = $stmt->getDocComment();
                        if ($docComment instanceof Doc) {
                            $docText = $docComment->getText();
                            break;
                        }
                    }
                }
            }
        }
        return $docText;
    }
}
