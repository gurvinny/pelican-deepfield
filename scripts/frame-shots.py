#!/usr/bin/env python3
"""Frame Deepfield screenshots with a cosmic-gradient bg + aurora glow + drop shadow.
Input: 2100x900 raw PNG (matches Pelican Hub 21:9 marketplace aspect).
Output: 2400x1030 framed PNG — leaves ~150px cosmic border on each side.
"""
import sys
import random
from pathlib import Path
from PIL import Image, ImageDraw, ImageFilter

W, H = 2400, 1030
MARGIN_X = 120
MARGIN_Y = 60
CORNER_R = 10
STAR_COUNT = 220
SHOT_MAX_W = W - MARGIN_X * 2
SHOT_MAX_H = H - MARGIN_Y * 2

# Deepfield palette
BG_DEEP   = (5, 6, 20)          # #050614
BG_MID    = (18, 12, 42)        # violet-mid
BG_ACCENT = (10, 24, 42)        # cyan-mid
CYAN      = (56, 225, 255)
TEAL      = (94, 234, 212)
VIOLET    = (167, 139, 250)


def cosmic_bg() -> Image.Image:
    """Base canvas: diagonal gradient BG_DEEP -> BG_MID with a violet radial nebula
    on the left and a subtle scatter of stars."""
    bg = Image.new("RGB", (W, H), BG_DEEP)
    px = bg.load()
    for y in range(H):
        t = y / H
        for x in range(W):
            u = x / W
            # Diagonal blend factor
            blend = (u * 0.55 + t * 0.45)
            r = int(BG_DEEP[0] + (BG_MID[0] - BG_DEEP[0]) * blend * 0.8)
            g = int(BG_DEEP[1] + (BG_MID[1] - BG_DEEP[1]) * blend * 0.8)
            b = int(BG_DEEP[2] + (BG_MID[2] - BG_DEEP[2]) * blend * 0.8)
            px[x, y] = (r, g, b)

    # Nebula radial blob on the upper-left
    nebula = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    nd = ImageDraw.Draw(nebula)
    for r in range(400, 0, -10):
        alpha = int(60 * (1 - r / 400) ** 2)
        color = (VIOLET[0], VIOLET[1], VIOLET[2], alpha)
        nd.ellipse((-100 - r // 2, 100 - r // 2, -100 + r // 2, 100 + r // 2), fill=color)
    nebula = nebula.filter(ImageFilter.GaussianBlur(radius=60))

    # Second nebula bottom-right — cyan tint
    neb2 = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    nd2 = ImageDraw.Draw(neb2)
    for r in range(360, 0, -10):
        alpha = int(45 * (1 - r / 360) ** 2)
        color = (CYAN[0], CYAN[1], CYAN[2], alpha)
        nd2.ellipse((W - r // 2, H - r // 2, W + r // 2, H + r // 2), fill=color)
    neb2 = neb2.filter(ImageFilter.GaussianBlur(radius=50))

    bg_rgba = bg.convert("RGBA")
    bg_rgba = Image.alpha_composite(bg_rgba, nebula)
    bg_rgba = Image.alpha_composite(bg_rgba, neb2)

    # Stars — small varying alpha dots
    rng = random.Random(42)
    stars = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    sd = ImageDraw.Draw(stars)
    for _ in range(STAR_COUNT):
        x = rng.randint(0, W - 1)
        y = rng.randint(0, H - 1)
        size = rng.choice([1, 1, 1, 2, 2, 3])
        alpha = rng.randint(60, 230)
        # 20% chance to be tinted cyan/violet
        r_ = rng.random()
        if r_ < 0.10:
            color = (*CYAN, alpha)
        elif r_ < 0.15:
            color = (*VIOLET, alpha)
        else:
            color = (232, 236, 255, alpha)
        sd.ellipse((x, y, x + size, y + size), fill=color)
    bg_rgba = Image.alpha_composite(bg_rgba, stars)

    return bg_rgba


def rounded_mask(size, radius):
    mask = Image.new("L", size, 0)
    d = ImageDraw.Draw(mask)
    d.rounded_rectangle((0, 0, size[0], size[1]), radius=radius, fill=255)
    return mask


def frame(raw_path: Path, out_path: Path):
    shot = Image.open(raw_path).convert("RGBA")
    # Scale to fit
    ratio = min(SHOT_MAX_W / shot.width, SHOT_MAX_H / shot.height)
    new_w = int(shot.width * ratio)
    new_h = int(shot.height * ratio)
    shot = shot.resize((new_w, new_h), Image.LANCZOS)
    # Round corners
    mask = rounded_mask((new_w, new_h), CORNER_R)
    shot.putalpha(mask)

    bg = cosmic_bg()

    # Shadow — dark, offset, blurred
    shadow = Image.new("RGBA", (new_w + 120, new_h + 120), (0, 0, 0, 0))
    sd = ImageDraw.Draw(shadow)
    sd.rounded_rectangle((60, 60, 60 + new_w, 60 + new_h), radius=CORNER_R, fill=(0, 0, 0, 200))
    shadow = shadow.filter(ImageFilter.GaussianBlur(radius=30))

    # Aurora glow — soft cyan halo underneath
    glow = Image.new("RGBA", (new_w + 200, new_h + 200), (0, 0, 0, 0))
    gd = ImageDraw.Draw(glow)
    gd.rounded_rectangle((100, 100, 100 + new_w, 100 + new_h), radius=CORNER_R, fill=(*TEAL, 80))
    glow = glow.filter(ImageFilter.GaussianBlur(radius=60))

    # Position
    px = (W - new_w) // 2
    py = (H - new_h) // 2

    # Layer: bg -> glow -> shadow -> shot
    bg.paste(glow, (px - 100, py - 100 + 8), glow)
    bg.paste(shadow, (px - 60, py - 60 + 20), shadow)
    bg.paste(shot, (px, py), shot)

    # Border/inner highlight
    bd_layer = Image.new("RGBA", (new_w, new_h), (0, 0, 0, 0))
    bdd = ImageDraw.Draw(bd_layer)
    bdd.rounded_rectangle((0, 0, new_w - 1, new_h - 1), radius=CORNER_R, outline=(*VIOLET, 90), width=1)
    bg.paste(bd_layer, (px, py), bd_layer)

    bg.convert("RGB").save(out_path, "PNG", optimize=True)
    print(f"✓ {out_path.name}  ({out_path.stat().st_size // 1024}KB)")


def main():
    src = Path(sys.argv[1])
    dst = Path(sys.argv[2])
    dst.mkdir(parents=True, exist_ok=True)
    for f in sorted(src.glob("*.png")):
        frame(f, dst / f.name)


if __name__ == "__main__":
    main()
