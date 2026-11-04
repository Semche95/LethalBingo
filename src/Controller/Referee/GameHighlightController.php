<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use App\Dto\GameHighlightNoteDto;
use App\Entity\Game;
use App\Entity\GameHighlight;
use App\Service\CsrfGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Highlights of a game: marked, annotated and deleted while it is in
 * progress (pauses included), read-only once it is over.
 */
#[Route('/referee/game')]
#[IsGranted('ROLE_REFEREE')]
class GameHighlightController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CsrfGuard $csrfGuard,
    ) {
    }

    private static function tokenId(Game $game): string
    {
        return 'game-highlights-' . $game->getId();
    }

    #[Route(
        '/{id}/highlight',
        name: 'app_referee_game_highlight_add',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST],
    )]
    public function add(Request $request, Game $game): Response
    {
        $this->assertEditable($request, $game);

        $highlight = new GameHighlight($game);
        $this->em->persist($highlight);
        $this->em->flush();

        return $this->render('components/GameHighlights/_row.html.twig', [
            'highlight' => $highlight,
            'editable' => true,
        ]);
    }

    #[Route(
        '/highlight/{id}/note',
        name: 'app_referee_game_highlight_note',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST],
    )]
    public function note(Request $request, GameHighlight $highlight, #[MapRequestPayload] GameHighlightNoteDto $payload): Response
    {
        $this->assertEditable($request, $highlight->getGame());

        $highlight->setNote($payload->note === '' ? null : $payload->note);
        $this->em->flush();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route(
        '/highlight/{id}/delete',
        name: 'app_referee_game_highlight_delete',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST],
    )]
    public function delete(Request $request, GameHighlight $highlight): Response
    {
        $this->assertEditable($request, $highlight->getGame());

        $this->em->remove($highlight);
        $this->em->flush();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function assertEditable(Request $request, Game $game): void
    {
        $this->csrfGuard->assertValid(self::tokenId($game), $request->headers->get('X-CSRF-Token'));

        if (!$game->isInProgress()) {
            throw new BadRequestHttpException();
        }
    }
}
