# Nota Miru — macOS-клиент

Нативное приложение (SwiftUI, macOS 13+) для редакции **Нота Миру**: статьи, SEO, выжимка, пресс-карты, доступы.

Сервер: Laravel в корне репозитория → `docs/MAC_APP_API.md`
API: `https://notame.ru/api/mac/v1`

## Сборка

```bash
cd clients/macos
./build.sh
```

Или в Xcode: `open NotaMiru.xcodeproj` → Run (⌘R).

- Bundle ID: `ru.factory-media.NotaMiru`
- Team ID: `R4YV9N2RNS`
- v0.2.1 (build 3)

## Обновление после `git pull`

```bash
cd clients/macos
git pull origin main
./build.sh
```

Release и DMG — см. раздел ниже. Версия в UI берётся из `NotaMiru/Models/AppInfo.swift` (должна совпадать с Xcode).

## Структура

```
clients/macos/
├── NotaMiru.xcodeproj
├── NotaMiru/
│   ├── NotaMiruApp.swift
│   ├── Services/       # APIClient, Keychain
│   ├── ViewModels/     # AppState
│   └── Views/          # Login, Articles, Editor, …
├── build.sh
├── package_dmg.sh
└── docs/SPEC.md
```

## DMG (Release)

```bash
cp notarize.env.example notarize.env   # один раз
./package_dmg.sh
```

## Связь с Laravel

Mac-клиент **не** обращается к MySQL напрямую — только JSON к `/api/mac/v1`.
Деплой API описан в `docs/MAC_APP_API.md` (корень репозитория).
