# EPG Aggregator

Malý PHP nástroj na:

- načítanie EPG (XMLTV) z viacerých zdrojov,
- normalizáciu do interného modelu,
- uloženie do SQLite,
- export vo formáte XMLTV + JSON (epg.json + channels.json).

## Základné príkazy

- ./bin/epg migrate
- ./bin/epg import source1 /path/to/source1.xml[.gz]
- ./bin/epg master apply data-samples/master.txt
- ./bin/epg export xmltv var/epg.xml
- ./bin/epg export json var/epg.json
- ./bin/epg export channels-json var/channels.json
- ./bin/epg channels [all|master]

