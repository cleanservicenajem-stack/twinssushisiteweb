# Mise en ligne de la carte Indigo (voie A) — marche à suivre

**Mise à jour du 24 novembre 2026 : carte VF2 (146 produits), horaires du 24 novembre, PDF VF4 à placer dans `assets/carte/carte-twins-sushi.pdf`. Ordre détaillé : `rapports/site-web_carte-vf2_24_novembre_2026.md`.**

Préparé le 21 novembre 2026 par l'agent « Site web ». **Rien de ceci n'a été exécuté.** Chaque étape est faite par Houssam.

## Ce qui change
- `index.php` : nouveau design (maquette 1b), même base Supabase.
- `inc/bootstrap.php` : lit les nouveaux champs (accroche, bannière, choix de prix, nombre de personnes). Si la migration n'est pas encore passée, il relit l'ancien format (le site ne tombe pas).
- `admin/` : champs « choix de prix », « pour combien de personnes », « accroche de rubrique » ; badges réduits à Veggie / Épicé ; description jusqu'à 400 caractères.
- `assets/site.css`, `assets/site.js`, `assets/menu/` (+146 photos), `assets/bannieres/` (21 bannières).
- `supabase/migration-carte-vf.sql` (ajoute 4 colonnes, ne supprime rien) et `supabase/seed-carte-vf4b-24-novembre-2026.sql` (remplace la carte, **sauvegarde l'ancienne dans `snapshots`**).
- Non touchés : réglages, horaires, comptes admin, ancien `seed.sql`, ancien CSS/JS (restent en place, inutilisés).

## Ordre conseillé
1. **Sauvegarde** : SQL Editor → coller `supabase/sauvegarde-24-novembre-2026.sql` → Run (copie des 5 tables dans le schéma `sauvegarde`, invisible du site). Le tableau final doit montrer des chiffres égaux deux à deux. En plus, facultatif : Table Editor → `items` puis `categories` → « Export » en CSV, fichiers gardés sur le Mac.
2. **Migration** : SQL Editor → coller `migration-carte-vf.sql` → Run. Aucun effet visible.
3. **Branche de test** : pousser les fichiers sur une branche (pas `main`). Vercel crée une adresse d'aperçu. Elle lit la même base : elle affiche encore l'ancienne carte dans le nouveau design. Vérifier l'aspect.
4. **Carte** : SQL Editor → coller `seed-carte-vf4b-24-novembre-2026.sql` → Run. Le site actuel en ligne affiche alors la nouvelle carte dans l'ancien design (quelques minutes, jusqu'à l'étape 5).
5. **Vérifier l'aperçu** (carte, panier, WhatsApp, recherche, box, téléphone).
6. **Mise en ligne** : fusionner la branche dans `main` ; Vercel déploie.
7. **Admin** : se connecter, ouvrir un plat, vérifier les nouveaux champs.

## Retour arrière
- **Carte** : admin → Sécurité & sauvegardes → restaurer la version d'avant le 21 novembre (créée par le seed). Les 4 colonnes ajoutées restent, sans effet.
- **Code** : dans Vercel, « Promote to Production » sur le déploiement précédent, ou annuler le commit sur `main`.

## À décider avant l'étape 4 (modifiables ensuite dans l'admin ou dans `index.php`)
- **Horaires (dit par Houssam le 24 novembre 2026)** : lundi – jeudi 12h – minuit ; vendredi 17h – 1h ; samedi 12h – 1h ; dimanche 17h – 1h. À saisir dans l'admin → « Infos & liens » → Horaires, **4 lignes** :
  | Libellé | Jours cochés | Ouverture | Fermeture | Texte affiché |
  |---|---|---|---|---|
  | Lundi – Jeudi | L M M J | 12:00 | 00:00 | 12h – minuit |
  | Vendredi | V | 17:00 | 01:00 | 17h – 1h |
  | Samedi | S | 12:00 | 01:00 | 12h – 1h |
  | Dimanche | D | 17:00 | 01:00 | 17h – 1h |
  Une fermeture plus tôt que l'ouverture (01:00) = après minuit : le site le comprend. Autre voie : `supabase/horaires-24-novembre-2026.sql` (le fichier du 21 novembre est périmé).
- **Bandeau** « Ouverture le lundi 28 septembre » (expiré, masqué) : à vider dans l'admin.
- **Signatures et rolls de la box** : listes `$sigNames` et `$boxNames` en haut de `index.php`.
- **Liens Glovo / Yassir / Kool** : vides, donc non affichés (admin).

## Testé le 21 novembre 2026 (en local, PHP 8.3, **faux Supabase** qui sert la carte au format de la base)
- Syntaxe PHP : tous les fichiers (`index.php`, `inc/`, `admin/`, `api/`) sans erreur.
- Page : 22 rubriques, 144 fiches, kanji, bannières, photos, signatures, box à composer ; panier avec choix de prix, message WhatsApp, recherche (« thon » : 12), filtres (Épicé 5, Veggie 9), box 4 rolls, bascule de thème.
- Base **sans** la migration : la page s'affiche quand même (repli sur l'ancien format). Base injoignable : page de secours (code 503).
- Admin : analyse de syntaxe JavaScript et test de l'analyse des « choix de prix » seulement.

## Non vérifié
- Aucun essai contre le **vrai** Supabase (ni lecture, ni migration, ni seed).
- L'**admin** n'a pas été essayé de bout en bout (connexion, enregistrement d'un plat avec choix de prix, accroche de rubrique) : à faire sur l'aperçu Vercel, étape 7.
- Rendu sur téléphone réel non vérifié sur le site PHP (vérifié sur les maquettes à 390 px).
- Le dossier `maquettes/` est dans la copie ; à ne pas pousser (inutile en ligne, 12 Mo).
