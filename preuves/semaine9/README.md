# Preuves — Semaine 9 (interface et non fonctionnel)

- **Date :** 11 novembre 2026, vers 22 h 20 (UTC+1)
- **Environnement :** Chrome local, Playwright 1.59.1, @axe-core/playwright 4.14.0, k6 2.3.0 (`tools/k6`, non versionné), serveur `php -S` + `scripts/serveur-test-router.php` (`APP_ENV=test` forcé), base `wassalni_test`. Seuls les comptes fictifs `semaine7-9.*@example.test` apparaissent.

| Fichier | Type | Contenu / résultat |
|---|---|---|
| `S09_PARCOURS_1` à `S09_PARCOURS_4_*.png` | Résultat | Parcours critique : recherche, fenêtre de réservation, confirmation, « Mes réservations » |
| `S09_parcours-verifications.json` | Résultat (base) | Réponse JSON et relecture SQL (2 places, 50 DT, stock 4 → 2) |
| `S09_A11Y_*.png` / `S09_accessibilite-axe.json` | Exécution | Audit axe sur 4 pages et sonde clavier |
| `S09_securite-elementaire.json` / `S09_SECU_page_erreur_500.png` | Exécution | En-têtes HTTP, attributs du cookie de session (sans valeur), page d'erreur |
| `S09_playwright-execution.txt` / `S09_playwright-rapport.json` | Exécution | 8 tests : 5 réussis, 3 échecs (= violations axe critiques, ANO-36) |
| `S09_k6-execution.txt` / `S09_k6-resume.json` | Exécution | 452 requêtes, 0 % d'échec, p95 208,7 ms, seuils respectés |
| `S09_concurrence-OBS-J.txt` | Exécution | Entrelacement de deux transactions : 2 réservations pour 1 place (ANO-38) |
| `S09_audit-dependances.txt` | Exécution | `composer audit` (0 vulnérabilité) et `npm audit` (outillage de test) |
