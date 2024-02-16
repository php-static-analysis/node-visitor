<?php

namespace test\PhpStaticAnalysis\NodeVisitor;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpStaticAnalysis\Attributes\IsReadOnly;
use PhpStaticAnalysis\Attributes\Param;
use PhpStaticAnalysis\Attributes\Property;
use PhpStaticAnalysis\Attributes\PropertyRead;
use PhpStaticAnalysis\Attributes\PropertyWrite;
use PhpStaticAnalysis\Attributes\Returns;
use PhpStaticAnalysis\Attributes\Template;
use PhpStaticAnalysis\Attributes\Type;

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
