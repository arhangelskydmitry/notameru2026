#!/bin/bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
APP="$ROOT/build/DerivedDataRelease/Build/Products/Release/NotaMiru.app"

echo "=== Проверка подписи Nota Miru ==="
echo ""

echo "→ Сертификаты в Keychain:"
security find-identity -v -p codesigning | grep -E "Developer ID|Apple Development" || echo "  (нет сертификатов)"
echo ""

if [ ! -d "$APP" ]; then
  echo "⚠ Release.app не найден. Сначала: ./package_dmg.sh"
  exit 1
fi

echo "→ Подпись приложения:"
codesign -dv --verbose=4 "$APP" 2>&1 | grep -E "Authority|TeamIdentifier|Identifier|Format" || true
echo ""

if codesign --verify --deep --strict --verbose=2 "$APP" 2>&1; then
  echo "✓ codesign verify OK"
else
  echo "✗ codesign verify FAILED"
  exit 1
fi

if codesign -dv "$APP" 2>&1 | grep -q "Developer ID Application"; then
  echo "✓ Подписано Developer ID — готово к нотаризации"
else
  echo "⚠ Подписано НЕ Developer ID (скорее Apple Development)"
  echo ""
  echo "Создайте сертификат:"
  echo "  Xcode → Settings → Accounts → Dmitry Arkhangelsky → Manage Certificates"
  echo "  → + → Developer ID Application"
  echo ""
  echo "Затем пересоберите: ./package_dmg.sh"
  exit 1
fi
