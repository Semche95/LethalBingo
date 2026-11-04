---
paths:
  - "src/**"
  - "tests/**"
  - "config/**"
  - "migrations/**"
---

# PHP (8.5+)

- Syntaxe moderne : constructor promotion, readonly, enums, match, nullsafe, first-class callables, named arguments. Pas de code déprécié.
- Attributs PHP partout (routing, validation, ORM, security), jamais d'annotations.
- `new ClassName()->method()` sans parenthèses autour du `new`.
- Pas d'appel entre controllers : logique partagée dans un Service. Early returns, pas de globales.
- **Repository** : DQL uniquement (pas de SQL natif), résultats bruts sans boucle PHP (`foreach`, `array_map`) ; le réarrangement se fait dans le service ou le contrôleur.
- Pas de fusion PHP de données qu'un template Twig re-parcourt : exposer les jeux de données bruts comme propriétés distinctes du composant et joindre dans le template.
- Paramètres mot de passe / secret : `#[SensitiveParameter]`.
