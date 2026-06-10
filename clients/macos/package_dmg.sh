#!/bin/bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
APP_NAME="NotaMiru"
INSTALL_APP_NAME="Nota Miru.app"
RELEASE_APP="$ROOT/build/DerivedDataRelease/Build/Products/Release/${APP_NAME}.app"
# Версию читаем из pbxproj (Info.plist использует $(MARKETING_VERSION))
VERSION="$(sed -n 's/.*MARKETING_VERSION = \([^;]*\);.*/\1/p' "$ROOT/NotaMiru.xcodeproj/project.pbxproj" | head -1)"
VERSION="${VERSION:-0.0.0}"
DMG_NAME="NotaMiru-${VERSION}.dmg"
STAGING="$ROOT/build/dmg-staging"
TEMP_DMG="$ROOT/build/temp.dmg"
OUTPUT="$ROOT/dist/$DMG_NAME"
VOLUME_NAME="Nota Miru"
MOUNT_POINT=""
TEAM_ID="R4YV9N2RNS"

mkdir -p "$ROOT/dist" "$ROOT/build"

echo "→ Release build…"
if [ "${GENERATE_APP_ICON:-0}" = "1" ]; then
  python3 "$ROOT/scripts/generate_icon.py" || echo "⚠ Не удалось обновить иконку, продолжаем release build"
fi
xcodebuild \
  -project "$ROOT/NotaMiru.xcodeproj" \
  -scheme NotaMiru \
  -configuration Release \
  -derivedDataPath "$ROOT/build/DerivedDataRelease" \
  build

if [ ! -d "$RELEASE_APP" ]; then
  echo "✗ Release app not found: $RELEASE_APP"
  exit 1
fi

cleanup() {
  if [ -n "$MOUNT_POINT" ] && [ -d "$MOUNT_POINT" ]; then
    hdiutil detach "$MOUNT_POINT" -force >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

rm -rf "$STAGING" "$TEMP_DMG"
mkdir -p "$STAGING"
cp -R "$RELEASE_APP" "$STAGING/$INSTALL_APP_NAME"
ln -s /Applications "$STAGING/Applications"

hdiutil create -volname "$VOLUME_NAME" -srcfolder "$STAGING" -ov -format UDZO "$OUTPUT"
echo "✓ DMG: $OUTPUT"

if [ -f "$ROOT/notarize.env" ]; then
  # shellcheck disable=SC1091
  source "$ROOT/notarize.env"
  if [ -n "${NOTARY_PROFILE:-}" ]; then
    echo "→ Notarization…"
    xcrun notarytool submit "$OUTPUT" --keychain-profile "$NOTARY_PROFILE" --wait
    xcrun stapler staple "$OUTPUT"
    echo "✓ Notarized"
  fi
fi
