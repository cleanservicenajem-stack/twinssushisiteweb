-- Twins Sushi — étape 1 : sauvegarde de la base avant la nouvelle carte (24 novembre 2026).
-- À exécuter par Houssam dans Supabase → SQL Editor. Ne modifie ni ne supprime rien :
-- copie les 5 tables du site dans un schéma séparé « sauvegarde », invisible depuis le site et l'API publique.

create schema if not exists sauvegarde;

create table sauvegarde.categories_2026_11_24    as select * from public.categories;
create table sauvegarde.items_2026_11_24         as select * from public.items;
create table sauvegarde.settings_2026_11_24      as select * from public.settings;
create table sauvegarde.opening_hours_2026_11_24 as select * from public.opening_hours;
create table sauvegarde.snapshots_2026_11_24     as select * from public.snapshots;

-- Vérification : les deux colonnes de chaque ligne doivent être égales.
select 'categories' as table_copiee, (select count(*) from public.categories) as en_ligne, (select count(*) from sauvegarde.categories_2026_11_24) as sauvegarde
union all select 'items',         (select count(*) from public.items),         (select count(*) from sauvegarde.items_2026_11_24)
union all select 'settings',      (select count(*) from public.settings),      (select count(*) from sauvegarde.settings_2026_11_24)
union all select 'opening_hours', (select count(*) from public.opening_hours), (select count(*) from sauvegarde.opening_hours_2026_11_24)
union all select 'snapshots',     (select count(*) from public.snapshots),     (select count(*) from sauvegarde.snapshots_2026_11_24);
