/* Scales the fixed-size .aa-stage to fit the window (OBS browser source or phone). */
(() => {
    const st = document.querySelector(".aa-stage");
    const w = +st.dataset.w || 1920, h = +st.dataset.h || 1080;
    const fit = () => (st.style.transform = `translate(-50%, -50%) scale(${Math.min(innerWidth / w, innerHeight / h)})`);
    addEventListener("resize", fit);
    fit();
})();
