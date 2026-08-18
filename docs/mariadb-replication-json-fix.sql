-- ============================================================================
-- MySQL 5.7 -> MariaDB replication: native JSON columns break the replica
-- ============================================================================
-- Symptom on the MariaDB replica:
--   Last_SQL_Errno: 1677
--   Last_SQL_Error: Column N of table 'db.tbl' cannot be converted
--                   from type 'json' to type 'longtext'
--
-- Cause: MySQL 5.7 has a native binary JSON type and writes that binary
-- representation into row-based binlog events. MariaDB has no native JSON --
-- there JSON is only an alias for LONGTEXT -- so the replica cannot decode the
-- event and the SQL thread stops.
--
-- Fix: store these columns as LONGTEXT on the SOURCE. MySQL converts the value
-- to its text representation in place, applications that json_encode/decode in
-- code are unaffected, and the resulting row events replicate cleanly.
-- "ALTER TABLE ... MODIFY ... LONGTEXT" is itself valid MariaDB syntax, so the
-- DDL replicates without a further error.
--
-- Requires ALTER on pixilink_mlsr and pixilinkvow. The bccondosandhomes
-- application user has neither, so a DBA/root must run this.
--
-- Audited 2026-08-17: these are the ONLY four native JSON columns on the
-- server. bccondosandhomes (the bcchv2 application database) has zero.
-- ============================================================================

-- Verify before: should list exactly the four columns below.
SELECT table_schema, table_name, column_name, data_type
FROM information_schema.columns
WHERE data_type = 'json'
ORDER BY table_schema, table_name;

-- ---------------------------------------------------------------------------
-- 1. pixilink_mlsr.bcn_building_info_cached.api_data
--    Written by bcchv2 (App\Models\BcnBuildingInfoCached::syncNow()). The model
--    casts api_data to 'object', i.e. json_encode/json_decode in PHP, so it
--    behaves identically once the column is LONGTEXT.
--    This is the column most likely to be breaking replication in practice,
--    because it is the one actively written to.
-- ---------------------------------------------------------------------------
ALTER TABLE `pixilink_mlsr`.`bcn_building_info_cached`
    MODIFY `api_data` LONGTEXT NULL;

-- ---------------------------------------------------------------------------
-- 2. pixilink_mlsr.bcn_building_info_cached_26jan2026.api_data
--    Dated copy of the same table. Included so a future rename/restore does not
--    reintroduce the problem. The other dated copies (_250604, _250604_jan26,
--    _250605) are already LONGTEXT.
-- ---------------------------------------------------------------------------
ALTER TABLE `pixilink_mlsr`.`bcn_building_info_cached_26jan2026`
    MODIFY `api_data` LONGTEXT NULL;

-- ---------------------------------------------------------------------------
-- 3-4. pixilinkvow.map_searches.data and pixilinkvow.saved_searches.data
--    NOT owned by bcchv2 -- these belong to the VOW application, whose source
--    is not on this server. CHECK THAT APPLICATION FIRST: if it uses MySQL
--    JSON-only SQL against these columns (the -> and ->> shorthand operators
--    specifically require a JSON-typed column), those queries must be rewritten
--    to JSON_EXTRACT(...) before converting. JSON_* functions themselves work
--    fine against LONGTEXT.
-- ---------------------------------------------------------------------------
ALTER TABLE `pixilinkvow`.`map_searches`
    MODIFY `data` LONGTEXT NULL;

ALTER TABLE `pixilinkvow`.`saved_searches`
    MODIFY `data` LONGTEXT NULL;

-- Verify after: should return zero rows.
SELECT table_schema, table_name, column_name, data_type
FROM information_schema.columns
WHERE data_type = 'json';

-- ---------------------------------------------------------------------------
-- Then restart the stalled replica (MariaDB side). Inspect first:
--     SHOW SLAVE STATUS\G      -- Last_SQL_Errno / Last_SQL_Error
-- If it stopped on a row event for one of the columns above, the event must be
-- skipped once the schema is fixed, then replication resumed:
--     STOP SLAVE;
--     SET GLOBAL sql_slave_skip_counter = 1;
--     START SLAVE;
--     SHOW SLAVE STATUS\G      -- expect Slave_SQL_Running: Yes, Last_SQL_Errno: 0
-- Skip only the event you have identified; blind repeated skipping silently
-- drops writes and lets the replica drift out of sync with the source.
-- ---------------------------------------------------------------------------
