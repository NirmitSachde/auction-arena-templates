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
