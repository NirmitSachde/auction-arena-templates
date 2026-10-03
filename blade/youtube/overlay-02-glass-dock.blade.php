{{-- Auction Arena · Overlay 02 Glass Dock
     Generated from overlays/02-glass-dock.html by tools/build-blade.mjs. Do not edit by hand:
     change the template or tools/blade/*, then run: node tools/build-blade.mjs --}}
@extends('frontend.layout.blank-layout')

@php
    /*
     | Auction Arena overlay data. Every field the overlay shows comes from $aa,
     | so this block is the only place that knows about the models.
     | Same sources as the existing YouTube overlay view: $tournament, $player,
     | $basePrice and $tournament->latestActiveBiddingStatus.
     | Fields marked CHECK are guesses at column names; fix them here.
     */
    $aaInr = function ($n) {
        if (!is_numeric($n)) return $n;
        $n = (string) (int) round($n);
        $last3 = substr($n, -3);
        $rest = substr($n, 0, -3);
        return ($rest === '' ? '' : preg_replace('/\B(?=(\d{2})+$)/', ',', $rest) . ',') . $last3;
    };
    $aaBid = $tournament->latestActiveBiddingStatus ?? null;
    $aaFirst = isset($player) ? (string) $player->first_name : 'WAITING...';
    $aaLast = isset($player) ? (string) $player->last_name : '';
    $aa = [
        'tournament.name' => $tournament->name ?? 'TOURNAMENT NAME',
        'tournament.logo' => $tournament->tournament_image ?? asset('assets/img/logo/favicon.png'), // CHECK column name
        'brand.logo' => asset('assets/img/logo/logo-white.png'),
        'player.name' => isset($player) ? \Illuminate\Support\Str::limit(trim($aaFirst . ' ' . $aaLast), 22) : 'WAITING...',
        'player.first' => $aaFirst,
        'player.last' => $aaLast,
        'player.role' => isset($player) ? ($player->player_speciality ?? '-') : '-',
        'player.photo' => isset($player) ? $player->player_image : asset('assets/users/imgs/unknown-player.png'),
        'player.base' => isset($basePrice) ? $aaInr($basePrice) : '-',
        'bid.points' => $aaBid ? $aaInr($aaBid->current_price) : '-',
        'team.name' => $aaBid?->team?->name ?? 'NO BIDS',
        'team.logo' => $aaBid?->team?->team_image ?? asset('assets/users/imgs/default-team.png'),
        'team.color' => $aaBid?->team?->color ?? '#e11d48', // CHECK: team colour column, if there is one
    ];
@endphp

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Shared base for every Auction Arena template: fixed 1920x1080 broadcast
           canvas, centred and scaled by shared/aa.js. Templates own everything else. */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { width: 100%; height: 100%; overflow: hidden; background: #000; }
        body { -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }
        img { display: block; max-width: 100%; }

        .aa-stage {
          position: absolute;
          left: 50%;
          top: 50%;
          width: 1920px;
          height: 1080px;
          overflow: hidden;
          transform-origin: center center;
          transform: translate(-50%, -50%) scale(0.5);
        }

        /* Value-change pulse, triggered by aa.js. Templates may override. */
        .aa-bump { animation: aa-bump 0.6s cubic-bezier(.2, .9, .3, 1.4); }
        @keyframes aa-bump {
          0% { transform: scale(1); filter: brightness(1); }
          35% { transform: scale(1.12); filter: brightness(1.6); }
          100% { transform: scale(1); filter: brightness(1); }
        }
        /* Inline values that pulse need an inline-block box to scale; divs stay block. */
        :where(span[data-bind]) { display: inline-block; }

        @media (prefers-reduced-motion: reduce) {
          *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; }
        }

        /* Shared layer for YouTube / OBS live overlays (templates in overlays/).
           The page is transparent so the browser source sits on top of the camera.
           Open with ?preview (or press B) to drop a sample camera frame behind it. */
        html, body { background: transparent !important; }

        /* SOLD / UNSOLD marquee strip across the top of the frame. */
        .ov-strip {
          position: absolute; left: 0; right: 0; top: 0; height: 58px; z-index: 50;
          display: flex; align-items: center; overflow: hidden; pointer-events: none;
          transform: translateY(-110%); transition: transform .45s cubic-bezier(.2, .8, .2, 1);
          box-shadow: 0 10px 30px rgba(0, 0, 0, .45);
        }
        html[data-state="sold"] .ov-strip { transform: none; background: var(--ov-sold, linear-gradient(90deg, #14532d, #22c55e 50%, #14532d)); }
        html[data-state="unsold"] .ov-strip { transform: none; background: var(--ov-unsold, linear-gradient(90deg, #450a0a, #b91c1c 50%, #450a0a)); }
        .ov-strip .track { display: flex; flex: none; white-space: nowrap; animation: ov-marquee 28s linear infinite; }
        .ov-strip .track span { padding: 0 90px; font: 900 26px/1 var(--ov-strip-font, "Montserrat", sans-serif); letter-spacing: 12px; color: #fff; text-shadow: 0 2px 6px rgba(0, 0, 0, .5); }
        html[data-state="sold"] .ov-strip .track span::after { content: "SOLD"; }
        html[data-state="unsold"] .ov-strip .track span::after { content: "UNSOLD"; }
        @keyframes ov-marquee { to { transform: translateX(-50%); } }

        /* Stamp that slams onto the player when the hammer falls. Templates place it. */
        .ov-stamp {
          position: absolute; z-index: 40; pointer-events: none; opacity: 0;
          padding: 6px 18px; border: 5px double currentColor; border-radius: 10px;
          font: 900 34px/1 var(--ov-stamp-font, "Montserrat", sans-serif); letter-spacing: 4px;
          background: rgba(0, 0, 0, .55); box-shadow: 0 10px 30px rgba(0, 0, 0, .5);
          transform: rotate(-14deg);
        }
        .ov-stamp::after { content: "SOLD"; }
        html[data-state="sold"] .ov-stamp { color: #4ade80; opacity: 1; animation: ov-stamp .55s cubic-bezier(.17, .89, .32, 1.28) both; }
        html[data-state="unsold"] .ov-stamp { color: #f87171; opacity: 1; animation: ov-stamp .55s cubic-bezier(.17, .89, .32, 1.28) both; }
        html[data-state="unsold"] .ov-stamp::after { content: "UNSOLD"; }
        @keyframes ov-stamp { from { opacity: 0; transform: scale(3.2) rotate(-24deg); } }

        /* LIVE dot used by several overlays. */
        .ov-live { display: inline-flex; align-items: center; gap: 10px; }
        .ov-live::before { content: ""; width: 12px; height: 12px; border-radius: 50%; background: currentColor; animation: ov-blink 1.2s ease-in-out infinite; }
        @keyframes ov-blink { 50% { opacity: .25; } }

        /* Overlay 02 Glass Dock: one floating frosted pill, keynote-calm. Lime accent on the live bid, everything else monochrome. */
        :root { --acc: #c6ff3d; --ink: #fff; --mute: rgba(255,255,255,.62); --glass: rgba(14,16,24,.62); --line: rgba(255,255,255,.16); }
        .aa-stage { font-family: Inter, system-ui, sans-serif; color: var(--ink); --ov-strip-font: Inter, sans-serif; --ov-stamp-font: Inter, sans-serif; }
        .glass { background: var(--glass); border: 1px solid var(--line); backdrop-filter: blur(22px) saturate(1.4); -webkit-backdrop-filter: blur(22px) saturate(1.4);
          box-shadow: 0 24px 60px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.14); }

        .top { position: absolute; top: 40px; left: 50%; transform: translateX(-50%); height: 60px; display: flex; align-items: center; gap: 14px; padding: 0 22px 0 8px; border-radius: 999px; white-space: nowrap; }
        .top img { width: 46px; height: 46px; object-fit: contain; }
        .top .t { font-weight: 700; font-size: 20px; letter-spacing: -.2px; }
        .top .sep { width: 1px; height: 24px; background: var(--line); }
        .top .live { color: #ff4d5e; font-weight: 800; font-size: 15px; letter-spacing: 2px; }
        .mark { position: absolute; top: 40px; right: 48px; height: 60px; }

        .dock { position: absolute; left: 50%; bottom: 44px; transform: translateX(-50%); height: 140px; display: flex; align-items: center; padding: 0 14px; border-radius: 999px; animation: dock .7s cubic-bezier(.2,.8,.2,1) both; }
        @keyframes dock { from { opacity: 0; transform: translate(-50%, 40px) scale(.96); } }
        .ph { position: relative; flex: none; width: 112px; height: 112px; border-radius: 50%; overflow: visible; }
        .ph img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; object-position: top center; background: #1b1e29; box-shadow: 0 0 0 3px rgba(255,255,255,.18); }
        .ph .ov-stamp { left: -6px; top: 30px; font-size: 26px; padding: 4px 12px; }
        .who { width: 470px; padding: 0 26px 0 22px; }
        .name { font-weight: 800; font-size: 40px; letter-spacing: -.8px; line-height: 1.05; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .role { margin-top: 6px; font-size: 18px; font-weight: 600; color: var(--mute); text-transform: uppercase; letter-spacing: 2px; }
        .cell { flex: none; padding: 0 30px; border-left: 1px solid var(--line); height: 84px; display: flex; flex-direction: column; justify-content: center; }
        .k { font-size: 14px; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; color: var(--mute); }
        .v { font-weight: 800; font-size: 30px; letter-spacing: -.5px; font-variant-numeric: tabular-nums; margin-top: 4px; }
        .bid { flex: none; margin-left: 10px; height: 112px; padding: 0 40px; border-radius: 999px; display: flex; flex-direction: column; justify-content: center; background: var(--acc); color: #0a0c10; min-width: 400px; }
        .bid .k { color: rgba(10,12,16,.62); }
        .bid .v { font-size: 60px; font-weight: 900; letter-spacing: -2px; line-height: 1; }
        .team { flex: none; display: flex; align-items: center; gap: 14px; padding: 0 26px 0 24px; }
        .team img { width: 76px; height: 76px; border-radius: 50%; object-fit: contain; background: #fff; padding: 6px; }
        .team .n { max-width: 190px; font-weight: 700; font-size: 20px; line-height: 1.15; }
        html[data-state="sold"] .bid { background: #4ade80; }
        html[data-state="unsold"] .bid { background: #f87171; }

        :root { --team: {{ $aa['team.color'] }}; }
    </style>
@endsection

@section('content')
    <div class="aa-stage">
      <div class="ov-strip"><div class="track"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div></div>
      <div class="top glass"><img data-src="tournament.logo" src="{{ $aa['tournament.logo'] }}" alt=""><span class="t" data-bind="tournament.name">{{ $aa['tournament.name'] }}</span><span class="sep"></span><span class="live ov-live">LIVE</span></div>
      <img class="mark" data-src="brand.logo" src="{{ $aa['brand.logo'] }}" alt="Auction Arena">

      <div class="dock glass">
        <div class="ph"><img data-src="player.photo" src="{{ $aa['player.photo'] }}" alt=""><div class="ov-stamp"></div></div>
        <div class="who"><div class="name" data-bind="player.name">{{ $aa['player.name'] }}</div><div class="role" data-bind="player.role">{{ $aa['player.role'] }}</div></div>
        <div class="cell"><span class="k">Base</span><span class="v" data-bind="player.base">{{ $aa['player.base'] }}</span></div>
        <div class="bid"><span class="k">Current bid</span><span class="v" data-bind="bid.points">{{ $aa['bid.points'] }}</span></div>
        <div class="team"><img data-src="team.logo" src="{{ $aa['team.logo'] }}" alt=""><div class="n" data-bind="team.name">{{ $aa['team.name'] }}</div></div>
      </div>
    </div>
@endsection

@section('page-scripts')
    @vite('resources/js/app.js')
    <script>
        /* Scales the fixed-size .aa-stage to fit the window (OBS browser source or phone). */
        (() => {
            const st = document.querySelector(".aa-stage");
            const w = +st.dataset.w || 1920, h = +st.dataset.h || 1080;
            const fit = () => (st.style.transform = `translate(-50%, -50%) scale(${Math.min(innerWidth / w, innerHeight / h)})`);
            addEventListener("resize", fit);
            fit();
        })();
    </script>
    <script>
        /* Live updates over Echo. Same channel and events as the existing YouTube
           overlay; every field is addressed by its data-bind / data-src key, so the
           markup above can change freely. */
        (() => {
            const DEFAULTS = {
                photo: "{{ asset('assets/users/imgs/unknown-player.png') }}",
                team: "{{ asset('assets/users/imgs/default-team.png') }}",
                color: "#e11d48",
            };
            const RESET_AFTER_MS = 8000;
            const root = document.documentElement;
            let resetTimer = null;

            const inr = (n) => (n == null || n === "" || isNaN(n) ? (n ?? "-") : new Intl.NumberFormat("en-IN").format(n));
            const limit = (s, n) => (s.length > n ? s.slice(0, n - 3) + "..." : s);
            function flash(el) { el.classList.remove("aa-bump"); void el.offsetWidth; el.classList.add("aa-bump"); }
            function text(key, value, pulse) {
                document.querySelectorAll(`[data-bind="${key}"]`).forEach((el) => { el.textContent = value; if (pulse) flash(el); });
            }
            function image(key, url) {
                document.querySelectorAll(`[data-src="${key}"]`).forEach((el) => (el.src = url));
                document.querySelectorAll(`[data-bg="${key}"]`).forEach((el) => (el.style.backgroundImage = `url("${url}")`));
            }
            const state = (s) => (root.dataset.state = s);
            const teamColor = (c) => root.style.setProperty("--team", c || DEFAULTS.color);
            function noBids() { text("team.name", "NO BIDS"); image("team.logo", DEFAULTS.team); teamColor(null); }
            function clearReset() { if (resetTimer) { clearTimeout(resetTimer); resetTimer = null; } }

            function finish(result) {
                state(result);
                clearReset();
                resetTimer = setTimeout(() => {
                    state("live");
                    text("player.name", "WAITING..."); text("player.first", "WAITING..."); text("player.last", "");
                    text("player.role", "-"); text("player.base", "-"); text("bid.points", "-");
                    image("player.photo", DEFAULTS.photo);
                    noBids();
                    resetTimer = null;
                }, RESET_AFTER_MS);
            }

            state("live");

            document.addEventListener("DOMContentLoaded", () => {
                if (typeof Echo === "undefined") return;
                Echo.channel(`private.tournaments.{{ $tournament->id }}.auction`)
                    .listen(".player-change-event", (e) => {
                        if (!e.player) return;
                        clearReset();
                        state("live");
                        const first = e.player.first_name || "", last = e.player.last_name || "";
                        text("player.name", limit(`${first} ${last}`.trim(), 22), true);
                        text("player.first", first); text("player.last", last);
                        text("player.role", e.player.speciality || "-");
                        text("player.base", inr(e.player.base_price));
                        text("bid.points", inr(e.current_bid_price), true);
                        image("player.photo", e.player.image_path || DEFAULTS.photo);
                        noBids();
                    })
                    .listen(".bid-placed", (e) => {
                        text("bid.points", inr(e.current_bid), true);
                        text("team.name", e.team?.name || "NO BIDS", true);
                        image("team.logo", e.team_image || DEFAULTS.team);
                        teamColor(e.team?.color);
                    })
                    .listen(".bid-undo", (e) => { text("bid.points", inr(e.current_bid), true); noBids(); })
                    .listen(".player-sold", () => finish("sold"))
                    .listen(".player-unsold", () => finish("unsold"))
                    .listen(".auction-finished", () => setTimeout(() => location.reload(), 5000));
            });
        })();
    </script>
@endsection
