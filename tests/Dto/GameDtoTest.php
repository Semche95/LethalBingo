<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\GameDto;
use App\Entity\BingoSquare;
use App\Entity\GameRound;
use App\Entity\Team;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GameDtoTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidDtoHasNoViolations(): void
    {
        $dto = new GameDto(team: new Team(), round: $this->createRound(complete: true));

        $violations = $this->validator->validate($dto);

        $this->assertCount(0, $violations);
    }

    public function testNullTeamAndRoundAreRejected(): void
    {
        $dto = new GameDto();

        $violations = $this->validator->validate($dto);

        $messages = [];
        foreach ($violations as $violation) {
            $messages[$violation->getPropertyPath()] = $violation->getMessage();
        }
        $this->assertSame([
            'team' => 'Veuillez sélectionner une équipe.',
            'round' => 'Veuillez sélectionner un bingo.',
        ], $messages);
    }

    public function testIncompleteRoundIsRejected(): void
    {
        $dto = new GameDto(team: new Team(), round: $this->createRound(complete: false));

        $violations = $this->validator->validate($dto);

        $this->assertCount(1, $violations);
        $this->assertSame('round', $violations[0]->getPropertyPath());
        $this->assertSame(
            'Le bingo sélectionné est incomplet et ne peut pas être utilisé pour une partie.',
            $violations[0]->getMessage(),
        );
    }

    private function createRound(bool $complete): GameRound
    {
        $round = new GameRound()->setSeason(1)->setNumber(1);
        if (!$complete) {
            return $round;
        }
        for ($row = 1; $row <= GameRound::MAX_ROWS; ++$row) {
            for ($col = 1; $col <= GameRound::MAX_COLS; ++$col) {
                $round->addBingoSquare(new BingoSquare()->setRow($row)->setCol($col));
            }
        }

        return $round;
    }
}
