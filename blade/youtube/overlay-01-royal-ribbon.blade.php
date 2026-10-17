{{-- Auction Arena · Overlay 01 Royal Ribbon
     Generated from overlays/01-royal-ribbon.html by tools/build-blade.mjs. Do not edit by hand:
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
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
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

        /* Overlay 01 Royal Ribbon: IPL-style navy and gold lower third. Photo medallion breaks out of the ribbon, gold bid plate, white team roundel. */
        :root {
          --navy: #071340; --navy2: #102a7a; --gold: #f7c948; --gold2: #d99a0b; --ink: #fff; --mute: #c3cdf0;
          --goldgrad: linear-gradient(180deg, #fff0b3 0%, #f7c948 40%, #d99a0b 100%);
        }
        .aa-stage { font-family: Montserrat, sans-serif; color: var(--ink); }

        /* Top bugs */
        .bug { position: absolute; top: 40px; left: 48px; height: 84px; display: flex; align-items: center; gap: 16px; padding: 0 30px 0 12px;
          background: linear-gradient(90deg, rgba(7,19,64,.96), rgba(16,42,122,.92)); border-bottom: 4px solid var(--gold);
          clip-path: polygon(0 0, 100% 0, calc(100% - 24px) 100%, 0 100%); }
        .bug img { width: 64px; height: 64px; object-fit: contain; }
        .bug .t { font-family: Oswald, sans-serif; font-weight: 600; font-size: 28px; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap; max-width: 620px; overflow: hidden; text-overflow: ellipsis; }
        .brand { position: absolute; top: 40px; right: 48px; height: 84px; display: flex; align-items: center; gap: 18px; }
        .brand .live { height: 44px; padding: 0 18px; background: #e11d2e; color: #fff; font: 700 22px/44px Oswald, sans-serif; letter-spacing: 3px; border-radius: 4px; }
        .brand img { height: 70px; filter: drop-shadow(0 4px 10px rgba(0,0,0,.5)); }

        /* Lower third */
        .l3 { position: absolute; left: 60px; right: 60px; bottom: 46px; height: 150px; display: flex; align-items: stretch; animation: rise .7s cubic-bezier(.2,.8,.2,1) both; }
        @keyframes rise { from { transform: translateY(120%); opacity: 0; } }
        .tab { position: absolute; left: 250px; top: -40px; height: 40px; display: flex; align-items: center; gap: 10px; padding: 0 34px 0 20px;
          background: var(--goldgrad); color: var(--navy); font: 800 16px/1 Montserrat, sans-serif; letter-spacing: 4px; text-transform: uppercase;
          clip-path: polygon(0 0, calc(100% - 20px) 0, 100% 100%, 0 100%); }
        .main { position: relative; flex: 1; min-width: 0; display: flex; align-items: center; padding-left: 250px;
          background: linear-gradient(90deg, rgba(7,19,64,.97), rgba(16,42,122,.95) 70%, rgba(7,19,64,.97));
          border-top: 3px solid var(--gold); box-shadow: 0 20px 50px rgba(0,0,0,.5); }
        .main::after { content: ""; position: absolute; inset: 0 0 55% 0; background: linear-gradient(180deg, rgba(255,255,255,.12), transparent); pointer-events: none; }
        .medal { position: absolute; left: 18px; bottom: 6px; width: 212px; height: 212px; border-radius: 50%; padding: 6px; background: var(--goldgrad); box-shadow: 0 16px 40px rgba(0,0,0,.6); }
        .medal img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; object-position: top center; background: #0b1544; }
        .medal .ov-stamp { left: 22px; top: 70px; }
        .who { min-width: 0; }
        .name { font-family: Oswald, sans-serif; font-weight: 700; font-size: 58px; line-height: 1; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-shadow: 0 3px 10px rgba(0,0,0,.4); }
        .chips { display: flex; gap: 12px; margin-top: 14px; }
        .chip { display: flex; align-items: center; gap: 10px; height: 36px; padding: 0 16px; border: 1px solid rgba(247,201,72,.45); background: rgba(0,0,0,.25); border-radius: 4px; white-space: nowrap; }
        .chip small { font-size: 13px; font-weight: 800; letter-spacing: 2px; color: var(--gold); }
        .chip b { font-size: 18px; font-weight: 800; text-transform: uppercase; }
        .bid { flex: none; width: 470px; display: flex; flex-direction: column; justify-content: center; align-items: center; background: var(--goldgrad); color: var(--navy);
          clip-path: polygon(34px 0, 100% 0, 100% 100%, 0 100%); padding-left: 30px; box-shadow: inset 0 3px 0 rgba(255,255,255,.6); }
        .bid small { font-weight: 900; font-size: 17px; letter-spacing: 5px; }
        .bid .v { font-family: Oswald, sans-serif; font-weight: 700; font-size: 78px; line-height: 1; font-variant-numeric: tabular-nums; }
        .team { flex: none; width: 250px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 8px; background: var(--navy); border-top: 3px solid var(--gold); }
        .team .logo { width: 86px; height: 86px; border-radius: 50%; background: #fff; padding: 8px; box-shadow: 0 6px 16px rgba(0,0,0,.4); }
        .team .logo img { width: 100%; height: 100%; object-fit: contain; }
        .team .n { max-width: 220px; font-size: 16px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .aa-stage { --ov-cel-bg: linear-gradient(160deg, #102a7a, #071340 70%); --ov-cel-accent: #f7c948; --ov-cel-ink: #071340; --ov-cel-font: Oswald, sans-serif; }
        .bug-stats { position: absolute; left: 48px; top: 138px; }
        .bug-stats > span { background: rgba(7,19,64,.9); border-left: 3px solid var(--gold); border-radius: 0; }

        :root { --team: {{ $aa['team.color'] }}; }
    </style>
@endsection

@section('content')
    <div class="aa-stage">
      <div class="ov-strip"><div class="track"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div></div>
      <div class="ov-celebrate"><div class="ov-confetti"><i style="--x:43%;--d:-2.27s;--t:3.31s;--dx:-136px;--c:#f7c948"></i><i style="--x:11%;--d:-1.97s;--t:2.77s;--dx:138px;--c:#ffffff"></i><i style="--x:9%;--d:-2.18s;--t:2.99s;--dx:-116px;--c:#1a3a9e"></i><i style="--x:57%;--d:-1.0s;--t:3.03s;--dx:122px;--c:#d99a0b"></i><i style="--x:56%;--d:-0.14s;--t:3.62s;--dx:-46px;--c:#f7c948"></i><i style="--x:82%;--d:-1.51s;--t:4.31s;--dx:135px;--c:#ffffff"></i><i style="--x:76%;--d:-0.95s;--t:4.36s;--dx:-137px;--c:#1a3a9e"></i><i style="--x:73%;--d:-2.06s;--t:3.12s;--dx:-87px;--c:#d99a0b"></i><i style="--x:71%;--d:-0.28s;--t:3.16s;--dx:-68px;--c:#f7c948"></i><i style="--x:15%;--d:-1.4s;--t:3.75s;--dx:30px;--c:#ffffff"></i><i style="--x:14%;--d:-1.31s;--t:2.71s;--dx:-130px;--c:#1a3a9e"></i><i style="--x:81%;--d:-0.49s;--t:3.82s;--dx:58px;--c:#d99a0b"></i><i style="--x:42%;--d:-1.12s;--t:4.26s;--dx:25px;--c:#f7c948"></i><i style="--x:40%;--d:-0.6s;--t:2.92s;--dx:-36px;--c:#ffffff"></i><i style="--x:12%;--d:-1.38s;--t:3.55s;--dx:15px;--c:#1a3a9e"></i><i style="--x:95%;--d:-1.08s;--t:3.7s;--dx:-123px;--c:#d99a0b"></i><i style="--x:17%;--d:-1.23s;--t:2.9s;--dx:15px;--c:#f7c948"></i><i style="--x:21%;--d:-2.24s;--t:3.36s;--dx:-121px;--c:#ffffff"></i><i style="--x:73%;--d:-1.38s;--t:4.18s;--dx:0px;--c:#1a3a9e"></i><i style="--x:45%;--d:-1.67s;--t:3.67s;--dx:136px;--c:#d99a0b"></i><i style="--x:60%;--d:-0.17s;--t:2.77s;--dx:-22px;--c:#f7c948"></i><i style="--x:62%;--d:-1.67s;--t:2.72s;--dx:-2px;--c:#ffffff"></i><i style="--x:84%;--d:-1.39s;--t:3.83s;--dx:68px;--c:#1a3a9e"></i><i style="--x:38%;--d:-1.72s;--t:4.2s;--dx:17px;--c:#d99a0b"></i><i style="--x:4%;--d:-2.26s;--t:3.24s;--dx:152px;--c:#f7c948"></i><i style="--x:16%;--d:-1.18s;--t:2.99s;--dx:-13px;--c:#ffffff"></i><i style="--x:18%;--d:-1.77s;--t:3.32s;--dx:94px;--c:#1a3a9e"></i><i style="--x:12%;--d:-0.4s;--t:3.32s;--dx:-18px;--c:#d99a0b"></i><i style="--x:19%;--d:-1.97s;--t:4.16s;--dx:-18px;--c:#f7c948"></i><i style="--x:92%;--d:-1.0s;--t:3.25s;--dx:34px;--c:#ffffff"></i><i style="--x:31%;--d:-0.36s;--t:2.92s;--dx:-42px;--c:#1a3a9e"></i><i style="--x:86%;--d:-0.56s;--t:3.47s;--dx:141px;--c:#d99a0b"></i><i style="--x:25%;--d:-0.63s;--t:2.61s;--dx:54px;--c:#f7c948"></i><i style="--x:70%;--d:-0.89s;--t:3.62s;--dx:-96px;--c:#ffffff"></i><i style="--x:90%;--d:-2.06s;--t:4.31s;--dx:-133px;--c:#1a3a9e"></i><i style="--x:60%;--d:-2.16s;--t:4.0s;--dx:126px;--c:#d99a0b"></i></div>
        <div class="ov-cel-card"><div class="ov-cel-ph"><img data-src="player.photo" src="{{ $aa['player.photo'] }}" alt=""></div><span class="ov-cel-tag">SOLD</span><div class="ov-cel-nm" data-bind="player.name">{{ $aa['player.name'] }}</div><div class="ov-cel-to">to <img data-src="team.logo" src="{{ $aa['team.logo'] }}" alt=""><span data-bind="team.name">{{ $aa['team.name'] }}</span></div><div class="ov-cel-price"><small>SOLD FOR</small><div data-bind="bid.points">{{ $aa['bid.points'] }}</div></div></div>
      </div>

      <div class="bug"><img data-src="tournament.logo" src="{{ $aa['tournament.logo'] }}" alt=""><span class="t" data-bind="tournament.name">{{ $aa['tournament.name'] }}</span></div>
      <div class="bug-stats"><div class="ov-stats"><span>Available <b data-bind="status.available">{{ $aa['status.available'] }}</b></span><span class="s">Sold <b data-bind="status.sold">{{ $aa['status.sold'] }}</b></span><span class="u">Unsold <b data-bind="status.unsold">{{ $aa['status.unsold'] }}</b></span></div></div>
      <div class="brand"><span class="live ov-live">LIVE</span><img data-src="brand.logo" src="{{ $aa['brand.logo'] }}" alt="Auction Arena"></div>

      <div class="l3">
        <div class="main">
          <div class="tab">&#9733; Now in auction</div>
          <div class="medal"><img data-src="player.photo" src="{{ $aa['player.photo'] }}" alt=""><div class="ov-stamp"></div></div>
          <div class="who">
            <div class="name" data-bind="player.name">{{ $aa['player.name'] }}</div>
            <div class="chips">
              <div class="chip"><small>ROLE</small><b data-bind="player.role">{{ $aa['player.role'] }}</b></div>
              <div class="chip"><small>BASE</small><b data-bind="player.base">{{ $aa['player.base'] }}</b></div>
            </div>
          </div>
        </div>
        <div class="bid" data-pulse><small>CURRENT BID</small><div class="v" data-bind="bid.points">{{ $aa['bid.points'] }}</div></div>
        <div class="team"><div class="logo"><img data-src="team.logo" src="{{ $aa['team.logo'] }}" alt=""></div><div class="n" data-bind="team.name">{{ $aa['team.name'] }}</div></div>
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
