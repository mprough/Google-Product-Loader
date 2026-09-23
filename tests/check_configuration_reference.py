"""Check that the admin configuration reference covers installed settings."""

from collections import Counter
from pathlib import Path
import re
import sys

root = Path(__file__).resolve().parents[1]
installer = (root / "YOUR_ADMIN/includes/init_includes/init_gpsf_admin.php").read_text()
reference = (root / "docs/configuration-settings.md").read_text()

# The first INSERT defines the fresh-install settings.
start = installer.index("if (!defined('GPSF_VERSION')) {")
end = installer.index("zen_register_admin_page('configGpsf'", start)
installed = set(re.findall(
    r"\('[^']*', '((?:GPSF|RHS_GPSF)_[A-Z0-9_]+)',",
    installer[start:end],
))
# New releases can also add settings through guarded upgrade INSERT ... SELECT.
installed.update(re.findall(
    r"SELECT '[^']*', '((?:GPSF|RHS_GPSF)_[A-Z0-9_]+)',",
    installer[end:],
))

# Count keys in the table's configuration-key column, excluding prose mentions.
documented_rows = re.findall(
    r"^\|[^\n]*?\| `((?:GPSF|RHS_GPSF)_[A-Z0-9_]+)` \|",
    reference,
    re.MULTILINE,
)
documented = set(documented_rows)
duplicates = sorted(key for key, count in Counter(documented_rows).items() if count > 1)
missing = sorted(installed - documented)
extra = sorted(documented - installed)

if not installed or missing or extra or duplicates:
    print(f"Installed: {len(installed)}; documented: {len(documented_rows)}")
    for title, keys in (("Missing", missing), ("Stale", extra), ("Duplicate", duplicates)):
        if keys:
            print(f"{title}: {', '.join(keys)}")
    sys.exit(1)

print(f"Configuration reference covers all {len(installed)} installed settings.")
