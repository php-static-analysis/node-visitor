<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use PhpParser\Node;

class IsReadOnlyAttributeNodeVisitorTest extends AttributeNodeVisitorTestBase
{
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
}
