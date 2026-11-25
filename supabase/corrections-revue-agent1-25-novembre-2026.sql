-- Twins Sushi — corrections de la revue de l'agent 1 (25 novembre 2026), source : cat1.py / cat3.py de la carte.
-- NON EXÉCUTÉ. Pastilles, ordre d'affichage, « 24 pièces » en double.
begin;
update public.items set badge = 'vege' where name in ('Menu Veggie', 'Poke Veggie');
update public.items set badge = ''     where name = 'Green Box';
update public.items set sort_order = 7 where name = 'Signature Box';
update public.items set sort_order = 8 where name = 'Salmon Lovers';
update public.items set sort_order = 8  where name = 'Edamame';
update public.items set sort_order = 9  where name = 'Yakitori Poulet';
update public.items set sort_order = 10 where name = 'Yakitori Bœuf Fromage';
update public.items set sort_order = 11 where name = 'Yakitori Crevettes';
update public.items set sort_order = 12 where name = 'Yakitori Saumon';
update public.items set sort_order = 2 where name = 'Salade de Chou';
update public.items set sort_order = 3 where name = 'Riz Vinaigré';
update public.items set sort_order = 4 where name = 'Riz Blanc';
update public.items set show_pcs = false where name = 'Formule Pizza';
commit;
select name, badge, sort_order, show_pcs from public.items where name in ('Menu Veggie','Poke Veggie','Green Box','Signature Box','Salmon Lovers','Edamame','Yakitori Poulet','Salade de Chou','Riz Vinaigré','Riz Blanc','Formule Pizza') order by category_id, sort_order;
