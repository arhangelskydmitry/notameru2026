#!/usr/bin/env python3
"""Усиление чернил в каталоге подписей и печатей NotaMiru.

Делает штрихи плотными и резкими, как настоящая ручка/печать,
сохраняя чисто-белый фон (важно для multiply-наложения):
  1. Нормализация белой точки (фон -> 255).
  2. Усиление плотности чернил: d' = min(1, k * d^gamma), v = 255*(1-d')
     по каждому каналу — белый остаётся белым, штрихи темнеют.
  3. Лёгкий unsharp mask для чёткости краёв.
Подписи усиливаются сильнее (ручка почти непрозрачна),
печати — умеренно (штемпельная краска полупрозрачна).
"""

import json
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageFilter

BASE = Path.home() / "Library/Application Support/NotaMiru/SigningAssets"
BACKUP = BASE / ".pre_enhance"

# kind -> (boost k, gamma, unsharp percent)
PARAMS = {
    "signature": (2.1, 0.85, 120),
    "stamp": (1.55, 0.90, 90),
}


def white_normalize(arr: np.ndarray) -> np.ndarray:
    """Белая точка -> 255 по каждому каналу (по медиане светлых пикселей)."""
    lum = arr.mean(axis=2)
    mask = lum > 165
    if mask.sum() < 50:
        return arr
    out = arr.astype(np.float64)
    for c in range(3):
        median = np.median(arr[:, :, c][mask])
        out[:, :, c] *= 255.0 / max(1.0, median * 0.97)
    return np.clip(out, 0, 255)


def boost_ink(arr: np.ndarray, k: float, gamma: float) -> np.ndarray:
    """Усиление плотности чернил с сохранением белого фона и оттенка."""
    v = arr.astype(np.float64) / 255.0
    density = 1.0 - v
    density = np.clip(k * np.power(density, gamma), 0.0, 1.0)
    return np.clip((1.0 - density) * 255.0, 0, 255)


def process(path: Path, kind: str) -> None:
    k, gamma, unsharp = PARAMS[kind]
    img = Image.open(path).convert("RGB")
    arr = np.asarray(img, dtype=np.float64)

    arr = white_normalize(arr)
    arr = boost_ink(arr, k, gamma)

    out = Image.fromarray(arr.astype(np.uint8), "RGB")
    out = out.filter(ImageFilter.UnsharpMask(radius=1.4, percent=unsharp, threshold=2))
    out.save(path, "PNG")


def main() -> int:
    index_path = BASE / "index.json"
    if not index_path.exists():
        print("index.json не найден:", index_path)
        return 1

    BACKUP.mkdir(exist_ok=True)
    assets = json.loads(index_path.read_text())
    done = 0
    for asset in assets:
        if asset["kind"] not in PARAMS:
            continue
        src = BASE / asset["fileName"]
        if not src.exists():
            print("пропуск (нет файла):", asset["name"])
            continue
        backup = BACKUP / asset["fileName"]
        if not backup.exists():
            backup.write_bytes(src.read_bytes())
        process(src, asset["kind"])
        done += 1
        print(f"ok [{asset['kind']}] {asset['name']}")

    print(f"\nОбработано: {done}. Бэкап: {BACKUP}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
