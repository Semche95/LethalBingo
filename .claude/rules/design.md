# Conception

- **DRY dans toute l'app** (PHP, Twig, TS, CSS) : réutiliser l'existant avant d'écrire du code ; ne jamais dupliquer une logique, une valeur ou une règle de style. Un bloc qui existe déjà ailleurs se factorise (Service, composant Twig, macro, helper TS, classe CSS). Ex. : modales de confirmation via le composant `ConfirmModal`.
- **SRP** : une seule responsabilité par fichier, module, classe et fonction.
- Avant d'écrire du code, concevoir les contrats (entrées, sorties, erreurs), les interfaces et l'arborescence des fichiers, les présenter et attendre la validation de l'utilisateur. Exception : un petit correctif qui n'ajoute ni fichier, ni interface, ni contrat.
- Ces principes s'appliquent au code écrit : ils n'autorisent jamais à refactorer le code existant au-delà de la demande.
