{{-- Auction Arena · Overlay 04 Neon Strike
     Generated from overlays/04-neon-strike.html by tools/build-blade.mjs. Do not edit by hand:
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
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;800;900&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">
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

        /* Overlay 04 Neon Strike: cyberpunk HUD. Angled cyan player bar bottom-left, magenta bid core bottom-right, scanline shimmer. */
        :root { --cy: #22e8ff; --mg: #ff2bd6; --bg: rgba(5,8,20,.9); --ink: #eafcff; --mute: #7fb6c4; }
        .aa-stage { font-family: Rajdhani, sans-serif; color: var(--ink); --ov-strip-font: Orbitron, sans-serif; --ov-stamp-font: Orbitron, sans-serif;
          --ov-sold: linear-gradient(90deg, #022c22, #10b981, #022c22); --ov-unsold: linear-gradient(90deg, #3b0764, #ff2bd6, #3b0764); }
        .hud { position: absolute; top: 40px; left: 48px; display: flex; align-items: center; gap: 16px; padding: 10px 40px 10px 14px; background: var(--bg);
          border: 2px solid var(--cy); box-shadow: 0 0 22px rgba(34,232,255,.45), inset 0 0 18px rgba(34,232,255,.15);
          clip-path: polygon(0 0, 100% 0, calc(100% - 22px) 100%, 0 100%); }
        .hud img { width: 56px; height: 56px; object-fit: contain; }
        .hud .t { font-family: Orbitron, sans-serif; font-weight: 800; font-size: 22px; letter-spacing: 2px; text-transform: uppercase; white-space: nowrap; }
        .mark { position: absolute; top: 40px; right: 48px; height: 66px; filter: drop-shadow(0 0 12px rgba(34,232,255,.6)); }
        .live { position: absolute; top: 132px; left: 48px; color: var(--mg); font-family: Orbitron, sans-serif; font-weight: 800; font-size: 16px; letter-spacing: 4px; text-shadow: 0 0 10px var(--mg); }

        .bar { position: absolute; left: 48px; bottom: 50px; width: 1100px; height: 150px; display: flex; align-items: center; padding-left: 190px; background: var(--bg);
          border: 2px solid var(--cy); box-shadow: 0 0 30px rgba(34,232,255,.35), inset 0 0 30px rgba(34,232,255,.12);
          clip-path: polygon(0 0, calc(100% - 60px) 0, 100% 100%, 0 100%); animation: in .6s cubic-bezier(.2,.8,.2,1) both; }
        @keyframes in { from { opacity: 0; transform: translateX(-60px); } }
        .bar::after { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(180deg, rgba(34,232,255,.06) 0 2px, transparent 2px 5px); pointer-events: none; }
        .ph { position: absolute; left: 30px; top: 9px; width: 132px; height: 132px; }
        .ph .frame { width: 100%; height: 100%; padding: 4px; background: var(--cy); clip-path: polygon(25% 0, 75% 0, 100% 50%, 75% 100%, 25% 100%, 0 50%); filter: drop-shadow(0 0 12px var(--cy)); }
        .ph img { width: 100%; height: 100%; object-fit: cover; object-position: top center; background: #0a1028; clip-path: polygon(25% 0, 75% 0, 100% 50%, 75% 100%, 25% 100%, 0 50%); }
        .ph .ov-stamp { left: -6px; top: 44px; font-size: 24px; }
        .name { font-family: Orbitron, sans-serif; font-weight: 900; font-size: 44px; letter-spacing: 1px; text-transform: uppercase; white-space: nowrap; max-width: 760px; overflow: hidden; text-overflow: ellipsis; text-shadow: 0 0 14px rgba(34,232,255,.7); }
        .meta { display: flex; gap: 26px; margin-top: 12px; font-size: 24px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .meta small { color: var(--cy); font-size: 16px; letter-spacing: 3px; margin-right: 10px; }

        .core { position: absolute; right: 48px; bottom: 50px; width: 680px; height: 190px; display: grid; grid-template-columns: 1fr 190px; background: var(--bg);
          border: 2px solid var(--mg); box-shadow: 0 0 34px rgba(255,43,214,.45), inset 0 0 30px rgba(255,43,214,.15);
          clip-path: polygon(40px 0, 100% 0, 100% 100%, 0 100%, 0 40px); animation: in .6s .1s cubic-bezier(.2,.8,.2,1) both; }
        .core .amt { display: flex; flex-direction: column; justify-content: center; padding-left: 46px; }
        .core small { font-family: Orbitron, sans-serif; font-size: 15px; letter-spacing: 5px; color: var(--mg); }
        .core .v { font-family: Orbitron, sans-serif; font-weight: 900; font-size: 58px; line-height: 1.1; font-variant-numeric: tabular-nums; text-shadow: 0 0 18px rgba(255,43,214,.8); }
        .core .tm { display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 10px; border-left: 1px solid rgba(255,43,214,.4); }
        .core .tm img { width: 92px; height: 92px; object-fit: contain; border-radius: 50%; background: #fff; padding: 6px; box-shadow: 0 0 18px rgba(255,43,214,.6); }
        .core .tm .n { max-width: 170px; font-size: 18px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.05; }
        .aa-stage { --ov-cel-bg: linear-gradient(160deg, #0b1030, #050814); --ov-cel-accent: #ff2bd6; --ov-cel-ink: #050814; --ov-cel-font: Orbitron, sans-serif; --ov-stats-font: Rajdhani, sans-serif; }
        .ov-cel-card { box-shadow: 0 0 60px rgba(255,43,214,.5), inset 0 0 0 3px #ff2bd6; }
        .ov-cel-nm { font-size: 40px; }
        .hud-stats { position: absolute; left: 380px; top: 124px; }
        .hud-stats > span { background: rgba(5,8,20,.9); border: 1px solid rgba(34,232,255,.5); border-radius: 0; font-size: 14px; }

        :root { --team: {{ $aa['team.color'] }}; }
    </style>
@endsection

@section('content')
    <div class="aa-stage">
      <div class="ov-strip"><div class="track"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div></div>
      <div class="ov-celebrate"><div class="ov-confetti"><i style="--x:36%;--d:-0.83s;--t:2.63s;--dx:-32px;--c:#22e8ff"></i><i style="--x:6%;--d:-0.04s;--t:3.92s;--dx:122px;--c:#ff2bd6"></i><i style="--x:26%;--d:-1.23s;--t:3.04s;--dx:68px;--c:#ffffff"></i><i style="--x:15%;--d:-1.58s;--t:3.77s;--dx:93px;--c:#a78bfa"></i><i style="--x:71%;--d:-2.0s;--t:3.31s;--dx:99px;--c:#22e8ff"></i><i style="--x:41%;--d:-1.65s;--t:4.37s;--dx:15px;--c:#ff2bd6"></i><i style="--x:27%;--d:-2.0s;--t:3.87s;--dx:-89px;--c:#ffffff"></i><i style="--x:53%;--d:-2.37s;--t:4.37s;--dx:-94px;--c:#a78bfa"></i><i style="--x:3%;--d:-0.17s;--t:3.93s;--dx:-30px;--c:#22e8ff"></i><i style="--x:57%;--d:-0.39s;--t:2.75s;--dx:35px;--c:#ff2bd6"></i><i style="--x:66%;--d:-1.61s;--t:3.11s;--dx:-36px;--c:#ffffff"></i><i style="--x:90%;--d:-0.7s;--t:3.43s;--dx:-80px;--c:#a78bfa"></i><i style="--x:36%;--d:-1.07s;--t:3.07s;--dx:8px;--c:#22e8ff"></i><i style="--x:72%;--d:-0.78s;--t:2.66s;--dx:-2px;--c:#ff2bd6"></i><i style="--x:29%;--d:-0.86s;--t:2.6s;--dx:35px;--c:#ffffff"></i><i style="--x:12%;--d:-1.14s;--t:3.5s;--dx:-58px;--c:#a78bfa"></i><i style="--x:33%;--d:-1.21s;--t:2.61s;--dx:-25px;--c:#22e8ff"></i><i style="--x:13%;--d:-0.35s;--t:3.66s;--dx:41px;--c:#ff2bd6"></i><i style="--x:4%;--d:-0.72s;--t:3.73s;--dx:-117px;--c:#ffffff"></i><i style="--x:76%;--d:-2.3s;--t:4.14s;--dx:-81px;--c:#a78bfa"></i><i style="--x:86%;--d:-2.14s;--t:4.01s;--dx:145px;--c:#22e8ff"></i><i style="--x:51%;--d:-1.83s;--t:3.9s;--dx:93px;--c:#ff2bd6"></i><i style="--x:21%;--d:-0.68s;--t:3.71s;--dx:-86px;--c:#ffffff"></i><i style="--x:7%;--d:-1.98s;--t:3.89s;--dx:102px;--c:#a78bfa"></i><i style="--x:82%;--d:-1.03s;--t:3.86s;--dx:98px;--c:#22e8ff"></i><i style="--x:19%;--d:-2.18s;--t:3.96s;--dx:131px;--c:#ff2bd6"></i><i style="--x:4%;--d:-1.98s;--t:3.65s;--dx:-43px;--c:#ffffff"></i><i style="--x:12%;--d:-0.07s;--t:2.84s;--dx:24px;--c:#a78bfa"></i><i style="--x:15%;--d:-0.9s;--t:3.41s;--dx:-135px;--c:#22e8ff"></i><i style="--x:82%;--d:-0.05s;--t:3.56s;--dx:-35px;--c:#ff2bd6"></i><i style="--x:64%;--d:-0.63s;--t:3.42s;--dx:-125px;--c:#ffffff"></i><i style="--x:97%;--d:-2.24s;--t:4.22s;--dx:-113px;--c:#a78bfa"></i><i style="--x:86%;--d:-1.26s;--t:3.94s;--dx:82px;--c:#22e8ff"></i><i style="--x:34%;--d:-1.94s;--t:4.12s;--dx:-40px;--c:#ff2bd6"></i><i style="--x:95%;--d:-1.82s;--t:3.02s;--dx:75px;--c:#ffffff"></i><i style="--x:65%;--d:-2.03s;--t:2.74s;--dx:-13px;--c:#a78bfa"></i></div>
        <div class="ov-cel-card"><div class="ov-cel-ph"><img data-src="player.photo" src="{{ $aa['player.photo'] }}" alt=""></div><span class="ov-cel-tag">SOLD</span><div class="ov-cel-nm" data-bind="player.name">{{ $aa['player.name'] }}</div><div class="ov-cel-to">to <img data-src="team.logo" src="{{ $aa['team.logo'] }}" alt=""><span data-bind="team.name">{{ $aa['team.name'] }}</span></div><div class="ov-cel-price"><small>SOLD FOR</small><div data-bind="bid.points">{{ $aa['bid.points'] }}</div></div></div>
      </div>
      <div class="hud"><img data-src="tournament.logo" src="{{ $aa['tournament.logo'] }}" alt=""><span class="t" data-bind="tournament.name">{{ $aa['tournament.name'] }}</span></div>
      <div class="live ov-live">LIVE AUCTION</div>
      <div class="hud-stats"><div class="ov-stats"><span>Available <b data-bind="status.available">{{ $aa['status.available'] }}</b></span><span class="s">Sold <b data-bind="status.sold">{{ $aa['status.sold'] }}</b></span><span class="u">Unsold <b data-bind="status.unsold">{{ $aa['status.unsold'] }}</b></span></div></div>
      <img class="mark" data-src="brand.logo" src="{{ $aa['brand.logo'] }}" alt="Auction Arena">

      <div class="bar">
        <div class="ph"><div class="frame"><img data-src="player.photo" src="{{ $aa['player.photo'] }}" alt=""></div><div class="ov-stamp"></div></div>
        <div>
          <div class="name" data-bind="player.name">{{ $aa['player.name'] }}</div>
          <div class="meta"><span><small>ROLE</small><span data-bind="player.role">{{ $aa['player.role'] }}</span></span><span><small>BASE</small><span data-bind="player.base">{{ $aa['player.base'] }}</span></span></div>
        </div>
      </div>
      <div class="core">
        <div class="amt" data-pulse><small>CURRENT BID</small><div class="v" data-bind="bid.points">{{ $aa['bid.points'] }}</div></div>
        <div class="tm"><img data-src="team.logo" src="{{ $aa['team.logo'] }}" alt=""><div class="n" data-bind="team.name">{{ $aa['team.name'] }}</div></div>
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
