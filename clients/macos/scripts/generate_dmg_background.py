#!/usr/bin/env python3
"""DMG installer background: Factory Media logo + arrow to Applications."""

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
LOGO_PATH = ROOT / "NotaMiru/Assets.xcassets/FactoryMediaLogo.imageset/logo.png"
OUT_PATH = ROOT / "build/dmg-assets/background.png"

# Matches Finder window ~520×320 content area (2x for Retina → 1040×640)
W, H = 1040, 640


def load_font(size: int, bold: bool = False):
    candidates = [
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold else "/System/Library/Fonts/Supplemental/Arial.ttf",
        "/System/Library/Fonts/Helvetica.ttc",
        "/Library/Fonts/Arial.ttf",
    ]
    for path in candidates:
        if Path(path).exists():
            try:
                return ImageFont.truetype(path, size)
            except OSError:
                continue
    return ImageFont.load_default()


def main() -> None:
    OUT_PATH.parent.mkdir(parents=True, exist_ok=True)

    img = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    # Gradient background
    for y in range(H):
        t = y / H
        r = int(18 + (10 - 18) * t)
        g = int(18 + (10 - 18) * t)
        b = int(24 + (14 - 24) * t)
        draw.line([(0, y), (W, y)], fill=(r, g, b, 255))

    # Subtle brand glow center-bottom
    glow = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    gd = ImageDraw.Draw(glow)
    gd.ellipse((W // 2 - 280, H // 2 - 40, W // 2 + 280, H // 2 + 200), fill=(245, 79, 71, 28))
    glow = glow.filter(__import__("PIL.ImageFilter", fromlist=["GaussianBlur"]).GaussianBlur(radius=60))
    img = Image.alpha_composite(img, glow)

    # Logo top center
    if LOGO_PATH.exists():
        logo = Image.open(LOGO_PATH).convert("RGBA")
        lw = int(W * 0.42)
        ratio = lw / logo.width
        lh = int(logo.height * ratio)
        logo = logo.resize((lw, lh), Image.Resampling.LANCZOS)
        img.paste(logo, ((W - lw) // 2, 36), logo)

    draw = ImageDraw.Draw(img)

    # Icon zone hints (soft circles under drop targets)
    app_cx, app_cy = 210, 430
    apps_cx, apps_cy = 830, 430
    for cx, cy in ((app_cx, app_cy), (apps_cx, apps_cy)):
        draw.ellipse((cx - 95, cy - 95, cx + 95, cy + 95), outline=(255, 255, 255, 35), width=2)

    # Arrow: app → Applications
    arrow_y = 430
    x1, x2 = 320, 720
    brand = (245, 79, 71, 255)
    draw.line([(x1, arrow_y), (x2, arrow_y)], fill=brand, width=8)
    # Arrowhead
    draw.polygon(
        [(x2, arrow_y), (x2 - 28, arrow_y - 18), (x2 - 28, arrow_y + 18)],
        fill=brand,
    )
    # Motion dots
    for i, dx in enumerate([0, 40, 80, 120]):
        alpha = 180 - i * 35
        draw.ellipse((x1 + 50 + dx - 6, arrow_y - 6, x1 + 50 + dx + 6, arrow_y + 6), fill=(245, 79, 71, alpha))

    # Labels
    font_lg = load_font(28, bold=True)
    font_sm = load_font(22)
    font_hint = load_font(20)

    draw.text((app_cx, 530), "Mac Cleaner", fill=(255, 255, 255, 230), font=font_lg, anchor="mm")
    draw.text((apps_cx, 530), "Программы", fill=(255, 255, 255, 230), font=font_lg, anchor="mm")
    draw.text((W // 2, 580), "Перетащите приложение в папку «Программы»", fill=(255, 255, 255, 140), font=font_hint, anchor="mm")
    draw.text((W // 2, H - 24), "© Factory Media", fill=(255, 255, 255, 90), font=font_sm, anchor="mm")

    img.convert("RGB").save(OUT_PATH, "PNG", optimize=True)
    print(f"Wrote {OUT_PATH} ({W}x{H})")


if __name__ == "__main__":
    main()
