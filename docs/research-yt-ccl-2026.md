# Reference study: CCL 2026 Dombivli live auction

Source: the YouTube live replay "Live Player Auction CCL 2026, Dombivali" (~3h35m), studied frame by frame with Gemini (Antigravity). The original screenshots (`idle`, `takeover`, `new_player`, `bidding`, `sold`) stay outside this repo.

## What they show

| State | What is on screen |
| --- | --- |
| Idle | Full-screen title slate. |
| Player takeover | Full-screen player profile, with Sold / Unsold / Available counts. |
| New player | Camera plus a lower third of three separate boxes (player, bid, team), reading "NO BIDS YET". |
| Bidding | Same lower third with the current bid and the bidding team's logo and name. |
| Sold | Full-screen red celebration with confetti, the winning team, the final price and a "SOLD!" stamp. |

The graphics are a web dashboard shown on the venue LED wall and captured into OBS, rather than a native overlay. Colours: blues `#1877F2` and `#0B192C`, celebration red `#8B0000`.

## What doesn't work

- Number formats change mid-player: `32,000` while bidding, `32K` once sold.
- The three boxes of the lower third don't read as one unit.
- Browser UI buttons are visible at the bottom edge.
- The full-screen takeovers hide the camera, and with it the room's reaction.

## What we took

1. **One joined lower third** instead of three boxes: every overlay in `overlays/`.
2. **Sold / Unsold / Available counts** on every overlay (`status.*`). On sold or unsold they adjust live.
3. **Bid pulse**: every new bid flashes the whole bid block (`[data-pulse]`), not just the digits.
4. **Indian comma formatting everywhere** (`1,00,00,000`), the same before and after the sale.
5. **SOLD celebration as an overlay**: a centred card with confetti, photo, team and price, themed per overlay. The camera stays visible around it. "NO BIDS YET" wording matches theirs.
