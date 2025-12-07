# Integrácia s HLS-Proxy

Tento dokument popisuje, ako EPG agregátor spolupracuje s **HLS-Proxy** a jeho `channels.json` / logami / XMLTV.

HLS-Proxy používa vlastný formát `channels.json` a sadu log v `./html/logos`. Podľa oficiálneho repozitára `hlsproxy/channels` platí, že:

- `_id` musí byť jedinečný,
- `country` je ISO 3166-1 alpha-2, lowercase (napr. `sk`, `cz`),
- `logo_url` je URL alebo relatívna cesta (napr. `/logos/file.png`),
- `group` odkazuje na ID z `groups.json`,
- `basealias` je hlavný alias kanálu,
- `aliases` je string aliasov oddelených `|`.

EPG agregátor generuje vlastný `channels.json` kompatibilný s týmto formátom, spravuje lokálnu sadu log **a generuje XMLTV súbor**, ktorý HLS-Proxy používa ako EPG zdroj.

---

## 1. Filesystem layout

### 1.1 Na strane HLS-Proxy

Typické rozloženie:

- `./hls-proxy` (binárka / spustiteľný súbor)
- `./channels.json` (náš generovaný súbor)
- `./html/logos/` (PNG/SVG logá kanálov)
- `./epg.xml` (XMLTV EPG z EPG agregátora – názov/cesta podľa konfigurácie HLS-Proxy)

HLS-Proxy pri štarte:

- ak vedľa seba nájde `channels.json`, použije ho namiesto build-in verzie,
- logá berie z `./html/logos/` podľa ciest uvedených v `logo_url`,
- XMLTV EPG input (`epg.xml` alebo iný názov) berie z cesty nastavenej v jeho konfigurácii.

### 1.2 Na strane EPG agregátora

EPG agregátor má vlastnú (konfigurovateľnú) cestu:

- `var/export/channels.json` – primárny export `channels.json`,
- `var/export/epg.xml` – XMLTV export master EPG,
- `var/html/logos_src/` – originálne logá (upload / nájdené),
- `var/html/logos/` – spracované logá (small/medium/large).

Nasadenie môže byť:

- **na tom istom serveri** – agregátor zapisuje priamo do HLS-Proxy adresára (napr. symlink / shared path),
- **na inom serveri** – pravidelný sync (rsync/cron) z `var/export` + `var/html/logos` do HLS-Proxy inštalácie.

---

## 2. Mapovanie z DB na `channels.json`

### 2.1 Štruktúra `channels.json`

Náš export bude pole objektov, kde každý záznam spĺňa HLS-Proxy formát:

```jsonc
[
  {
    "_id": "discoverychannel",
    "country": "sk",
    "group": "documentary",
    "logo_url": "/logos/m/discoverychannel.png",
    "basealias": "discoverychannel",
    "aliases": "discoverychannel|discoverychannelhd|discoveryhdsk"
  },
  ...
]
