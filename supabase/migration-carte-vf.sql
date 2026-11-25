-- Twins Sushi — migration pour la carte Indigo (20 novembre 2026).
-- À exécuter UNE FOIS dans le SQL Editor de Supabase, AVANT seed-carte-vf.sql. Rejouable sans danger.
-- Ne supprime ni ne modifie aucune donnée : elle ajoute seulement des colonnes et élargit une limite.
--
--   categories.note     accroche de la rubrique (« 6 pièces · roulés dans l'algue »)
--   categories.banner   chemin de la bannière de la rubrique (« assets/bannieres/11_maki.webp »)
--   items.options       choix de prix : [{"l":"Veggie","p":45},{"l":"Poulet","p":61}] (null = prix unique)
--   items.persons       « 3-4 pers. » pour les box
--   items.description   limite portée de 220 à 400 caractères (compositions des box)

begin;

alter table public.categories add column if not exists note   text not null default '';
alter table public.categories add column if not exists banner text not null default '';

alter table public.items add column if not exists options jsonb;
alter table public.items add column if not exists persons text not null default '';

alter table public.items drop constraint if exists items_description_check;
alter table public.items add constraint items_description_check check (length(description) <= 400);

commit;
