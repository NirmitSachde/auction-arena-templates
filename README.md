# Auction Arena · Main Display Templates

15 broadcast-grade layouts for the live auction main screen. Each is a single HTML file with its own CSS, drawn on a fixed 1920x1080 canvas that scales to any monitor.

## How it works

- `shared/data.js`: the one live payload (tournament, sponsor, player, stats, status counts, bid, team). Every template reads only from this.
- `shared/aa.js`: fills `data-bind` / `data-src` fields, renders stats, scales the canvas, pulses values when they change. Call `AA.update({ bid: { amount: "1.50" } })` from the real backend.
- `shared/base.css`: reset + the 1920x1080 stage.
- Demo keys: SPACE or → raises the bid, T switches the bidding team.
- Open `index.html` for the gallery.
- Swap `assets/player.svg` for a transparent-background player cut-out PNG for the best look.

## Concepts

1. **Bento Noir**: Apple-keynote bento grid on near-black; every data point in its own rounded tile, electric lime accent, the bid tile takes a 2x2 hero cell.
2. **Stadium Glass**: blurred floodlight bokeh behind frosted glass panels; player cut-out breaks out of the glass, soft cyan edge light.
3. **Neon Grid**: cyberpunk HUD; perspective grid floor, cyan and magenta glow lines, angular clipped panels, scanline shimmer on the bid.
4. **Carbon Pro**: F1 pit-wall aesthetic; woven carbon fibre texture, racing-red accents, telemetry-style stat bars and a speedometer-style bid readout.
5. **Aurora Mesh**: Pitch.com style animated mesh gradient (violet, coral, teal) with soft glass cards and generous whitespace; calm but premium.
6. **Prime Broadcast**: IPL-style TV package; navy and gold diagonal slashes, stacked lower-third bars, sponsor bug and "LIVE" chip.
7. **Editorial**: magazine cover layout; giant Bebas surname behind the player, monochrome with a single hot-orange accent, numbers as typographic heroes.
8. **Ultimate Card**: player presented as a holographic collectible card (FUT style) centre stage, animated foil sheen, stats on the card face.
9. **Black Gold**: luxury black and gold, thin art-deco line work, serif display type for the name, gold-foil gradient bid.
10. **Data Terminal**: Swiss grid, NFL Next Gen Stats vibe; graphite and signal orange, every stat with a mini bar, monospace figures.
11. **Spotlight**: dark arena with a volumetric spotlight cone on the player, dust particles, bid glowing like a scoreboard.
12. **Ticker Live**: sports-news energy; scrolling ticker rails top and bottom, split-flap style bid digits, breaking-news red.
13. **Arctic Frost**: ice-blue and white glass on deep midnight, crisp thin type, frosted stat tiles, subtle snow-light shimmer.
14. **Velocity**: motion-blur speed streaks, everything skewed on a 12° diagonal, kinetic chevrons pointing at the bid.
15. **Team Takeover**: the bidding team owns the screen; its colours flood the background and accents (CSS variables), giant watermark crest.
