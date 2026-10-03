/* Story card download: renders the 1080x1920 stage to a PNG with html2canvas
   at 1:1, ignoring the on-screen scale. Loaded only by stories/. */
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
      const name = (window.AUCTION.player.name || "player").replace(/\W+/g, "-").toLowerCase();
      a.download = `${name}-story.png`;
      a.href = canvas.toDataURL("image/png");
      a.click();
    } finally {
      btn.disabled = false;
    }
  }

  window.AAStory = { download, load };
  document.addEventListener("DOMContentLoaded", () => {
    const btn = document.createElement("button");
    btn.className = "st-download";
    btn.textContent = "DOWNLOAD PNG";
    btn.onclick = () => download(btn);
    document.body.appendChild(btn);
  });
})();
