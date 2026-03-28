// UNI-VIRTUAL — main.js

// ── TABS GLOBALES ────────────────────────────────────────────────────────
function showTab(name) {
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b  => b.classList.remove('active'));
  const pane = document.getElementById('tab-' + name);
  if (pane) pane.classList.add('active');
  if (event && event.target) event.target.classList.add('active');
}

// ── DRAG & DROP UPLOAD ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.uarea').forEach(area => {
    const inp  = area.querySelector('input[type=file]');
    const txt  = area.querySelector('.uarea-txt');
    if (!inp) return;

    area.addEventListener('dragover',  e => { e.preventDefault(); area.classList.add('drag'); });
    area.addEventListener('dragleave', ()  => area.classList.remove('drag'));
    area.addEventListener('drop', e => {
      e.preventDefault(); area.classList.remove('drag');
      if (e.dataTransfer.files[0]) {
        inp.files = e.dataTransfer.files;
        if (txt) txt.innerHTML = '✅ <strong>' + e.dataTransfer.files[0].name + '</strong>';
      }
    });
    inp.addEventListener('change', () => {
      if (inp.files[0] && txt) txt.innerHTML = '✅ <strong>' + inp.files[0].name + '</strong>';
    });
  });

  // ── AUTO-OCULTAR ALERTAS ─────────────────────────────────────────────
  document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => { a.style.transition = 'opacity 0.6s'; a.style.opacity = '0'; setTimeout(() => a.remove(), 600); }, 4000);
  });

  // ── NOTA INPUT VALIDATION ────────────────────────────────────────────
  document.querySelectorAll('input[type=number].nota-inp').forEach(inp => {
    inp.addEventListener('blur', () => {
      let v = parseFloat(inp.value);
      if (isNaN(v)) return;
      if (v < 0) v = 0;
      if (v > 5) v = 5;
      inp.value = v.toFixed(1);
    });
  });

  // ── CONFIRM ANTES DE ELIMINAR ────────────────────────────────────────
  document.querySelectorAll('a[href*="eliminar"], a[href*="del="]').forEach(a => {
    a.addEventListener('click', e => {
      if (!confirm('¿Confirmar eliminación?')) e.preventDefault();
    });
  });

  // ── TOAST MANUAL (para llamadas desde PHP redirects con ?msg) ────────
  const params = new URLSearchParams(window.location.search);
  const msg = params.get('msg');
  if (msg) showToast(decodeURIComponent(msg));
});

// ── TOAST ────────────────────────────────────────────────────────────────
function showToast(msg, color) {
  const t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = `position:fixed;bottom:22px;right:22px;background:${color||'#0d2247'};color:#fff;padding:11px 18px;border-radius:8px;font-size:13px;font-weight:500;box-shadow:0 8px 40px rgba(13,34,71,.2);z-index:9999;border-left:4px solid #e8c06a;transition:all .3s;opacity:0;transform:translateY(20px);font-family:'Source Sans 3',sans-serif;max-width:320px`;
  document.body.appendChild(t);
  setTimeout(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; }, 10);
  setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(20px)'; setTimeout(()=>t.remove(),300); }, 3200);
}

// ── PROGRESS BAR ANIMACIÓN ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.prog-bar').forEach(bar => {
    const w = bar.style.width; bar.style.width = '0';
    setTimeout(() => { bar.style.transition = 'width 0.8s ease'; bar.style.width = w; }, 100);
  });
});
