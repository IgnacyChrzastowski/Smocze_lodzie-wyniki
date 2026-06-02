-- Migracja: dodanie kolumny status do tabeli zawody
-- Uruchom ten skrypt raz na istniejącej bazie danych

ALTER TABLE `zawody`
  ADD COLUMN `status` ENUM('aktywne', 'zakończone') NOT NULL DEFAULT 'aktywne' AFTER `nazwa`;

-- Ustaw istniejące zawody jako zakończone (opcjonalnie - możesz ręcznie zmienić)
-- UPDATE `zawody` SET `status` = 'zakończone';

-- Lub zostaw wszystkie jako aktywne (domyślnie zrobi to ALTER TABLE powyżej)
