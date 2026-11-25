-- Twins Sushi — horaires du 24 novembre 2026 (dit par Houssam, transmis par la session de pilotage).
-- NON EXÉCUTÉ. Alternative au réglage dans l'admin (Infos & liens → Horaires).
-- Remplace horaires-21-novembre-2026.sql (périmé).
-- Jours au format du site : 0 = dimanche … 6 = samedi. Une fermeture avant l'ouverture (01:00) = après minuit.
--   Lundi – Jeudi   12h – minuit
--   Vendredi        17h – 1h
--   Samedi          12h – 1h
--   Dimanche        17h – 1h

begin;

delete from public.opening_hours;

insert into public.opening_hours (label, days, opens, closes, display, sort_order) values
  ('Lundi – Jeudi', '1,2,3,4', '12:00', '00:00', '12h – minuit', 0),
  ('Vendredi',      '5',       '17:00', '01:00', '17h – 1h',     1),
  ('Samedi',        '6',       '12:00', '01:00', '12h – 1h',     2),
  ('Dimanche',      '0',       '17:00', '01:00', '17h – 1h',     3);

commit;
