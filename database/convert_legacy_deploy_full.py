#!/usr/bin/env python3
"""
Legacy-Deploy-Konvertierung **mit Urlaubsdaten** (Termine, Sperren, Kommentare).

Gleich wie convert_legacy_deploy.py, aber urlaub/urlaub_event/urlaub_kommentar/
urlaubssperre/event werden mit importiert. Zeiterfassung bleibt ausgeschlossen.

Usage:
  python3 database/convert_legacy_deploy_full.py --source /pfad/zur/prod.sql
"""

from __future__ import annotations

import argparse
import os
import sys

from convert_legacy_deploy import DB, DEFAULT_SRC, SKIP_HR_TABLES, convert


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Legacy-MySQL-Dump für EasyTime-Deploy (inkl. Urlaub/Termine)."
    )
    parser.add_argument("--source", "-s", default=DEFAULT_SRC, help="Pfad zum MySQL-Dump")
    parser.add_argument("--output", "-o", default=DB, help="Ziel-SQLite-Datei")
    args = parser.parse_args()

    if not os.path.isfile(args.source):
        print(f"Quelldatei nicht gefunden: {args.source}", file=sys.stderr)
        sys.exit(1)

    errors = convert(args.source, args.output, skip_tables=SKIP_HR_TABLES)
    if errors > 0:
        sys.exit(1)


if __name__ == "__main__":
    main()
