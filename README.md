# gmlview

Vibekodowana przeglądarka plików GML z RCN (Rejestr Cen Nieruchomości).

## Użycie

Wpisz `make` i wejdź pod adres wypisany na starcie, np. dla:

```
    inet 127.0.0.1/8 scope host lo
    inet6 ::1/128 scope host 
    inet 172.17.0.2/16 brd 172.17.255.255 scope global eth0
[Thu Mar  5 19:31:40 2026] PHP 8.3.30 Development Server (http://0.0.0.0:80) started
```

to będzie **172.17.0.2**.

## Wymagania

- docker
- katalog gml (obok src/) z plikami gml

## WAŻNE

Większość projektu wygenerowałem darmową wersją ChatGPT. Nie daję żadnej
gwarancji, a szczególnie odnośnie bezpieczeństwa tego rozwiązania.

Nie próbuj wystawiać tego kontenera na świat, a najlepiej nawet na swoją sieć
wewnętrzną.

Projekt zawiera wiele błędów, których nie mam zamiaru naprawiać. Nie działa na
pewno wiele transakcji na jedną działkę. Gubi też sporo danych przy konwersji.
Jeśli chcesz, przyjmuję pull requesty, nie wiem kiedy (i czy w ogóle) znajdę
czas na ich review i merge. Dopuszczam kod generowany przez AI, o ile działa.
