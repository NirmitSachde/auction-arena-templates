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
