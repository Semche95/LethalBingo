<?php

declare(strict_types=1);

namespace App\Dql;

use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

final class JsonAgg extends FunctionNode
{
    private mixed $expression = null;

    private mixed $orderBy = null;

    /** @var ConditionalExpression|null */
    private mixed $filterCondition = null;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->expression = $parser->StringPrimary();

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(TokenType::T_ORDER)) {
            $this->orderBy = $parser->OrderByClause();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(TokenType::T_IDENTIFIER) && strtoupper($lexer->lookahead->value) === 'FILTER') {
            $parser->match(TokenType::T_IDENTIFIER);
            $parser->match(TokenType::T_OPEN_PARENTHESIS);
            $parser->match(TokenType::T_WHERE);
            $this->filterCondition = $parser->ConditionalExpression();
            $parser->match(TokenType::T_CLOSE_PARENTHESIS);
        }
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $inner = sprintf(
            'JSON_AGG(%s%s)',
            $sqlWalker->walkStringPrimary($this->expression),
            $this->orderBy ? ' ' . $sqlWalker->walkOrderByClause($this->orderBy) : '',
        );

        if ($this->filterCondition !== null) {
            return 'COALESCE(' . $inner . ' FILTER (WHERE ' . $sqlWalker->walkConditionalExpression($this->filterCondition) . '), \'[]\')';
        }

        return $inner;
    }
}
