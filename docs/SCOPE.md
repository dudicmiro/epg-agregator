## Cieľ

- Agregovať EPG dáta (XMLTV) z viacerých zdrojov.
- Normalizovať ich do jednotného interného modelu (Channel, Program).
- Ukladať do SQLite (čas v UTC).
- Exportovať:
  - `epg.xml` (XMLTV),
  - `epg.json` (jednoduchý JSON s kanálmi a programami),
  - `channels.json` (HLS-Proxy štýl zoznam kanálov).

## In scope (MVP)

- 1 zdroj: `source1` (XMLTV, podporuje aj `.gz`).
- CLI rozhranie:
  - `migrate`, `import`, `master apply/rollback`, `channels`, `export`.
- Normalizácia ako jediný bod "logiky".
- Deduplikácia programov po normalizácii (`dedup_key`).
- Master zoznam kanálov (textový zoznam, poradie = pozícia).

## Out of scope (zatím)

- HTTP/UI vrstva (webové rozhranie).
- Manažment viacerých zdrojov cez UI.
- Jazykové priority (SK > CZ > EN > ...).
- Pokročilé aliasovanie kanálov podľa playlistov.
- Integrácia s HLS-Proxy beyond exportu `channels.json`.
