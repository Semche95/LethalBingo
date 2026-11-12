<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\GameDto;
use App\Entity\BingoSquare;
use App\Entity\GameRound;
use App\Entity\Team;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testCountdownDurationAddsHoursAndMinutes(): void
    {
        $this->assertSame(5400, new GameDto()->getCountdownDurationSeconds());
        $this->assertSame(2700, new GameDto(countdownHours: 0, countdownMinutes: 45)->getCountdownDurationSeconds());
        $this->assertSame(7200, new GameDto(countdownHours: 2, countdownMinutes: null)->getCountdownDurationSeconds());
    }

    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('provideInvalidCountdownDurations')]
    public function testInvalidCountdownDurationIsRejected(?int $hours, ?int $minutes, array $expected): void
    {
        $dto = new GameDto(team: new Team(), round: $this->createRound(complete: true), countdownHours: $hours, countdownMinutes: $minutes);

        $messages = [];
        foreach ($this->validator->validate($dto) as $violation) {
            $messages[$violation->getPropertyPath()] = $violation->getMessage();
        }
        $this->assertSame($expected, $messages);
    }

    /**
     * @return array<string, array{int|null, int|null, array<string, string>}>
     */
    public static function provideInvalidCountdownDurations(): array
    {
        return [
            'zero' => [0, 0, ['countdownHours' => 'La durée doit être supérieure à zéro.']],
            'both empty' => [null, null, ['countdownHours' => 'La durée doit être supérieure à zéro.']],
            'hours above the maximum' => [100, 0, ['countdownHours' => 'Les heures vont de 0 à 99.']],
            'negative hours' => [-1, 30, ['countdownHours' => 'Les heures vont de 0 à 99.']],
            'minutes above 59' => [1, 60, ['countdownMinutes' => 'Les minutes vont de 0 à 59.']],
        ];
    }

    public function testMaximumCountdownDurationIsAccepted(): void
    {
        $dto = new GameDto(team: new Team(), round: $this->createRound(complete: true), countdownHours: 99, countdownMinutes: 59);

        $this->assertCount(0, $this->validator->validate($dto));
    }

    public function testCountdownDurationIsIgnoredWithoutCountdown(): void
    {
        $dto = new GameDto(team: new Team(), round: $this->createRound(complete: true), hasCountdown: false, countdownHours: 0, countdownMinutes: 99);

        $this->assertCount(0, $this->validator->validate($dto));
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
