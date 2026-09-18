from __future__ import annotations

import argparse
import math
import re
import xml.etree.ElementTree as ET
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "assets" / "readme"
PROJECT = "Torrent Downloader"
SOURCE = "No authoritative product roadmap/checklist exists in the repository."
CARD = (OUT / "progress-card.svg").read_text(encoding="utf-8") if (OUT / "progress-card.svg").exists() else ""
MINI = (OUT / "progress-mini.svg").read_text(encoding="utf-8") if (OUT / "progress-mini.svg").exists() else ""
LEGACY_METER_PATTERNS = (re.compile(r"[█▓▒░]{4,}"), re.compile(r"\[[#=\-]{6,}\]"))

def _validate(svg: str) -> None:
    root = ET.fromstring(svg)
    if root.tag.rsplit("}", 1)[-1] != "svg" or not root.attrib.get("viewBox"):
        raise SystemExit("invalid SVG root/viewBox")
    for node in root.iter():
        for key in ("x", "y", "width", "height", "rx", "ry"):
            value = node.attrib.get(key)
            if value is None:
                continue
            try:
                number = float(value)
            except ValueError:
                continue
            if not math.isfinite(number) or number < 0:
                raise SystemExit(f"invalid geometry: {key}={value}")

def main() -> int:
    parser = argparse.ArgumentParser(description=f"Check {PROJECT} README progress SVGs. Source: {SOURCE}")
    parser.add_argument("--check", action="store_true")
    args = parser.parse_args()
    if not args.check:
        raise SystemExit("This N/A progress set is intentionally static; use --check to validate it.")
    for name in ("progress-card.svg", "progress-mini.svg", "progress-template.svg", "app-icon.svg", "hero.svg"):
        path = OUT / name
        if not path.exists():
            raise SystemExit(f"missing {path.relative_to(ROOT)}")
        _validate(path.read_text(encoding="utf-8"))
    readme = (ROOT / "README.md").read_text(encoding="utf-8")
    for required in ("<!-- SWIR-README-STANDARD:v2 -->", "assets/readme/progress-card.svg", "assets/readme/progress-mini.svg", "Product progress | **N/A**", "## 🔎 Search Keywords"):
        if required not in readme:
            raise SystemExit(f"README missing required content: {required}")
    if any(pattern.search(readme) for pattern in LEGACY_METER_PATTERNS):
        raise SystemExit("legacy text progress meter detected")
    print(f"OK: {PROJECT}; product progress N/A; SVG-only presentation")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
