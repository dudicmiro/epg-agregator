# EPG Aggregator

Malý PHP nástroj na:

- načítanie EPG (XMLTV) z viacerých zdrojov,
- normalizáciu do interného modelu,
- uloženie do SQLite,
- export vo formáte XMLTV + JSON (epg.json + channels.json).

## Požiadavky

- PHP >= 8.3 (CLI)
- Composer
- SQLite3

## Základné príkazy

```bash
./bin/epg migrate
./bin/epg import source1 /path/to/source1.xml[.gz]
./bin/epg master apply docs/master.txt
./bin/epg export xmltv var/epg.xml
./bin/epg export json var/epg.json
./bin/epg export channels-json var/channels.json
./bin/epg channels [all|master]
