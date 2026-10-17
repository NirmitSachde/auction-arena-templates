{{-- Auction Arena · Overlay 05 Score Strip
     Generated from overlays/05-score-strip.html by tools/build-blade.mjs. Do not edit by hand:
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
        'team.name' => $aaBid?->team?->name ?? 'NO BIDS YET',
        'team.logo' => $aaBid?->team?->team_image ?? asset('assets/users/imgs/default-team.png'),
        'team.color' => $aaBid?->team?->color ?? '#e11d48', // CHECK: team colour column, if there is one
        'status.available' => $availableCount ?? '-', // CHECK: players still in the pool
        'status.sold' => $soldCount ?? '-', // CHECK
        'status.unsold' => $unsoldCount ?? '-', // CHECK
    ];
@endphp

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Saira+Condensed:wght@600;700;800&family=Saira:wght@500;600;700&display=swap" rel="stylesheet">
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

        /* SOLD celebration: a centred card with confetti for the whole sold state.
           Keeps the camera visible around it instead of a full-screen takeover.
           Templates theme it with --ov-cel-bg / -accent / -ink / -font. */
        .ov-celebrate { position: absolute; inset: 0; z-index: 45; pointer-events: none; display: none; overflow: hidden; }
        html[data-state="sold"] .ov-celebrate { display: block; }
        .ov-cel-card {
          position: absolute; left: 50%; top: 47%; width: 660px; padding: 0 40px 34px; text-align: center; color: #fff;
          background: var(--ov-cel-bg, linear-gradient(160deg, #7f1d1d, #b91c1c 55%, #450a0a)); border-radius: 26px;
          box-shadow: 0 40px 90px rgba(0, 0, 0, .55), inset 0 0 0 3px var(--ov-cel-accent, #fcd34d);
          font-family: var(--ov-cel-font, "Montserrat", sans-serif);
          transform: translate(-50%, -50%); animation: ov-cel-in .6s cubic-bezier(.17, .89, .32, 1.28) both;
        }
        @keyframes ov-cel-in { from { opacity: 0; transform: translate(-50%, -50%) scale(.6); } }
        .ov-cel-ph { width: 190px; height: 190px; margin: -95px auto 16px; border-radius: 50%; overflow: hidden; background: #111;
          border: 6px solid var(--ov-cel-accent, #fcd34d); box-shadow: 0 14px 30px rgba(0, 0, 0, .5); }
        .ov-cel-ph img { width: 100%; height: 100%; object-fit: cover; object-position: top center; }
        .ov-cel-tag { display: inline-block; padding: 7px 24px 7px 32px; border-radius: 999px; background: var(--ov-cel-accent, #fcd34d); color: var(--ov-cel-ink, #111);
          font-weight: 900; font-size: 24px; letter-spacing: 8px; }
        .ov-cel-nm { margin-top: 14px; font-weight: 900; font-size: 50px; line-height: 1.1; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ov-cel-to { display: flex; align-items: center; justify-content: center; gap: 14px; margin-top: 10px; font-weight: 800; font-size: 24px; letter-spacing: 2px; text-transform: uppercase; }
        .ov-cel-to img { width: 62px; height: 62px; border-radius: 50%; background: #fff; padding: 5px; object-fit: contain; }
        .ov-cel-price { margin-top: 18px; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, .2); }
        .ov-cel-price small { display: block; font-size: 16px; font-weight: 800; letter-spacing: 6px; opacity: .75; }
        .ov-cel-price div { margin-top: 6px; font-weight: 900; font-size: 80px; line-height: 1; font-variant-numeric: tabular-nums; color: var(--ov-cel-accent, #fcd34d); }
        .ov-confetti i { position: absolute; top: -40px; left: var(--x); width: 14px; height: 24px; border-radius: 3px; background: var(--c);
          animation: ov-fall var(--t) linear var(--d) infinite; }
        @keyframes ov-fall { to { transform: translate(var(--dx), 1180px) rotate(900deg); } }

        /* Supply context: Available / Sold / Unsold counts. Templates restyle as needed. */
        .ov-stats { display: flex; gap: 8px; }
        .ov-stats > span { display: flex; align-items: center; gap: 8px; height: 34px; padding: 0 14px; border-radius: 6px; background: rgba(0, 0, 0, .6);
          font: 700 13px/1 var(--ov-stats-font, "Montserrat", sans-serif); letter-spacing: 2px; text-transform: uppercase; color: rgba(255, 255, 255, .78); white-space: nowrap; }
        .ov-stats b { font-size: 20px; letter-spacing: 0; color: #fff; }
        .ov-stats .s b { color: #4ade80; }
        .ov-stats .u b { color: #f87171; }

        /* Every new bid flashes the whole bid block ([data-pulse]), not just the digits. */
        .aa-pulse { animation: ov-pulse .7s ease-out; }
        @keyframes ov-pulse { 0% { filter: brightness(1.8) saturate(1.3); } 100% { filter: none; } }

        /* Overlay 05 Score Strip: cricket TV scorebug. One slim segmented strip plus a ticker; the least camera coverage of the set. */
        :root { --deep: #0b1d4d; --mid: #14306f; --hot: #ff2e63; --ink: #fff; --mute: #a9b8e0; }
        .aa-stage { font-family: Saira, sans-serif; color: var(--ink); --ov-strip-font: "Saira Condensed", sans-serif; --ov-stamp-font: "Saira Condensed", sans-serif; }
        .wrap { position: absolute; left: 60px; right: 60px; bottom: 40px; animation: up .6s cubic-bezier(.2,.8,.2,1) both; }
        @keyframes up { from { transform: translateY(160px); } }
        .strip { display: flex; align-items: stretch; height: 92px; background: var(--deep); border-radius: 10px 10px 0 0; overflow: hidden; box-shadow: 0 18px 40px rgba(0,0,0,.5); }
        .strip > * { display: flex; align-items: center; }
        .tour { padding: 0 18px; background: #fff; }
        .tour img { width: 68px; height: 68px; object-fit: contain; }
        .ph { position: relative; width: 92px; flex: none; background: var(--mid); }
        .ph img { width: 100%; height: 100%; object-fit: cover; object-position: top center; }
        .who { flex: 1; min-width: 0; padding: 0 24px; flex-direction: column; align-items: flex-start; justify-content: center; }
        .name { font-family: "Saira Condensed", sans-serif; font-weight: 800; font-size: 46px; line-height: 1; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
        .role { font-size: 15px; font-weight: 700; letter-spacing: 3px; color: var(--mute); text-transform: uppercase; margin-top: 4px; }
        .seg { flex: none; padding: 0 26px; flex-direction: column; align-items: flex-start; justify-content: center; background: var(--mid); border-left: 1px solid rgba(255,255,255,.08); }
        .k { font-size: 13px; font-weight: 700; letter-spacing: 3px; color: var(--mute); text-transform: uppercase; }
        .v { font-family: "Saira Condensed", sans-serif; font-weight: 700; font-size: 34px; line-height: 1; font-variant-numeric: tabular-nums; }
        .bid { flex: none; min-width: 360px; padding: 0 30px; flex-direction: column; align-items: flex-start; justify-content: center; background: var(--hot); }
        .bid .k { color: rgba(255,255,255,.85); }
        .bid .v { font-size: 56px; }
        .team { flex: none; padding: 0 22px 0 18px; gap: 12px; background: #fff; color: var(--deep); }
        .team img { width: 64px; height: 64px; object-fit: contain; }
        .team .n { max-width: 170px; font-family: "Saira Condensed", sans-serif; font-weight: 800; font-size: 24px; line-height: 1; text-transform: uppercase; }
        .ticker { height: 40px; display: flex; align-items: center; background: #fff; color: var(--deep); border-radius: 0 0 10px 10px; overflow: hidden; }
        .ticker .lab { flex: none; height: 100%; display: flex; align-items: center; padding: 0 18px; background: var(--hot); color: #fff; font-weight: 800; font-size: 15px; letter-spacing: 3px; }
        .ticker .vp { flex: 1; min-width: 0; overflow: hidden; }
        .ticker .run { display: flex; white-space: nowrap; animation: ov-marquee 40s linear infinite; }
        .ticker .run span { padding: 0 34px; font-weight: 700; font-size: 17px; letter-spacing: 1px; text-transform: uppercase; }
        .ticker .run span::after { content: "\25C6"; margin-left: 68px; color: var(--hot); }
        .ph .ov-stamp { left: -30px; top: -70px; font-size: 30px; }
        html[data-state="sold"] .bid { background: #16a34a; }
        html[data-state="unsold"] .bid { background: #6b7280; }
        .corner { position: absolute; top: 40px; right: 48px; display: flex; align-items: center; gap: 14px; }
        .corner .live { height: 40px; padding: 0 16px; background: var(--hot); font-weight: 800; font-size: 16px; letter-spacing: 3px; border-radius: 4px; }
        .corner img { height: 64px; }
        .aa-stage { --ov-cel-bg: linear-gradient(160deg, #14306f, #0b1d4d 70%); --ov-cel-accent: #ff2e63; --ov-cel-ink: #ffffff; --ov-cel-font: "Saira Condensed", sans-serif; --ov-stats-font: Saira, sans-serif; }
        .corner .ov-stats > span { background: rgba(11,29,77,.92); }

        :root { --team: {{ $aa['team.color'] }}; }
    </style>
@endsection

@section('content')
    <div class="aa-stage">
      <div class="ov-strip"><div class="track"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div></div>
      <div class="ov-celebrate"><div class="ov-confetti"><i style="--x:7%;--d:-1.48s;--t:3.76s;--dx:-121px;--c:#ff2e63"></i><i style="--x:78%;--d:-0.35s;--t:3.06s;--dx:-5px;--c:#ffffff"></i><i style="--x:81%;--d:-1.36s;--t:2.62s;--dx:-129px;--c:#3b82f6"></i><i style="--x:64%;--d:-0.65s;--t:3.81s;--dx:-49px;--c:#fbbf24"></i><i style="--x:88%;--d:-1.18s;--t:3.88s;--dx:-14px;--c:#ff2e63"></i><i style="--x:61%;--d:-1.12s;--t:3.98s;--dx:121px;--c:#ffffff"></i><i style="--x:27%;--d:-0.75s;--t:2.75s;--dx:82px;--c:#3b82f6"></i><i style="--x:4%;--d:-0.7s;--t:2.74s;--dx:99px;--c:#fbbf24"></i><i style="--x:59%;--d:-2.39s;--t:3.3s;--dx:-53px;--c:#ff2e63"></i><i style="--x:11%;--d:-1.4s;--t:2.86s;--dx:108px;--c:#ffffff"></i><i style="--x:35%;--d:-2.29s;--t:2.84s;--dx:100px;--c:#3b82f6"></i><i style="--x:37%;--d:-2.13s;--t:3.87s;--dx:-42px;--c:#fbbf24"></i><i style="--x:65%;--d:-2.15s;--t:3.48s;--dx:-148px;--c:#ff2e63"></i><i style="--x:22%;--d:-0.01s;--t:3.49s;--dx:70px;--c:#ffffff"></i><i style="--x:53%;--d:-0.72s;--t:2.85s;--dx:16px;--c:#3b82f6"></i><i style="--x:50%;--d:-0.76s;--t:4.11s;--dx:-160px;--c:#fbbf24"></i><i style="--x:43%;--d:-1.8s;--t:4.11s;--dx:-99px;--c:#ff2e63"></i><i style="--x:27%;--d:-1.71s;--t:4.22s;--dx:-12px;--c:#ffffff"></i><i style="--x:34%;--d:-0.89s;--t:3.31s;--dx:141px;--c:#3b82f6"></i><i style="--x:11%;--d:-0.87s;--t:3.37s;--dx:-20px;--c:#fbbf24"></i><i style="--x:8%;--d:-0.67s;--t:2.69s;--dx:-14px;--c:#ff2e63"></i><i style="--x:83%;--d:-2.25s;--t:3.05s;--dx:-24px;--c:#ffffff"></i><i style="--x:57%;--d:-1.23s;--t:2.94s;--dx:31px;--c:#3b82f6"></i><i style="--x:56%;--d:-2.12s;--t:4.06s;--dx:44px;--c:#fbbf24"></i><i style="--x:72%;--d:-1.32s;--t:3.9s;--dx:-135px;--c:#ff2e63"></i><i style="--x:95%;--d:-0.99s;--t:3.71s;--dx:-90px;--c:#ffffff"></i><i style="--x:84%;--d:-2.09s;--t:3.47s;--dx:121px;--c:#3b82f6"></i><i style="--x:18%;--d:-0.41s;--t:3.35s;--dx:-16px;--c:#fbbf24"></i><i style="--x:40%;--d:-0.61s;--t:3.93s;--dx:-27px;--c:#ff2e63"></i><i style="--x:53%;--d:-1.57s;--t:3.14s;--dx:125px;--c:#ffffff"></i><i style="--x:87%;--d:-0.95s;--t:2.9s;--dx:-78px;--c:#3b82f6"></i><i style="--x:11%;--d:-0.5s;--t:4.23s;--dx:94px;--c:#fbbf24"></i><i style="--x:72%;--d:-0.53s;--t:4.23s;--dx:70px;--c:#ff2e63"></i><i style="--x:56%;--d:-0.34s;--t:2.95s;--dx:-114px;--c:#ffffff"></i><i style="--x:24%;--d:-0.82s;--t:2.76s;--dx:-38px;--c:#3b82f6"></i><i style="--x:49%;--d:-0.62s;--t:3.63s;--dx:-150px;--c:#fbbf24"></i></div>
        <div class="ov-cel-card"><div class="ov-cel-ph"><img data-src="player.photo" src="{{ $aa['player.photo'] }}" alt=""></div><span class="ov-cel-tag">SOLD</span><div class="ov-cel-nm" data-bind="player.name">{{ $aa['player.name'] }}</div><div class="ov-cel-to">to <img data-src="team.logo" src="{{ $aa['team.logo'] }}" alt=""><span data-bind="team.name">{{ $aa['team.name'] }}</span></div><div class="ov-cel-price"><small>SOLD FOR</small><div data-bind="bid.points">{{ $aa['bid.points'] }}</div></div></div>
      </div>
      <div class="corner"><div class="ov-stats"><span>Available <b data-bind="status.available">{{ $aa['status.available'] }}</b></span><span class="s">Sold <b data-bind="status.sold">{{ $aa['status.sold'] }}</b></span><span class="u">Unsold <b data-bind="status.unsold">{{ $aa['status.unsold'] }}</b></span></div><span class="live ov-live">LIVE</span><img data-src="brand.logo" src="{{ $aa['brand.logo'] }}" alt="Auction Arena"></div>
      <div class="wrap">
        <div class="strip">
          <div class="tour"><img data-src="tournament.logo" src="{{ $aa['tournament.logo'] }}" alt=""></div>
          <div class="ph"><img data-src="player.photo" src="{{ $aa['player.photo'] }}" alt=""><div class="ov-stamp"></div></div>
          <div class="who"><div class="name" data-bind="player.name">{{ $aa['player.name'] }}</div><div class="role" data-bind="player.role">{{ $aa['player.role'] }}</div></div>
          <div class="seg"><span class="k">Base</span><span class="v" data-bind="player.base">{{ $aa['player.base'] }}</span></div>
          <div class="bid" data-pulse><span class="k">Current bid</span><span class="v" data-bind="bid.points">{{ $aa['bid.points'] }}</span></div>
          <div class="team"><img data-src="team.logo" src="{{ $aa['team.logo'] }}" alt=""><span class="n" data-bind="team.name">{{ $aa['team.name'] }}</span></div>
        </div>
        <div class="ticker"><span class="lab">AUCTION</span><div class="vp"><div class="run"><span data-bind="tournament.name">{{ $aa['tournament.name'] }}</span><span>Live player auction</span><span>Powered by Auction Arena</span><span data-bind="tournament.name">{{ $aa['tournament.name'] }}</span><span>Live player auction</span><span>Powered by Auction Arena</span><span data-bind="tournament.name">{{ $aa['tournament.name'] }}</span><span>Live player auction</span><span>Powered by Auction Arena</span><span data-bind="tournament.name">{{ $aa['tournament.name'] }}</span><span>Live player auction</span><span>Powered by Auction Arena</span></div></div></div>
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
            function restart(el, cls) { if (el) { el.classList.remove(cls); void el.offsetWidth; el.classList.add(cls); } }
            function flash(el) { restart(el, "aa-bump"); restart(el.closest("[data-pulse]"), "aa-pulse"); }
            function text(key, value, pulse) {
                document.querySelectorAll(`[data-bind="${key}"]`).forEach((el) => { el.textContent = value; if (pulse) flash(el); });
            }
            function image(key, url) {
                document.querySelectorAll(`[data-src="${key}"]`).forEach((el) => (el.src = url));
                document.querySelectorAll(`[data-bg="${key}"]`).forEach((el) => (el.style.backgroundImage = `url("${url}")`));
            }
            const state = (s) => (root.dataset.state = s);
            const teamColor = (c) => root.style.setProperty("--team", c || DEFAULTS.color);
            function noBids() { text("team.name", "NO BIDS YET"); image("team.logo", DEFAULTS.team); teamColor(null); }
            /* Sold / unsold move one player out of the pool; skipped if the counts aren't numbers. */
            function count(key, delta) {
                const el = document.querySelector(`[data-bind="${key}"]`);
                const n = el ? parseInt(el.textContent.replace(/,/g, ""), 10) : NaN;
                if (!isNaN(n)) text(key, inr(Math.max(0, n + delta)), true);
            }
            function clearReset() { if (resetTimer) { clearTimeout(resetTimer); resetTimer = null; } }

            function finish(result) {
                state(result);
                count(`status.${result}`, 1);
                count("status.available", -1);
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
