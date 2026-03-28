<?php
/**
 * DirectivoView — pages/directivo/dashboard.php
 * 
 * SCRUM-121: Agrega sección Configuración al panel del directivo.
 * SCRUM-123: Extiende la clase abstracta View.
 * 
 * El componente React Configuracion es idéntico al del estudiante,
 * pero llama a /api/v1/directivo.php (si existe) o al endpoint genérico
 * de usuarios /api/v1/usuarios.php?action=actualizar_perfil_propio.
 */

require_once __DIR__ . '/../../includes/View.php';

class DirectivoView extends View {

    private int    $idDirectivo;
    private int    $idFacultad;
    private string $facultadNombre;

    public function __construct() {
        parent::__construct('directivo');

        // Cargar datos del directivo
        $stmt = db()->prepare(
            'SELECT d.idDirectivo, d.idFacultad, f.nombre AS facultad
             FROM directivos d
             JOIN facultades f ON f.idFacultad = d.idFacultad
             WHERE d.idUsuario = ?'
        );
        $stmt->execute([$this->idUsuario]);
        $row = $stmt->fetch();

        $this->idDirectivo    = $row ? (int)$row['idDirectivo']    : 0;
        $this->idFacultad     = $row ? (int)$row['idFacultad']     : 0;
        $this->facultadNombre = $row ? $row['facultad']             : 'Sin facultad';
    }

    protected function sessionExtra(): array {
        return [
            'idDirectivo'    => $this->idDirectivo,
            'idFacultad'     => $this->idFacultad,
            'facultadNombre' => $this->facultadNombre,
        ];
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
  <script src="https://unpkg.com/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <script>
    window.__S = <?= $sJS ?>;
  </script>

  <script type="text/babel">
    const { useState, useEffect } = React;
    const S = window.__S;
    const API = (path) => `<?= BASE_URL ?>/api/v1/${path}`;

    // ── Componente Configuración (reutilizado — SCRUM-121) ──────────
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
        const res = await fetch(API('directivo.php'), {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'actualizar_perfil', nombre }),
        });
        const data = await res.json();
        setMsg({ ok: data.status === 'ok', text: data.mensaje || 'Perfil actualizado' });
        if (data.ok) S.nombre = nombre;
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
        const res = await fetch(API('directivo.php'), {
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
        if (data.ok) { setPA(''); setPN(''); setPC(''); }
        setLoading(false);
      };

      return (
        <div className="panel-section">
          <h2 className="section-title">⚙️ Configuración</h2>
          <div className="tabs-nav" style={{marginBottom:'24px'}}>
            {['perfil','contraseña'].map(t => (
              <button key={t}
                className={`tab-btn ${tab===t?'active':''}`}
                onClick={() => { setTab(t); setMsg(null); }}>
                {t==='perfil'?'👤 Perfil':'🔑 Contraseña'}
              </button>
            ))}
          </div>

          {msg && (
            <div className={`alert ${msg.ok?'alert-success':'alert-error'}`}
                 style={{marginBottom:'16px'}}>
              {msg.text}
            </div>
          )}

          {tab === 'perfil' && (
            <div style={{maxWidth:'400px'}}>
              <label className="form-label">Nombre completo</label>
              <input className="form-input" value={nombre}
                onChange={e => setNombre(e.target.value)} />
              <label className="form-label" style={{marginTop:'12px'}}>
                Facultad (no editable)
              </label>
              <input className="form-input" value={S.facultadNombre}
                disabled style={{opacity:0.6}} />
              <button className="btn-primary"
                onClick={guardarPerfil}
                disabled={loading || !nombre.trim()}
                style={{marginTop:'16px'}}>
                {loading ? 'Guardando...' : 'Guardar cambios'}
              </button>
            </div>
          )}

          {tab === 'contraseña' && (
            <div style={{maxWidth:'400px'}}>
              {[
                ['Contraseña actual', passActual, setPA],
                ['Nueva contraseña (mín. 6 caracteres)', passNueva, setPN],
                ['Confirmar contraseña', passCfm, setPC],
              ].map(([label, val, setter]) => (
                <div key={label} style={{marginBottom:'12px'}}>
                  <label className="form-label">{label}</label>
                  <input className="form-input" type="password" value={val}
                    onChange={e => setter(e.target.value)} />
                </div>
              ))}
              <button className="btn-primary"
                onClick={cambiarPassword}
                disabled={loading || !passActual || !passNueva || !passCfm}
                style={{marginTop:'8px'}}>
                {loading ? 'Cambiando...' : 'Cambiar contraseña'}
              </button>
            </div>
          )}
        </div>
      );
    }

    // ── Panel de estadísticas de facultad ────────────────────────────
    function ResumenFacultad() {
      const [stats, setStats] = useState(null);

      useEffect(() => {
        fetch(API(`stats.php?action=facultad&idFacultad=${S.idFacultad}`), {
          credentials: 'include'
        })
        .then(r => r.json())
        .then(d => setStats(d.data));
      }, []);

      if (!stats) return <div className="loading">Cargando estadísticas...</div>;

      return (
        <div className="panel-section">
          <h2 className="section-title">📊 Resumen — {S.facultadNombre}</h2>
          <div className="stats-grid">
            {[
              { label: 'Profesores',  value: stats.totalProfesores,  icon: '👨‍🏫' },
              { label: 'Estudiantes', value: stats.totalEstudiantes, icon: '👩‍🎓' },
              { label: 'Materias',    value: stats.totalMaterias,    icon: '📚' },
              { label: 'Tasa aprobación', value: `${stats.tasaAprobacion ?? 0}%`, icon: '✅' },
            ].map(s => (
              <div key={s.label} className="stat-card">
                <span className="stat-icon">{s.icon}</span>
                <span className="stat-value">{s.value}</span>
                <span className="stat-label">{s.label}</span>
              </div>
            ))}
          </div>
        </div>
      );
    }

    // ── App principal del Directivo ──────────────────────────────────
    function DirectivoApp() {
      const [vista, setVista] = useState('resumen');
      const { nombre, initials } = window.__S;

      const nav = [
        {id:'resumen',       ico:'📊', lbl:'Resumen'},
        {id:'profesores',    ico:'👨‍🏫',lbl:'Profesores'},
        {id:'estudiantes',   ico:'👩‍🎓',lbl:'Estudiantes'},
        {id:'materias',      ico:'📚', lbl:'Materias'},
        {id:'configuracion', ico:'⚙️', lbl:'Configuración'},
      ];
      const titulo = nav.find(n=>n.id===vista)?.lbl || '';

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
                <div className="sb-user-name">{nombre.split(' ').slice(0,2).join(' ')}</div>
                <div className="sb-user-role">Directivo</div>
              </div>
            </div>
            <div className="sb-section">Principal</div>
            <nav className="sb-nav">
              {nav.map(n=>(
                <div key={n.id}
                     className={`sb-item ${vista===n.id?'active':''}`}
                     onClick={()=>setVista(n.id)}>
                  <span className="sb-item-ico">{n.ico}</span>
                  <span className="sb-item-lbl">{n.lbl}</span>
                </div>
              ))}
            </nav>
            <div className="sb-logout">
              <a href="/univirtual/pages/auth/logout.php"
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
              <div className="tb-notif">
                <span>🔔</span>
              </div>
            </div>
          </header>

          <main className="main">
            {vista==='resumen'       && <ResumenFacultad/>}
            {vista==='profesores'    && <Profesores/>}
            {vista==='estudiantes'   && <Estudiantes/>}
            {vista==='materias'      && <MateriasFacultad/>}
            {vista==='configuracion' && <Configuracion rol="directivo"/>}
          </main>
        </>
      );
    }

    ReactDOM.createRoot(document.getElementById('root')).render(<DirectivoApp />);
  </script>
</body>
</html>
<?php
    } // end render()
} // end class DirectivoView

(new DirectivoView())->render();