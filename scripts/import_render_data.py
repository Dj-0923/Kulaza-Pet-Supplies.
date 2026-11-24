#!/usr/bin/env python3
"""Import local SQLite business data into an empty Render PostgreSQL database."""

import getpass
import sqlite3
import sys
from pathlib import Path


TABLES = (
    "categories",
    "brands",
    "products",
    "price_records",
    "transactions",
    "transaction_items",
)


def main() -> int:
    source_path = Path(__file__).resolve().parents[1] / "database" / "database.sqlite"
    if not source_path.is_file():
        print(f"Local SQLite database not found: {source_path}")
        return 1

    try:
        import psycopg
        from psycopg import sql
    except ImportError:
        print("Missing import dependency. Run: python -m pip install -r scripts\\requirements-render-import.txt")
        return 1

    source = sqlite3.connect(source_path)
    try:
        source_tables = {
            row[0]
            for row in source.execute(
                "SELECT name FROM sqlite_master WHERE type = 'table'"
            )
        }
        missing_tables = [table for table in TABLES if table not in source_tables]
        if missing_tables:
            print(f"Local database is missing tables: {', '.join(missing_tables)}")
            return 1

        source_counts = {
            table: source.execute(f'SELECT COUNT(*) FROM "{table}"').fetchone()[0]
            for table in TABLES
        }
        print("Local records to import:")
        for table, count in source_counts.items():
            print(f"  {table}: {count}")
        print("Staff/users, Gmail settings, sessions, and temporary tokens are not imported.")

        if input('Type IMPORT to continue (anything else cancels): ').strip() != "IMPORT":
            print("Import cancelled.")
            return 0

        database_url = getpass.getpass("Render External Database URL (input hidden): ").strip()
        if not database_url:
            print("No database URL provided; import cancelled.")
            return 1

        try:
            with psycopg.connect(database_url, sslmode="require", connect_timeout=15) as target:
                with target.cursor() as cursor:
                    target_counts = {}
                    for table in TABLES:
                        cursor.execute(
                            sql.SQL("SELECT COUNT(*) FROM {}").format(sql.Identifier(table))
                        )
                        target_counts[table] = cursor.fetchone()[0]

                    populated = {
                        table: count for table, count in target_counts.items() if count
                    }
                    if populated:
                        print("Import stopped: Render already has business data; nothing was changed.")
                        for table, count in populated.items():
                            print(f"  {table}: {count}")
                        return 1

                    for table in TABLES:
                        source_columns = [
                            row[1] for row in source.execute(f'PRAGMA table_info("{table}")')
                        ]
                        cursor.execute(
                            """
                            SELECT column_name
                            FROM information_schema.columns
                            WHERE table_schema = current_schema() AND table_name = %s
                            """,
                            (table,),
                        )
                        target_columns = {row[0] for row in cursor.fetchall()}
                        unsupported = [
                            column for column in source_columns if column not in target_columns
                        ]
                        if unsupported:
                            raise RuntimeError(
                                f"Render schema for {table} is missing local columns: "
                                f"{', '.join(unsupported)}"
                            )

                        rows = source.execute(
                            f'SELECT * FROM "{table}" ORDER BY "id"'
                        ).fetchall()
                        if rows:
                            insert = sql.SQL("INSERT INTO {} ({}) VALUES ({})").format(
                                sql.Identifier(table),
                                sql.SQL(", ").join(
                                    sql.Identifier(column) for column in source_columns
                                ),
                                sql.SQL(", ").join(
                                    sql.Placeholder() for _ in source_columns
                                ),
                            )
                            cursor.executemany(insert, rows)

                    for table in TABLES:
                        cursor.execute(
                            sql.SQL(
                                "SELECT setval(pg_get_serial_sequence({}, 'id'), "
                                "COALESCE(MAX(id), 1), COUNT(*) > 0) FROM {}"
                            ).format(sql.Literal(table), sql.Identifier(table))
                        )

                    for table, expected in source_counts.items():
                        cursor.execute(
                            sql.SQL("SELECT COUNT(*) FROM {}").format(sql.Identifier(table))
                        )
                        actual = cursor.fetchone()[0]
                        if actual != expected:
                            raise RuntimeError(
                                f"Verification failed for {table}: expected {expected}, got {actual}"
                            )
        except psycopg.Error as error:
            print(
                f"PostgreSQL import failed ({error.sqlstate or type(error).__name__}). "
                "The database transaction was rolled back; local data was not changed."
            )
            return 1
        except RuntimeError as error:
            print(f"Import failed: {error}. The database transaction was rolled back.")
            return 1

        print("Import completed and verified.")
        return 0
    finally:
        source.close()


if __name__ == "__main__":
    sys.exit(main())
