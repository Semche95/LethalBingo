---
paths:
  - "src/**"
  - "tests/**"
  - "config/**"
  - "composer.json"
---

# Symfony

- Nouvelle fonctionnalité via `composer require` (la recipe Flex enregistre le bundle et sa config) : jamais de `bundles.php` ni de config de bundle écrits à la main.
- Autowiring et autoconfiguration ; cas particuliers dans la classe (`#[Autowire]`, `#[Target]`), `services.yaml` en dernier recours.
- Données de requête via `#[MapRequestPayload]` / `#[MapQueryString]`, pas de `json_decode()` manuel.
- Pas de service `readonly` susceptible de devenir `lazy: true` (un proxy lazy ne peut pas étendre une classe `readonly`).
- Exclusion mutuelle (actions concurrentes sur la même donnée) via `symfony/lock` (`LockFactory`), jamais de drapeau ou fichier verrou maison.
- En cas d'erreur, lire `var/log/dev.log` et le profiler (`/_profiler`) avant de modifier le code.
- `make:*` avec tous les arguments et `--no-interaction` (sinon la commande s'interrompt).
- Vérifier plutôt que supposer : `debug:router`, `debug:container`, `debug:autowiring`, `debug:config`, `config:dump-reference`, la source dans `vendor/`, et la doc de la version installée.
