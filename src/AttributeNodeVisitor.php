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
use PhpStaticAnalysis\Attributes\Deprecated;
use PhpStaticAnalysis\Attributes\Immutable;
use PhpStaticAnalysis\Attributes\Impure;
use PhpStaticAnalysis\Attributes\Internal;
use PhpStaticAnalysis\Attributes\IsReadOnly;
use PhpStaticAnalysis\Attributes\Method;
use PhpStaticAnalysis\Attributes\Mixin;
use PhpStaticAnalysis\Attributes\Param;
use PhpStaticAnalysis\Attributes\ParamOut;
use PhpStaticAnalysis\Attributes\Property;
use PhpStaticAnalysis\Attributes\PropertyRead;
use PhpStaticAnalysis\Attributes\PropertyWrite;
use PhpStaticAnalysis\Attributes\Pure;
use PhpStaticAnalysis\Attributes\RequireExtends;
use PhpStaticAnalysis\Attributes\RequireImplements;
use PhpStaticAnalysis\Attributes\Returns;
use PhpStaticAnalysis\Attributes\SelfOut;
use PhpStaticAnalysis\Attributes\Template;
use PhpStaticAnalysis\Attributes\TemplateContravariant;
use PhpStaticAnalysis\Attributes\TemplateCovariant;
use PhpStaticAnalysis\Attributes\TemplateExtends;
use PhpStaticAnalysis\Attributes\TemplateImplements;
use PhpStaticAnalysis\Attributes\TemplateUse;
use PhpStaticAnalysis\Attributes\Throws;
use PhpStaticAnalysis\Attributes\Type;

class AttributeNodeVisitor extends NodeVisitorAbstract
{
    public const TOOL_PHPSTAN = 'phpstan';
    public const TOOL_PSALM = 'psalm';

    private const ARGS_NONE = 'none';
    private const ARGS_NONE_WITH_PREFIX = 'none with prefix';
    private const ARGS_ONE = 'one';
    private const ARGS_ONE_OPTIONAL = 'one optional';
    private const ARGS_ONE_WITH_PREFIX = 'one with prefix';
    private const ARGS_TWO_WITH_TYPE = 'two with type';
    private const ARGS_MANY_IN_USE = "many in use";
    private const ARGS_MANY_WITH_NAME = "many with name";
    private const ARGS_MANY_WITHOUT_NAME = "many without name";
    private const ARGS_MANY_WITHOUT_NAME_AND_PREFIX = "many without name and prexif";

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
            Deprecated::class,
            Immutable::class,
            Internal::class,
            Method::class,
            Mixin::class,
            Property::class,
            PropertyRead::class,
            PropertyWrite::class,
            Template::class,
            TemplateContravariant::class,
            TemplateCovariant::class,
            TemplateExtends::class,
            TemplateImplements::class,
            TemplateUse::class,
        ],
        Stmt\ClassConst::class => [
            Deprecated::class,
            Internal::class,
            Type::class,
        ],
        Stmt\ClassMethod::class => [
            Deprecated::class,
            Impure::class,
            Internal::class,
            Param::class,
            ParamOut::class,
            Pure::class,
            Returns::class,
            SelfOut::class,
            Template::class,
            Throws::class,
            Type::class,
        ],
        Stmt\Function_::class => [
            Deprecated::class,
            Impure::class,
            Internal::class,
            Param::class,
            ParamOut::class,
            Pure::class,
            Returns::class,
            Template::class,
            Throws::class,
            Type::class,
        ],
        Stmt\Interface_::class => [
            Deprecated::class,
            Immutable::class,
            Internal::class,
            Method::class,
            Mixin::class,
            Property::class,
            PropertyRead::class,
            PropertyWrite::class,
            Template::class,
            TemplateContravariant::class,
            TemplateCovariant::class,
        ],
        Stmt\Property::class => [
            Deprecated::class,
            Internal::class,
            IsReadOnly::class,
            Property::class,
            Type::class,
        ],
        Stmt\Trait_::class => [
            Deprecated::class,
            Immutable::class,
            Internal::class,
            Method::class,
            Mixin::class,
            Property::class,
            PropertyRead::class,
            PropertyWrite::class,
            RequireExtends::class,
            RequireImplements::class,
            Template::class,
            TemplateContravariant::class,
            TemplateCovariant::class,
        ],
    ];

    private const SHORT_NAME_TO_FQN = [
        'Deprecated' => Deprecated::class,
        'Immutable' => Immutable::class,
        'Impure' => Impure::class,
        'Internal' => Internal::class,
        'IsReadOnly' => IsReadOnly::class,
        'Method' => Method::class,
        'Mixin' => Mixin::class,
        'Param' => Param::class,
        'ParamOut' => ParamOut::class,
        'Property' => Property::class,
        'PropertyRead' => PropertyRead::class,
        'PropertyWrite' => PropertyWrite::class,
        'Pure' => Pure::class,
        'RequireExtends' => RequireExtends::class,
        'RequireImplements' => RequireImplements::class,
        'Returns' => Returns::class,
        'SelfOut' => SelfOut::class,
        'Template' => Template::class,
        'TemplateContravariant' => TemplateContravariant::class,
        'TemplateCovariant' => TemplateCovariant::class,
        'TemplateExtends' => TemplateExtends::class,
        'TemplateImplements' => TemplateImplements::class,
        'TemplateUse' => TemplateUse::class,
        'Throws' => Throws::class,
        'Type' => Type::class,
    ];

    private const ANNOTATION_PER_ATTRIBUTE = [
        Deprecated::class => [
            'all' => 'deprecated',
        ],
        Immutable::class => [
            'all' => 'immutable',
        ],
        Impure::class => [
            'all' => 'impure',
        ],
        Internal::class => [
            'all' => 'internal',
        ],
        IsReadOnly::class => [
            'all' => 'readonly',
        ],
        Method::class => [
            'all' => 'method',
        ],
        Mixin::class => [
            'all' => 'mixin',
        ],
        Param::class => [
            'all' => 'param',
        ],
        ParamOut::class => [
            'all' => 'param-out',
        ],
        Property::class => [
            Stmt\Class_::class => 'property',
            Stmt\Property::class => 'var',
        ],
        PropertyRead::class => [
            'all' => 'property-read',
        ],
        PropertyWrite::class => [
            'all' => 'property-write',
        ],
        Pure::class => [
            'all' => 'pure',
        ],
        RequireExtends::class => [
            'all' => 'require-extends',
        ],
        RequireImplements::class => [
            'all' => 'require-implements',
        ],
        Returns::class => [
            'all' => 'return',
        ],
        SelfOut::class => [
            'all' => 'self-out',
        ],
        Template::class => [
            'all' => 'template',
        ],
        TemplateContravariant::class => [
            'all' => 'template-contravariant',
        ],
        TemplateCovariant::class => [
            'all' => 'template-covariant',
        ],
        TemplateExtends::class => [
            'all' => 'template-extends',
        ],
        TemplateImplements::class => [
            'all' => 'template-implements',
        ],
        TemplateUse::class => [
            'all' => 'template-use',
        ],
        Throws::class => [
            'all' => 'throws',
        ],
        Type::class => [
            Stmt\ClassConst::class => 'var',
            Stmt\ClassMethod::class => 'return',
            Stmt\Function_::class => 'return',
            Stmt\Property::class => 'var',
        ],
    ];

    private const ARGUMENTS_PER_ATTRIBUTE = [
        Deprecated::class => [
            'all' => self::ARGS_NONE,
        ],
        Immutable::class => [
            'all' => self::ARGS_NONE_WITH_PREFIX,
        ],
        Impure::class => [
            'all' => self::ARGS_NONE_WITH_PREFIX,
        ],
        Internal::class => [
            'all' => self::ARGS_ONE_OPTIONAL,
        ],
        IsReadOnly::class => [
            'all' => self::ARGS_NONE,
        ],
        Method::class => [
            'all' => self::ARGS_MANY_WITHOUT_NAME,
        ],
        Mixin::class => [
            'all' => self::ARGS_MANY_WITHOUT_NAME,
        ],
        Param::class => [
            'all' => self::ARGS_MANY_WITH_NAME,
        ],
        ParamOut::class => [
            'all' => self::ARGS_MANY_WITH_NAME,
        ],
        Property::class => [
            Stmt\Class_::class => self::ARGS_MANY_WITH_NAME,
            Stmt\Property::class => self::ARGS_ONE,
        ],
        PropertyRead::class => [
            'all' => self::ARGS_MANY_WITH_NAME,
        ],
        PropertyWrite::class => [
            'all' => self::ARGS_MANY_WITH_NAME,
        ],
        Pure::class => [
            'all' => self::ARGS_NONE_WITH_PREFIX,
        ],
        RequireExtends::class => [
            'all' => self::ARGS_ONE_WITH_PREFIX,
        ],
        RequireImplements::class => [
            'all' => self::ARGS_MANY_WITHOUT_NAME_AND_PREFIX,
        ],
        Returns::class => [
            'all' => self::ARGS_ONE,
        ],
        SelfOut::class => [
            'all' => self::ARGS_ONE_WITH_PREFIX,
        ],
        Template::class => [
            'all' => self::ARGS_TWO_WITH_TYPE,
        ],
        TemplateContravariant::class => [
            'all' => self::ARGS_TWO_WITH_TYPE,
        ],
        TemplateCovariant::class => [
            'all' => self::ARGS_TWO_WITH_TYPE,
        ],
        TemplateExtends::class => [
            'all' => self::ARGS_ONE,
        ],
        TemplateImplements::class => [
            'all' => self::ARGS_MANY_WITHOUT_NAME,
        ],
        TemplateUse::class => [
            'all' => self::ARGS_MANY_IN_USE,
        ],
        Throws::class => [
            'all' => self::ARGS_MANY_WITHOUT_NAME,
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

    public function __construct(
        private string $toolType = ''
    ) {
        $this->initPositions();
    }

    public function enterNode(Node $node)
    {
        if (in_array($node::class, self::ALLOWED_NODE_TYPES)) {
            /** @var Stmt\Class_|Stmt\ClassConst|Stmt\ClassMethod|Stmt\Function_|Stmt\Interface_|Stmt\Property|Stmt\Trait_ $node */
            $tagsToAdd = [];
            $useTagsToAdd = [];
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
                            case self::ARGS_NONE_WITH_PREFIX:
                                $tagsToAdd[] = $this->createTag($nodeType, $attributeName, prefix: $this->toolType);
                                $tagCreated = true;
                                break;
                            case self::ARGS_ONE:
                                if (isset($args[0])) {
                                    $tagsToAdd[] = $this->createTag($nodeType, $attributeName, $args[0]);
                                    $tagCreated = true;
                                }
                                break;
                            case self::ARGS_ONE_WITH_PREFIX:
                                if (isset($args[0])) {
                                    $tagsToAdd[] = $this->createTag($nodeType, $attributeName, $args[0], prefix: $this->toolType);
                                    $tagCreated = true;
                                }
                                break;
                            case self::ARGS_ONE_OPTIONAL:
                                if (isset($args[0])) {
                                    $tagsToAdd[] = $this->createTag(
                                        $nodeType,
                                        $attributeName,
                                        $args[0],
                                        prefix: $this->toolType === self::TOOL_PSALM ? $this->toolType : null
                                    );
                                } else {
                                    $tagsToAdd[] = $this->createTag($nodeType, $attributeName);
                                }
                                $tagCreated = true;
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
                            case self::ARGS_MANY_WITHOUT_NAME:
                                foreach ($args as $arg) {
                                    $tagsToAdd[] = $this->createTag($nodeType, $attributeName, $arg);
                                    $tagCreated = true;
                                }
                                break;
                            case self::ARGS_MANY_WITHOUT_NAME_AND_PREFIX:
                                foreach ($args as $arg) {
                                    $tagsToAdd[] = $this->createTag($nodeType, $attributeName, $arg, prefix: $this->toolType);
                                    $tagCreated = true;
                                }
                                break;
                            case self::ARGS_MANY_IN_USE:
                                foreach ($args as $arg) {
                                    if ($arg->value instanceof String_) {
                                        $useValue = $arg->value->value;
                                        $useTagsToAdd[$useValue] = $this->createTag($nodeType, $attributeName, $arg);
                                    }
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
                $tagsToAdd = array_merge($tagsToAdd, $this->getParamTagsFromParams($node));
            }
            if ($tagsToAdd !== []) {
                $this->addDocTagsToNode($tagsToAdd, $node);
            }
            if ($useTagsToAdd !== [] && $node instanceof Stmt\Class_) {
                $this->addUseDocTagsToNodeTraitUses($useTagsToAdd, $node);
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
        string $nameToUse = null,
        string $prefix = null
    ): string {
        if (array_key_exists($nodeType, self::ANNOTATION_PER_ATTRIBUTE[$attributeName])) {
            $tagName = self::ANNOTATION_PER_ATTRIBUTE[$attributeName][$nodeType];
        } elseif (array_key_exists('all', self::ANNOTATION_PER_ATTRIBUTE[$attributeName])) {
            $tagName = self::ANNOTATION_PER_ATTRIBUTE[$attributeName]['all'];
        } else {
            return '';
        }
        if ($prefix !== null && $prefix !== '') {
            $tagName = $prefix . '-' . $tagName;
        }
        $tag = '@' . $tagName;
        if ($argument) {
            $value = $argument->value;
            $type = '';
            if ($value instanceof String_) {
                $type = $value->value;
            } elseif ($value instanceof Node\Expr\ClassConstFetch &&
                $value->class instanceof Node\Name &&
                $value->name instanceof Node\Identifier &&
                (string)$value->name == 'class'
            ) {
                $type = (string)$value->class;
                if ($this->toolType === self::TOOL_PHPSTAN) {
                    $type = '\\' . $type;
                }
            }
            if ($type !== '') {
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

    #[Returns('string[]')]
    private function getParamTagsFromParams(Stmt\ClassMethod|Stmt\Function_ $node): array
    {
        $nodeType = $node::class;
        $tagsToAdd = [];
        foreach ($node->getParams() as $param) {
            $attributeGroups = $param->attrGroups;
            foreach ($attributeGroups as $attributeGroup) {
                $attributes = $attributeGroup->attrs;
                foreach ($attributes as $attribute) {
                    $attributeName = $attribute->name->toString();
                    $attributeName = self::SHORT_NAME_TO_FQN[$attributeName] ?? $attributeName;
                    if ($attributeName === Param::class || $attributeName === ParamOut::class) {
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
        return $tagsToAdd;
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

    #[Param(useTagsToAdd: 'string[]')]
    private function addUseDocTagsToNodeTraitUses(array $useTagsToAdd, Stmt\Class_ $node): void
    {
        $this->initPositions();
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    foreach ($useTagsToAdd as $tagValue => $useTag) {
                        $tagParts = explode('<', (string)$tagValue);
                        $tagName = $tagParts[0];
                        $parts = array_reverse(explode('\\', $tagName));
                        $traitParts = array_reverse($trait->getParts());
                        $useMatches = true;
                        foreach ($parts as $i => $part) {
                            if (!isset($traitParts[$i]) || $traitParts[$i] !== $part) {
                                $useMatches = false;
                                break;
                            }
                        }
                        if ($useMatches) {
                            $this->addDocTagsToNode([$useTag], $stmt);
                            unset($useTagsToAdd[$tagName]);
                            break;
                        }
                    }
                }
            }
        }
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
