#!/usr/bin/env python3
"""
Konvertiert einen Legacy-MySQL-Dump (ahaappup) für den Produktions-Deploy.

Importiert Stammdaten (Mitarbeiter, Standorte, Führerscheinklassen, …),
überspringt Urlaubs-Historie und HR-Zeiterfassung.

Usage:
  python3 database/convert_legacy_deploy.py --source /pfad/zur/prod.sql
  python3 database/convert_legacy_deploy.py --source prod.sql --output database/database.sqlite
"""

from __future__ import annotations

import argparse
import os
import sqlite3
import sys

# Gemeinsame Logik mit convert_import.py
from convert_import import DB, SCHEMA, process_dump

DEFAULT_SRC = os.path.join(os.path.dirname(__file__), "..", "ahaappup (1).sql")

# Zeiterfassung / Monatsberichte (in beiden Deploy-Profilen ausgeschlossen)
SKIP_HR_TABLES = frozenset({
    "taetigkeit",
    "zuschlag",
    "uebertrag",
    "monatsbericht_view",
})

# Nur im Stammdaten-Profil zusätzlich ausgeschlossen
SKIP_VACATION_TABLES = frozenset({
    "urlaub",
    "urlaub_event",
    "urlaub_kommentar",
    "urlaubssperre",
    "event",
})

SKIP_INSERT_TABLES = SKIP_HR_TABLES | SKIP_VACATION_TABLES


def convert(source: str, output: str, skip_tables: frozenset[str] | None = None) -> int:
    skip = skip_tables if skip_tables is not None else SKIP_INSERT_TABLES
    print(f"Lese MySQL-Dump: {source}")
    inserts = process_dump(source)
    print(f"  {len(inserts)} INSERT-Blöcke gefunden.")

    skipped = 0
    to_import = []
    for new_table, new_cols, values_sql in inserts:
        if new_table in skip:
            skipped += 1
            continue
        to_import.append((new_table, new_cols, values_sql))

    skip_label = "Urlaub/Zeiterfassung" if skip & SKIP_VACATION_TABLES else "Zeiterfassung"
    print(f"  {skipped} Blöcke übersprungen ({skip_label}).")
    print(f"  {len(to_import)} Blöcke werden importiert.")

    print(f"Erstelle SQLite-Datenbank: {output}")
    if os.path.exists(output):
        os.remove(output)

    con = sqlite3.connect(output)
    cur = con.cursor()
    cur.executescript(SCHEMA)
    con.commit()

    print("Importiere Daten …")
    errors = 0
    for new_table, new_cols, values_sql in to_import:
        col_str = ", ".join(new_cols)
        vals = values_sql.rstrip(";").strip()
        sql = f"INSERT OR IGNORE INTO {new_table} ({col_str}) VALUES {vals};"
        try:
            cur.executescript(sql)
        except Exception as e:
            print(f"  FEHLER in {new_table}: {e}")
            print(f"    SQL (Anfang): {sql[:200]}")
            errors += 1

    con.commit()
    con.close()

    print(f"Fertig. Fehler: {errors}")

    con2 = sqlite3.connect(output)
    cur2 = con2.cursor()
    tables = [
        r[0]
        for r in cur2.execute(
            "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
        )
    ]
    print("\nZeilenzahlen:")
    for t in tables:
        n = cur2.execute(f"SELECT COUNT(*) FROM {t}").fetchone()[0]
        marker = " (leer, absichtlich)" if t in skip and n == 0 else ""
        print(f"  {t:<30} {n:>8}{marker}")
    con2.close()

    return errors


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Legacy-MySQL-Dump für EasyTime-Deploy konvertieren (ohne Urlaubsdaten)."
    )
    parser.add_argument(
        "--source",
        "-s",
        default=DEFAULT_SRC,
        help=f"Pfad zum MySQL-Dump (Standard: {DEFAULT_SRC})",
    )
    parser.add_argument(
        "--output",
        "-o",
        default=DB,
        help=f"Ziel-SQLite-Datei (Standard: {DB})",
    )
    args = parser.parse_args()

    if not os.path.isfile(args.source):
        print(f"Quelldatei nicht gefunden: {args.source}", file=sys.stderr)
        sys.exit(1)

    errors = convert(args.source, args.output)
    if errors > 0:
        sys.exit(1)


if __name__ == "__main__":
    main()
