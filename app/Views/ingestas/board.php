<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Tablero de Ingestas</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  :root{
    --line:#1f2730;
    --idle:#3c4856; --on:#00d46a; --label:#9aa8b1;
  }
  html,body{height:100%;margin:0;background:transparent;color:#d7e0e5;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif}
  .wrap{display:flex;flex-direction:column;height:100%}

  .board {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    gap: 10px;
    flex: 1;
    padding: 14px;
  }
  .col {
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
  }
  .tile {
    aspect-ratio: 1/1;
    background: rgba(17,22,27,0.8); /* semitransparente */
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 8px;
    box-sizing: border-box;
    display:flex;
    flex-direction:column;
    position:relative;
  }
  .tile .title {
    font-weight:600;
    font-size:12px;
    line-height:1.2;
    color:#d7e0e5;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    text-overflow:ellipsis;
    margin-bottom:4px;
  }
  .meta {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:6px;
    border-top:1px solid var(--line);
    padding-top:4px; margin-top:auto;
    font-size:11px;
  }
  .meta .ing {
    font-size:11px;
    letter-spacing:.3px;
    padding:2px 6px;
    border-radius:999px;
    background:rgba(14,21,27,0.6);
    border:1px solid var(--line);
    color:var(--label);
    font-weight:600;
  }
  .meta .ing.rec { color:#ff5b5b; }
  .meta .cv {
    color:#9aa8b1;
    max-width:55%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    text-align:right;
    font-size:11px;
  }

  footer {
    padding:6px 12px;
    border-top:1px solid var(--line);
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    color:#8ea0a9;
    font-size:12px;
    gap:6px;
  }
  .clocks { font-variant-numeric:tabular-nums; font-size:12px; display:flex; gap:14px; }
  .legend { display:flex; gap:14px; }
  .dot { width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:6px; }
</style>
</head>
<body>
<div class="wrap">
  <main class="board" id="board"></main>

  <footer>
    <div class="clocks">
      <span id="clock-local">BUE --:--:--</span>
      <span id="clock-utc">UTC --:--:--</span>
    </div>
    <div class="legend">
      <span><span class="dot" style="background:var(--idle)"></span>Libre</span>
      <span><span class="dot" style="background:var(--on)"></span>Ocupada</span>
    </div>
  </footer>
</div>


<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script>
  const BASE_URL = "<?= site_url(); ?>";
  const LABELS   = <?= json_encode(array_values($labels), JSON_UNESCAPED_UNICODE); ?>;
  const REFRESH_MS = <?= (int)$refreshMs ?>;
  const PUSHER_KEY = "<?= esc($pusher['key']) ?>";
  const PUSHER_CLUSTER = "<?= esc($pusher['cluster']) ?>";
  const board = document.getElementById('board');

  function idFromLabel(s){ return 'slot-' + s.replace(/\s+/g,'-').replace(/\./g,'-'); }

  function renderSkeleton(){
    board.innerHTML = '';
    for(let c=0; c<15; c++){
      const col = document.createElement('div');
      col.className = 'col';
      for(let r=0; r<4; r++){
        const idx = c*4+r;
        const name = LABELS[idx] || '';
        const id = idFromLabel(name);
        const t = document.createElement('div');
        t.className = 'tile';
        t.id = id;
        t.innerHTML = `
          <div class="title"></div>
          <div class="meta">
            <span class="ing">${name}</span>
            <span class="cv"></span>
          </div>
        `;
        col.appendChild(t);
      }
      board.appendChild(col);
    }
  }
  renderSkeleton();

  function parseDateString(str) {
    if (!str) return null;
    if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/.test(str)) {
      return new Date(str.replace(' ', 'T'));
    }
    return null;
  }

  function isActiveNow(s) {
    const now = new Date();
    const start = parseDateString(s.start);
    const end   = parseDateString(s.end);
    if (start && end)   return now >= start && now <= end;
    if (start && !end)  return now >= start;
    if (!start && end)  return now <= end;
    return false;
  }

  function paintByDeviceTime(slots){
    slots.forEach(s=>{
      const el = document.getElementById(idFromLabel(s.name));
      if (!el) return;

      const active = s.event_id && isActiveNow(s);
      const titleEl = el.querySelector('.title');
      const ingEl   = el.querySelector('.ing');
      const cvEl    = el.querySelector('.cv');

      if (cvEl) cvEl.textContent = s.conv ? String(s.conv) : '';
      if (titleEl) {
        titleEl.textContent = s.title || '';
        titleEl.title = s.title || '';
      }
      el.title = s.title || '';

      if (ingEl) {
        ingEl.textContent = s.name || '';
        ingEl.classList.toggle('rec', active);
      }
      el.classList.toggle('on', active);
    });
  }

  async function refresh(){
    try{
      const res = await fetch(`${BASE_URL}/ingestas/status`);
      const j = await res.json();
      if(j && j.ok){
        paintByDeviceTime(j.slots);
      }
    }catch(e){ console.error(e); }
  }
  refresh();
  setInterval(refresh, REFRESH_MS);

  if (PUSHER_KEY) {
    const pusher = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
    const ch = pusher.subscribe('calendar-channel');
    ch.bind('event-updated', refresh);
    ch.bind('event-created', refresh);
  }

  function pad(n){return String(n).padStart(2,'0')}
  function tick(){
    const d=new Date();
    document.getElementById('clock-local').textContent = 
      `BUE ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    document.getElementById('clock-utc').textContent   = 
      `UTC ${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}:${pad(d.getUTCSeconds())}`;
  }
  setInterval(tick,1000); tick();
</script>



</body>
</html>

<script>
function initBoard() {
    const container = document.getElementById("board");
    if (!container) return;

    // Evitar inicializar 2 veces
    if (container.dataset.initialized) return;
    container.dataset.initialized = "1";

function updateClock() {
    const now = new Date();

    const bueEl = document.getElementById("clock-local");
    const utcEl = document.getElementById("clock-utc");

    if (bueEl) {
        bueEl.textContent = "BUE " + now.toLocaleTimeString("es-AR", {
            timeZone: "America/Argentina/Buenos_Aires",
            hour12: false
        });
    }
    if (utcEl) {
        utcEl.textContent = "UTC " + now.toLocaleTimeString("en-GB", {
            timeZone: "UTC",
            hour12: false
        });
    }
}

setInterval(updateClock, 1000);
updateClock();


    // 📡 Cargar estado
    async function loadStatus() {
        try {
const res = await fetch("<?= site_url('ingestas/status') ?>");
            const data = await res.json();
            if (!data.ok) return;

            for (const slot of data.slots) {
                const el = document.querySelector(`[data-slot='${slot.name}']`);
                if (!el) continue;

                if (slot.event_id) {
                    el.classList.add("busy");
                    el.title = slot.title + " (" + (slot.conv || "") + ")";
                    el.querySelector(".slot-title").textContent = slot.title;
                    el.querySelector(".slot-conv").textContent = slot.conv || "";
                } else {
                    el.classList.remove("busy");
                    el.title = "";
                    el.querySelector(".slot-title").textContent = "";
                    el.querySelector(".slot-conv").textContent = "";
                }
            }
        } catch (e) {
            console.error("Error cargando estado", e);
        }
    }
    setInterval(loadStatus, <?= $refreshMs ?>);
    loadStatus();
}

// 👉 Inicializa en vista completa
$(document).ready(function () {
    initBoard();
});

// 👉 Re-inicializa al abrir modal AJAX
$(document).on("shown.bs.modal", "#ajaxModal", function () {
    initBoard();
});
</script>
