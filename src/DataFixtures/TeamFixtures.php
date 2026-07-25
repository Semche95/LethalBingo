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
    /**
     * Load teams data.
     */
    public function load(ObjectManager $manager): void
    {
        $teams = [
            ['Ministère du quota', ['VoidWalker', 'Kairo', 'Biscotte', 'Solstice']],
            ['Les bras cassés', ['Tofu', 'BrokenCompass', 'Nox', 'Cactus']],
            ['Service des pertes acceptables', ['Torque', 'Moka', 'Warden', 'DeadPixel']],
            ['Les chasseurs de bracken', ['Flint', 'Kuma', 'Echo', 'Pika']],
            ['Les collectionneurs d\'apparatus', ['CopperHead', 'Zephyr', 'Arctis', 'Crow']],
        ];
        foreach ($teams as $teamData) {
            [$name, $members] = $teamData;
            $team = new Team()->setName($name);
            foreach ($members as $i => $memberName) {
                $member = new TeamMember()
                    ->setCaptain($i === 0)
                    ->setName($memberName);
                $team->addMember($member);
            }
            $manager->persist($team);
        }
        $manager->flush();
    }
}
