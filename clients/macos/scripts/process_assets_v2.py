#!/usr/bin/env python3
"""Финальная обработка каталога подписей и печатей NotaMiru (вариант V2).

Из оригинальных сканов (.originals):
  1. Нормализация белой точки -> чисто-белый фон (для multiply).
  2. Лёгкое усиление плотности чернил x1.2.
  3. Для подписей — лёгкий синий оттенок ручки (бленд 45% к синему).
  4. Мягкая нерезкая маска для чёткости.
"""

import json
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageFilter

BASE = Path.home() / "Library/Application Support/NotaMiru/SigningAssets"
ORIGINALS = BASE / ".originals"

BOOST = 1.2
PEN_BLUE = np.array([40.0, 70.0, 165.0])  # лёгкий синий ручки
TINT_MIX = 0.45                            # доля синего в бленде для подписей


def white_norm(arr: np.ndarray) -> np.ndarray:
    lum = arr.mean(axis=2)
    mask = lum > 165
    out = arr.astype(np.float64)
    if mask.sum() >= 50:
        for c in range(3):
            median = np.median(arr[:, :, c][mask])
            out[:, :, c] *= 255.0 / max(1.0, median * 0.97)
    return np.clip(out, 0, 255)


def boost_ink(arr: np.ndarray, k: float) -> np.ndarray:
    v = arr / 255.0
    d = np.clip(k * (1.0 - v), 0.0, 1.0)
    return np.clip((1.0 - d) * 255.0, 0, 255)


def blue_tint(arr: np.ndarray) -> np.ndarray:
    """Лёгкий синий оттенок: чернила смещаются к цвету ручки, фон не трогаем."""
    density = 1.0 - arr.mean(axis=2, keepdims=True) / 255.0  # 0=фон, 1=чернила
    tinted = 255.0 - density * (255.0 - PEN_BLUE)
    mix = TINT_MIX * np.clip(density / 0.08, 0.0, 1.0)       # фон остаётся белым
    return np.clip(arr * (1.0 - mix) + tinted * mix, 0, 255)


def process(src: Path, dst: Path, kind: str) -> None:
    img = Image.open(src).convert("RGB")
    arr = white_norm(np.asarray(img, dtype=np.float64))
    arr = boost_ink(arr, BOOST)
    if kind == "signature":
        arr = blue_tint(arr)
    out = Image.fromarray(arr.astype(np.uint8))
    out = out.filter(ImageFilter.UnsharpMask(radius=1.2, percent=60, threshold=2))
    out.save(dst, "PNG")


def main() -> int:
    assets = json.loads((BASE / "index.json").read_text())
    done = 0
    for asset in assets:
        if asset["kind"] not in ("signature", "stamp"):
            continue
        src = ORIGINALS / asset["fileName"]
        if not src.exists():
            print("пропуск (нет оригинала):", asset["name"])
            continue
        process(src, BASE / asset["fileName"], asset["kind"])
        done += 1
        print(f"ok [{asset['kind']}] {asset['name']}")
    print(f"\nОбработано: {done}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
