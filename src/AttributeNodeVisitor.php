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
use PhpStaticAnalysis\Attributes\Property;
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
            Property::class,
            Template::class,
        ],
        Stmt\ClassConst::class => [
            Type::class,
        ],
        Stmt\ClassMethod::class => [
            Param::class,
            Returns::class,
            Template::class,
            Type::class,
        ],
        Stmt\Function_::class => [
            Param::class,
            Returns::class,
            Template::class,
            Type::class,
        ],
        Stmt\Interface_::class => [
            Template::class,
        ],
        Stmt\Property::class => [
            IsReadOnly::class,
            Property::class,
            Type::class,
        ],
        Stmt\Trait_::class => [
            Template::class,
        ],
    ];

    private const SHORT_NAME_TO_FQN = [
        'IsReadOnly' => IsReadOnly::class,
        'Param' => Param::class,
        'Property' => Property::class,
        'Returns' => Returns::class,
        'Template' => Template::class,
        'Type' => Type::class,
    ];

    private const ANNOTATION_PER_ATTRIBUTE = [
        IsReadOnly::class => [
                'all' => 'readonly',
            ],
        Param::class => [
                'all' => 'param',
            ],
        Property::class => [
            Stmt\Class_::class => 'property',
            Stmt\Property::class => 'var',
        ],
        Returns::class => [
                'all' => 'return',
            ],
        Template::class => [
                'all' => 'template',
            ],
        Type::class => [
                Stmt\ClassConst::class => 'var',
                Stmt\ClassMethod::class => 'return',
                Stmt\Function_::class => 'return',
                Stmt\Property::class => 'var',
            ],
    ];

    private const ARGUMENTS_PER_ATTRIBUTE = [
        IsReadOnly::class => [
            'all' => self::ARGS_NONE,
        ],
        Param::class => [
            'all' => self::ARGS_MANY_WITH_NAME,
        ],
        Property::class => [
            Stmt\Class_::class => self::ARGS_MANY_WITH_NAME,
            Stmt\Property::class => self::ARGS_ONE,
        ],
        Returns::class => [
            'all' => self::ARGS_ONE,
        ],
        Template::class => [
            'all' => self::ARGS_TWO_WITH_TYPE,
        ],
        Type::class => [
            'all' => self::ARGS_ONE
        ],
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
            /** @var Stmt\Class_|Stmt\ClassConst|Stmt\ClassMethod|Stmt\Function_|Stmt\Interface_|Stmt\Property|Stmt\Trait_ $node */
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

                        if (array_key_exists($nodeType, self::ARGUMENTS_PER_ATTRIBUTE[$attributeName])) {
                            $argumentType = self::ARGUMENTS_PER_ATTRIBUTE[$attributeName][$nodeType];
                        } elseif (array_key_exists('all', self::ARGUMENTS_PER_ATTRIBUTE[$attributeName])) {
                            $argumentType = self::ARGUMENTS_PER_ATTRIBUTE[$attributeName]['all'];
                        } else {
                            continue;
                        }
                        switch ($argumentType) {
                            case self::ARGS_NONE:
                                $tagsToAdd[] = $this->createTag($nodeType, $attributeName);
                                $tagCreated = true;
                                break;
                            case self::ARGS_ONE:
                                if (isset($args[0])) {
                                    $tagsToAdd[] = $this->createTag($nodeType, $attributeName, $args[0]);
                                    $tagCreated = true;
                                }
                                break;
                            case self::ARGS_TWO_WITH_TYPE:
                                if (isset($args[0])) {
                                    $tagsToAdd[] = $this->createTag($nodeType, $attributeName, $args[0], $args[1] ?? null);
                                    $tagCreated = true;
                                }
                                break;
                            case self::ARGS_MANY_WITH_NAME:
                                foreach ($args as $arg) {
                                    $tagsToAdd[] = $this->createTag($nodeType, $attributeName, $arg, useName: true);
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
                                            $tagsToAdd[] = $this->createTag($nodeType, $attributeName, $args[0], useName: true, nameToUse: $name);
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
        string $nodeType,
        string $attributeName,
        Arg $argument = null,
        Arg $of = null,
        bool $useName = false,
        string $nameToUse = null
    ): string {
        if (array_key_exists($nodeType, self::ANNOTATION_PER_ATTRIBUTE[$attributeName])) {
            $tagName = self::ANNOTATION_PER_ATTRIBUTE[$attributeName][$nodeType];
        } elseif (array_key_exists('all', self::ANNOTATION_PER_ATTRIBUTE[$attributeName])) {
            $tagName = self::ANNOTATION_PER_ATTRIBUTE[$attributeName]['all'];
        } else {
            return '';
        }
        $tag = '@' . $tagName;
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
