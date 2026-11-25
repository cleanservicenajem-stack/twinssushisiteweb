-- Twins Sushi — carte VF6 (25 novembre 2026) : composition du Crispy Salmon « saumon, avocat, oignons frits ». NON EXÉCUTÉ.
-- Avant : « saumon, avocat, sauce teriyaki » (VF5). Nom et prix (35) inchangés.
update public.items set description = 'saumon, avocat, oignons frits' where code = 'R08-10' and name = 'Crispy Salmon';
select code, name, description, price from public.items where code = 'R08-10';
