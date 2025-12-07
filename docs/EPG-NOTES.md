# EPG Aggregator – Technické poznámky

Doplnenie k `VISION.md`. Pracovný návrh, nie dogma.

---

## 1. Súbory a konfigurácia

### 1.1 Vstupné zdroje EPG

- Konfig súbor: `epg_sources.txt` v root-e projektu.
- Každý **nekomentovaný riadok** = jeden zdroj.
- Riadky začínajúce `#` sa ignorujú.
- Poradie riadkov = priorita zdrojov (1 = najvyššia).
- Riadok je obyčajné URL, môže obsahovať aj `user:pass@`.

Typy (podľa path):

- `*.xml` → `type=xmltv`, `role=epg`
- `*.xml.gz` → `type=xmltv_gz`, `role=epg`
- `/xmltv/channels/channels.xml` → `type=tvheadend_channels`, `role=channels`
- `/xmltv/xmltv` → `type=tvheadend_xmltv`, `role=epg`

Heslá z `user:pass@` sa použijú iba na HTTP Basic Auth, **nikdy sa nebudú logovať**.

### 1.2 Logá

- `logos_src/` – zdrojové logá (picons, manuálne uploady, web).
- `logos/` – normalizované logá (napr. `logos/s/`, `logos/m/`, `logos/l/`).
- Exporty používajú len normalizované logá (rovnaké rozmery, transparentné pozadie).

---

## 2. Kanály a aliasy

### 2.1 Kanál

Tabuľka `channels`:

- `id`
- `name` – hlavný názov („Markíza HD“)
- `normalized_name` – normalizovaný názov na párovanie
- `is_master` – či je v master liste
- `sort_order` – poradie v master liste
- `country`, `grp`
- `logo_path` – napr. `/logos/m/markiza.png`
- `logo_source` – `auto` / `manual`

### 2.2 Aliasová mapa

`channel_aliases`:

- `channel_id`
- `alias` – textový alias („markiza“, „markiza-sk“…)
- `is_base` – base alias pre `channels.json`
- `source` – `auto` / `manual` / import

**Normalizácia názvu**:

- lower-case,
- bez diakritiky,
- odrezanie koncoviek typu `SK`, `CZ`, `HD`, `UHD`, `4K`, `720p`, `Origin`, atď.
- zoznam trim-tokenov bude konfigurovateľný.

Aliasová mapa sa používa pri zdrojoch bez SID/hash a pri generovaní `channels.json`.

---

## 3. ID kanálov (SID/UUID a spol.)

Tabuľka `channel_ids`:

- `channel_id` → `channels.id`
- `namespace` – napr. `sat.skylink.sid`, `tvh.main.channelUuid`, `epg.webgrab.tvg-id`
- `value` – hodnota (`6905`, `fd22bbad...`)
- `source` – `manual`, `import:tvh`, `import:linksat`, …

Pri importe EPG:

- podľa typu zdroja sa určí `namespace`,
- `(namespace, value)` → nájdi `channel_id`,
- ak nenájde, kanál je „unmapped“ a treba raz ručne doriešiť.

---

## 4. Programy (EPG záznamy)

Tabuľka `programs`:

- `channel_id`
- `epg_source_id`
- `start_utc`, `end_utc`
- `title`, `subtitle`
- `summary`, `description`
- `lang`

Rozšírené polia (ak existujú):

- `image_url`, `content_type`
- `age_rating`, `rating_label`, `rating_icon`
- `season`, `episode`
- `external_event_id`
- `extra_json` (voľný JSON na doplnky – napr. TVH metadata)

Staré záznamy → pravidelné mazanie (držanie posledných X dní).

---

## 5. Import a stav zdrojov

### 5.1 Cron

- Import beží (napr.) raz za 24 hodín.
- Pre každý riadok v `epg_sources.txt`:
  - stiahnuť obsah,
  - podľa typu (xmltv / tvheadend…) ho spracovať,
  - naplniť `channels`, `channel_ids`, `programs`.

### 5.2 `epg_source_state`

Pre každý zdroj si pamätáme:

- `last_status`:
  - `OK` – stiahnuté, nový obsah,
  - `NO_UPDATE` – stiahnuté, hash rovnaký ako minule,
  - `EMPTY` – 0 programov/kanálov,
  - `DEAD` – HTTP chyba, timeout, rozbitý XML…
- `last_hash`
- `last_success_at`, `last_fail_at`
- `fail_streak`

Pravidlá:

- `EMPTY`/`DEAD` → `fail_streak++`
- `OK`/`NO_UPDATE` → `fail_streak = 0`

Interpretácia:

- `1–2` → WARNING (žltá),
- `>=3` → REDFLAG (kandidát na vyradenie / manuálnu kontrolu).

---

## 6. Exporty

Exporty:

1. **XMLTV** – pre celý master list alebo subset kanálov (CLI parameter).
2. **`channels.json` pre hls-proxy** – ID/basealias, názov, krajina, group, `logo_url`, aliasy.

Export vie:

- vygenerovať všetky master kanály,
- alebo len zoznam kanálov zadaný v CLI.
