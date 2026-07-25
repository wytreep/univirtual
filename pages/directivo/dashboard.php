<?php
/**
 * DirectivoView — pages/directivo/dashboard.php
 * SCRUM-121: Configuración de perfil
 * SCRUM-128: Fix — eliminadas dependencias de tablas directivos/facultades
 *            que no existen en el schema. Componentes Profesores,
 *            Estudiantes y MateriasFacultad implementados.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/View.php';

class DirectivoView extends View {

    public function __construct() {
        parent::__construct('directivo');
    }

    public function render(): void {
        $sJS = $this->sessionJS();
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>UNI-VIRTUAL — Panel Directivo</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/panel.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
</head>
<body>
  <div id="root"></div>

  <?= $this->reactCDN() ?>

  <script>
    window.__S = <?= $sJS ?>;
  </script>

  <script type="text/babel">
    const { useState, useEffect } = React;
    const S   = window.__S;
    const API = (path) => `<?= BASE_URL ?>/api/v1/${path}`;

    // ── Spinner ──────────────────────────────────────────────────────
    function Spinner() {
      return <div className="spinner"></div>;
    }

    // ── Configuración (SCRUM-121) ────────────────────────────────────
    function Configuracion() {
      const [tab, setTab]         = useState('perfil');
      const [nombre, setNombre]   = useState(S.nombre);
      const [passActual, setPA]   = useState('');
      const [passNueva, setPN]    = useState('');
      const [passCfm, setPC]      = useState('');
      const [msg, setMsg]         = useState(null);
      const [loading, setLoading] = useState(false);

      const guardarPerfil = async () => {
        setLoading(true); setMsg(null);
        try {
          const res  = await fetch(API('directivo.php'), {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'actualizar_perfil', nombre }),
          });
          const data = await res.json();
          setMsg({ ok: data.status === 'ok', text: data.mensaje || 'Perfil actualizado' });
          if (data.status === 'ok') S.nombre = nombre;
        } catch(e) {
          setMsg({ ok: false, text: 'Error de conexión' });
        }
        setLoading(false);
      };

      const cambiarPassword = async () => {
        if (passNueva !== passCfm) {
          setMsg({ ok: false, text: 'Las contraseñas no coinciden.' }); return;
        }
        if (passNueva.length < 6) {
          setMsg({ ok: false, text: 'Mínimo 6 caracteres.' }); return;
        }
        setLoading(true); setMsg(null);
        try {
          const res  = await fetch(API('directivo.php'), {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'cambiar_password',
              actual: passActual,
              nueva:  passNueva,
            }),
          });
          const data = await res.json();
          setMsg({ ok: data.status === 'ok', text: data.mensaje || 'Contraseña actualizada' });
          if (data.status === 'ok') { setPA(''); setPN(''); setPC(''); }
        } catch(e) {
          setMsg({ ok: false, text: 'Error de conexión' });
        }
        setLoading(false);
      };

      return (
        <div className="page">
          <div className="welcome-banner">
            <h1>⚙️ Configuración</h1>
          </div>
          <div className="card" style={{maxWidth:'520px'}}>
            <div className="tabs">
              {['perfil','contraseña'].map(t => (
                <button key={t}
                  className={`tab ${tab===t?'active':''}`}
                  onClick={() => { setTab(t); setMsg(null); }}>
                  {t==='perfil'?'👤 Perfil':'🔑 Contraseña'}
                </button>
              ))}
            </div>

            {msg && (
              <div className={`alert ${msg.ok?'alert-success':'alert-error'}`}
                   style={{margin:'16px 0'}}>
                {msg.text}
              </div>
            )}

            {tab === 'perfil' && (
              <div style={{padding:'16px 0'}}>
                <div className="form-group">
                  <label className="form-label">Nombre completo</label>
                  <input className="form-input" value={nombre}
                    onChange={e => setNombre(e.target.value)} />
                </div>
                <div className="form-group" style={{marginTop:'12px'}}>
                  <label className="form-label">Email (no editable)</label>
                  <input className="form-input" value={S.email}
                    disabled style={{opacity:0.6}} />
                </div>
                <button className="btn btn-primary"
                  onClick={guardarPerfil}
                  disabled={loading || !nombre.trim()}
                  style={{marginTop:'16px'}}>
                  {loading ? 'Guardando...' : 'Guardar cambios'}
                </button>
              </div>
            )}

            {tab === 'contraseña' && (
              <div style={{padding:'16px 0'}}>
                {[
                  ['Contraseña actual', passActual, setPA],
                  ['Nueva contraseña (mín. 6 caracteres)', passNueva, setPN],
                  ['Confirmar contraseña', passCfm, setPC],
                ].map(([label, val, setter]) => (
                  <div key={label} className="form-group" style={{marginBottom:'12px'}}>
                    <label className="form-label">{label}</label>
                    <input className="form-input" type="password" value={val}
                      onChange={e => setter(e.target.value)} />
                  </div>
                ))}
                <button className="btn btn-primary"
                  onClick={cambiarPassword}
                  disabled={loading || !passActual || !passNueva || !passCfm}
                  style={{marginTop:'8px'}}>
                  {loading ? 'Cambiando...' : 'Cambiar contraseña'}
                </button>
              </div>
            )}
          </div>
        </div>
      );
    }

    // ── Resumen ──────────────────────────────────────────────────────
    function ResumenFacultad() {
      const [stats, setStats] = useState(null);

      useEffect(() => {
        fetch(API('directivo.php?action=resumen'), { credentials: 'include' })
          .then(r => r.json())
          .then(d => {
            if (d.status === 'ok') setStats(d.data);
          })
          .catch(() => setStats({}));
      }, []);

      if (!stats) return <Spinner/>;

      // directivo.php?action=resumen devuelve campos directos
      const items = [
        { label: 'Profesores',      value: stats.totalProfesores  ?? '—', icon: '👨‍🏫' },
        { label: 'Estudiantes',     value: stats.totalEstudiantes ?? '—', icon: '👩‍🎓' },
        { label: 'Materias',        value: stats.totalMaterias    ?? '—', icon: '📚' },
        { label: 'Tasa aprobación', value: `${stats.tasaAprobacion ?? 0}%`, icon: '✅' },
      ];

      return (
        <div className="page">
          <div className="welcome-banner">
            <h1>📊 Resumen — Facultad de Ingeniería</h1>
          </div>
          <div className="stat-grid-6" style={{gridTemplateColumns:'repeat(auto-fit,minmax(180px,1fr))'}}>
            {items.map(s => (
              <div key={s.label} className="card" style={{textAlign:'center',padding:'24px 16px'}}>
                <div style={{fontSize:'2rem',marginBottom:'8px'}}>{s.icon}</div>
                <div style={{fontSize:'2rem',fontWeight:700,color:'var(--navy)'}}>{s.value}</div>
                <div style={{fontSize:'0.9rem',color:'var(--muted)',marginTop:'4px'}}>{s.label}</div>
              </div>
            ))}
          </div>
        </div>
      );
    }

    // ── Profesores ───────────────────────────────────────────────────
    function Profesores() {
      const [lista, setLista] = useState(null);

      useEffect(() => {
        fetch(API('directivo.php?action=profesores'), { credentials: 'include' })
          .then(r => r.json())
          .then(d => setLista(d.data || []))
          .catch(() => setLista([]));
      }, []);

      if (!lista) return <Spinner/>;

      return (
        <div className="page">
          <div className="welcome-banner">
            <h1>👨‍🏫 Profesores</h1>
            <span className="badge">{lista.length} registrados</span>
          </div>
          <div className="card">
            <table className="tbl">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Email</th>
                  <th>Código</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                {lista.length === 0 && (
                  <tr><td colSpan="4" style={{textAlign:'center',color:'var(--muted)'}}>
                    Sin profesores registrados
                  </td></tr>
                )}
                {lista.map(p => (
                  <tr key={p.idUsuario}>
                    <td>{p.nombre}</td>
                    <td>{p.email}</td>
                    <td>{p.codigoProf || '—'}</td>
                    <td>
                      <span className={`badge ${p.activo==1?'badge-success':'badge-danger'}`}>
                        {p.activo==1?'Activo':'Inactivo'}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      );
    }

    // ── Estudiantes ──────────────────────────────────────────────────
    function Estudiantes() {
      const [lista, setLista] = useState(null);

      useEffect(() => {
        fetch(API('directivo.php?action=estudiantes'), { credentials: 'include' })
          .then(r => r.json())
          .then(d => setLista(d.data || []))
          .catch(() => setLista([]));
      }, []);

      if (!lista) return <Spinner/>;

      return (
        <div className="page">
          <div className="welcome-banner">
            <h1>👩‍🎓 Estudiantes</h1>
            <span className="badge">{lista.length} registrados</span>
          </div>
          <div className="card">
            <table className="tbl">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Email</th>
                  <th>Código</th>
                  <th>Semestre</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                {lista.length === 0 && (
                  <tr><td colSpan="5" style={{textAlign:'center',color:'var(--muted)'}}>
                    Sin estudiantes registrados
                  </td></tr>
                )}
                {lista.map(e => (
                  <tr key={e.idUsuario}>
                    <td>{e.nombre}</td>
                    <td>{e.email}</td>
                    <td>{e.codigoEst || '—'}</td>
                    <td>{e.semestre || '—'}</td>
                    <td>
                      <span className={`badge ${e.activo==1?'badge-success':'badge-danger'}`}>
                        {e.activo==1?'Activo':'Inactivo'}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      );
    }

    // ── Materias ─────────────────────────────────────────────────────
    function MateriasFacultad() {
      const [lista, setLista] = useState(null);

      useEffect(() => {
        fetch(API('directivo.php?action=materias'), { credentials: 'include' })
          .then(r => r.json())
          .then(d => setLista(d.data || []))
          .catch(() => setLista([]));
      }, []);

      if (!lista) return <Spinner/>;

      return (
        <div className="page">
          <div className="welcome-banner">
            <h1>📚 Materias</h1>
            <span className="badge">{lista.length} activas</span>
          </div>
          <div className="card">
            <table className="tbl">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Código</th>
                  <th>Créditos</th>
                  <th>Profesor</th>
                  <th>Inscritos</th>
                </tr>
              </thead>
              <tbody>
                {lista.length === 0 && (
                  <tr><td colSpan="5" style={{textAlign:'center',color:'var(--muted)'}}>
                    Sin materias registradas
                  </td></tr>
                )}
                {lista.map(m => (
                  <tr key={m.idMateria}>
                    <td>
                      <span style={{
                        display:'inline-block',
                        width:'10px',height:'10px',
                        borderRadius:'50%',
                        background: m.color || '#ccc',
                        marginRight:'8px'
                      }}></span>
                      {m.nombre}
                    </td>
                    <td>{m.codigo}</td>
                    <td>{m.creditos}</td>
                    <td>{m.profesor || '—'}</td>
                    <td>{m.totalInscritos ?? '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      );
    }

    // ── App principal ────────────────────────────────────────────────
    function DirectivoApp() {
      const [vista, setVista] = useState('resumen');
      const { nombre, initials } = window.__S;

      const nav = [
        { id: 'resumen',       ico: '📊',  lbl: 'Resumen'       },
        { id: 'profesores',    ico: '👨‍🏫', lbl: 'Profesores'    },
        { id: 'estudiantes',   ico: '👩‍🎓', lbl: 'Estudiantes'   },
        { id: 'materias',      ico: '📚',  lbl: 'Materias'       },
        { id: 'configuracion', ico: '⚙️',  lbl: 'Configuración'  },
      ];

      const titulo = nav.find(n => n.id === vista)?.lbl || '';

      return (
        <>
          <aside className="sidebar">
            <div className="sb-brand">
              <div className="sb-brand-name">UNI<em>VIRTUAL</em></div>
              <div className="sb-brand-sub">Plataforma Académica</div>
            </div>
            <div className="sb-user">
              <div className="sb-avatar">{initials}</div>
              <div>
                <div className="sb-user-name">
                  {nombre ? nombre.split(' ').slice(0,2).join(' ') : 'Directivo'}
                </div>
                <div className="sb-user-role">Directivo</div>
              </div>
            </div>
            <div className="sb-section">Principal</div>
            <nav className="sb-nav">
              {nav.map(n => (
                <div key={n.id}
                     className={`sb-item ${vista === n.id ? 'active' : ''}`}
                     onClick={() => setVista(n.id)}>
                  <span className="sb-item-ico">{n.ico}</span>
                  <span className="sb-item-lbl">{n.lbl}</span>
                </div>
              ))}
            </nav>
            <div className="sb-logout">
              <a href="<?= BASE_URL ?>/pages/auth/logout.php"
                 className="sb-logout-btn">
                <span className="sb-logout-ico">↩</span>
                <span>Cerrar sesión</span>
              </a>
            </div>
          </aside>

          <header className="topbar">
            <div className="tb-left">
              <div className="tb-breadcrumb">
                <span>UNI-VIRTUAL</span>
                <span>›</span>
                <strong>Directivo</strong>
              </div>
              <div className="tb-title">{titulo}</div>
            </div>
            <div className="tb-right">
              <span className="tb-semester">2026-I</span>
              <div className="tb-notif"><span>🔔</span></div>
            </div>
          </header>

          <main className="main">
            {vista === 'resumen'       && <ResumenFacultad/>}
            {vista === 'profesores'    && <Profesores/>}
            {vista === 'estudiantes'   && <Estudiantes/>}
            {vista === 'materias'      && <MateriasFacultad/>}
            {vista === 'configuracion' && <Configuracion/>}
          </main>
        </>
      );
    }

    ReactDOM.createRoot(document.getElementById('root')).render(<DirectivoApp/>);
  </script>
</body>
</html>
<?php
    }
}

(new DirectivoView())->render();