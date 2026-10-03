{{-- Auction Arena · Story 01 Gold Rush
     Generated from stories/01-gold-rush.html by tools/build-blade.mjs. Do not edit by hand:
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
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Montserrat:wght@500;700;800;900&display=swap" rel="stylesheet">
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

        /* Story 01 Gold Rush: black-tie luxury. Arched portrait in a gold frame, serif name, gold price plate, art-deco hairlines. */
        :root { --gold: #e8c46a; --gold2: #b8862b; --ink: #fff; --mute: #b9a985; }
        .aa-stage { font-family: Montserrat, sans-serif; color: var(--ink);
          background: radial-gradient(ellipse 80% 45% at 50% 38%, rgba(232,196,106,.28), transparent 70%), radial-gradient(ellipse 120% 60% at 50% 110%, #2a1d07, transparent 60%), linear-gradient(180deg, #0b0905, #000 55%, #0d0a04); }
        .frame { position: absolute; inset: 40px; border: 2px solid rgba(232,196,106,.55); border-radius: 6px; }
        .frame::before { content: ""; position: absolute; inset: 14px; border: 1px solid rgba(232,196,106,.25); border-radius: 4px; }
        .top { position: absolute; left: 0; right: 0; top: 150px; text-align: center; }
        .top img { width: 120px; height: 120px; object-fit: contain; margin: 0 auto 18px; }
        .top .t { font-family: Cinzel, serif; font-weight: 700; font-size: 38px; letter-spacing: 6px; text-transform: uppercase; color: var(--gold); padding: 0 120px; line-height: 1.25; }
        .rule { width: 320px; height: 2px; margin: 22px auto 0; background: linear-gradient(90deg, transparent, var(--gold), transparent); }
        .arch { position: absolute; left: 50%; top: 470px; width: 640px; height: 780px; margin-left: -320px; border-radius: 320px 320px 18px 18px; padding: 10px;
          background: linear-gradient(180deg, #fbe7a6, var(--gold) 40%, var(--gold2)); box-shadow: 0 0 120px rgba(232,196,106,.25); }
        .arch .st-photo { width: 100%; height: 100%; border-radius: 310px 310px 10px 10px; background-color: #1a140a; }
        .sold { position: absolute; left: 50%; top: 1170px; width: 360px; margin-left: -180px; padding: 16px 0; text-align: center; background: #000; border: 3px solid var(--gold);
          font-family: Cinzel, serif; font-weight: 900; font-size: 54px; letter-spacing: 18px; color: var(--gold); text-indent: 18px; }
        .name { position: absolute; left: 60px; right: 60px; top: 1310px; text-align: center; font-family: Cinzel, serif; font-weight: 800; font-size: 76px; line-height: 1.05; text-transform: uppercase; letter-spacing: 2px; }
        .role { text-align: center; position: absolute; left: 0; right: 0; top: 1410px; font-size: 26px; font-weight: 700; letter-spacing: 10px; color: var(--mute); text-transform: uppercase; }
        .price { position: absolute; left: 140px; right: 140px; top: 1480px; height: 180px; display: flex; align-items: center; border-top: 2px solid var(--gold); border-bottom: 2px solid var(--gold); }
        .price .amt { flex: 1; text-align: center; }
        .price small { display: block; font-size: 22px; font-weight: 800; letter-spacing: 8px; color: var(--mute); }
        .price .v { font-family: Cinzel, serif; font-weight: 900; font-size: 84px; line-height: 1.1; color: var(--gold); }
        .price .tm { width: 170px; display: flex; flex-direction: column; align-items: center; gap: 8px; border-left: 1px solid rgba(232,196,106,.4); }
        .price .tm img { width: 96px; height: 96px; border-radius: 50%; background: #fff; padding: 6px; object-fit: contain; }
        .price .tm span { font-size: 17px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; text-align: center; color: var(--ink); }
        .foot { position: absolute; left: 0; right: 0; bottom: 92px; display: flex; justify-content: center; align-items: center; gap: 16px; font-size: 18px; font-weight: 700; letter-spacing: 5px; color: var(--mute); }
        .foot img { height: 56px; }

        :root { --team: {{ $aa['team.color'] }}; }
    </style>
@endsection

@section('content')
    <div class="aa-stage" data-w="1080" data-h="1920">
      <div class="frame"></div>
      <div class="top"><img data-src="tournament.logo" src="{{ $aa['tournament.logo'] }}" alt=""><div class="t" data-bind="tournament.name">{{ $aa['tournament.name'] }}</div><div class="rule"></div></div>
      <div class="arch"><div class="st-photo" data-bg="player.photo" style="background-image: url('{{ $aa['player.photo'] }}')"></div></div>
      <div class="sold">SOLD</div>
      <div class="name" data-bind="player.name" data-fit>{{ $aa['player.name'] }}</div>
      <div class="role" data-bind="player.role">{{ $aa['player.role'] }}</div>
      <div class="price">
        <div class="amt"><small>SOLD FOR</small><div class="v" data-bind="bid.points">{{ $aa['bid.points'] }}</div></div>
        <div class="tm"><img data-src="team.logo" src="{{ $aa['team.logo'] }}" alt=""><span data-bind="team.name">{{ $aa['team.name'] }}</span></div>
      </div>
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
