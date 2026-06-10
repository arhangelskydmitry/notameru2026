#!/bin/bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
PROJECT="$ROOT/NotaMiru.xcodeproj"
SCHEME="NotaMiru"
BUILD_DIR="$ROOT/build"

echo "→ Сборка NotaMiru..."
if [ "${GENERATE_APP_ICON:-0}" = "1" ]; then
  python3 "$ROOT/scripts/generate_icon.py" || echo "⚠ Не удалось обновить иконку, продолжаем сборку"
fi
xcodebuild \
  -project "$PROJECT" \
  -scheme "$SCHEME" \
  -configuration Debug \
  -derivedDataPath "$BUILD_DIR/DerivedData" \
  build

APP="$BUILD_DIR/DerivedData/Build/Products/Debug/NotaMiru.app"
if [ -d "$APP" ]; then
  echo "✓ Готово: $APP"
  open "$APP" || echo "⚠ Приложение собрано, но не открылось автоматически: $APP"
else
  echo "✗ Приложение не найдено после сборки"
  exit 1
fi
