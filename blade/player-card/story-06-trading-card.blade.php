{{-- Auction Arena · Story 06 Trading Card
     Generated from stories/06-trading-card.html by tools/build-blade.mjs. Do not edit by hand:
     change the template or tools/blade/*, then run: node tools/build-blade.mjs --}}
@extends('frontend.layout.blank-layout')

@php
    /*
     | Auction Arena story card data. Every field the card shows comes from $aa,
     | so this block is the only place that knows about the models.
     | CHECK each source against the player-card controller: the names below
     | follow the YouTube overlay ($tournament, $player) plus the sold record.
     */
    $aaInr = function ($n) {
        if (!is_numeric($n)) return $n;
        $n = (string) (int) round($n);
        $last3 = substr($n, -3);
        $rest = substr($n, 0, -3);
        return ($rest === '' ? '' : preg_replace('/\B(?=(\d{2})+$)/', ',', $rest) . ',') . $last3;
    };
    $aaTeam = $team ?? null; // CHECK: the buying team
    $aa = [
        'tournament.name' => $tournament->name ?? '',
        'tournament.logo' => $tournament->tournament_image ?? asset('assets/img/logo/favicon.png'), // CHECK column name
        'brand.logo' => asset('assets/img/logo/logo-white.png'),
        'player.name' => trim($player->first_name . ' ' . $player->last_name),
        'player.first' => $player->first_name,
        'player.last' => $player->last_name,
        'player.role' => $player->player_speciality ?? '',
        'player.photo' => $player->player_image ?? asset('assets/users/imgs/unknown-player.png'),
        'player.base' => $aaInr($basePrice ?? '-'), // CHECK
        'bid.points' => $aaInr($soldPrice ?? '-'), // CHECK: final bid points
        'team.name' => $aaTeam->name ?? '',
        'team.logo' => $aaTeam->team_image ?? asset('assets/users/imgs/default-team.png'),
        'team.color' => $aaTeam->color ?? '#e11d48', // CHECK: team colour column, if there is one
    ];
@endphp

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@600;700;800;900&display=swap" rel="stylesheet">
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

        /* Shared layer for phone-size story cards (templates in stories/).
           The stage is 1080x1920 (9:16) so the downloaded PNG drops straight into an
           Instagram / WhatsApp story. Cards must stay html2canvas-friendly: no
           clip-path, mask, filter, backdrop-filter, blend modes or gradient text,
           photos as background-image (data-bg), and animations only as "from" frames
           so the resting state is what gets captured. */
        html, body { background: #0b0d12; }
        .aa-stage[data-h="1920"] { width: 1080px; height: 1920px; }
        .st-photo { background-size: cover; background-position: center top; background-repeat: no-repeat; }

        .st-download {
          position: fixed; right: 20px; bottom: 20px; z-index: 10;
          padding: 14px 22px; border: 0; border-radius: 12px; cursor: pointer;
          background: #ffcc00; color: #111; font: 800 15px/1 system-ui, sans-serif; letter-spacing: 1px;
          box-shadow: 0 8px 24px rgba(0, 0, 0, .45);
        }
        .st-download[disabled] { opacity: .6; cursor: progress; }
        .aa-capture *, .aa-capture *::before, .aa-capture *::after { animation: none !important; transition: none !important; }

        /* Names stay on one line; story.js shrinks [data-fit] text to its box. */
        [data-fit] { white-space: nowrap; }

        /* Story 06 Trading Card: the player as a gold collectible card centre stage, on a deep holo backdrop. Card face carries role, base, sold price and the team crest. */
        :root { --g1: #fff3c4; --g2: #f2c94c; --g3: #b7811f; --ink: #2a1c04; }
        .aa-stage { font-family: Inter, sans-serif; color: #fff;
          background: radial-gradient(ellipse 60% 35% at 50% 48%, rgba(242,201,76,.35), transparent 70%), radial-gradient(circle at 15% 20%, rgba(80,120,255,.35), transparent 40%), radial-gradient(circle at 85% 80%, rgba(200,60,255,.3), transparent 40%), linear-gradient(160deg, #0b1026, #050611 60%, #120a24); }
        .rays { position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22 viewBox=%220 0 40 40%22%3E%3Cpath d=%22M0 40L40 0V3L3 40Z%22 fill=%22%23fff%22 fill-opacity=%22.04%22/%3E%3C/svg%3E") 0 0 / 40px 40px repeat; }
        .top { position: absolute; left: 0; right: 0; top: 140px; display: flex; justify-content: center; align-items: center; gap: 18px; }
        .top img { width: 84px; height: 84px; object-fit: contain; }
        .top .t { font-family: Oswald, sans-serif; font-weight: 600; font-size: 40px; text-transform: uppercase; letter-spacing: 2px; max-width: 760px; line-height: 1.05; }
        .card { position: absolute; left: 50%; top: 290px; width: 800px; height: 1250px; margin-left: -400px; border-radius: 60px; padding: 14px;
          background: linear-gradient(150deg, var(--g1), var(--g2) 30%, var(--g3) 60%, var(--g2) 85%, var(--g1)); box-shadow: 0 0 120px rgba(242,201,76,.35); }
        .face { position: relative; width: 100%; height: 100%; border-radius: 48px; overflow: hidden; color: var(--ink);
          background: linear-gradient(170deg, #fff8dc, #f6d77a 45%, #e6b445); }
        .face .st-photo { position: absolute; left: 0; right: 0; top: 0; height: 760px; background-color: #e9c665; }
        .face .fade { position: absolute; left: 0; right: 0; top: 560px; height: 220px; background: linear-gradient(180deg, rgba(240,200,100,0), #f3cf6c); }
        .corner { position: absolute; left: 44px; top: 44px; text-align: center; }
        .corner .r { font-family: Oswald, sans-serif; font-weight: 700; font-size: 34px; text-transform: uppercase; line-height: 1; }
        .corner img { width: 96px; height: 96px; object-fit: contain; margin-top: 14px; background: #fff; border-radius: 50%; padding: 6px; }
        .chip { position: absolute; right: 40px; top: 44px; padding: 10px 22px; border-radius: 12px; background: #1b1203; color: var(--g2); font-weight: 900; font-size: 28px; letter-spacing: 6px; }
        .name { position: absolute; left: 30px; right: 30px; top: 780px; text-align: center; font-family: Oswald, sans-serif; font-weight: 700; font-size: 80px; line-height: 1.12; text-transform: uppercase; }
        .bar { position: absolute; left: 70px; right: 70px; top: 885px; height: 3px; background: rgba(42,28,4,.35); }
        .stats { position: absolute; left: 60px; right: 60px; top: 910px; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr); gap: 18px 30px; }
        .stats .k { font-size: 20px; font-weight: 900; letter-spacing: 4px; opacity: .65; }
        .stats .v { font-family: Oswald, sans-serif; font-weight: 700; font-size: 46px; line-height: 1.05; text-transform: uppercase; white-space: nowrap; }
        .stats .wide { grid-column: 1 / -1; padding-top: 14px; border-top: 3px solid rgba(42,28,4,.35); text-align: center; }
        .stats .wide .v { font-size: 92px; }
        .foot { position: absolute; left: 0; right: 0; bottom: 110px; display: flex; justify-content: center; align-items: center; gap: 16px; font-size: 20px; font-weight: 800; letter-spacing: 6px; color: rgba(255,255,255,.75); }
        .foot img { height: 58px; }
        .sub { position: absolute; left: 0; right: 0; top: 1590px; text-align: center; font-family: Oswald, sans-serif; font-size: 40px; font-weight: 600; letter-spacing: 3px; text-transform: uppercase; }
        .sub b { color: var(--g2); }

        :root { --team: {{ $aa['team.color'] }}; }
    </style>
@endsection

@section('content')
    <div class="aa-stage" data-w="1080" data-h="1920">
      <div class="rays"></div>
      <div class="top"><img data-src="tournament.logo" src="{{ $aa['tournament.logo'] }}" alt=""><div class="t" data-bind="tournament.name">{{ $aa['tournament.name'] }}</div></div>
      <div class="card"><div class="face">
        <div class="st-photo" data-bg="player.photo" style="background-image: url('{{ $aa['player.photo'] }}')"></div>
        <div class="fade"></div>
        <div class="corner"><div class="r" data-bind="player.role">{{ $aa['player.role'] }}</div><img data-src="team.logo" src="{{ $aa['team.logo'] }}" alt=""></div>
        <div class="chip">SOLD</div>
        <div class="name" data-bind="player.name" data-fit>{{ $aa['player.name'] }}</div>
        <div class="bar"></div>
        <div class="stats">
          <div><div class="k">BASE</div><div class="v" data-bind="player.base" data-fit>{{ $aa['player.base'] }}</div></div>
          <div><div class="k">TEAM</div><div class="v" data-bind="team.name" data-fit>{{ $aa['team.name'] }}</div></div>
          <div class="wide"><div class="k">SOLD FOR</div><div class="v" data-bind="bid.points">{{ $aa['bid.points'] }}</div></div>
        </div>
      </div></div>
      <div class="sub">Welcome to <b data-bind="team.name">{{ $aa['team.name'] }}</b></div>
      <div class="foot">AUCTION PARTNER <img data-src="brand.logo" src="{{ $aa['brand.logo'] }}" alt="Auction Arena"></div>
    </div>
@endsection

@section('page-scripts')
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
        /* Story card download: renders the 1080x1920 stage to a PNG with html2canvas
           at 1:1, ignoring the on-screen scale. Used by stories/ and inlined into the
           Blade story views by tools/build-blade.mjs. */
        (function () {
          const SRC = "https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js";
          let lib;
          const load = () => (lib ||= new Promise((ok, fail) => {
            const s = document.createElement("script");
            s.src = SRC; s.onload = () => ok(window.html2canvas); s.onerror = fail;
            document.head.appendChild(s);
          }));

          async function download(btn) {
            const stage = document.querySelector(".aa-stage");
            btn.disabled = true;
            try {
              const h2c = await load();
              await document.fonts.ready;
              const canvas = await h2c(stage, {
                scale: 1, width: 1080, height: 1920, useCORS: true, backgroundColor: null,
                onclone(doc) {
                  doc.documentElement.classList.add("aa-capture");
                  const s = doc.querySelector(".aa-stage");
                  s.style.transform = "none"; s.style.left = "0"; s.style.top = "0";
                }
              });
              const a = document.createElement("a");
              const name = playerName().replace(/\W+/g, "-").toLowerCase() || "player";
              a.download = `${name}-story.png`;
              a.href = canvas.toDataURL("image/png");
              a.click();
            } finally {
              btn.disabled = false;
            }
          }

          function playerName() {
            const t = (k) => (document.querySelector(`[data-bind="${k}"]`) || {}).textContent || "";
            return (t("player.name") || `${t("player.first")} ${t("player.last")}`).trim();
          }

          /* [data-fit] text stays on one line: shrink it until it fits its box. */
          function fit() {
            document.querySelectorAll("[data-fit]").forEach((el) => {
              el.style.fontSize = "";
              let size = parseFloat(getComputedStyle(el).fontSize);
              const min = size * 0.4;
              while (el.scrollWidth > el.clientWidth + 1 && size > min) el.style.fontSize = (size -= 2) + "px";
            });
          }

          window.AAStory = { download, load, fit };
          document.fonts.ready.then(fit);
          document.addEventListener("DOMContentLoaded", () => {
            fit();
            new MutationObserver(fit).observe(document.querySelector(".aa-stage"), { subtree: true, characterData: true, childList: true });
            const btn = document.createElement("button");
            btn.className = "st-download";
            btn.textContent = "DOWNLOAD PNG";
            btn.onclick = () => download(btn);
            document.body.appendChild(btn);
          });
        })();
    </script>
@endsection
