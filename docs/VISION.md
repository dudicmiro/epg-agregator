# EPG Aggregator – Vision

Tento dokument popisuje, čo má EPG agregátor robiť a v akom koncepte ho ďalej rozvíjať.

---

## 1. Cieľ

EPG agregátor:

- zbiera EPG z viacerých heterogénnych zdrojov (XMLTV, TVHeadend, vlastný export…),
- normalizuje a zlučuje ich do jednej internej databázy,
- rieši aliasy, ID kanálov, jazyky a logá,
- exportuje len tie kanály, ktoré sú v **master liste**,
- poskytuje výstupy:
  - XMLTV (hlavný EPG export),
  - `channels.json` kompatibilný s projektom **hls-proxy**,
- drží si vlastnú sadu log (picons) v jednotných rozmeroch.

Cieľ: malý, čistý, spoľahlivý EPG engine, ktorý sa dá použiť v HLS proxy a ďalších projektoch.

---

## 2. Kanály a master list

- **Channel** = logický TV kanál (Markíza, JOJ…).
- Kanál má:
  - meno a normalizovaný názov,
  - aliasy (viac názvov pre ten istý kanál),
  - rôzne ID (SID, UUID, xmltv-id…),
  - príznak, či patrí do **master listu**,
  - poradie v master liste,
  - informáciu o logu.

Typy kanálov:

- **Master** – sú v master liste, objavia sa vo výstupoch.
- **Discovered** – detegované z EPG zdrojov, ale zatiaľ nie sú v master liste.

Master list:

- je prioritný zoznam kanálov (poradie zodpovedá poradiu vo výstupe),
- používateľ si ho vie upraviť (poradie, pridanie/odstránenie kanálov),
- v UI to môže byť jednoduchý textarea / editor.

---

## 3. Vstupné zdroje EPG

- Zoznam vstupov je v súbore `config/epg_sources.txt` (1 riadok = 1 zdroj).
- Zdroj môže byť:
  - klasické XMLTV (HTTP, HTTPs),
  - XMLTV gzip (`.xml.gz`),
  - TVHeadend channels list (`/xmltv/channels/channels.xml`),
  - TVHeadend XMLTV export (`/xmltv/xmltv`),
  - ďalšie typy podľa potreby.

Poradie riadkov v súbore = priorita zdrojov.

Health-check zdrojov:

- samostatná tabuľka v DB (`epg_source_state`) si drží:
  - posledný stav (`OK`, `NO_UPDATE`, `EMPTY`, `DEAD`),
  - hash posledného obsahu,
  - počítadlo neúspechov `fail_streak`.

Cron/daemon:

- import beží zhruba každých 24 hodín,
- pri 1–2 neúspechoch → WARNING,
- pri 3+ neúspechoch → REDFLAG (kandidát na vyradenie / kontrolu).

---

## 4. Identita kanála (channel IDs)

Jeden kanál môže mať viac identít:

- SID od rôznych providerov (Skylink, Freesat, …),
- TVHeadend UUID/hash,
- XMLTV `channel id`,
- Webgrab/iptv-epg `tvg-id`,
- interné ID iných systémov.

Tieto ID sa ukladajú do jednotnej tabuľky `channel_ids`:

- `channel_id` – náš kanál,
- `namespace` – typ ID (napr. `sat.skylink.sid`, `tvh.main.channelUuid`),
- `value` – konkrétna hodnota (napr. `6905`, `fd22bbad...`),
- `source` – kto to založil (`manual`, `import:tvh`, `import:linksat`…).

Pri importe EPG sa `<channel id>` (alebo iný identifikátor) prekladá na náš `channel_id` práve cez túto tabuľku.

---

## 5. Aliasová mapa názvov

Pre lepšie párovanie podľa názvov má každý kanál:

- `name` – hlavný názov,
- `normalized_name` – názov po normalizácii,
- sadu aliasov v tabuľke `channel_aliases`.

Normalizácia:

- lower-case,
- odstránenie diakritiky,
- odseknutie suffixov typu `SK`, `CZ`, `HD`, `UHD`, `4K`, `720p`, `Origin` atď.
- tieto „trim tokens“ budú konfigurovateľné.

Aliasová mapa:

- používa sa najmä pri zdrojoch bez SID/hash (SNP picons, obyčajné XMLTV),
- prežije vyradenie kanála z master listu (aliasy zostávajú v DB),
- je podkladom pre `channels.json` (basealias + aliases).

---

## 6. Logá (picons)

Agregátor si spravuje vlastnú sadu log:

- **zdrojové logá** v `logos_src/`:
  - z picon packov (SRP/SNP),
  - z externých webov (napr. Linksat),
  - manuálne nahrané používateľom.
- **normalizované logá** v `logos/`:
  - generované z originálu do fixných rozmerov (napr. s/m/l),
  - používajú sa v exportoch (XMLTV, `channels.json`).

Priorita:

1. manuálne logo (`logo_source = 'manual'`) – nikdy neprepísať automaticky,
2. picon packy,
3. ostatné zdroje.

Picon pack Skylink (SRP):

- názvy typu `1_0_1_<SIDhex>_<TSID>_<ONID>_...png`,
- z `<SIDhex>` sa získava dec SID a spáruje sa so Skylink kanálmi.

---

## 7. Programy (EPG záznamy)

Tabuľka `programs` reprezentuje jednotlivé EPG udalosti:

- `channel_id`,
- `epg_source_id`,
- `start_utc`, `end_utc`,
- `title`, `subtitle`,
- `summary`, `description`,
- `lang`.

Rozšírené polia (ak ich zdroj poskytuje, typicky TVHeadend):

- `image_url`,
- `content_type` (žáner),
- `age_rating`, `rating_label`, `rating_icon`,
- `season`, `episode`,
- `external_event_id`,
- `extra_json` (ľubovoľné doplnkové meta).

Merge EPG:

- viac zdrojov pre ten istý kanál a čas → vyberá sa podľa priority zdroja a jazyka,
- možný „best of both“ prístup (napr. text z jedného zdroja, poster z TVHeadend).

---

## 8. Exporty

EPG agregátor poskytuje:

1. **XMLTV export** – výsledné EPG pre master kanály (alebo podmnožinu podľa CLI).
2. **`channels.json`** – podklad pre HLS-Proxy:

   - `_id` / `basealias`,
   - `country`, `group`,
   - `logo_url` (naše normalizované logo),
   - `aliases` (zoznam aliasov kanála).

Export sa dá obmedziť na:

- celý master list,
- konkrétny kanál,
- zoznam kanálov (CLI parameter).

---

## 9. Ďalšie smery

- UI pre:
  - editable master list,
  - mapovanie TVHeadend kanálov na naše kanály,
  - stav vstupných zdrojov (OK / WARNING / REDFLAG).
- Pruning starých EPG záznamov (uchovávať len X dní).
- Viac typov výstupov (JSON API, indexy pre vyhľadávanie).
