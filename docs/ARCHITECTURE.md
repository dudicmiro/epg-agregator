# ARCHITECTURE

## Pipeline

Source → Ingest → Parse → Normalize → Store → Export

- **Source**: URL alebo súbor s XMLTV (vč. `.gz`).
- **Ingest**: prečítanie súboru/URL, rozbalenie `.gz`.
- **Parse**: XMLTV → ParsedEpg (ParsedChannel, ParsedProgram).
- **Normalize**: ParsedEpg → NormalizedEpg (Channel, NormalizedProgram).
- **Store**: SQLite (tabuľky `channels`, `programs`, `settings`).
- **Export**: XMLTV, JSON (epg.json), HLS-Proxy štýl `channels.json`.

## Štruktúra repozitára

- `src/Domain/`
  - `Channel`, `Program` – interný model.
- `src/Ingest/`
  - `Source1Client` – načítanie zdroja (file/URL, gz detekcia).
- `src/Parse/`
  - `Source1Parser` – XMLTV → ParsedEpg (SimpleXML + LIBXML_PARSEHUGE).
- `src/Normalize/`
  - `Source1Normalizer` – konverzia na interný model + UTC časy.
- `src/Store/`
  - `SqliteConnectionFactory`, `SchemaManager`
  - `ChannelRepository`, `ProgramRepository`, `SettingsRepository`
- `src/Export/`
  - `XmltvExporter`, `JsonExporter`, `ChannelsJsonExporter`
- `bin/`
  - `epg` – CLI front-end.
- `data-samples/`
  - testovacie XMLTV sample a `master.txt`.
- `var/`
  - `epg.sqlite` (DB), exportované súbory (ignorované v gite).

## DB model (SQLite)

- `channels`:
  - `id`, `xmltv_id`, `name`, `logo_url`,
  - `is_master` (0/1), `position` (poradie v master liste).
- `programs`:
  - `id`, `channel_id`, `title`, `subtitle`, `description`,
  - `start_utc`, `end_utc`,
  - `source`, `source_program_id`,
  - `dedup_key` (unique index).
- `settings`:
  - `key`, `value` – generický key/value store (napr. master list text).
  