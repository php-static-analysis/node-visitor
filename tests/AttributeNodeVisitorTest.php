<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;

class AttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
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
}
