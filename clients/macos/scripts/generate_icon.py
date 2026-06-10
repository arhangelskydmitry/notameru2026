#!/usr/bin/env python3
"""Generate Nota Miru AppIcon set from the site logo."""

from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
LOGO_PATH = ROOT / "NotaMiru/Assets.xcassets/NotaMiruLogo.imageset/logo.png"
FALLBACK_LOGO = ROOT.parents[1] / "public/images/logo.png"
OUT_DIR = ROOT / "NotaMiru/Assets.xcassets/AppIcon.appiconset"

SIZES = [16, 32, 64, 128, 256, 512, 1024]


def load_logo() -> Image.Image:
    path = LOGO_PATH if LOGO_PATH.exists() else FALLBACK_LOGO
    return Image.open(path).convert("RGBA")


def extract_symbol(logo: Image.Image) -> Image.Image:
    """Crop the red Nota Miru mark from the left side of the full wordmark."""
    w, h = logo.size
    symbol = logo.crop((0, 0, int(w * 0.24), h))

    bbox = symbol.getbbox()
    if bbox:
        symbol = symbol.crop(bbox)

    return symbol


def make_master(size: int = 1024) -> Image.Image:
    symbol = extract_symbol(load_logo())

    canvas = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(canvas)

    # Light macOS-style background, close to the public website.
    for y in range(size):
        t = y / size
        r = int(255 + (238 - 255) * t)
        g = int(255 + (244 - 255) * t)
        b = int(255 + (255 - 255) * t)
        draw.line([(0, y), (size, y)], fill=(r, g, b, 255))

    # Soft blue glow behind the red Nota Miru mark.
    glow = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    glow_draw = ImageDraw.Draw(glow)
    cx, cy = size // 2, size // 2
    glow_radius = int(size * 0.34)
    glow_draw.ellipse(
        (cx - glow_radius, cy - glow_radius, cx + glow_radius, cy + glow_radius),
        fill=(51, 101, 196, 30),
    )
    glow = glow.filter(ImageFilter.GaussianBlur(radius=size * 0.06))
    canvas = Image.alpha_composite(canvas, glow)

    # Scale the red symbol generously so small icon sizes remain readable.
    target_h = int(size * 0.66)
    ratio = target_h / symbol.height
    target_w = int(symbol.width * ratio)
    symbol = symbol.resize((target_w, target_h), Image.Resampling.LANCZOS)

    x = (size - target_w) // 2
    y = (size - target_h) // 2

    shadow = Image.new("RGBA", symbol.size, (0, 0, 0, 0))
    shadow_alpha = symbol.getchannel("A").filter(ImageFilter.GaussianBlur(radius=size * 0.018))
    shadow.putalpha(shadow_alpha)
    canvas.paste(shadow, (x, y + int(size * 0.025)), shadow)
    canvas.paste(symbol, (x, y), symbol)

    return canvas.convert("RGB")


def main() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    master = make_master(1024)

    for s in SIZES:
        img = master if s == 1024 else master.resize((s, s), Image.Resampling.LANCZOS)
        out = OUT_DIR / f"icon_{s}x{s}.png" if s != 1024 else OUT_DIR / "icon_1024.png"
        img.save(out, "PNG", optimize=True)
        print(f"Wrote {out} ({s}x{s})")

    # Legacy alias used by Contents.json for 1024@2x slot
    print("Done.")


if __name__ == "__main__":
    main()
