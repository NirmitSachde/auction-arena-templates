/* Auction Arena template runtime (no dependencies).
   - Scales the fixed 1920x1080 .aa-stage to fit any screen (letterboxed).
   - Fills [data-bind="path.to.value"] text and [data-src="path"] images.
   - Renders [data-stats] lists by cloning their first child <template>.
   - Demo controls: SPACE / → raises the bid, T cycles bidding team.
   - AA.update(patch) deep-merges live data and re-renders; elements whose
     value changed get the .aa-bump class for one animation cycle. */
(function () {
  const get = (o, p) => p.split(".").reduce((a, k) => (a == null ? a : a[k]), o);
  const merge = (t, s) => {
    for (const k in s) {
      if (s[k] && typeof s[k] === "object" && !Array.isArray(s[k])) merge((t[k] ||= {}), s[k]);
      else t[k] = s[k];
    }
    return t;
  };

  function bump(el) {
    el.classList.remove("aa-bump");
    void el.offsetWidth;
    el.classList.add("aa-bump");
  }

  function render(first) {
    const d = window.AUCTION;
    document.querySelectorAll("[data-bind]").forEach((el) => {
      const v = get(d, el.dataset.bind);
      const s = v == null ? "" : String(v);
      if (el.textContent !== s) {
        el.textContent = s;
        if (!first) bump(el);
      }
    });
    document.querySelectorAll("[data-src]").forEach((el) => {
      const v = get(d, el.dataset.src);
      if (v && el.getAttribute("src") !== v) el.setAttribute("src", v);
    });
    document.querySelectorAll("[data-stats]").forEach((box) => {
      const tpl = box.querySelector("template");
      if (!tpl) return;
      const stats = get(d, box.dataset.stats) || [];
      const max = +box.dataset.max || stats.length;
      box.querySelectorAll(":scope > :not(template)").forEach((n) => n.remove());
      stats.slice(0, max).forEach((s, i) => {
        const node = tpl.content.firstElementChild.cloneNode(true);
        node.style.setProperty("--i", i);
        node.querySelectorAll("[data-stat]").forEach((f) => (f.textContent = s[f.dataset.stat]));
        box.appendChild(node);
      });
    });
    const pct = Math.round((d.status.sold / Math.max(1, d.status.available)) * 100);
    document.documentElement.style.setProperty("--aa-sold-pct", pct);
  }

  function fit() {
    const st = document.querySelector(".aa-stage");
    if (!st) return;
    const k = Math.min(innerWidth / 1920, innerHeight / 1080);
    st.style.transform = `translate(-50%, -50%) scale(${k})`;
  }

  /* Demo-only bid simulation so the screen feels live on a monitor. */
  const teams = [
    { name: "Meena Sports", short: "MS", maxBid: "54.3L", purse: "8.2CR", slots: "7 / 15" },
    { name: "Royal Strikers", short: "RS", maxBid: "1.2CR", purse: "11.6CR", slots: "5 / 15" },
    { name: "Titan Warriors", short: "TW", maxBid: "88.0L", purse: "6.9CR", slots: "9 / 15" }
  ];
  let ti = 0;
  function raiseBid() {
    const b = window.AUCTION.bid;
    b.raw += b.increment;
    b.amount = (b.raw / 1e7).toFixed(2);
    render(false);
  }
  function nextTeam() {
    ti = (ti + 1) % teams.length;
    merge(window.AUCTION.team, teams[ti]);
    render(false);
  }

  window.AA = {
    update(patch) { merge(window.AUCTION, patch); render(false); },
    render: () => render(false)
  };

  addEventListener("resize", fit);
  addEventListener("keydown", (e) => {
    if (e.code === "Space" || e.code === "ArrowRight") { e.preventDefault(); raiseBid(); }
    if (e.code === "KeyT") nextTeam();
  });
  document.addEventListener("DOMContentLoaded", () => { render(true); fit(); document.body.classList.add("aa-ready"); });
})();
