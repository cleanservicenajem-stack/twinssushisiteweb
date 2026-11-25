-- Twins Sushi — retrait du fixe 05 21 23 19 26 (Houssam, 25 novembre 2026 : « on a uniquement le 0619 »).
-- Le téléphone du site devient le 06 19 16 20 94 (même numéro que WhatsApp). Réglage modifiable aussi dans l'admin (Infos & liens).
-- Avant : tel = +212521231926, tel_affiche = 05 21 23 19 26 (copie dans sauvegarde.settings_2026_11_24).
update public.settings set value = '"+212619162094"'::jsonb where key = 'tel';
update public.settings set value = '"06 19 16 20 94"'::jsonb where key = 'tel_affiche';
select key, value from public.settings where key in ('tel', 'tel_affiche', 'wa', 'wa_affiche') order by key;
