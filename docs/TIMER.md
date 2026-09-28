# Spécification du timer

Document de référence sur le fonctionnement attendu du timer.

---

## 1. Principe général

Le serveur est l'autorité sur le temps. Chaque client (arbitre et joueurs) reproduit localement le même calcul que le serveur pour afficher un compte à rebours fluide sans avoir à interroger le serveur en continu.

**Règle non négociable : une seule source d'ancre temporelle, partagée par tous les clients au même instant.**

- Le serveur publie `microtime(true)` (temps fractionnaire) dans le payload du message Mercure diffusé à chaque mutation du jeu (start, stop, pause, reprise, ±1 minute, validation de case, etc.).
- Tous les clients reçoivent ce broadcast en même temps et s'ancrent dessus.
- Au tout premier rendu de page (avant tout broadcast), le composant Live (`GameTimer.php`) résout lui-même un `microtime(true)` dans son `mount()`, pour ne jamais laisser l'ancre à une valeur par défaut invalide (ex. `0.0`, qui donnerait un calcul depuis l'epoch Unix).
- **Aucun fetch périodique côté client ne doit exister** vers un endpoint de type `/game/server-time`. Chaque client interrogeant le serveur indépendamment produit une ancre légèrement différente d'un client à l'autre : l'ancre doit toujours venir du broadcast partagé, jamais d'une requête individuelle.

### Estimation du temps serveur côté client

```
estimatedServerTime(ms) = syncedServerTime * 1000 + (performance.now() - clientTimeAtRender)
```

- `syncedServerTime` : l'ancre reçue (secondes, fractionnaire).
- `clientTimeAtRender` : `performance.now()` capturé au même moment que l'ancre.
- `performance.now()` est une horloge **monotone** (ne dépend pas de l'horloge système de la machine), donc immunisée contre un changement d'heure locale ou une mise en veille du poste.
- L'ancre se met à jour : au premier rendu, à chaque broadcast Mercure, et lors de refresh événementiels ponctuels (`visibilitychange` quand l'onglet redevient visible, reconnexion de l'`EventSource` Mercure après une coupure). Jamais via un intervalle récurrent. Ces refresh événementiels ne transportent pas toujours un nouveau `serverTime` (c'est le cas typique de `visibilitychange`, qui déclenche un re-render sans repasser par un broadcast). **Le point d'entrée qui reçoit `serverTime` doit systématiquement produire une ancre fraîche quand aucune valeur n'est fournie (repli sur l'heure serveur actuelle), jamais laisser l'ancre inchangée dans ce cas.** Une ancre qui reste figée quand `serverTime` est absent romprait justement le mécanisme de refresh événementiel décrit ci-dessus, et produirait un décompte qui semble s'arrêter puis reprendre en retard après un changement d'onglet.

---

## 2. Calcul du temps restant, précision et parité PHP/TS

Le calcul existe en double : une fois en PHP (`GameTimerService::getRemainingSeconds()`, autorité), une fois en TS (`calculateRemainingSeconds()` dans `game_timer_math.ts`, réplique locale pour l'affichage entre deux broadcasts). **Les deux doivent produire exactement le même résultat pour un même instant, à n'importe quelle fraction de seconde.**

Toute la chaîne de calcul doit conserver la précision sous-seconde jusqu'au bout, et n'appliquer l'arrondi (`floor`/`ceil` selon la convention retenue) qu'une seule fois, à la toute fin, jamais sur des valeurs intermédiaires. Si le calcul tronque un timestamp à la seconde entière avant de faire une soustraction, le résultat peut différer d'une seconde selon la fraction exacte de l'instant considéré.

**L'écart de pause doit être calculé par une seule soustraction directe entre les deux timestamps concernés, jamais en soustrayant entre eux deux écarts calculés séparément par rapport à l'instant présent.** Concrètement, pendant une pause, le temps figé se calcule comme `lastPausedAt − startedAt` (une seule petite soustraction), pas comme `(maintenant − startedAt) − (maintenant − lastPausedAt)` (deux grandes soustractions dont le résultat s'annule presque exactement). Cette seconde forme est mathématiquement équivalente mais instable numériquement : soustraire deux grands nombres flottants presque égaux amplifie l'erreur d'arrondi relative sur ce qui reste, ce qui peut faire basculer l'arrondi final d'une évaluation à l'autre quand le résultat tombe pile sur une frontière de seconde, produisant une oscillation visible de l'affichage en pause. Cette règle s'applique à tout calcul similaire dans la formule (PHP comme TS) : toujours réduire d'abord à la petite différence utile, ne jamais passer par deux grands intermédiaires qui se recombinent.

### Test de parité : comment le vérifier correctement

Un test de parité doit appeler **les vraies fonctions de production** des deux côtés (`GameTimerService::getRemainingSeconds()` en PHP, `calculateRemainingSeconds()` en TS), jamais une réimplémentation locale de l'algorithme de l'autre langage écrite à la main dans le test. Un test qui compare deux réimplémentations entre elles ne prouve rien sur le code réel. La méthode qui fonctionne : une fixture de données générée une fois depuis la vraie fonction TS (jeu d'instants à diverses fractions de seconde, cas en course et en pause), committée, puis consommée par les deux suites de tests pour vérifier que le code de production de chaque langage produit bien les valeurs de la fixture.

---

## 3. Ajustement du temps par l'arbitre (`+1 minute` / `−1 minute`)

`startedAt` marque le début de partie : il n'est modifié que lors du lancement du jeu (`start()`) et de sa réinitialisation (`reset()`). `finishedAt` marque la fin de partie : en plus de ces deux mêmes actions, il est aussi modifié à l'arrêt du jeu (`stop()`), sans que cela n'affecte `startedAt`. Aucune autre action, y compris l'ajustement manuel du temps par l'arbitre, ne doit toucher à l'un ou l'autre de ces deux champs.

L'ajustement manuel repose sur un champ dédié, `manualAdjustmentSeconds` : un compteur cumulatif qui s'additionne à la formule de calcul du temps restant, au même titre que `totalPausedSeconds`. `+1 minute` incrémente ce compteur de 60, `−1 minute` le décrémente de 60. Dans la formule de calcul du temps restant (PHP et TS), le temps écoulé réel est réduit à la fois de `totalPausedSeconds` (temps passé en pause) et de `manualAdjustmentSeconds` (ajustements de l'arbitre) : une valeur positive de `manualAdjustmentSeconds` diminue le temps écoulé effectif et augmente donc le temps restant ; une valeur négative fait l'inverse.

### Absorption du déficit avant toute mutation

Il n'existe aucun champ séparé pour « geler » le temps au passage à zéro. À la place, une seule règle s'applique systématiquement, avant l'effet propre de n'importe quelle mutation qui peut changer le temps restant (`+1 minute`, `−1 minute`, réglage à une valeur spécifique, pause, reprise, ou toute action future) :

**Avant d'appliquer l'effet de la mutation, on calcule le temps restant brut avec l'état actuel. S'il est négatif ou nul, on ajoute sa valeur absolue au temps de pause total, ce qui ramène le temps restant calculé à exactement zéro. L'effet propre de la mutation s'applique ensuite, sur cette base.** Ce temps restant brut est la valeur à précision sous-seconde décrite en section 2, avant l'arrondi final : l'absorption doit toujours porter sur ce nombre flottant, jamais sur une valeur déjà arrondie (le `HH:MM:SS` affiché ou un entier de secondes), pour ne pas réintroduire une erreur d'une fraction de seconde.

Exemple : le timer est resté 5 minutes en overtime (temps restant brut : `−300s`). Un clic sur `+1 minute` déclenche d'abord l'absorption (le déficit de 300s est ajouté au temps de pause total, le temps restant calculé redevient `0`), puis le `+60` s'applique sur cette base propre : résultat exact, `00:01:00`. Ce résultat ne dépend ni de la durée pendant laquelle le timer est resté en négatif, ni du nombre de mutations déjà survenues entre-temps : chaque mutation commence par nettoyer un éventuel déficit avant d'agir, donc le calcul reste correct quelle que soit la séquence d'événements qui a précédé.

Cette règle n'a besoin d'aucun état persistant supplémentaire ni d'aucune valeur calculée rétroactivement : elle s'applique de la même façon à chaque mutation, à partir des seules valeurs déjà en base au moment où elle s'exécute.

---

## 4. Boucle d'affichage

- Le rendu du `HH:MM:SS` est piloté par `requestAnimationFrame`, pas par `setInterval`. Deux `setInterval` indépendants sur deux machines différentes n'ont pas la même phase : leurs ticks tombent à des instants différents, ce qui peut produire un écart d'affichage visible (jusqu'à 500ms) entre l'arbitre et un joueur au moment précis où le chiffre affiché change. `requestAnimationFrame` élimine ce déphasage car il n'y a plus de phase à désynchroniser : chaque client vérifie en continu (~60 fois/seconde) et réagit dès que possible.
- Le DOM n'est réécrit que lorsque le texte affiché change réellement (comparaison au contenu actuel du `textContent`, pas seulement à une valeur mise en cache côté JS), pas à chaque frame.
- **Un seul rédacteur pour l'élément affichant le temps.** Le calcul et l'écriture du `HH:MM:SS` affiché sont exclusivement de la responsabilité du client. Le composant Live ne doit jamais écrire de valeur calculée dans cet élément lors d'un re-render (que ce soit via le rendu Twig initial d'un texte déjà formaté, ou via toute autre voie). Le re-render Live reste nécessaire pour rafraîchir l'ancre temporelle et les autres champs d'état, mais l'élément d'affichage du temps lui-même doit être exclu du morph Live (par exemple via `data-live-ignore`), pour empêcher que deux calculs distincts, faits à deux instants ou avec deux ancres légèrement différents, ne se disputent l'écriture du même élément et ne produisent une oscillation visible entre deux valeurs adjacentes.

---

## 5. Alertes de seuil, comportement attendu (spécification exacte)

Deux mécanismes distincts, à ne pas implémenter de la même façon.

### Couleur : une fonction pure du temps restant actuel

La couleur affichée n'est ni un événement ponctuel, ni une fenêtre programmée dans le temps (pas de minuteur, pas d'état mémorisé, pas de moment de déclenchement à retenir). C'est une fonction pure du temps restant actuel, recalculée à chaque instant à partir de sa seule valeur présente :

- Couleur **warning** : affichée si et seulement si le temps restant actuel se situe dans les 5 secondes qui suivent (strictement en dessous de) un multiple de 10 minutes. La durée du jeu est arbitraire (1h, 3h, etc.) : la règle s'applique à chaque multiple de 10 minutes restantes, pas à une liste figée de paliers.
- Couleur **danger** : affichée si et seulement si le temps restant actuel se situe dans les 5 secondes qui suivent (strictement en dessous de) 5 minutes ou 3 minutes.
- Couleur **danger continue** : affichée dès que le temps restant passe strictement en dessous de 60 secondes. Contrairement aux deux cas précédents, ce n'est pas limité à une fenêtre de 5 secondes. **Le seuil d'1 minute n'a pas de flash de 5 secondes séparé** : le passage sous 60 secondes bascule directement dans ce mode continu, qui prend le relais sans interruption, y compris si le temps restant atteint zéro ou devient négatif (partie en overtime). **La couleur ne s'arrête que lorsque `finishedAt` est renseigné : la partie est alors terminée, il n'y a plus de temps restant à signaler.**

Parce que cette condition ne dépend que du temps restant à l'instant présent, elle n'a besoin d'aucune mise à jour explicite lors d'une mutation : décompte naturel, `+1 minute`, `−1 minute`, ajustement à une valeur spécifique (boutons de debug), pause, reprise, ou toute autre action future qui changerait le temps restant sont tous couverts automatiquement, dans n'importe quel ordre et n'importe quelle combinaison, simplement en recalculant la condition à chaque frame à partir du temps restant du moment. Aucune règle spécifique à écrire par type de mutation ni par enchaînement de mutations.

### Son : un événement ponctuel, déclenché une fois par franchissement

Le son se déclenche une seule fois, au moment précis où le temps restant franchit un seuil vers le bas (passe d'une valeur à ce seuil ou au-dessus à une valeur en dessous), pour chacun des seuils suivants : chaque multiple de 10 minutes, 5 minutes, 3 minutes, 1 minute, et zéro. Le son joué à zéro est différent des autres (son de fin, pas une répétition du bip), mais il suit le même mécanisme de détection que les autres seuils.

**Le son ne se déclenche que lorsqu'un seuil est franchi par le décompte naturel (l'écoulement normal du temps). Un franchissement provoqué par un ajustement manuel de l'arbitre (`+1 minute`, `−1 minute`, réglage direct à une valeur spécifique) ne doit jamais déclencher le son, même s'il traverse un ou plusieurs seuils au passage.** La couleur n'est pas concernée par cette distinction : elle continue de refléter uniquement la valeur actuelle du temps restant, indépendamment de l'origine de la mutation qui l'a amenée là.

Cette distinction porte uniquement sur le déclenchement du son, pas sur la mise à jour de la référence de comparaison décrite ci-dessous : cette référence continue de se resynchroniser à chaque évaluation, y compris celles qui suivent un ajustement manuel, pour qu'un franchissement ultérieur du décompte naturel reste correctement détecté. Autrement dit, un ajustement manuel doit rester silencieux tout en gardant la référence à jour, il ne doit pas être ignoré par le mécanisme de détection.

Cette détection compare le temps restant actuel à sa valeur lors de la dernière évaluation. Cette référence doit être resynchronisée à chaque mutation du temps de la partie, quelle qu'en soit l'origine (décompte naturel, `+1 minute`, `−1 minute`, ajustement à une valeur spécifique, pause, reprise, ou toute autre action future) : si une seule voie de mutation n'alimente pas cette référence, la détection peut se dérégler silencieusement pour toutes les mutations suivantes, y compris longtemps après. Un ajustement qui fait remonter le temps restant au-dessus d'un seuil déjà franchi ne doit pas redéclencher le son dans l'autre sens : les alertes préviennent qu'il reste peu de temps, pas l'inverse.

**L'absorption d'un déficit (section 3) est une étape de calcul interne à une mutation, jamais un événement observable en soi.** La détection du son ne doit comparer l'état qu'une seule fois par mutation, entre la valeur du temps restant observée avant que la mutation ne commence et la valeur finale une fois l'absorption et l'effet propre de la mutation tous les deux appliqués. L'état intermédiaire, juste après l'absorption mais avant l'effet de la mutation, ne doit jamais être exposé au mécanisme de détection du son : l'évaluer séparément risquerait de déclencher à tort le son de fin sur un passage par zéro purement transitoire, qui n'a rien à voir avec un vrai franchissement du seuil.

Le seul état à conserver entre deux évaluations est la valeur du temps restant observée lors de l'évaluation précédente, rien de plus. Aucun ensemble de seuils « déjà signalés » ni aucun indicateur booléen par seuil ne doit être mémorisé séparément de cette valeur : un tel état, une fois marqué, risque de ne jamais être remis à zéro correctement pour toutes les voies de mutation possibles, ce qui empêche un seuil franchi une première fois de redéclencher le son lors d'un franchissement ultérieur légitime, même après une longue série de mutations entre les deux. La comparaison de la valeur actuelle à la seule valeur précédente, mise à jour à chaque évaluation sans exception, suffit à couvrir tous les cas sans avoir besoin d'un état supplémentaire.

---

## 6. Fiabilité des tests automatisés sur ce composant

Une suite de tests entièrement verte n'est pas une preuve suffisante que le timer fonctionne réellement. La validation d'un changement sur ce composant passe par une vérification manuelle en navigateur réel (deux fenêtres pour la synchro arbitre/joueur, rechargement de page), indépendamment du nombre de tests automatisés qui passent.

La détection du son (section 5) doit être testée par des séquences de mutations enchaînées, pas seulement par des cas isolés : combiner dans un ordre arbitraire des décomptes naturels, des `+1 minute`, des `−1 minute` et des ajustements directs à une valeur spécifique, et vérifier qu'un seuil franchi naturellement plus tard dans la partie continue de déclencher correctement le son, même après une séquence de mutations qui l'a temporairement approché puis éloigné. Un test qui ne couvre que des mutations isolées (un seul `+1` ou un seul `−1`) ne suffit pas à garantir ce comportement. La couleur, étant une fonction pure du temps restant actuel, n'a pas besoin de ce type de test par séquence : il suffit de vérifier qu'elle est correcte pour chaque valeur de temps restant testée isolément.

---

## 7. Récapitulatif

- Le serveur est l'autorité sur le temps ; tout le reste de la spécification en découle.
- Une seule ancre temporelle : le broadcast Mercure (+ résolution au premier rendu). Pas de fetch périodique.
- `performance.now()` pour l'extrapolation locale, pas `Date.now()`.
- `requestAnimationFrame` pour la boucle d'affichage, pas `setInterval`.
- Calcul du temps restant en précision sous-seconde de bout en bout, un seul arrondi final, identique en PHP et en TS (vérifié par fixture partagée, pas par réimplémentation croisée dans les tests).
- L'écart de pause se calcule par une seule soustraction directe entre les deux timestamps concernés (`lastPausedAt − startedAt`), jamais en soustrayant deux grands écarts calculés séparément par rapport à l'instant présent, pour éviter une instabilité d'arrondi flottant qui ferait osciller l'affichage en pause près d'une frontière de seconde.
- `startedAt` ne change qu'au lancement et à la réinitialisation du jeu ; `finishedAt` change aussi à l'arrêt du jeu, sans affecter `startedAt`.
- `+1`/`−1` minute via un compteur cumulatif dédié (`manualAdjustmentSeconds`).
- Avant toute mutation, absorption automatique d'un éventuel déficit (temps restant brut négatif ou nul) dans le temps de pause total, pour que l'effet de la mutation s'applique ensuite sur une base propre, quel que soit le temps passé en overtime.
- Couleur des seuils : fonction pure du temps restant actuel (aucun état ni minuteur), affichée quand le temps restant est dans les 5 secondes qui suivent un seuil (10min, 5min, 3min) ; dès 60 secondes, bascule en danger continu sans flash séparé, y compris en overtime ; s'arrête uniquement quand `finishedAt` est renseigné, la partie étant alors terminée.
- Son des seuils : événement ponctuel déclenché uniquement par le décompte naturel, jamais par un ajustement manuel de l'arbitre (`+1`/`−1` minute, réglage direct) même s'il traverse un seuil ; la référence de comparaison, elle, se resynchronise à chaque évaluation quelle qu'en soit l'origine, y compris les ajustements manuels, pour rester fiable sur les franchissements naturels suivants. Un son de fin distinct se déclenche une fois au passage à zéro (uniquement par décompte naturel, même règle). L'état intermédiaire de l'absorption (section 3) n'est jamais exposé à cette détection.
- Spécification des seuils : chaque multiple de 10 minutes restantes → warning ; 5/3min → danger (flash 5s) ; 1min → bascule directe en danger continu (section 5).
- Toute correction sur ce composant se valide en navigateur réel.
