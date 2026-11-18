# Spécification de la page de partie

Document de référence sur le fonctionnement attendu de la page de partie (`/game/{slug}`), côté arbitre et côté joueurs. Le timer a sa propre spécification : `docs/TIMER.md`.

---

## 1. Principe général

La page est la même pour tout le monde : les joueurs la suivent, l'arbitre la pilote. **Le serveur décide de ce que chacun voit** : rien de ce qui doit rester caché aux joueurs n'est envoyé dans leur page, même masqué en CSS.

- Route `app_game` (`GameController::game()`), slug de 12 caractères alphanumériques, partie chargée par `GameRepository::findOneForPlay()`. Un slug inconnu renvoie une 404.
- La page est publique : aucun compte n'est demandé. Les joueurs n'ont pas de compte, ils suivent leur partie par son lien unique.
- Le slug est tiré au hasard à la création de la partie (`Game::generateSlug()`). L'arbitre peut le régénérer depuis le formulaire de la partie (« Nouveau lien ») : l'ancien lien cesse alors de fonctionner.
- Aucun pseudo de compte n'est affiché aux joueurs : rien de ce qu'ils voient ne dépend du `username`.

### Ce que voit chaque profil

- **Joueur** (anonyme, ou connecté sans `ROLE_REFEREE`) : titre (bingo, équipe, statut), timer, grille, bouton Accueil. Aucune commande : les joueurs ne font que regarder.
- **Arbitre** (`ROLE_REFEREE`), en plus : panneau Notes, carte des actions de la partie (date, retour au tableau de bord, Démarrer / Arrêter / Réinitialiser, temps forts une fois la partie terminée), boutons de chaque case, carte Étapes, panneau Historique, commandes du timer.
- **Administrateur** (`ROLE_ADMIN`) : comme l'arbitre, plus les boutons de debug du timer si `DEBUG_TIMER=true` (`docs/TIMER.md`, section 3).

### Garanties côté serveur

L'endpoint des Live Components est public : masquer un bouton ne suffit jamais. Chaque mutation est protégée par le rôle côté serveur :

- `#[IsGranted('ROLE_REFEREE')]` sur `GameFrame::startGame`, `stopGame`, `resetGame`, sur `BingoCard::reveal`, `conceal`, `validate`, `invalidate`, et sur les actions du timer ;
- `GameHistory::__invoke` est réservé à l'arbitre : un joueur qui demande l'historique n'obtient rien ;
- `GameController::saveNotes` et `GameHighlightController` sont réservés à l'arbitre.

---

## 2. Cycle de vie de la partie

États (`Game`) :

- **non démarrée** : `startedAt` nul ;
- **en cours** (`isInProgress()`) : démarrée et pas encore arrêtée. Une partie en pause ou dont le décompte est à zéro reste en cours ;
- **terminée** (`isFinished()`) : `finishedAt` renseigné.

Le statut est affiché sous le titre : « Non démarrée », « En cours » (point vert animé) ou « Terminée ».

### Actions de l'arbitre

- **Démarrer** (`GameFrame::startGame` → `GameTimerService::start`) : sans confirmation, seulement si la partie n'est pas démarrée.
- **Arrêter** (`stopGame`) : confirmation par la modale `stop-game-modal`, seulement si la partie est en cours. Seul `finishedAt` change (règles de date : `docs/TIMER.md`, section 3).
- **Réinitialiser** (`resetGame` → `GameService::reset`) : confirmation par la modale `reset-game-modal` (action irréversible), dès que la partie est démarrée, terminée comprise. Dans une transaction : timer remis à zéro, suppression des cases dévoilées et validées, de l'historique des actions et des temps forts.
- **Les notes de l'arbitre (panneau Notes, à gauche) ne sont jamais supprimées**, ni par la réinitialisation ni par aucune autre action de la partie : elles appartiennent à l'arbitre, pas au déroulement de la partie (section 4).

Une action qui ne correspond pas à l'état de la partie ne fait rien (démarrer une partie démarrée, arrêter une partie non démarrée…).

### L'arrêt dévoile toutes les cases

**Seul l'arrêt de la partie par l'arbitre dévoile toutes les cases aux joueurs.** Le temps qui arrive à zéro ne termine jamais la partie (`docs/TIMER.md`, section 3). Une fois la partie terminée :

- les joueurs reçoivent tous les libellés, et voient quelles cases ont été validées ;
- tous les boutons de case disparaissent, la grille est en lecture seule ;
- les temps forts restent consultables en lecture seule ;
- l'historique reste lisible.

---

## 3. Grille

Grille de 5 × 5 (`GameRound::MAX_ROWS`, `MAX_COLS`). Les lignes sont des lettres (A à E), les colonnes des chiffres (1 à 5) : une case se désigne par ses coordonnées (`B4`). Chaque cellule porte l'id `square{ligne}{colonne}`.

### Visibilité des cases

- **Cases visibles d'office** : un motif fixe de 7 cases, A3, B4, C1, C3, C5, D2, E3 (`BingoService::VISIBLE_SQUARES`). Elles ne peuvent pas être masquées.
- **Cases dévoilées** : les autres cases, que l'arbitre dévoile une à une (`BingoSquareRevealed`) et peut masquer à nouveau.
- Une case est affichable pour les joueurs si elle est visible d'office, dévoilée, ou si la partie est terminée (`BingoCard::isSquareContentShowable()`). **Avant le démarrage aussi** : les joueurs voient déjà les cases visibles d'office et toutes celles que l'arbitre a dévoilées. L'arbitre voit toujours tout.

**Filtrage serveur** (`BingoService::gameSquares()`) : tant que la partie n'est pas terminée, le libellé et la catégorie d'une case non affichable ne sont pas envoyés aux joueurs. Ils voient `???` à sa place. Les cases sont relues à chaque rendu, jamais stockées dans une LiveProp.

### États visuels

- **Masquée** (`bingo-square-masked`, fond hachuré) : case que les joueurs ne voient pas encore. L'arbitre la voit aussi hachurée, avec son libellé, pour savoir ce qui est caché aux joueurs.
- **Validée** (`bingo-square-validated`, fond vert et coche) : affichée seulement si la case est affichable. **Un joueur ne voit jamais qu'une case encore masquée a été validée.**
- Les cases se touchent et partagent une seule bordure ; le liseré vert d'une case validée est dessiné à l'intérieur de la case.

### Boutons de l'arbitre

Affichés en haut de chaque case, uniquement pour l'arbitre et tant que la partie n'est pas terminée. Les icônes montrent l'état actuel de la case, pas l'action suivante.

- **Dévoiler / masquer** : possibles avant le démarrage et pendant la partie. Désactivé pour les cases visibles d'office.
- **Valider / invalider** : seulement pendant la partie en cours, y compris en pause et une fois le décompte à zéro.

Règles serveur (`GameSquareService`) : dévoiler et masquer sont refusés une fois la partie terminée, valider et invalider hors partie en cours ; la case doit appartenir au bingo de la partie. Les actions sur les cases d'une partie passent par un verrou (`game-squares-<id>`) : deux arbitres qui cliquent en même temps ne créent pas de doublon.

---

## 4. Outils de l'arbitre

### Étapes

Carte « Étapes » sous la grille (`GameSteps`) : pour chaque catégorie, les cases pas encore validées **dans cette partie**, triées par ligne puis colonne (`BingoSquareRepository::findNotValidatedWithCategory()`). Plusieurs équipes peuvent jouer le même bingo : une case validée par une autre équipe reste une étape de cette partie. Un clic sur les coordonnées remonte en haut de la page et fait clignoter la case dans la grille (`game--game-frame#scrollToSquare`, classe `blink`).

### Historique

Panneau latéral (`GameHistory`), réservé à l'arbitre, ouvert ou fermé à la demande comme les notes :

- actions sur les cases (dévoilée, masquée, validée, invalidée) et temps forts, du plus ancien au plus récent, avec leur nombre ;
- avant le lancement : heure seule ; ensuite : durée écoulée depuis le lancement (pauses comprises) et heure ;
- une ligne « Lancement · HH:MM:SS » sépare les actions faites avant et après le démarrage ;
- seul un changement réel d'état est journalisé (`GameSquareAction`) : dévoiler une case déjà dévoilée n'ajoute rien. Démarrage, arrêt et notes ne sont pas journalisés ; la réinitialisation vide l'historique.

### Temps forts

Moments marqués par l'arbitre pendant la partie (`GameHighlights`, `GameHighlightController`) :

- marquage et liste dans les commandes du timer pendant la partie ; liste en lecture seule dans la carte des actions une fois la partie terminée ; rien avant le démarrage ;
- marquables et modifiables seulement pendant la partie en cours, y compris en pause et une fois le décompte à zéro ;
- durée d'un temps fort : `markedAt − startedAt`, pauses et temps passé à zéro compris ;
- chaque temps fort peut recevoir une note (sauvegarde automatique) et être supprimé, avec une confirmation dans la ligne elle-même ;
- la liste est gérée par le client (`data-live-ignore`) : un re-render Live ne doit pas effacer un temps fort qui vient d'être ajouté. Live ne retirant jamais un élément marqué ainsi, le composant est placé dans un conteneur qui, lui, disparaît à la réinitialisation.

### Notes

Panneau latéral de l'arbitre, masqué par défaut :

- sauvegarde automatique à la saisie (`app_game_save_notes`), avec l'état affiché sous le champ (« Sauvegarde en cours... », « Notes sauvegardées », « Erreur lors de la sauvegarde ») ;
- modifiables avant, pendant et après la partie ;
- **jamais supprimées par la réinitialisation** (section 2) ;
- ni diffusées en temps réel ni montrées aux joueurs ;
- l'ouverture du panneau et la hauteur du champ sont mémorisées par partie dans le navigateur (`localStorage`).

---

## 5. Temps réel

Toute mutation de la partie est diffusée par Mercure sur le topic `game/<slug>`, et seul `GameService::refreshGameFrame()` publie. **Le message ne contient aucune donnée de partie** : seulement le nom d'un événement et l'heure du serveur. Chaque client recharge alors les composants concernés, et le serveur refiltre ce que chacun a le droit de voir.

| Événement | Publié après | Composants rechargés |
|---|---|---|
| `gameFrameRefresh` | démarrage, arrêt, réinitialisation, modification de la partie | tous |
| `bingoCardRefresh`, `gameStepsRefresh` | action sur une case | grille, historique, étapes |
| `gameHistoryRefresh` | ajout, note ou suppression d'un temps fort | historique |
| `gameTimerRefresh` | action sur le timer (`docs/TIMER.md`) | timer |

Après une veille, un retour du réseau ou une reconnexion à Mercure, la connexion est rouverte puis toute la partie rechargée (`gameFrameRefresh` émis localement), pour rattraper les messages manqués (`docs/TIMER.md`, section 1).

---

## 6. Sécurité des mutations

- **Actions Live** (démarrage, arrêt, réinitialisation, cases, timer) : pas de jeton, la protection vient de l'endpoint Live, qui exige des en-têtes qu'un site tiers ne peut pas envoyer sans préflight CORS. Le rôle est vérifié par `#[IsGranted]`.
- **Requêtes `fetch`** (notes, temps forts) : jeton CSRF de session par partie (`save-notes-<id>`, `game-highlights-<id>`), envoyé dans l'en-tête `X-CSRF-Token` (`postJson()`), vérifié par `CsrfGuard`, rafraîchi toutes les 10 minutes. Un jeton invalide renvoie une 400 plutôt qu'une déconnexion.

---

## 7. Mise en page

Ordre des blocs, de haut en bas :

1. titre : nom du bingo, puis équipe et statut sur la même ligne ;
2. timer (`docs/TIMER.md`) ; une fois la partie terminée, remplacé par « Durée de la partie » pour une partie sans limite de temps, absent sinon ;
3. carte des actions de l'arbitre : date de démarrage ou d'arrêt, « Retour au tableau de bord » à gauche, action principale au centre (Démarrer, Arrêter ou liste des temps forts), Réinitialiser isolé à droite ;
4. grille ;
5. carte Étapes (arbitre).

Boutons flottants : Notes en haut à gauche ; Accueil en haut à droite, à gauche du bouton Historique.

### Timer

- Le temps est **toujours centré**, quels que soient les éléments à côté : l'icône à sa gauche et le badge « PAUSE » à sa droite sont placés en dehors du flux et ne le décalent pas ; une place égale leur est réservée de chaque côté.
- Les commandes de l'arbitre sont à droite du temps sur grand écran, dessous sur téléphone.

### Design

- Palette propre à la page, thème sombre uniquement, définie sur `body.game-page` (`assets/app.css`, section « Game page ») : orange du HUD pour l'action principale et le temps, vert pour la validation, bleu pour l'information, jaune pour les temps forts et la pause, rouge foncé réservé à la réinitialisation. Les variables Tabler (`--tblr-*`) y sont rebranchées. Aucune couleur en dur dans les règles.
- Police 3270 (`assets/fonts/`) pour les titres, libellés du HUD, chiffres et coordonnées ; police de Tabler pour le texte.
- Un seul bouton plein à l'écran : l'action principale de la carte des actions. Les autres sont en contour ou discrets, Réinitialiser en rouge foncé.
- Les modales de la page (arrêt, réinitialisation, `ConfirmModal`) reprennent la surface des cartes et un titre en 3270. Le bouton de confirmation de l'arrêt est orange, comme le bouton qui l'ouvre.
- Largeur maximale de 1140 px. **Lisible à 375 px, sans défilement horizontal.** Les marges laissent la place aux boutons flottants sur téléphone, sans plus.

### Hauteur sur un écran Full HD

**Côté joueurs, sur un écran Full HD (1920 × 1080), le timer et les cinq lignes de la grille doivent toujours tenir ensemble dans la fenêtre**, barre des tâches du système et barre du navigateur déduites. Hauteur utile de référence : **990 px**. On mesure du haut de la carte du timer au bas de la grille : le titre peut être remonté hors de l'écran. La règle vaut aussi pour des libellés de case longs. Elle ne s'applique pas à l'arbitre, dont la carte des actions s'intercale entre le timer et la grille. Toute hausse de la hauteur du timer ou des cases côté joueurs se vérifie à cette taille.

---

## 8. Tests

- `tests/Controller/GameControllerTest.php` : page publique, filtrage des libellés avant, pendant et après la partie (cases visibles d'office et dévoilées dès avant le démarrage), 404, notes.
- `tests/Service/GameServiceTest.php`, `GameSquareServiceTest.php`, `GameSquareServiceRulesTest.php`, `GameSquareServiceSquareActionsTest.php`, `BingoServiceTest.php` : cycle de vie, règles des cases par état, verrou, motif visible.
- `tests/Twig/GameHistoryTest.php`, `GameFrameTest.php`, `GameComponentsQueryCountTest.php` : historique, durée de partie, nombre de requêtes, étapes (y compris avec une autre partie sur le même bingo).
- `tests/Controller/Referee/GameHighlightControllerTest.php` : temps forts.
- `assets/tests/game_frame_controller.test.ts` : relais Mercure et reconnexions.

Non couverts : le refus des actions Live de `GameFrame` et `BingoCard` à un non-arbitre, l'affichage des boutons de case selon l'état, les contrôleurs JS des notes, des temps forts et de l'historique, la hauteur en Full HD côté joueurs. Toute modification visible se vérifie à l'écran sur https://lethalbingo.localhost, côté arbitre et côté joueur, y compris à 375 px.

---

## 9. Récapitulatif

- Page publique par lien unique ; le serveur filtre ce que voit chacun, chaque mutation est protégée par le rôle.
- Avant le démarrage comme pendant la partie, les joueurs voient les 7 cases visibles d'office et celles dévoilées par l'arbitre ; après l'arrêt, tout.
- Seul l'arrêt par l'arbitre termine la partie et dévoile tout ; le décompte à zéro ne termine rien.
- Une case validée mais encore masquée ne trahit jamais sa validation aux joueurs.
- Réinitialiser efface cases, historique et temps forts, jamais les notes de l'arbitre.
- Mercure ne transporte que des noms d'événements, jamais de données de partie.
- Temps toujours centré, un seul bouton plein, palette sombre de la page sans couleur en dur, lisible à 375 px ; en Full HD, timer et grille entière tiennent ensemble dans 990 px de haut côté joueurs.
