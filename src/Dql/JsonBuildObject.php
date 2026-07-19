<?php

declare(strict_types=1);

namespace App\Dql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

final class JsonBuildObject extends FunctionNode
{
    /** @var list<array{0: mixed, 1: mixed}> */
    private array $pairs = [];

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $key = $parser->StringPrimary();
        $parser->match(TokenType::T_COMMA);
        $value = $parser->StringPrimary();
        $this->pairs[] = [$key, $value];

        while ($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);
            $key = $parser->StringPrimary();
            $parser->match(TokenType::T_COMMA);
            $value = $parser->StringPrimary();
            $this->pairs[] = [$key, $value];
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $args = [];

        foreach ($this->pairs as [$key, $value]) {
            $args[] = $sqlWalker->walkStringPrimary($key);
            $args[] = $sqlWalker->walkStringPrimary($value);
        }

        return sprintf('JSON_BUILD_OBJECT(%s)', implode(', ', $args));
    }
}
