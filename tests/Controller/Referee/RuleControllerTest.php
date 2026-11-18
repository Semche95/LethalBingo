<?php

declare(strict_types=1);

namespace App\Tests\Controller\Referee;

use App\Entity\ComputedMeasure;
use App\Entity\Rule;
use App\Tests\Controller\ControllerTestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class RuleControllerTest extends ControllerTestCase
{
    use InteractsWithLiveComponents;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsReferee();
    }

    public function testListsTheRulesWithTheOriginOfTheirValue(): void
    {
        $this->createFreeRule('Jours passés d\'affilée', -100);

        $this->client->request('GET', '/referee/rule');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'Cases validées');
        self::assertSelectorTextContains('table', 'Calculée par l\'application');
        self::assertSelectorTextContains('table', 'Jours passés d\'affilée');
        self::assertSelectorTextContains('table', '×−100');
        self::assertSelectorTextContains('table', 'Saisie par l\'arbitre');
    }

    public function testComputedRulesCannotBeDeletedFromTheList(): void
    {
        $this->client->request('GET', '/referee/rule');

        self::assertSelectorCount(\count(ComputedMeasure::cases()), 'button.btn-delete-icon[disabled]');
    }

    public function testAddPageIsNotFoundOutsideTheModal(): void
    {
        $this->client->request('GET', '/referee/rule/add');

        self::assertResponseStatusCodeSame(404);
    }

    public function testEditPageIsNotFoundOutsideTheModal(): void
    {
        $rule = $this->createFreeRule();

        $this->client->request('GET', '/referee/rule/edit/' . $rule->getId());

        self::assertResponseStatusCodeSame(404);
    }

    public function testEditFragmentOfAComputedRuleOnlyHasTheCoefficient(): void
    {
        $rule = $this->findComputedRule();

        $this->client->request('GET', '/referee/rule/edit/' . $rule->getId(), server: ['HTTP_X_FRAGMENT_REQUEST' => '1']);

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('input[name="rule_form[measure]"]');
        self::assertSelectorExists('input[name="rule_form[coefficient]"]');
        self::assertSelectorNotExists('h1.dashboard-title');
    }

    public function testCreatesAFreeRuleWithANegativeCoefficient(): void
    {
        $this->createLiveComponent('RuleForm', ['rule' => new Rule()], $this->client)
            ->submitForm(['rule_form' => ['measure' => 'Morts de l\'équipe', 'coefficient' => '-30']], 'save');

        $rule = $this->em->getRepository(Rule::class)->findOneBy(['measure' => 'Morts de l\'équipe']);
        self::assertNotNull($rule);
        self::assertSame(-30, $rule->getCoefficient());
        self::assertFalse($rule->isComputed());
    }

    public function testRejectsAFreeRuleWithoutMeasure(): void
    {
        $ruleCount = $this->em->getRepository(Rule::class)->count();

        try {
            $this->createLiveComponent('RuleForm', ['rule' => new Rule()], $this->client)
                ->submitForm(['rule_form' => ['measure' => '', 'coefficient' => '10']], 'save');
            self::fail('A free rule without measure must be rejected.');
        } catch (UnprocessableEntityHttpException) {
        }

        self::assertSame($ruleCount, $this->em->getRepository(Rule::class)->count());
    }

    public function testRejectsARuleWithoutCoefficient(): void
    {
        $ruleCount = $this->em->getRepository(Rule::class)->count();

        try {
            $this->createLiveComponent('RuleForm', ['rule' => new Rule()], $this->client)
                ->submitForm(['rule_form' => ['measure' => 'Measure', 'coefficient' => '']], 'save');
            self::fail('A rule without coefficient must be rejected.');
        } catch (UnprocessableEntityHttpException) {
        }

        self::assertSame($ruleCount, $this->em->getRepository(Rule::class)->count());
    }

    public function testChangesOnlyTheCoefficientOfAComputedRule(): void
    {
        $rule = $this->findComputedRule();

        $this->createLiveComponent('RuleForm', ['rule' => $rule], $this->client)
            ->submitForm(['rule_form' => ['coefficient' => '150']], 'save');

        $rule = $this->reload(Rule::class, $rule->getId());
        self::assertSame(150, $rule->getCoefficient());
        self::assertSame(ComputedMeasure::ValidatedSquares->label(), $rule->getLabel());
    }

    public function testRejectsAMeasureForAComputedRule(): void
    {
        $rule = $this->findComputedRule();

        try {
            $this->createLiveComponent('RuleForm', ['rule' => $rule], $this->client)
                ->submitForm(['rule_form' => ['measure' => 'Hijacked', 'coefficient' => '150']], 'save');
            self::fail('The measure of a computed rule must be rejected.');
        } catch (UnprocessableEntityHttpException) {
        }

        self::assertNull($this->reload(Rule::class, $rule->getId())->getMeasure());
    }

    public function testDeletesAFreeRule(): void
    {
        $ruleId = $this->createFreeRule()->getId();
        $token = $this->fetchToken('/referee/rule', '#delete-rule-modal-' . $ruleId);

        $this->client->request('POST', '/referee/rule/delete/' . $ruleId, ['token' => $token]);

        self::assertResponseRedirects('/referee/rule');
        self::assertNull($this->reload(Rule::class, $ruleId));
        $this->client->followRedirect();
        self::assertFlash('success', 'Règle supprimée');
    }

    public function testRefusesToDeleteAComputedRule(): void
    {
        $this->createFreeRule();
        $token = $this->fetchToken('/referee/rule', '[id^="delete-rule-modal-"]');
        $ruleId = $this->findComputedRule()->getId();

        $this->client->request('POST', '/referee/rule/delete/' . $ruleId, ['token' => $token]);

        self::assertResponseRedirects('/referee/rule');
        self::assertNotNull($this->reload(Rule::class, $ruleId));
        $this->client->followRedirect();
        self::assertFlash('error', 'Impossible de supprimer une règle calculée par l\'application');
    }

    public function testDeleteWithAnInvalidTokenIsRejected(): void
    {
        $ruleId = $this->createFreeRule()->getId();

        $this->client->request('POST', '/referee/rule/delete/' . $ruleId, ['token' => 'invalid']);

        self::assertResponseStatusCodeSame(400);
        self::assertNotNull($this->reload(Rule::class, $ruleId));
    }

    private function findComputedRule(): Rule
    {
        $rule = $this->em->getRepository(Rule::class)->findOneBy(['computedMeasure' => ComputedMeasure::ValidatedSquares]);
        self::assertNotNull($rule);

        return $rule;
    }
}
