#!/usr/bin/env python3
"""Импорт подписей Оксаны Почепы в каталог NotaMiru.

1. Автообрезка тёмных полос сканера по краям.
2. Обработка V2: нормализация белого, усиление чернил x1.2, синий оттенок, нерезкая маска.
3. Оригиналы сохраняются в .originals, записи добавляются в index.json.
"""

import json
import shutil
import sys
import uuid
from pathlib import Path

import numpy as np
from PIL import Image, ImageFilter

BASE = Path.home() / "Library/Application Support/NotaMiru/SigningAssets"
ORIGINALS = BASE / ".originals"
SIGNER = "Оксана Почепа"

BOOST = 1.2
PEN_BLUE = np.array([40.0, 70.0, 165.0])
TINT_MIX = 0.45


def auto_trim(arr: np.ndarray) -> np.ndarray:
    """Срезает края, где много тёмных пикселей (полосы сканера)."""
    lum = arr.mean(axis=2)
    dark = lum < 120
    h, w = dark.shape

    def edge(extent: int, frac_fn) -> int:
        cut = 0
        for i in range(extent):
            if frac_fn(i) > 0.25:
                cut = i + 1
        return cut

    max_v = max(2, h // 12)
    max_h = max(2, w // 12)
    top = edge(max_v, lambda i: dark[i].mean())
    bottom = edge(max_v, lambda i: dark[h - 1 - i].mean())
    left = edge(max_h, lambda i: dark[:, i].mean())
    right = edge(max_h, lambda i: dark[:, w - 1 - i].mean())

    # небольшой отступ после полосы
    pad = 3
    return arr[top + pad if top else 0 : h - (bottom + pad if bottom else 0),
               left + pad if left else 0 : w - (right + pad if right else 0)]


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
    density = 1.0 - arr.mean(axis=2, keepdims=True) / 255.0
    tinted = 255.0 - density * (255.0 - PEN_BLUE)
    mix = TINT_MIX * np.clip(density / 0.08, 0.0, 1.0)
    return np.clip(arr * (1.0 - mix) + tinted * mix, 0, 255)


def main() -> int:
    sources = sorted(Path.home().glob("Documents/Pochepa[123].png"))
    if len(sources) != 3:
        print("Не найдены все три файла Pochepa1-3.png:", sources)
        return 1

    ORIGINALS.mkdir(parents=True, exist_ok=True)
    index_path = BASE / "index.json"
    assets = json.loads(index_path.read_text())

    for i, src in enumerate(sources, start=1):
        file_name = f"{uuid.uuid4()}.png".upper().replace(".PNG", ".png")

        img = Image.open(src).convert("RGB")
        arr = auto_trim(np.asarray(img, dtype=np.float64))

        # обрезанный оригинал — в .originals для будущих перегенераций
        Image.fromarray(arr.astype(np.uint8)).save(ORIGINALS / file_name, "PNG")

        arr = white_norm(arr)
        arr = boost_ink(arr, BOOST)
        arr = blue_tint(arr)
        out = Image.fromarray(arr.astype(np.uint8))
        out = out.filter(ImageFilter.UnsharpMask(radius=1.2, percent=60, threshold=2))
        out.save(BASE / file_name, "PNG")

        assets.append({
            "kind": "signature",
            "signer": SIGNER,
            "fileName": file_name,
            "id": str(uuid.uuid4()).upper(),
            "name": f"Подпись {i}",
        })
        print(f"ok: {src.name} -> {file_name} ({out.size[0]}x{out.size[1]})")

    index_path.write_text(json.dumps(assets, ensure_ascii=False, indent=2))
    print(f"\nДобавлено 3 подписи, подписант: {SIGNER}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
