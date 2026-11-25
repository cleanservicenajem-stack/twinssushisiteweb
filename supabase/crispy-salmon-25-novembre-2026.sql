-- Twins Sushi — « California Saumon Oignon Frit » devient « Crispy Salmon » (Houssam, 25 novembre 2026).
-- Même rubrique (California), même prix (35), même photo ; description de la VF5 : « saumon, avocat, sauce teriyaki ». NON EXÉCUTÉ.
update public.items set name = 'Crispy Salmon', description = 'saumon, avocat, sauce teriyaki'
 where code = 'R08-10' and name = 'California Saumon Oignon Frit';
select code, name, description, price, image from public.items where code = 'R08-10';
