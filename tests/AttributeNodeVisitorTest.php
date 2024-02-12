<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use Exception;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\IsReadOnly;
use PhpStaticAnalysis\Attributes\Param;
use PhpStaticAnalysis\Attributes\Returns;
use PhpStaticAnalysis\Attributes\Template;
use PhpStaticAnalysis\Attributes\Type;
use PhpStaticAnalysis\NodeVisitor\AttributeNodeVisitor;
use PHPUnit\Framework\TestCase;

class AttributeNodeVisitorTest extends TestCase
{
    private const UNTOUCHED = "/**\n * untouched\n */";

    private AttributeNodeVisitor $nodeVisitor;

    public function setUp(): void
    {
        $this->nodeVisitor = new AttributeNodeVisitor();
    }

    public function testDoesNotProcessUnknownNodes(): void
    {
        $node = new Node\Stmt\Use_([]);
        $this->setDocComment($node, self::UNTOUCHED);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals(self::UNTOUCHED, $docText);
    }

    public function testDoesNotProcessNodesWithoutAttributes(): void
    {
        $node = new Node\Stmt\ClassMethod('test');
        $this->setDocComment($node, self::UNTOUCHED);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals(self::UNTOUCHED, $docText);
    }

    public function testDoesNotProcessAttributeNotAvailableForStmt(): void
    {
        $node = new Node\Stmt\ClassMethod('test');
        $this->setDocComment($node, self::UNTOUCHED);
        $this->addIsReadOnlyAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals(self::UNTOUCHED, $docText);
    }

    public function testAddsReadOnlyPHPDoc(): void
    {
        $node = new Node\Stmt\Property(0, []);
        $this->addIsReadOnlyAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @readonly\n */", $docText);
    }

    public function testAddsReadOnlyPHPDocMaintainingExistingPHPDoc(): void
    {
        $node = new Node\Stmt\Property(0, []);
        $this->setDocComment($node, self::UNTOUCHED);
        $this->addIsReadOnlyAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * untouched\n * @readonly\n */", $docText);
    }

    public function testAddsReturnPHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addReturnsAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @return string\n */", $docText);
    }

    public function testAddsVarPHPDoc(): void
    {
        $node = new Node\Stmt\Property(0, []);
        $this->addTypeAttributeToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @var string\n */", $docText);
    }

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

    public function testAddsParamPHPDoc(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param string \$param\n */", $docText);
    }

    public function testAddsSeveralParamPHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamAttributesToNode($node, 2);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param string \$param\n * @param string \$param\n */", $docText);
    }

    public function testAddsMultiplePHPDocs(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamAttributesToNode($node);
        $this->addParamAttributesToNode($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param string \$param\n * @param string \$param\n */", $docText);
    }

    public function testAddsParamPHPDocToParam(): void
    {
        $node = new Node\Stmt\ClassMethod('Test');
        $this->addParamAttributeToNodeParam($node);
        $this->nodeVisitor->enterNode($node);
        $docText = $this->getDocText($node);
        $this->assertEquals("/**\n * @param string \$param\n */", $docText);
    }

    private function setDocComment(Node $node, string $text): void
    {
        $docComment = new Doc(
            $text,
        );
        $node->setDocComment($docComment);
    }

    private function addIsReadOnlyAttributeToNode(
        Node\Stmt\ClassMethod | Node\Stmt\Property $node
    ): void {
        $attributeName = new FullyQualified(IsReadOnly::class);
        $attribute = new Attribute($attributeName);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }

    private function addReturnsAttributeToNode(Node\Stmt\ClassMethod $node): void
    {
        $args = [
            new Node\Arg(new Node\Scalar\String_('string'))
        ];
        $attributeName = new FullyQualified(Returns::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = [new AttributeGroup([$attribute])];
    }

    private function addTypeAttributeToNode(Node\Stmt\Property $node): void
    {
        $args = [
            new Node\Arg(new Node\Scalar\String_('string'))
        ];
        $attributeName = new FullyQualified(Type::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = [new AttributeGroup([$attribute])];
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

    private function addParamAttributesToNode(Node\Stmt\ClassMethod $node, int $num = 1): void
    {
        $name = new Identifier('param');
        $value = new Node\Scalar\String_('string');
        $args = [];
        for ($i = 0; $i < $num; $i++) {
            $args[] = new Node\Arg($value, name: $name);
        }
        $attributeName = new FullyQualified(Param::class);
        $attribute = new Attribute($attributeName, $args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
    }

    private function addParamAttributeToNodeParam(Node\Stmt\ClassMethod $node): void
    {
        $var = new Node\Expr\Variable('param');
        $parameter = new Node\Param($var);
        $value = new Node\Scalar\String_('string');
        $args = [new Node\Arg($value)];
        $attributeName = new FullyQualified(Param::class);
        $attribute = new Attribute($attributeName, $args);
        $parameter->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([$attribute])]);
        $node->params = [$parameter];
    }

    private function getDocText(Node $node): string
    {
        $docComment = $node->getDocComment();
        $docText = '';
        if ($docComment instanceof Doc) {
            $docText = $docComment->getText();
        }
        return $docText;
    }
}
