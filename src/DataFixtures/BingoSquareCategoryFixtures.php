<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\BingoSquareCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixture for loading bingo square categories data.
 */
class BingoSquareCategoryFixtures extends Fixture
{
    public static function reference(int $index): string
    {
        return 'category_' . $index;
    }

    /**
     * Load bingo square categories data.
     */
    public function load(ObjectManager $manager): void
    {
        $categories = [
            ['pendant la game', 3],
            ['morts', 5],
            ['lancement du vaisseau', 1],
            ['autre', 4],
            ['the company', 2],
        ];
        foreach ($categories as $i => $categoryData) {
            [$label, $position] = $categoryData;
            $category = new BingoSquareCategory()
                ->setLabel($label)
                ->setPosition($position);
            $manager->persist($category);
            $this->addReference(self::reference($i), $category);
        }
        $manager->flush();
    }
}
