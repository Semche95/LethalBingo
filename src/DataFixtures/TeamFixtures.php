<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Team;
use App\Entity\TeamMember;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixture for loading teams data.
 */
class TeamFixtures extends Fixture
{
    use FakerTrait;

    /**
     * Load teams data.
     */
    public function load(ObjectManager $manager): void
    {
        $faker = self::getFaker();
        for ($i = 0; $i < 4; $i++) {
            $team = new Team()
                ->setName($faker->text(50));
            for ($j = 0; $j < 3; $j++) {
                $member = new TeamMember()
                    ->setCaptain($j === 0)
                    ->setName($faker->firstName);
                $team->addMember($member);
            }
            $manager->persist($team);
        }
        $manager->flush();
    }
}
