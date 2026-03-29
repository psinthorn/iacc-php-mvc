#!/usr/bin/env bash
set -euo pipefail

# Export a snapshot of INFORMATION_SCHEMA.COLUMNS for the iACC schema.
# Usage: ./scripts/export-schema-inventory.sh [schema_name]
# Default schema is "iacc". Requires docker compose mysql service running.

DB_NAME=${1:-iacc}
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
OUTPUT_DIR="$ROOT_DIR/docs/schema"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
OUTPUT_FILE="$OUTPUT_DIR/iacc_schema_snapshot_${TIMESTAMP}.csv"

mkdir -p "$OUTPUT_DIR"

echo "Exporting schema metadata for database '$DB_NAME' to $OUTPUT_FILE"

echo "table_name,column_name,data_type,max_length,is_nullable,column_default,column_key,extra" > "$OUTPUT_FILE"

read -r -d '' QUERY <<SQL
SELECT TABLE_NAME,
       COLUMN_NAME,
       DATA_TYPE,
       IFNULL(CHARACTER_MAXIMUM_LENGTH, ''),
       IS_NULLABLE,
       IFNULL(COLUMN_DEFAULT, ''),
       COLUMN_KEY,
       EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA='${DB_NAME}'
ORDER BY TABLE_NAME, ORDINAL_POSITION;
SQL

# Execute query inside the mysql container and append to the CSV
if docker compose ps mysql --status running >/dev/null 2>&1; then
  docker compose exec mysql mysql -N -B -uroot -proot --default-character-set=utf8mb4 -e "$QUERY" >> "$OUTPUT_FILE"
else
  echo "MySQL container is not running. Start it with 'docker compose up -d mysql' and retry." >&2
  exit 1
fi

cat <<EOM
Schema snapshot complete.
File: $OUTPUT_FILE
Rows: $(($(wc -l < "$OUTPUT_FILE") - 1))
EOM
