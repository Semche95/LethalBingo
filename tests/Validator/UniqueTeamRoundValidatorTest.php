<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Dto\GameDto;
use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\Team;
use App\Repository\GameRepository;
use App\Validator\UniqueTeamRound;
use App\Validator\UniqueTeamRoundValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UniqueTeamRoundValidatorTest extends TestCase
{
    public function testNoViolationWithoutExistingGame(): void
    {
        $this->assertNoViolation(new GameDto(new Team(), new GameRound()), []);
    }

    public function testNoViolationWhenTheOnlyGameIsTheEditedOne(): void
    {
        $game = new Game();

        $this->assertNoViolation(new GameDto(new Team(), new GameRound(), game: $game), [$game]);
    }

    public function testNoViolationWithoutTeamOrRound(): void
    {
        $this->assertNoViolation(new GameDto(round: new GameRound()), null);
        $this->assertNoViolation(new GameDto(team: new Team()), null);
    }

    public function testViolationWhenTheTeamAlreadyPlayedTheRound(): void
    {
        $constraint = new UniqueTeamRound();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('atPath')->with('round')->willReturnSelf();
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($builder);

        $this->createValidator([new Game()])
            ->validateInContext(new GameDto(new Team(), new GameRound(), game: new Game()), $constraint, $context);
    }

    /**
     * @param list<Game>|null $existingGames null when the repository must not be queried
     */
    private function assertNoViolation(GameDto $dto, ?array $existingGames): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $this->createValidator($existingGames)->validateInContext($dto, new UniqueTeamRound(), $context);
    }

    /**
     * @param list<Game>|null $existingGames
     */
    private function createValidator(?array $existingGames): UniqueTeamRoundValidator
    {
        $repository = $this->createMock(GameRepository::class);
        $repository->expects($existingGames === null ? $this->never() : $this->once())
            ->method('findBy')
            ->willReturn($existingGames ?? []);

        return new UniqueTeamRoundValidator($repository);
    }
}
