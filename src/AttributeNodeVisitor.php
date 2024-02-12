<?php

declare(strict_types=1);

namespace PhpStaticAnalysis\NodeVisitor;

use PhpParser\Comment;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;
use PhpStaticAnalysis\Attributes\IsReadOnly;
use PhpStaticAnalysis\Attributes\Param;
use PhpStaticAnalysis\Attributes\Returns;
use PhpStaticAnalysis\Attributes\Template;
use PhpStaticAnalysis\Attributes\Type;

class AttributeNodeVisitor extends NodeVisitorAbstract
{
    private const ARGS_NONE = 'none';
    private const ARGS_ONE = 'one';
    private const ARGS_TWO_WITH_TYPE = 'two with type';
    private const ARGS_MANY_WITH_NAME = "many with name";

    private const ALLOWED_NODE_TYPES = [
        Stmt\Class_::class,
        Stmt\ClassConst::class,
        Stmt\ClassMethod::class,
        Stmt\Function_::class,
        Stmt\Interface_::class,
        Stmt\Property::class,
        Stmt\Trait_::class,
    ];

    private const ALLOWED_ATTRIBUTES_PER_NODE_TYPE = [
        Stmt\Class_::class => [
            Template::class,
        ],
        Stmt\ClassConst::class => [
            Type::class,
        ],
        Stmt\ClassMethod::class => [
            Param::class,
            Returns::class,
            Template::class,
        ],
        Stmt\Function_::class => [
            Param::class,
            Returns::class,
            Template::class,
        ],
        Stmt\Interface_::class => [
            Template::class,
        ],
        Stmt\Property::class => [
            Type::class,
            IsReadOnly::class,
        ],
        Stmt\Trait_::class => [
            Template::class,
        ],
    ];

    private const SHORT_NAME_TO_FQN = [
        'IsReadOnly' => IsReadOnly::class,
        'Param' => Param::class,
        'Returns' => Returns::class,
        'Template' => Template::class,
        'Type' => Type::class,
    ];

    private const ANNOTATION_PER_ATTRIBUTE = [
        IsReadOnly::class => 'readonly',
        Param::class => 'param',
        Returns::class => 'return',
        Template::class => 'template',
        Type::class => 'var',
    ];

    private const ARGUMENTS_PER_ATTRIBUTE = [
        IsReadOnly::class => self::ARGS_NONE,
        Param::class => self::ARGS_MANY_WITH_NAME,
        Returns::class => self::ARGS_ONE,
        Template::class => self::ARGS_TWO_WITH_TYPE,
        Type::class => self::ARGS_ONE
    ];

    private int $startLine;
    private int $startFilePos;
    private int $startTokenPos;
    private int $endLine;
    private int $endFilePos;
    private int $endTokenPos;

    public function __construct()
    {
        $this->initPositions();
    }

    public function enterNode(Node $node)
    {
        if (in_array($node::class, self::ALLOWED_NODE_TYPES)) {
            /** @var Stmt\Class_|Stmt\ClassMethod|Stmt\Function_|Stmt\Interface_|Stmt\Property|Stmt\Trait_ $node */
            $tagsToAdd = [];
            $attributeGroups = $node->attrGroups;
            $nodeType = $node::class;

            $this->initPositions();

            foreach ($attributeGroups as $attributeGroup) {
                $attributes = $attributeGroup->attrs;
                foreach ($attributes as $attribute) {
                    $attributeName = $attribute->name->toString();
                    $attributeName = self::SHORT_NAME_TO_FQN[$attributeName] ?? $attributeName;
                    if (
                        in_array(
                            $attributeName,
                            self::ALLOWED_ATTRIBUTES_PER_NODE_TYPE[$nodeType]
                        )
                    ) {
                        $args = $attribute->args;
                        $tagCreated = false;
                        switch (self::ARGUMENTS_PER_ATTRIBUTE[$attributeName]) {
                            case self::ARGS_NONE:
                                $tagsToAdd[] = $this->createTag($attributeName);
                                $tagCreated = true;
                                break;
                            case self::ARGS_ONE:
                                if (isset($args[0])) {
                                    $tagsToAdd[] = $this->createTag($attributeName, $args[0]);
                                    $tagCreated = true;
                                }
                                break;
                            case self::ARGS_TWO_WITH_TYPE:
                                if (isset($args[0])) {
                                    $tagsToAdd[] = $this->createTag($attributeName, $args[0], $args[1] ?? null);
                                    $tagCreated = true;
                                }
                                break;
                            case self::ARGS_MANY_WITH_NAME:
                                foreach ($args as $arg) {
                                    $tagsToAdd[] = $this->createTag($attributeName, $arg, useName: true);
                                    $tagCreated = true;
                                }
                                break;
                        }
                        if ($tagCreated) {
                            $this->updatePositions($attribute);
                        }
                    }
                }
            }
            if ($node instanceof Stmt\ClassMethod || $node instanceof Stmt\Function_) {
                foreach ($node->getParams() as $param) {
                    $attributeGroups = $param->attrGroups;
                    foreach ($attributeGroups as $attributeGroup) {
                        $attributes = $attributeGroup->attrs;
                        foreach ($attributes as $attribute) {
                            $attributeName = $attribute->name->toString();
                            $attributeName = self::SHORT_NAME_TO_FQN[$attributeName] ?? $attributeName;
                            if ($attributeName === Param::class) {
                                $args = $attribute->args;
                                $tagCreated = false;
                                if (isset($args[0])) {
                                    $var = $param->var;
                                    if ($var instanceof Node\Expr\Variable) {
                                        $name = $var->name;
                                        if (is_string($name)) {
                                            $tagsToAdd[] = $this->createTag($attributeName, $args[0], useName: true, nameToUse: $name);
                                            $tagCreated = true;
                                        }
                                    }
                                }
                                if ($tagCreated) {
                                    $this->updatePositions($attribute);
                                }
                            }
                        }
                    }
                }
            }
            if ($tagsToAdd !== []) {
                $this->addDocTagsToNode($tagsToAdd, $node);
            }
        }
        return $node;
    }

    private function createTag(
        string $attributeName,
        Arg $argument = null,
        Arg $of = null,
        bool $useName = false,
        string $nameToUse = null
    ): string {
        $tag = '@' . self::ANNOTATION_PER_ATTRIBUTE[$attributeName];
        if ($argument) {
            $value = $argument->value;
            $type = '';
            if ($value instanceof String_) {
                $type = $value->value;
                $tag .= ' ' . $type;
            }
            if ($of) {
                $value = $of->value;
                if ($value instanceof String_) {
                    $tag .= ' of ' . $value->value;
                }
            }
            if ($useName) {
                if ($nameToUse === null) {
                    $nameToUse = $argument->name;
                } else {
                    $nameToUse = new Node\Identifier($nameToUse);
                }
                if ($nameToUse instanceof Node\Identifier) {
                    //we only add a space if it is not a variadic parameter
                    if (!str_ends_with($type, '...')) {
                        $tag .= ' ';
                    }
                    $tag .= '$' . $nameToUse->toString();
                }
            }
        }
        return $tag;
    }

    #[Param(tagsToAdd: 'string[]')]
    private function addDocTagsToNode(array $tagsToAdd, Node $node): void
    {
        $docComment = $node->getDocComment();

        $docText = "/**\n";
        if ($docComment !== null) {
            $docCommentText = $docComment->getText();
            $commentEndPos = strpos($docCommentText, ' */');
            if ($commentEndPos !== false) {
                $this->updatePositions($docComment);
                $docText = substr($docCommentText, 0, $commentEndPos);
            }
        }
        foreach ($tagsToAdd as $tagToAdd) {
            $docText .= ' * ' . $tagToAdd . "\n";
        }
        $docText .= " */";

        $docComment = new Doc(
            $docText,
            $this->startLine,
            $this->startFilePos,
            $this->startTokenPos,
            $this->endLine,
            $this->endFilePos,
            $this->endTokenPos
        );
        $node->setDocComment($docComment);
    }
    
    private function updatePositions(Node|Comment $node): void
    {
        $this->startLine = min($this->startLine, $node->getStartLine());
        $this->startFilePos = min($this->startFilePos, $node->getStartFilePos());
        $this->startTokenPos = min($this->startTokenPos, $node->getStartTokenPos());
        $this->endLine = max($this->endLine, $node->getEndLine());
        $this->endFilePos = max($this->endFilePos, $node->getEndFilePos());
        $this->endTokenPos = max($this->endTokenPos, $node->getEndTokenPos());
    }

    private function initPositions(): void
    {
        $this->startLine = PHP_INT_MAX;
        $this->startFilePos = PHP_INT_MAX;
        $this->startTokenPos = PHP_INT_MAX;
        $this->endLine = 0;
        $this->endFilePos = 0;
        $this->endTokenPos = 0;
    }
}
