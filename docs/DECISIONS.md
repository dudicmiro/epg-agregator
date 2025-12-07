# DECISIONS

## 2025-12-07 – MVP backbone

- Použitý jazyk: PHP 8.3 (CLI), SQLite ako storage.
- Interný model: `Channel`, `Program`, časy vždy v UTC (ISO8601 v DB).
- Deduplikácia programov po normalizácii (`dedup_key` + UNIQUE index).
- Parser pre XMLTV používa `LIBXML_PARSEHUGE` kvôli veľkým EPG feedom.
- Normalizácia je JEDINÉ miesto, kde sa rieši logika a konverzie.
- Import rieši len:
  - ingest (file/URL, gzip),
  - parse,
  - normalize,
  - zápis do DB + deduplikácia.

## 2025-12-07 – Master zoznam kanálov

- Kanály sa pri importe iba:
  - upsertujú podľa `xmltv_id`,
  - aktualizuje sa `name` + `logo_url`.
- Pole `is_master` + `position` sa mení LEN cez master mechanizmus.
- Master list je text (jeden channel na riadok, v poradí).
- Master sa aplikuje cez CLI: `epg master apply master.txt`.
- Predchádzajúca verzia master listu sa ukladá do `settings` a je možné
  ju obnoviť cez `epg master rollback`.
- Exporty (XMLTV, epg.json, channels.json) pracujú LEN s master kanálmi.

## 2025-12-07 – Exporty

- `epg.xml` – XMLTV:
  - generuje sa z DB (channels + programs),
  - používa UTC + `+0000` offset v XMLTV formáte.
- `epg.json` – jednoduchý JSON:
  - obsahuje `generated_at_utc` + zoznam kanálov,
  - každý kanál obsahuje zoznam programov s UTC časmi a source info.
- `channels.json` – HLS-Proxy style:
  - exportuje iba master kanály,
  - `_id` = `channels.id`,
  - `aliases` = `|slug(name)|xmltv_id|`,
  - `basealias` = slug názvu kanála,
  - `country` zatiaľ `null`,
  - `group` zatiaľ `"custom"`.

## 2025-12-07 – CLI

- `epg migrate` – vytvorí/aktualizuje DB schema.
- `epg import source1 file.xml[.gz]`:
  - zmeria čas importu (sekundy, 3 desatinné miesta),
  - vypíše počty: inserted, skipped (no channel), skipped (duplicates).
- `epg master apply master.txt` / `epg master rollback`.
- `epg export xmltv epg.xml[.gz]`
- `epg export json epg.json`
- `epg export channels-json channels.json`
- `epg channels [all|master]` – výpis kanálov + počtu programov.

