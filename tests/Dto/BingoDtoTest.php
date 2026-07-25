<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\BingoDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BingoDtoTest extends TestCase
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
        $dto = new BingoDto(season: 1, round: 1, squares: []);

        $violations = $this->validator->validate($dto);

        $this->assertCount(0, $violations);
    }

    public function testSeasonMustBePositive(): void
    {
        $dto = new BingoDto(season: -1, round: 1);

        $violations = $this->validator->validate($dto);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testRoundMustBePositive(): void
    {
        $dto = new BingoDto(season: 1, round: 0);

        $violations = $this->validator->validate($dto);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testNullSeasonAndRoundAreAllowed(): void
    {
        $dto = new BingoDto(season: null, round: null);

        $violations = $this->validator->validate($dto);

        $this->assertCount(0, $violations);
    }

    public function testDuplicateLabelsAreDetected(): void
    {
        $squares = [
            1 => [1 => ['id' => null, 'category' => 1, 'label' => 'Foo', 'visible' => '1']],
            2 => [1 => ['id' => null, 'category' => 1, 'label' => 'Foo', 'visible' => '1']],
        ];

        $dto = new BingoDto(season: 1, round: 1, squares: $squares);

        $violations = $this->validator->validate($dto);

        $hasDuplicateViolation = false;
        foreach ($violations as $violation) {
            if (str_contains((string) $violation->getMessage(), 'dupliqués')) {
                $hasDuplicateViolation = true;
                break;
            }
        }

        $this->assertTrue($hasDuplicateViolation, 'Expected duplicate label violation');
    }

    public function testUniqueLabelsProduceNoViolation(): void
    {
        $squares = [
            1 => [1 => ['id' => null, 'category' => 1, 'label' => 'Foo', 'visible' => '1']],
            2 => [1 => ['id' => null, 'category' => 1, 'label' => 'Bar', 'visible' => '1']],
        ];

        $dto = new BingoDto(season: 1, round: 1, squares: $squares);

        $violations = $this->validator->validate($dto);

        $hasDuplicateViolation = false;
        foreach ($violations as $violation) {
            if (str_contains((string) $violation->getMessage(), 'dupliqués')) {
                $hasDuplicateViolation = true;
                break;
            }
        }

        $this->assertFalse($hasDuplicateViolation, 'No duplicate label violation expected');
    }

    public function testEmptyLabelsAreNotCountedAsDuplicates(): void
    {
        $squares = [
            1 => [
                1 => ['id' => null, 'category' => 1, 'label' => '', 'visible' => '0'],
                2 => ['id' => null, 'category' => 1, 'label' => '', 'visible' => '0'],
            ],
        ];

        $dto = new BingoDto(season: 1, round: 1, squares: $squares);

        $violations = $this->validator->validate($dto);

        $hasDuplicateViolation = false;
        foreach ($violations as $violation) {
            if (str_contains((string) $violation->getMessage(), 'dupliqués')) {
                $hasDuplicateViolation = true;
                break;
            }
        }

        $this->assertFalse($hasDuplicateViolation, 'Empty labels must not be treated as duplicates');
    }

    public function testWhitespaceOnlyLabelsAreNotCountedAsDuplicates(): void
    {
        $squares = [
            1 => [
                1 => ['id' => null, 'category' => 1, 'label' => '   ', 'visible' => '0'],
                2 => ['id' => null, 'category' => 1, 'label' => '   ', 'visible' => '0'],
            ],
        ];

        $dto = new BingoDto(season: 1, round: 1, squares: $squares);

        $violations = $this->validator->validate($dto);

        $hasDuplicateViolation = false;
        foreach ($violations as $violation) {
            if (str_contains((string) $violation->getMessage(), 'dupliqués')) {
                $hasDuplicateViolation = true;
                break;
            }
        }

        $this->assertFalse($hasDuplicateViolation, 'Whitespace-only labels must not be treated as duplicates');
    }

    public function testDuplicateDetectionIgnoresEmptyButCatchesNonEmptyDuplicates(): void
    {
        $squares = [
            1 => [
                1 => ['id' => null, 'category' => 1, 'label' => '', 'visible' => '0'],
                2 => ['id' => null, 'category' => 1, 'label' => '', 'visible' => '0'],
            ],
            2 => [
                1 => ['id' => null, 'category' => 1, 'label' => 'Unique', 'visible' => '1'],
                2 => ['id' => null, 'category' => 1, 'label' => 'Unique', 'visible' => '1'],
            ],
        ];

        $dto = new BingoDto(season: 1, round: 1, squares: $squares);

        $violations = $this->validator->validate($dto);

        $hasDuplicateViolation = false;
        foreach ($violations as $violation) {
            if (str_contains((string) $violation->getMessage(), 'dupliqués')) {
                $hasDuplicateViolation = true;
                break;
            }
        }

        $this->assertTrue($hasDuplicateViolation, 'Non-empty duplicate labels must still be detected even when empty labels exist');
    }

    public function testNullLabelsAreIgnoredInDuplicateCheck(): void
    {
        $squares = [
            1 => [
                1 => ['id' => null, 'category' => 1, 'label' => null, 'visible' => '0'],
                2 => ['id' => null, 'category' => 1, 'label' => null, 'visible' => '0'],
            ],
        ];

        $dto = new BingoDto(season: 1, round: 1, squares: $squares);

        $violations = $this->validator->validate($dto);

        $hasDuplicateViolation = false;
        foreach ($violations as $violation) {
            if (str_contains((string) $violation->getMessage(), 'dupliqués')) {
                $hasDuplicateViolation = true;
                break;
            }
        }

        $this->assertFalse($hasDuplicateViolation, 'Null labels must be ignored in duplicate check');
    }

    public function testEmptySquaresArrayIsValid(): void
    {
        $dto = new BingoDto(season: 1, round: 1, squares: []);

        $violations = $this->validator->validate($dto);

        $this->assertCount(0, $violations);
    }
}
