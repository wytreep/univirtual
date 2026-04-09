<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/View.php';

/**
 * EstudianteView — Panel del Estudiante (SPA React)
 * Reemplaza: aula.php, materias.php, notas.php,
 *            notificaciones.php, tareas.php, ver_video.php
 */
class EstudianteView extends View
{
  private int $idEst;
  private int $semestre;
  private string $programa;

  public function __construct()
  {
    parent::__construct('estudiante');
    $this->idEst = (int) ($_SESSION['idEspecifico'] ?? 0);
    // Cargar datos del estudiante para semestre/programa
    $s = db()->prepare("
            SELECT e.semestre, c.nombre AS programa
            FROM estudiantes e
            LEFT JOIN carreras c ON e.idCarrera = c.idCarrera
            WHERE e.idEstudiante = ?
        ");
    $s->execute([$this->idEst]);
    $d = $s->fetch();
    $this->semestre = $d['semestre'] ?? 1;
    $this->programa = $d['programa'] ?? 'Ingeniería de Sistemas';
  }

  protected function sessionExtra(): array {
    $romanos = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
    return [
      'idEst' => $this->idEst,
      'semestre' => 'Semestre ' . ($romanos[$this->semestre - 1] ?? $this->semestre),
      'programa' => $this->programa,
    ];
  }

  public function render(): void
  {
    $s = $this->sessionJS();
    $this->html($s);
  }

  private function html(string $s): void
  { ?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width,initial-scale=1.0">
      <title>UNI-VIRTUAL — Estudiante</title>
      <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=DM+Serif+Display:ital@0;1&family=JetBrains+Mono:wght@400;600&display=swap"
        rel="stylesheet">
      <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
      <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
      <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
      <script src="https://meet.jit.si/external_api.js"></script>
      <link rel="stylesheet" href="/univirtual/assets/css/panel.css">
      <style>
        /* Estilos adicionales específicos del estudiante */
        .ring-container {
          display: flex;
          flex-direction: column;
          align-items: center;
          padding: 16px 0 8px;
        }
      </style>
    </head>

    <body>
      <div id="root"></div>
      <script>window.__S = <?= $s ?>; window.API = '/univirtual/api/v1'; window.BASE = '/univirtual';</script>
      <script type="text/babel">
        const { useState, useEffect, useCallback, useRef } = React;
        const api = {
          get: (u) => fetch(API + u, { credentials: 'include' }).then(r => r.json()),
          post: (u, b, isForm) => fetch(API + u, { method: 'POST', credentials: 'include', ...(isForm ? { body: b } : { headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(b) }) }).then(r => r.json()),
        };
        const Spinner = () => <div className="loading-center"><div className="spinner" /><span>Cargando...</span></div>;
        const Alert = ({ type, msg, onClose }) => msg ? <div className={`alert alert-${type}`}>{msg}<span onClick={onClose} style={{ cursor: 'pointer', marginLeft: 8 }}>✕</span></div> : null;
        const Modal = ({ title, onClose, children, footer }) => (
          <div className="modal-overlay" onClick={e => e.target === e.currentTarget && onClose()}>
            <div className="modal">
              <div className="modal-header"><span className="modal-title">{title}</span><button className="modal-close" onClick={onClose}>✕</button></div>
              <div className="modal-body">{children}</div>
              {footer && <div className="modal-footer">{footer}</div>}
            </div>
          </div>
        );

        // ── JITSI MEET — Link directo (SCRUM-118) ─────────────────────────────────────────

        // Anillo de progreso SVG
        const Ring = ({ pct, color = '#2462b0', size = 100 }) => {
          const r = 40, c = 2 * Math.PI * r, off = c - (pct / 100) * c;
          return <div style={{ position: 'relative', width: size, height: size }}>
            <svg width={size} height={size} viewBox="0 0 100 100" style={{ transform: 'rotate(-90deg)' }}>
              <circle cx={50} cy={50} r={r} fill="none" stroke="var(--border)" strokeWidth={8} />
              <circle cx={50} cy={50} r={r} fill="none" stroke={color} strokeWidth={8} strokeLinecap="round" strokeDasharray={c} strokeDashoffset={off} style={{ transition: 'stroke-dashoffset .6s' }} />
            </svg>
            <div style={{ position: 'absolute', inset: 0, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}>
              <span style={{ fontFamily: "'DM Serif Display',serif", fontSize: size / 5, color: 'var(--navy)', lineHeight: 1 }}>{pct}%</span>
              <span style={{ fontSize: size / 11, color: 'var(--muted)' }}>semestre</span>
            </div>
          </div>;
        };

        // ── DASHBOARD (Inicio) ─────────────────────────────────────────────────
        const Dashboard = ({ onMat, onNotifs }) => {
          const [data, setData] = useState(null);
          const { idEst, nombre, semestre, programa } = window.__S;
          useEffect(() => { api.get(`/estudiante.php?action=dashboard&idEst=${idEst}`).then(r => r.status === 'ok' && setData(r.data)); }, []);
          if (!data) return <Spinner />;
          const apellido = nombre.split(' ').slice(1).join(' '); const pnombre = nombre.split(' ')[0];
          const { estadisticas: st = {}, materias = [], proximasTareas = [], notificaciones = [] } = data;
          const pctSemestre = Math.round((st.semanasTranscurridas || 14) / (st.totalSemanas || 20) * 100);
          const colorsNotif = { material: '#2462b0', tarea: '#e07b39', nota: '#1a7a48', foro: '#6d28d9', sistema: '#c0392b', info: '#888' };
          return <div className="page">
            {/* Banner bienvenida */}
            <div className="welcome-banner">
              <div>
                <div className="wb-label">Bienvenido de nuevo</div>
                <div className="wb-name">{pnombre} <em>{apellido}</em></div>
                <div className="wb-sub">{semestre} · {programa} · 2026-I</div>
              </div>
              <div className="wb-stats">
                {[{ n: materias.length, l: 'Materias' }, { n: st.tareasProximas || 0, l: 'Pendientes' }, { n: `${st.asistencia || 0}%`, l: 'Asistencia' }].map(s => (
                  <div className="wb-stat" key={s.l}><div className="wb-stat-num">{s.n}</div><div className="wb-stat-lbl">{s.l}</div></div>
                ))}
              </div>
            </div>
            {/* Métricas */}
            <div className="metric-row">
              <div className="metric-card"><div className="metric-ico" style={{ background: '#e8f0fb' }}>📊</div><div><div className="metric-val" style={{ color: '#2462b0' }}>{st.promedio || '—'}</div><div className="metric-lbl">Promedio parcial</div></div></div>
              <div className="metric-card"><div className="metric-ico" style={{ background: '#f0ebff' }}>📘</div><div><div className="metric-val" style={{ color: '#6d28d9' }}>{st.creditosInscritos || 0}</div><div className="metric-lbl">Créditos inscritos</div></div></div>
              <div className="metric-card"><div className="metric-ico" style={{ background: '#e8f5ee' }}>📅</div><div><div className="metric-val" style={{ color: '#1a7a48' }}>{st.asistencia || 0}%</div><div className="metric-lbl">Asistencia</div></div></div>
            </div>
            {/* Grid principal */}
            <div className="page-grid">
              {/* Col izquierda */}
              <div>
                {/* Mis materias */}
                <div className="card">
                  <div className="card-header">
                    <span className="card-title">📚 Mis Materias</span>
                    <span className="card-link" onClick={onMat}>Ver todas →</span>
                  </div>
                  <div className="card-body">
                    {materias.length === 0 && <p style={{ color: 'var(--muted)', textAlign: 'center', padding: 16 }}>Sin materias inscritas.</p>}
                    {materias.slice(0, 4).map(m => {
                      const pct = Math.min(100, Math.round(((m.nota_parcial1 || 0) / 5) * 100));
                      return <div key={m.idMateria} className="mat-row" onClick={() => onMat(m)}>
                        <div className="mat-color" style={{ background: m.color || '#2462b0' }} />
                        <div className="mat-info">
                          <div className="mat-name">{m.nombre}</div>
                          <div className="pbar-wrap" style={{ marginTop: 6 }}><div className="pbar-fill" style={{ width: `${pct}%`, background: m.color || '#2462b0' }} /></div>
                        </div>
                        <div className="mat-right">
                          <div className="mat-pct-num">{pct}%</div>
                          {(m.pendientes || 0) > 0 ? <span className="tag tag-warn">{m.pendientes} tareas</span> : <span className="tag tag-ok">Al día</span>}
                        </div>
                      </div>;
                    })}
                  </div>
                </div>
                {/* Próximas entregas */}
                <div className="card">
                  <div className="card-header">
                    <span className="card-title">📅 Próximas Entregas</span>
                  </div>
                  <div className="card-body">
                    {proximasTareas.length === 0 && <p style={{ color: 'var(--muted)', textAlign: 'center', padding: 16 }}>Sin entregas próximas. 🎉</p>}
                    {proximasTareas.map((t, i) => {
                      const f = new Date(t.fechaEntrega); const hoy = new Date(); const diff = Math.ceil((f - hoy) / 86400000);
                      const dia = f.getDate(), mes = f.toLocaleDateString('es-CO', { month: 'short' }).toUpperCase();
                      return <div key={i} className="delivery-item">
                        <div className="delivery-date"><div className="delivery-day">{dia}</div><div className="delivery-mon">{mes}</div></div>
                        <div className="delivery-info">
                          <div className="delivery-name">{t.titulo}</div>
                          <div className="delivery-tags">
                            <span className="tag tag-navy" style={{ fontFamily: 'monospace', fontSize: 11 }}>{t.materia}</span>
                            {diff <= 1 ? <span className="tag tag-urgente">Urgente</span> : diff <= 3 ? <span className="tag tag-warn">Pronto</span> : null}
                          </div>
                        </div>
                      </div>;
                    })}
                  </div>
                </div>
              </div>
              {/* Col derecha */}
              <div>
                {/* Progreso del semestre */}
                <div className="card">
                  <div className="card-header"><span className="card-title">📈 Progreso</span></div>
                  <div className="card-body">
                    <div className="ring-container"><Ring pct={pctSemestre} /></div>
                    <div style={{ marginTop: 8 }}>
                      {[{ c: '#2462b0', l: 'Semanas', v: `${st.semanasTranscurridas || 14}/${st.totalSemanas || 20}` }, { c: '#1a7a48', l: 'Entregas', v: `${st.entregasHechas || 0}/${st.totalActividades || 0}` }, { c: '#d4a843', l: 'Pendientes', v: st.tareasProximas || 0 }].map(r => (
                        <div key={r.l} className="prog-item">
                          <div className="prog-item-lbl"><div className="prog-item-dot" style={{ background: r.c }} />{r.l}</div>
                          <div className="prog-item-val">{r.v}</div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
                {/* Novedades */}
                <div className="card">
                  <div className="card-header">
                    <span className="card-title">🔔 Novedades</span>
                    <span className="card-link" onClick={onNotifs}>Ver todas</span>
                  </div>
                  <div className="card-body" style={{ padding: '6px 20px' }}>
                    {notificaciones.length === 0 && <p style={{ color: 'var(--muted)', fontSize: 13, textAlign: 'center', padding: 12 }}>Sin novedades</p>}
                    {notificaciones.slice(0, 5).map((n, i) => (
                      <div key={i} className="notif-item">
                        <div style={{ display: 'flex', gap: 8 }}>
                          <div className="notif-dot" style={{ marginTop: 4, background: colorsNotif[n.tipo] || '#888' }} />
                          <div><div className="notif-title">{n.titulo}</div><div className="notif-msg">{n.mensaje}</div><div className="notif-time">{n.creado_en?.slice(0, 10)}</div></div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
                {/* Acceso rápido */}
                <div className="card">
                  <div className="card-header"><span className="card-title">⚡ Acceso Rápido</span></div>
                  <div className="card-body">
                    <div className="quick-grid">
                      {[{ ico: '📚', lbl: 'Materias', fn: () => onMat() }, { ico: '📋', lbl: 'Tareas', fn: () => { } }, { ico: '📊', lbl: 'Notas', fn: () => { } }, { ico: '🔔', lbl: 'Notifs', fn: onNotifs }].map(b => (
                        <div key={b.lbl} className="quick-btn" onClick={b.fn}><span className="quick-btn-ico">{b.ico}</span><span className="quick-btn-lbl">{b.lbl}</span></div>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>;
        };

        // ── MIS MATERIAS ───────────────────────────────────────────────────────
        const MisMaterias = ({ onAula }) => {
          const [data, setData] = useState(null); const { idEst } = window.__S;
          useEffect(() => { api.get(`/estudiante.php?action=materias&idEst=${idEst}`).then(r => r.status === 'ok' && setData(r.data)); }, []);
          if (!data) return <Spinner />;
          return <div className="page">
            <div className="welcome-banner" style={{ padding: '20px 28px', marginBottom: 20 }}><div><div className="wb-label">Académico</div><div className="wb-name" style={{ fontSize: 22 }}>Mis Materias</div></div><div className="wb-stats"><div className="wb-stat"><div className="wb-stat-num">{data.length}</div><div className="wb-stat-lbl">Inscritas</div></div></div></div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(300px,1fr))', gap: 16 }}>
              {data.map(m => {
                const prom = ((m.nota_parcial1 || 0) + (m.nota_parcial2 || 0) + (m.nota_talleres || 0)) / 3;
                const pp = prom > 0 ? prom.toFixed(1) : '—';
                return <div key={m.idMateria} className="card" style={{ margin: 0, cursor: 'pointer', borderTop: `4px solid ${m.color || '#2462b0'}` }} onClick={() => onAula(m)}>
                  <div className="card-header"><span className="card-title">{m.nombre}</span><code style={{ fontSize: 11, color: 'var(--muted)' }}>{m.codigo}</code></div>
                  <div className="card-body">
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 12 }}>
                      <div><div style={{ fontSize: 24, fontFamily: "'DM Serif Display',serif", color: parseFloat(pp) >= 3 ? 'var(--green)' : parseFloat(pp) ? 'var(--red)' : 'var(--navy)' }}>{pp}</div><div style={{ fontSize: 11, color: 'var(--muted)' }}>Promedio parcial</div></div>
                      <div style={{ textAlign: 'right' }}><div style={{ fontSize: 14, fontWeight: 600, color: 'var(--navy)' }}>{m.pendientes || 0}</div><div style={{ fontSize: 11, color: 'var(--muted)' }}>Pendientes</div></div>
                    </div>
                    <div style={{ fontSize: 12, color: 'var(--muted)', marginBottom: 8 }}>{m.prof_nombre} · {m.creditos} créditos</div>
                    <div style={{ display: 'flex', gap: 6, marginBottom: 10, flexWrap: 'wrap' }}>
                      {[['P1', m.nota_parcial1], ['P2', m.nota_parcial2], ['Tall.', m.nota_talleres], ['Final', m.nota_final]].map(([l, v]) => (
                        <div key={l} style={{ flex: 1, minWidth: 50, background: 'var(--bg)', borderRadius: 7, padding: '5px 8px', textAlign: 'center' }}>
                          <div style={{ fontSize: 10, color: 'var(--muted)' }}>{l}</div>
                          <div style={{ fontSize: 14, fontWeight: 700, color: v >= 3 ? 'var(--green)' : v ? 'var(--red)' : 'var(--muted)' }}>{v ?? '—'}</div>
                        </div>
                      ))}
                    </div>
                    <button className="btn btn-primary" style={{ width: '100%', marginTop: 4 }}>🏫 Entrar al Aula</button>
                  </div>
                </div>;
              })}
            </div>
          </div>;
        };

        // ── TAREAS ─────────────────────────────────────────────────────────────
        const Tareas = () => {
          const [data, setData] = useState(null); const [tab, setTab] = useState('pendientes'); const [modal, setModal] = useState(null); const [file, setFile] = useState(null); const [comment, setComment] = useState(''); const [saving, setSaving] = useState(false); const [alerta, setAlerta] = useState(null); const { idEst } = window.__S;
          useEffect(() => { api.get(`/estudiante.php?action=tareas&idEst=${idEst}`).then(r => r.status === 'ok' && setData(r.data)); }, []);
          const entregar = async () => { if (!file) return setAlerta({ type: 'err', msg: 'Selecciona un archivo' }); const fd = new FormData(); fd.append('action', 'entregar'); fd.append('idActividad', modal.idActividad); fd.append('idEst', idEst); fd.append('comentario', comment); fd.append('archivo', file); setSaving(true); const r = await api.post('/estudiante.php', fd, true); setSaving(false); if (r.status === 'ok') { setModal(null); setFile(null); setComment(''); setAlerta({ type: 'ok', msg: '¡Entrega realizada!' }); api.get(`/estudiante.php?action=tareas&idEst=${idEst}`).then(r2 => r2.status === 'ok' && setData(r2.data)); } else setAlerta({ type: 'err', msg: r.mensaje }); };
          if (!data) return <Spinner />;
          const { pendientes = [], entregadas = [], vencidas = [] } = data;
          const curr = tab === 'pendientes' ? pendientes : tab === 'entregadas' ? entregadas : vencidas;
          return <div className="page">
            <div className="welcome-banner" style={{ padding: '20px 28px', marginBottom: 20 }}><div><div className="wb-label">Académico</div><div className="wb-name" style={{ fontSize: 22 }}>Tareas y Actividades</div></div>
              <div className="wb-stats">{[{ n: pendientes.length, l: 'Pendientes' }, { n: entregadas.length, l: 'Entregadas' }, { n: vencidas.length, l: 'Vencidas' }].map(s => <div key={s.l} className="wb-stat"><div className="wb-stat-num">{s.n}</div><div className="wb-stat-lbl">{s.l}</div></div>)}</div>
            </div>
            <Alert type={alerta?.type} msg={alerta?.msg} onClose={() => setAlerta(null)} />
            <div className="tabs">
              <div className={`tab ${tab === 'pendientes' ? 'active' : ''}`} onClick={() => setTab('pendientes')}>⏳ Pendientes ({pendientes.length})</div>
              <div className={`tab ${tab === 'entregadas' ? 'active' : ''}`} onClick={() => setTab('entregadas')}>✅ Entregadas ({entregadas.length})</div>
              <div className={`tab ${tab === 'vencidas' ? 'active' : ''}`} onClick={() => setTab('vencidas')}>❌ Vencidas ({vencidas.length})</div>
            </div>
            {curr.length === 0 && <div className="card"><div style={{ padding: 36, textAlign: 'center', color: 'var(--muted)' }}>Sin actividades en esta categoría.</div></div>}
            {curr.map(t => {
              const f = new Date(t.fechaEntrega); const diff = Math.ceil((f - new Date()) / 86400000);
              const dia = f.getDate(), mes = f.toLocaleDateString('es-CO', { month: 'short' }).toUpperCase();
              return <div key={t.idActividad} className="card" style={{ margin: '0 0 12px', borderLeft: `4px solid ${t.color || '#2462b0'}` }}>
                <div style={{ padding: '16px 18px', display: 'flex', gap: 16, alignItems: 'flex-start' }}>
                  <div className="delivery-date"><div className="delivery-day">{dia}</div><div className="delivery-mon">{mes}</div></div>
                  <div style={{ flex: 1 }}>
                    <div style={{ fontWeight: 700, fontSize: 14 }}>{t.titulo}</div>
                    <div style={{ fontSize: 12, color: 'var(--muted)', marginTop: 3 }}>{t.materia} · Puntaje: {t.puntaje_max}</div>
                    {t.descripcion && <p style={{ fontSize: 13, color: 'var(--text-lt)', marginTop: 6 }}>{t.descripcion}</p>}
                    {t.nota !== undefined && t.nota !== null && <div style={{ marginTop: 8 }}><span className="tag tag-ok">Nota: {t.nota}</span>{t.feedback && <span style={{ fontSize: 12, color: 'var(--muted)', marginLeft: 8 }}>{t.feedback}</span>}</div>}
                  </div>
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 6, alignItems: 'flex-end', flexShrink: 0 }}>
                    {tab === 'pendientes' && diff <= 1 && <span className="tag tag-urgente">Urgente</span>}
                    {tab === 'pendientes' && <button className="btn btn-primary btn-sm" onClick={() => setModal(t)}>📤 Entregar</button>}
                    {t.enunciado && <a href={`/univirtual/api/descargar.php?id=${t.idActividad}&tipo=enunciado&preview=1`} className="btn btn-outline btn-sm" target="_blank">📄 Enunciado</a>}
                  </div>
                </div>
              </div>;
            })}
            {modal && <Modal title={`📤 Entregar: ${modal.titulo}`} onClose={() => setModal(null)} footer={<><button className="btn btn-outline" onClick={() => setModal(null)}>Cancelar</button><button className="btn btn-primary" onClick={entregar} disabled={saving}>{saving ? 'Enviando...' : '📤 Enviar Entrega'}</button></>}>
              <p style={{ fontSize: 13, color: 'var(--muted)', marginBottom: 14 }}>{modal.materia} · Vence {new Date(modal.fechaEntrega).toLocaleDateString('es-CO')}</p>
              <div className="form-group"><label className="form-label">Archivo de entrega</label><input type="file" className="form-input" onChange={e => setFile(e.target.files[0])} accept=".pdf,.docx,.doc,.zip,.rar" /></div>
              <div className="form-group"><label className="form-label">Comentario (opcional)</label><textarea className="form-input" rows={3} value={comment} onChange={e => setComment(e.target.value)} placeholder="Notas o comentarios para el profesor..." /></div>
              {file && <div style={{ background: 'var(--bg)', borderRadius: 8, padding: '8px 12px', fontSize: 12, color: 'var(--muted)' }}>📎 {file.name} ({(file.size / 1024 / 1024).toFixed(2)} MB)</div>}
            </Modal>}
          </div>;
        };

        // ── CALIFICACIONES ─────────────────────────────────────────────────────
        const Calificaciones = () => {
          const [data, setData] = useState(null); const { idEst } = window.__S;
          useEffect(() => { api.get(`/estudiante.php?action=notas&idEst=${idEst}`).then(r => r.status === 'ok' && setData(r.data)); }, []);
          if (!data) return <Spinner />;
          return <div className="page">
            <div className="welcome-banner" style={{ padding: '20px 28px', marginBottom: 20 }}>
              <div><div className="wb-label">Académico</div><div className="wb-name" style={{ fontSize: 22 }}>Calificaciones</div></div>
              <div className="wb-stats"><div className="wb-stat"><div className="wb-stat-num" style={{ color: 'var(--gold-lt)' }}>{data.promedio || '—'}</div><div className="wb-stat-lbl">Promedio</div></div></div>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(320px,1fr))', gap: 16 }}>
              {data.materias.map(m => {
                const notas = [m.nota_parcial1, m.nota_parcial2, m.nota_talleres, m.nota_final].filter(n => n !== null && n !== undefined);
                const prom = notas.length ? notas.reduce((a, b) => a + (+b), 0) / notas.length : null;
                const estado = m.estado || 'en_curso';
                return <div key={m.idMateria} className="card" style={{ margin: 0, borderTop: `4px solid ${m.color || '#2462b0'}` }}>
                  <div className="card-header">
                    <div><div style={{ fontWeight: 700, fontSize: 14 }}>{m.nombre}</div><code style={{ fontSize: 11, color: 'var(--muted)' }}>{m.codigo}</code></div>
                    <span className={`tag ${estado === 'aprobado' ? 'tag-ok' : estado === 'reprobando' ? 'tag-err' : 'tag-blue'}`}>{estado === 'aprobado' ? '✅ Aprobado' : estado === 'reprobando' ? '❌ Reprobando' : '📘 En curso'}</span>
                  </div>
                  <div className="card-body">
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8, marginBottom: 12 }}>
                      {[['Parcial 1', m.nota_parcial1], ['Parcial 2', m.nota_parcial2], ['Talleres', m.nota_talleres], ['Nota Final', m.nota_final]].map(([l, v]) => (
                        <div key={l} style={{ background: 'var(--bg)', borderRadius: 8, padding: '8px 12px' }}>
                          <div style={{ fontSize: 11, color: 'var(--muted)', marginBottom: 2 }}>{l}</div>
                          <div style={{ fontSize: 20, fontFamily: "'DM Serif Display',serif", color: v >= 3 ? 'var(--green)' : v ? 'var(--red)' : 'var(--muted)' }}>{v ?? '—'}</div>
                        </div>
                      ))}
                    </div>
                    {prom && <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                      <div className="pbar-wrap" style={{ flex: 1 }}><div className="pbar-fill" style={{ width: `${(prom / 5) * 100}%`, background: prom >= 3 ? 'var(--green)' : 'var(--red)' }} /></div>
                      <span style={{ fontSize: 13, fontWeight: 700, color: prom >= 3 ? 'var(--green)' : 'var(--red)' }}>Prom: {prom.toFixed(1)}</span>
                    </div>}
                  </div>
                </div>;
              })}
            </div>
          </div>;
        };

        // ── AULA VIRTUAL (estudiante) ──────────────────────────────────────────
        const AulaEst = ({ materia, onBack }) => {
          const [tab, setTab] = useState('materiales'); const [data, setData] = useState(null); const [modal, setModal] = useState(null); const [file, setFile] = useState(null); const [comment, setComment] = useState(''); const [msgs, setMsgs] = useState({}); const [saving, setSaving] = useState(false); const [alerta, setAlerta] = useState(null); const { idEst } = window.__S;
          const setMsgForo = (idForo, val) => setMsgs(prev => ({ ...prev, [idForo]: val }));
          const cargar = useCallback(() => { api.get(`/estudiante.php?action=aula&idAula=${materia.idAula}&idEst=${idEst}`).then(r => r.status === 'ok' && setData(r.data)); }, [materia, idEst]);
          useEffect(() => cargar(), [cargar]);
          // ── Videollamadas (solo lectura para estudiante) ─────────────────────────
          const [vlLista, setVlLista] = useState([]);
          const cargarVL = useCallback(() => {
            fetch(`/univirtual/api/v1/videollamadas.php?action=lista&idMateria=${materia.idMateria}`)
              .then(r => r.json()).then(r => r.status === 'ok' && setVlLista(r.data || []));
          }, [materia]);
          useEffect(() => { if (tab === 'videollamadas') cargarVL(); }, [tab, cargarVL]);
          const entregar = async (act) => { if (!file) return setAlerta({ type: 'err', msg: 'Selecciona archivo' }); const fd = new FormData(); fd.append('action', 'entregar'); fd.append('idActividad', act.idActividad); fd.append('idEst', idEst); fd.append('comentario', comment); fd.append('archivo', file); setSaving(true); const r = await api.post('/estudiante.php', fd, true); setSaving(false); if (r.status === 'ok') { setModal(null); setFile(null); setComment(''); cargar(); setAlerta({ type: 'ok', msg: 'Entrega enviada' }); } else setAlerta({ type: 'err', msg: r.mensaje }); };
          const pubMsg = async (idForo) => {
            const texto = (msgs[idForo] || '').trim();
            if (!texto) return;
            setMsgForo(idForo, '');
            // Optimistic update — agregar el mensaje inmediatamente
            const nuevoMsg = { idForo, contenido: texto, autor: window.__S.nombre, rol_autor: 'estudiante', publicado_en: new Date().toISOString() };
            setData(prev => prev ? { ...prev, mensajes: [...(prev.mensajes || []), nuevoMsg] } : prev);
            const r = await api.post('/estudiante.php', { action: 'publicar_mensaje', idForo, contenido: texto, idEst });
            if (r.status !== 'ok') { setMsgForo(idForo, texto); setAlerta({ type: 'err', msg: r.mensaje || 'Error al enviar' }); }
            else cargar(); // recargar para obtener el mensaje con ID real
          };
          if (!data) return <Spinner />;
          return <div className="page">
            <div className="aula-header">
              <div className="aula-banner">
                <div>
                  <button onClick={onBack} style={{ background: 'rgba(255,255,255,.12)', border: '1px solid rgba(255,255,255,.2)', color: 'rgba(255,255,255,.7)', borderRadius: 6, padding: '4px 10px', cursor: 'pointer', fontSize: 12, marginBottom: 10 }}>← Volver</button>
                  <div className="aula-name">{materia.nombre}</div>
                  <div className="aula-code">{materia.codigo} · Prof. {materia.prof_nombre}</div>
                </div>
              </div>
              <div style={{ padding: '0 24px', borderTop: '1px solid var(--border-lt)' }}>
                <div className="tabs" style={{ borderBottom: 'none', marginBottom: 0 }}>
                  {[{ id: 'materiales', ico: '📄' }, { id: 'actividades', ico: '📋' }, { id: 'foros', ico: '💬' }, { id: 'asistencia', ico: '✅' }, { id: 'videollamadas', ico: '🎥' }].map(t => (
                    <div key={t.id} className={`tab ${tab === t.id ? 'active' : ''}`} onClick={() => setTab(t.id)}>{t.ico} {t.id.charAt(0).toUpperCase() + t.id.slice(1)}</div>
                  ))}
                </div>
              </div>
            </div>
            <Alert type={alerta?.type} msg={alerta?.msg} onClose={() => setAlerta(null)} />
            {/* Materiales */}
            {tab === 'materiales' && <div>
              {(data.materiales || []).length === 0 && <div className="card"><div style={{ padding: 36, textAlign: 'center', color: 'var(--muted)' }}>Sin materiales publicados.</div></div>}
              {(data.materiales || []).map(m => (
                <div key={m.idMaterial} className="card" style={{ margin: '0 0 12px' }}>
                  <div style={{ padding: '14px 18px', display: 'flex', alignItems: 'center', gap: 14 }}>
                    <div style={{ width: 40, height: 40, borderRadius: 9, background: { pdf: '#fdecea', video: '#e8f0fb', presentacion: '#e8f5ee' }[m.tipo] || 'var(--bg)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20 }}>{m.tipo === 'pdf' ? '📄' : m.tipo === 'video' ? '▶️' : '📊'}</div>
                    <div style={{ flex: 1 }}><div style={{ fontWeight: 600, fontSize: 14 }}>{m.titulo}</div><div style={{ fontSize: 12, color: 'var(--muted)' }}>{m.descripcion} · {(m.tamano / 1024 / 1024 || 0).toFixed(1)} MB</div></div>
                    <a href={`/univirtual/api/descargar.php?id=${m.idMaterial}&tipo=material&preview=1`} className="btn btn-primary btn-sm">⬇ Descargar</a>
                  </div>
                </div>
              ))}
            </div>}
            {/* Actividades */}
            {tab === 'actividades' && <div>
              {(data.actividades || []).map(a => {
                const entregado = !!a.idEntrega; const venc = new Date(a.fechaEntrega) < new Date();
                return <div key={a.idActividad} className="card" style={{ margin: '0 0 12px', borderLeft: `4px solid ${materia.color || '#2462b0'}` }}>
                  <div style={{ padding: '16px 18px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                      <div><div style={{ fontWeight: 700, fontSize: 14 }}>{a.titulo}</div><div style={{ fontSize: 12, color: 'var(--muted)', marginTop: 3 }}>Vence: {new Date(a.fechaEntrega).toLocaleDateString('es-CO')} · Puntaje: {a.puntaje_max}</div></div>
                      <div style={{ display: 'flex', gap: 6 }}>
                        {entregado ? <span className="tag tag-ok">{a.nota ? `Nota: ${a.nota}` : '✅ Entregada'}</span> : venc ? <span className="tag tag-err">Vencida</span> : <span className="tag tag-warn">Pendiente</span>}
                      </div>
                    </div>
                    {a.descripcion && <p style={{ fontSize: 13, color: 'var(--text-lt)', marginTop: 8 }}>{a.descripcion}</p>}
                    {a.feedback && <div style={{ marginTop: 8, background: '#e8f5ee', borderRadius: 7, padding: '8px 12px', fontSize: 12 }}><strong>Retroalimentación:</strong> {a.feedback}</div>}
                    {!entregado && !venc && <button className="btn btn-primary btn-sm" style={{ marginTop: 10 }} onClick={() => setModal(a)}>📤 Entregar</button>}
                    {a.enunciado && <a href={`/univirtual/api/descargar.php?id=${a.idActividad}&tipo=enunciado&preview=1`} className="btn btn-outline btn-sm" style={{ marginTop: 10, marginLeft: 6 }} target="_blank">📄 Enunciado</a>}
                  </div>
                </div>;
              })}
            </div>}
            {/* Foros */}
            {tab === 'foros' && <div>
              {(data.foros || []).map(f => (
                <div key={f.idForo} className="card" style={{ margin: '0 0 14px' }}>
                  <div className="card-header"><span className="card-title">💬 {f.titulo}</span><span className="tag tag-blue">{f.total_msgs} mensajes</span></div>
                  <div className="card-body">
                    {(data.mensajes?.filter(m => m.idForo === f.idForo) || []).map((m, i) => (
                      <div key={m.idMensaje ?? `${f.idForo}-${i}`} style={{ display: 'flex', gap: 10, marginBottom: 10, padding: '8px 12px', background: m.rol_autor === 'profesor' ? '#f0f4ff' : 'var(--bg)', borderRadius: 8, borderLeft: `3px solid ${m.rol_autor === 'profesor' ? '#2462b0' : 'var(--border)'}` }}>
                        <div style={{ width: 30, height: 30, borderRadius: '50%', background: m.rol_autor === 'profesor' ? '#2462b0' : 'var(--border)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff', fontSize: 12, fontWeight: 700, flexShrink: 0 }}>{m.autor?.charAt(0)}</div>
                        <div><div style={{ fontSize: 12, fontWeight: 600 }}>{m.autor} <span style={{ color: 'var(--muted)', fontWeight: 400 }}>{m.publicado_en?.slice(0, 10)}</span></div><div style={{ fontSize: 13, marginTop: 3 }}>{m.contenido}</div></div>
                      </div>
                    ))}
                    {f.abierto && <div style={{ display: 'flex', gap: 8, marginTop: 10 }}>
                      <input className="form-input" style={{ flex: 1 }} placeholder="Escribe en el foro..." value={msgs[f.idForo] || ''} onChange={e => setMsgForo(f.idForo, e.target.value)} onKeyDown={e => e.key === 'Enter' && !e.shiftKey && pubMsg(f.idForo)} />
                      <button className="btn btn-primary btn-sm" onClick={() => pubMsg(f.idForo)}>Enviar</button>
                    </div>}
                  </div>
                </div>
              ))}
            </div>}
            {/* Asistencia */}
            {tab === 'asistencia' && <div className="card">
              <div className="card-header"><span className="card-title">✅ Mi Asistencia</span></div>
              <div className="card-body">
                <div style={{ textAlign: 'center', marginBottom: 20 }}>
                  <Ring pct={data.porcentajeAsistencia || 0} color={data.porcentajeAsistencia >= 75 ? '#1a7a48' : '#c0392b'} />
                </div>
                <table className="tbl"><thead><tr><th>Fecha</th><th style={{ textAlign: 'center' }}>Asistencia</th></tr></thead>
                  <tbody>{(data.asistencia || []).slice(0, 10).map((a, i) => <tr key={i}><td>{new Date(a.fecha).toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' })}</td><td style={{ textAlign: 'center' }}>{a.asistio ? <span className="tag tag-ok">✅ Presente</span> : <span className="tag tag-err">❌ Ausente</span>}</td></tr>)}</tbody></table>
              </div>
            </div>}

            {/* ── TAB VIDEOLLAMADAS (estudiante — solo lectura) ───────────────── */}
            {tab === 'videollamadas' && <div>
              <div style={{ marginBottom: 16 }}>
                <div style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)' }}>Clases Virtuales</div>
                <div style={{ fontSize: 12, color: 'var(--muted)', marginTop: 2 }}>Clases programadas por tu profesor vía Jitsi Meet. No requiere instalación.</div>
              </div>

              {vlLista.length === 0 && <div className="card"><div style={{ textAlign: 'center', padding: 40, color: 'var(--muted)' }}>
                <div style={{ fontSize: 36, marginBottom: 12 }}>🎥</div>
                <div style={{ fontWeight: 600, marginBottom: 6 }}>Sin clases virtuales programadas</div>
                <div style={{ fontSize: 13 }}>Tu profesor aún no ha programado videollamadas para esta materia.</div>
              </div></div>}

              {vlLista.map(vl => {
                const enCurso = vl.estado === 'en_curso';
                const finalizada = vl.estado === 'finalizada';
                const badgeTxt = { en_curso: '🟢 En curso ahora', programada: '🔵 Programada', finalizada: '⚫ Finalizada' }[vl.estado];
                const badgeStyle = {
                  en_curso: { background: '#e8f5ee', color: '#1a7a48', border: '1px solid #a8d5b5' },
                  programada: { background: '#eef3fb', color: '#2462b0', border: '1px solid #b8d0f0' },
                  finalizada: { background: '#f4f5f8', color: '#6b7a99', border: '1px solid #dde3f0' },
                }[vl.estado];
                const fecha = new Date(vl.programada_en);
                const fechaFmt = fecha.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                const horaFmt = fecha.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
                return <div key={vl.idVideoLlamada} className="card" style={{ marginBottom: 12, border: enCurso ? '2px solid #1a7a48' : '1px solid var(--border-lt)', position: 'relative', overflow: 'hidden' }}>
                  {enCurso && <div style={{ position: 'absolute', top: 0, left: 0, right: 0, height: 3, background: 'linear-gradient(90deg,#1a7a48,#2ecc71)' }} />}
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 12 }}>
                    <div style={{ flex: 1 }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 6 }}>
                        <span style={{ fontSize: 22 }}>🎥</span>
                        <div>
                          <div style={{ fontWeight: 700, fontSize: 14, color: 'var(--navy)' }}>{vl.titulo}</div>
                          <div style={{ fontSize: 12, color: 'var(--muted)', textTransform: 'capitalize' }}>{fechaFmt} · {horaFmt} · {vl.duracion_min} min</div>
                        </div>
                      </div>
                      {vl.descripcion && <div style={{ fontSize: 12, color: 'var(--muted)', paddingLeft: 32, marginBottom: 6 }}>{vl.descripcion}</div>}
                      <div style={{ paddingLeft: 32 }}>
                        <span style={{ ...badgeStyle, padding: '3px 10px', borderRadius: 20, fontSize: 11, fontWeight: 600 }}>{badgeTxt}</span>
                        {vl.estado === 'programada' && vl.minutosParaInicio > 0 && <span style={{ fontSize: 12, color: 'var(--muted)', marginLeft: 10 }}>Empieza en {vl.minutosParaInicio > 59 ? Math.floor(vl.minutosParaInicio / 60) + 'h ' + vl.minutosParaInicio % 60 + 'min' : vl.minutosParaInicio + ' min'}</span>}
                      </div>
                    </div>
                    {!finalizada && <a
                      href={`https://meet.jit.si/${vl.roomName}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="btn btn-primary btn-sm"
                    >
                      🎥 Unirse a la clase
                    </a>}
                    {(vl.grabaciones||[]).length>0&&<span style={{fontSize:12,color:'#1a7a48',fontWeight:600,marginLeft:10}}>
                      🎞 {vl.grabaciones.length} grabación{vl.grabaciones.length>1?'es':''}
                    </span>}
                  </div>
                </div>;
              })}

              {/* Grabaciones disponibles */}
              {vlLista.some(v => (v.grabaciones||[]).length>0) && <div className="card" style={{marginTop:12}}>
                <div className="card-header"><span className="card-title">🎞 Grabaciones de la materia</span></div>
                <div className="card-body">
                  {vlLista.filter(v=>v.grabaciones&&v.grabaciones.length>0).map(vl=>(
                    <div key={vl.idVideoLlamada} style={{marginBottom:12,paddingBottom:12,borderBottom:'1px solid var(--border-lt)'}}>
                      <div style={{fontWeight:600,fontSize:13,marginBottom:6}}>{vl.titulo}</div>
                      {vl.grabaciones.map((g,gi)=><div key={gi} style={{display:'flex',alignItems:'center',gap:10,padding:'6px 0'}}>
                        <span style={{fontSize:14}}>🎥</span>
                        <div style={{flex:1}}>
                          <div style={{fontSize:12,fontWeight:500}}>{g.nombre||`Grabación ${gi+1}`}</div>
                          <div style={{fontSize:11,color:'var(--muted)'}}>{g.tamano_mb>0?`${g.tamano_mb} MB`:''} · {g.creada_en?.slice(0,10)}</div>
                        </div>
                        <a href={g.urlGrabacion} target="_blank" rel="noopener noreferrer" className="btn btn-outline btn-sm">⬇ Descargar</a>
                      </div>)}
                    </div>
                  ))}
                </div>
              </div>}
            </div>}

            {modal && <Modal title={`📤 Entregar: ${modal.titulo}`} onClose={() => setModal(null)} footer={<><button className="btn btn-outline" onClick={() => setModal(null)}>Cancelar</button><button className="btn btn-primary" onClick={() => entregar(modal)} disabled={saving}>{saving ? 'Enviando...' : '📤 Enviar'}</button></>}>
              <div className="form-group"><label className="form-label">Archivo</label><input type="file" className="form-input" onChange={e => setFile(e.target.files[0])} /></div>
              <div className="form-group"><label className="form-label">Comentario</label><textarea className="form-input" rows={3} value={comment} onChange={e => setComment(e.target.value)} /></div>
            </Modal>}
          </div>;
        };

        // ── NOTIFICACIONES ─────────────────────────────────────────────────────
        const Notificaciones = () => {
          const [data, setData] = useState(null); const { idEst } = window.__S;
          const colors = { material: '#2462b0', tarea: '#e07b39', nota: '#1a7a48', foro: '#6d28d9', sistema: '#c0392b', info: '#888', asistencia: '#d4a843', videollamada: '#0891b2' };
          const icons = { material: '📄', tarea: '📋', nota: '📊', foro: '💬', sistema: '⚙️', asistencia: '✅', videollamada: '🎥', info: '🔔' };

          const cargarNotifs = useCallback(() => {
            api.get(`/estudiante.php?action=notificaciones&idEst=${idEst}`)
              .then(r => r.status === 'ok' && setData(r.data));
          }, [idEst]);

          useEffect(() => {
            cargarNotifs();
            // Polling cada 30s para notificaciones en "tiempo real"
            const interval = setInterval(cargarNotifs, 30000);
            return () => clearInterval(interval);
          }, [cargarNotifs]);

          const handleClick = async (n) => {
            // Marcar como leída esta notificación específica
            if (!n.leida) {
              await api.post('/estudiante.php', { action: 'marcar_notif', idNotif: n.idNotif, idEst });
              setData(prev => prev.map(x => x.idNotif === n.idNotif ? { ...x, leida: 1 } : x));
            }
            // Navegar a la URL de destino si existe
            if (n.url && n.url.trim() && n.url !== '#') {
              window.location.href = n.url;
            }
          };

          if (!data) return <Spinner />;
          const sinLeer = data.filter(n => !n.leida).length;
          return <div className="page">
            <div className="welcome-banner" style={{ padding: '20px 28px', marginBottom: 20 }}>
              <div>
                <div className="wb-label">Sistema</div>
                <div className="wb-name" style={{ fontSize: 22 }}>🔔 Notificaciones</div>
                <div style={{ fontSize: 13, color: 'rgba(255,255,255,.6)', marginTop: 4 }}>
                  Actualización automática cada 30 segundos
                </div>
              </div>
              <div className="wb-stats">
                <div className="wb-stat"><div className="wb-stat-num">{sinLeer}</div><div className="wb-stat-lbl">Sin leer</div></div>
                <div className="wb-stat"><div className="wb-stat-num">{data.length}</div><div className="wb-stat-lbl">Total</div></div>
              </div>
            </div>
            {sinLeer > 0 && <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: 12 }}>
              <button className="btn btn-outline btn-sm" onClick={async () => {
                await api.post('/estudiante.php', { action: 'marcar_notifs', idEst });
                setData(prev => prev.map(n => ({ ...n, leida: 1 })));
              }}>✓ Marcar todas como leídas</button>
            </div>}
            <div className="card">
              <div className="card-body" style={{ padding: '8px 20px' }}>
                {data.length === 0 && <p style={{ textAlign: 'center', padding: 36, color: 'var(--muted)' }}>Sin notificaciones.</p>}
                {data.map((n, i) => (
                  <div key={n.idNotif || i}
                    className="notif-item"
                    onClick={() => handleClick(n)}
                    style={{
                      opacity: n.leida ? 0.7 : 1,
                      cursor: n.url && n.url !== '#' ? 'pointer' : 'default',
                      background: !n.leida ? '#fafbff' : 'transparent',
                      borderRadius: 8,
                      transition: 'background .2s',
                    }}>
                    <div style={{ display: 'flex', gap: 12, alignItems: 'flex-start' }}>
                      <div style={{
                        width: 36, height: 36, borderRadius: 9,
                        background: `${colors[n.tipo] || '#888'}20`,
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        fontSize: 18, flexShrink: 0
                      }}>
                        {icons[n.tipo] || '🔔'}
                      </div>
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <div className="notif-title" style={{ fontWeight: n.leida ? 500 : 700 }}>{n.titulo}</div>
                        <div className="notif-msg">{n.mensaje}</div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginTop: 4 }}>
                          <div className="notif-time">{n.creado_en?.slice(0, 16).replace('T', ' ')}</div>
                          {n.url && n.url !== '#' && <span style={{ fontSize: 11, color: '#2462b0' }}>→ Ver detalle</span>}
                        </div>
                      </div>
                      {!n.leida && <div style={{
                        width: 9, height: 9, borderRadius: '50%',
                        background: colors[n.tipo] || '#2462b0', marginTop: 8, flexShrink: 0
                      }} />}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>;
        };

        // ── CONFIGURACIÓN ──────────────────────────────────────────────────────
        const Configuracion = ({ rol }) => {
          const u = window.__S;
          const { idEst } = u;
          const [form, setForm] = useState({ nombre: u.nombre || '' });
          const [pwd, setPwd] = useState({ actual: '', nueva: '', confirmar: '' });
          const [tab, setTab] = useState('perfil');
          const [msg, setMsg] = useState(null);
          const [saving, setSaving] = useState(false);
          const guardarPerfil = async () => {
            if (!form.nombre.trim()) return setMsg({ type: 'err', txt: 'El nombre no puede estar vacío' });
            setSaving(true);
            const r = await api.post('/estudiante.php', { action: 'actualizar_perfil', nombre: form.nombre, idEst });
            setSaving(false);
            setMsg(r.status === 'ok' ? { type: 'ok', txt: 'Perfil actualizado' } : { type: 'err', txt: r.mensaje || 'Error al guardar' });
            if (r.status === 'ok') window.__S.nombre = form.nombre;
          };
          const cambiarPassword = async () => {
            if (pwd.nueva.length < 6) return setMsg({ type: 'err', txt: 'Mínimo 6 caracteres' });
            if (pwd.nueva !== pwd.confirmar) return setMsg({ type: 'err', txt: 'Las contraseñas no coinciden' });
            if (!pwd.actual) return setMsg({ type: 'err', txt: 'Ingresa tu contraseña actual' });
            setSaving(true);
            const r = await api.post('/estudiante.php', { action: 'cambiar_password', actual: pwd.actual, nueva: pwd.nueva, idEst });
            setSaving(false);
            if (r.status === 'ok') { setPwd({ actual: '', nueva: '', confirmar: '' }); setMsg({ type: 'ok', txt: 'Contraseña actualizada' }); }
            else setMsg({ type: 'err', txt: r.mensaje || 'Error' });
          };
          return <div className="page">
            <div className="welcome-banner" style={{ padding: '20px 28px', marginBottom: 20 }}>
              <div><div className="wb-label">Cuenta</div><div className="wb-name" style={{ fontSize: 22 }}>⚙️ Configuración</div></div>
            </div>
            <div style={{ display: 'flex', gap: 8, marginBottom: 16 }}>
              {[['perfil', '👤 Perfil'], ['password', '🔑 Contraseña']].map(([id, lbl]) => (
                <button key={id} onClick={() => { setTab(id); setMsg(null); }} style={{
                  padding: '8px 18px', borderRadius: 20, border: 'none', cursor: 'pointer', fontSize: 13, fontWeight: 600,
                  background: tab === id ? '#0d1f4e' : '#fff', color: tab === id ? '#fff' : '#4a5568',
                  boxShadow: '0 1px 4px rgba(0,0,0,.08)'
                }}>
                  {lbl}
                </button>
              ))}
            </div>
            {msg && <div style={{
              padding: '10px 16px', borderRadius: 8, marginBottom: 16, fontSize: 13,
              background: msg.type === 'ok' ? '#e8f5ee' : '#fdecea',
              color: msg.type === 'ok' ? '#1a7a48' : '#c0392b',
              border: `1px solid ${msg.type === 'ok' ? '#a8d5b5' : '#f5a7a7'}`
            }}>
              {msg.type === 'ok' ? '✅' : '❌'} {msg.txt}
            </div>}
            {tab === 'perfil' && <div className="card">
              <div className="card-header"><span className="card-title">👤 Información del Perfil</span></div>
              <div className="card-body">
                <div style={{ display: 'flex', alignItems: 'center', gap: 20, padding: '16px 0', borderBottom: '1px solid var(--border-lt)', marginBottom: 20 }}>
                  <div style={{ width: 72, height: 72, borderRadius: '50%', background: 'linear-gradient(135deg,#0d1f4e,#1a7a48)', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 26, fontWeight: 700 }}>
                    {u.initials}
                  </div>
                  <div>
                    <div style={{ fontSize: 18, fontWeight: 700, color: 'var(--navy)' }}>{u.nombre}</div>
                    <div style={{ fontSize: 13, color: 'var(--muted)', marginTop: 2 }}>{u.semestre || ''} · {u.programa || ''}</div>
                    <span style={{ display: 'inline-block', marginTop: 6, padding: '2px 10px', borderRadius: 12, fontSize: 11, fontWeight: 600, background: '#e8f5ee', color: '#1a7a48' }}>Estudiante</span>
                  </div>
                </div>
                <div className="form-group">
                  <label className="form-label">Nombre completo</label>
                  <input className="form-input" value={form.nombre} onChange={e => setForm({ ...form, nombre: e.target.value })} />
                </div>
                <button className="btn btn-primary" onClick={guardarPerfil} disabled={saving}>
                  {saving ? 'Guardando...' : '💾 Guardar cambios'}
                </button>
              </div>
            </div>}
            {tab === 'password' && <div className="card">
              <div className="card-header"><span className="card-title">🔑 Cambiar Contraseña</span></div>
              <div className="card-body">
                {[['actual', 'Contraseña actual'], ['nueva', 'Nueva contraseña'], ['confirmar', 'Confirmar nueva contraseña']].map(([k, lbl]) => (
                  <div key={k} className="form-group">
                    <label className="form-label">{lbl}</label>
                    <input className="form-input" type="password" value={pwd[k]} onChange={e => setPwd({ ...pwd, [k]: e.target.value })} />
                  </div>
                ))}
                <button className="btn btn-primary" onClick={cambiarPassword} disabled={saving}>
                  {saving ? 'Cambiando...' : '🔑 Cambiar contraseña'}
                </button>
              </div>
            </div>}
          </div>;
        };

        // ── APP ESTUDIANTE ─────────────────────────────────────────────────────
        const EstudianteApp = () => {
          const q = new URLSearchParams(window.location.search);
          const [vista, setVista] = useState(q.get('view') || 'dashboard');
          const [aulaActiva, setAulaActiva] = useState(null); const [badge, setBadge] = useState({ tareas: 0, notifs: 0 });

          const { nombre, initials, idEst } = window.__S;
          // Cargar badges
          useEffect(() => { api.get(`/estudiante.php?action=dashboard&idEst=${idEst}`).then(r => { if (r.status === 'ok') { const d = r.data; setBadge({ tareas: d.estadisticas?.tareasProximas || 0, notifs: d.notificaciones?.filter(n => !n.leida).length || 0 }); } }); }, []);
          const irAula = m => { if (m) { setAulaActiva(m); setVista('aula'); } else setVista('materias'); };
          const nav = [{ id: 'dashboard', ico: '📊', lbl: 'Inicio' }, { id: 'materias', ico: '📚', lbl: 'Mis Materias' }, { id: 'tareas', ico: '📋', lbl: 'Tareas', badge: badge.tareas }, { id: 'calificaciones', ico: '📊', lbl: 'Calificaciones' }, { id: 'notificaciones', ico: '🔔', lbl: 'Notificaciones', badge: badge.notifs }, { id: 'configuracion', ico: '⚙️', lbl: 'Configuración' }];
          const titulo = { dashboard: 'Panel del Estudiante', materias: 'Mis Materias', tareas: 'Tareas', calificaciones: 'Calificaciones', notificaciones: 'Notificaciones', aula: aulaActiva?.nombre || 'Aula Virtual' }[vista] || '';
          return <>
            <aside className="sidebar">
              <div className="sb-brand"><div className="sb-brand-name">UNI<em>VIRTUAL</em></div><div className="sb-brand-sub">Plataforma Académica</div></div>
              <div className="sb-user"><div className="sb-avatar">{initials}</div><div><div className="sb-user-name">{nombre.split(' ').slice(0, 2).join(' ')}</div><div className="sb-user-role">Estudiante</div></div></div>
              <div className="sb-section">Principal</div>
              <nav className="sb-nav">
                {nav.map(n => <div key={n.id} className={`sb-item ${vista === n.id ? 'active' : ''}`} onClick={() => { setVista(n.id); setAulaActiva(null); }}>
                  <span className="sb-item-ico">{n.ico}</span>
                  <span className="sb-item-lbl">{n.lbl}</span>
                  {n.badge > 0 && <span className="sb-badge">{n.badge}</span>}
                </div>)}
              </nav>
              <div className="sb-logout"><a href="/univirtual/pages/auth/logout.php" className="sb-logout-btn"><span className="sb-logout-ico">↩</span><span>Cerrar sesión</span></a></div>
            </aside>
            <header className="topbar">
              <div className="tb-left">
                <div className="tb-breadcrumb"><span>UNI-VIRTUAL</span><span>›</span><strong>Estudiante</strong>{vista === 'aula' && <><span>›</span><strong>{aulaActiva?.nombre}</strong></>}</div>
                <div className="tb-title">{titulo}</div>
              </div>
              <div className="tb-right"><span className="tb-semester">2026-I</span><div className="tb-notif" style={{ cursor: 'pointer' }} onClick={() => setVista('notificaciones')}><span>🔔</span>{badge.notifs > 0 && <div className="tb-notif-dot" />}</div>
                <div style={{ width: 32, height: 32, borderRadius: '50%', background: '#1a7a48', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700, fontSize: 13, cursor: 'pointer', marginLeft: 8 }} onClick={() => setVista('configuracion')}>{window.__S.initials}</div>
              </div>
            </header>
            <main className="main">
              {vista === 'dashboard' && <Dashboard onMat={irAula} onNotifs={() => setVista('notificaciones')} />}
              {vista === 'materias' && <MisMaterias onAula={m => { setAulaActiva(m); setVista('aula'); }} />}
              {vista === 'tareas' && <Tareas />}
              {vista === 'calificaciones' && <Calificaciones />}
              {vista === 'notificaciones' && <Notificaciones />}
              {vista === 'configuracion' && <Configuracion rol="estudiante" />}
              {vista === 'aula' && aulaActiva && <AulaEst materia={aulaActiva} onBack={() => setVista('materias')} />}
            </main>
          </>;
        };
        ReactDOM.createRoot(document.getElementById('root')).render(<EstudianteApp />);
      </script>
    </body>

    </html>
    <?php
  }
}
(new EstudianteView())->render();