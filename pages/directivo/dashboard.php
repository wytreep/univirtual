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

    /**
     * UV-31: expone la facultad y el periodo vigente al frontend (window.__S).
     */
    protected function sessionExtra(): array {
        $facultad = [
            'idFacultad'  => 0,
            'nombre'      => 'Sin facultad asignada',
            'codigo'      => '—',
            'descripcion' => '',
            'cargo'       => '',
        ];

        try {
            $stmt = db()->prepare(
                "SELECT d.idFacultad, d.cargo, f.nombre, f.codigo, f.descripcion
                 FROM directivos d
                 JOIN facultades f ON f.idFacultad = d.idFacultad
                 WHERE d.idUsuario = ?"
            );
            $stmt->execute([$this->idUsuario]);
            $row = $stmt->fetch();
            if ($row) {
                $facultad = [
                    'idFacultad'  => (int)$row['idFacultad'],
                    'nombre'      => $row['nombre'],
                    'codigo'      => $row['codigo'],
                    'descripcion' => $row['descripcion'],
                    'cargo'       => $row['cargo'],
                ];
            }
        } catch (Throwable $e) {
            // La vista sigue funcionando aunque falle la consulta de facultad.
        }

        return [
            'facultad' => $facultad,
            'periodo'  => periodoAcademico(),
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
</head>
<body>
  <div id="root"></div>

  <?= $this->reactCDN() ?>

  <script>
    window.__S = <?= $sJS ?>;
  </script>

  <script type="text/babel">
    const { useState, useEffect, useRef } = React;
    const S   = window.__S;
    const API = (path) => `<?= BASE_URL ?>/api/v1/${path}`;

    // ── Spinner ──────────────────────────────────────────────────────
    function Spinner() {
      return <div className="spinner"></div>;
    }

    // ── Gráfica reutilizable (Chart.js) ──────────────────────────────
    function ChartCanvas({ type, data, options = {} }) {
      const canvasRef = useRef();
      const chartRef  = useRef();

      useEffect(() => {
        if (!canvasRef.current || !data) return;
        if (chartRef.current) { chartRef.current.destroy(); chartRef.current = null; }
        chartRef.current = new Chart(canvasRef.current, {
          type,
          data,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { font: { family: 'DM Sans', size: 11 } } } },
            ...options,
          },
        });
        return () => {
          if (chartRef.current) { chartRef.current.destroy(); chartRef.current = null; }
        };
      }, [data]);

      return <canvas ref={canvasRef} style={{ width: '100%', height: '100%' }}></canvas>;
    }

    const fmtNota = (v) => (v === null || v === undefined) ? '—' : Number(v).toFixed(2);
    const fmtPct  = (v) => (v === null || v === undefined) ? '—' : `${Number(v).toFixed(1)}%`;

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

      const facultad = stats.facultad || S.facultad || {};
      const periodo  = stats.periodo  || S.periodo  || {};

      // directivo.php?action=resumen devuelve los KPI ya segmentados
      const items = [
        { label: 'Profesores',       value: stats.totalProfesores    ?? '—', icon: '👨‍🏫' },
        { label: 'Estudiantes',      value: stats.totalEstudiantes   ?? '—', icon: '👩‍🎓' },
        { label: 'Materias',         value: stats.totalMaterias      ?? '—', icon: '📚' },
        { label: 'Carreras',         value: stats.totalCarreras      ?? '—', icon: '🎓' },
        { label: 'Inscripciones',    value: stats.totalInscripciones ?? '—', icon: '📝' },
        { label: 'Tasa aprobación',  value: `${stats.tasaAprobacion ?? 0}%`, icon: '✅' },
        { label: 'Promedio general', value: fmtNota(stats.promedioGeneral), icon: '📈' },
      ];

      return (
        <div className="page">
          <div className="welcome-banner">
            <div>
              <div className="wb-label">Panel del directivo</div>
              <h1>📊 {facultad.nombre || 'Mi facultad'}</h1>
              <div className="wb-sub">
                {periodo.etiqueta || '—'} · Semana {periodo.semanaActual ?? '—'} de {periodo.totalSemanas ?? '—'}
                {periodo.inicio && periodo.fin ? ` · ${periodo.inicio} a ${periodo.fin}` : ''}
              </div>
            </div>
            <span className="badge">{facultad.codigo || '—'}</span>
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

    // ── Reportes (UV-31) ─────────────────────────────────────────────
    function ReportesFacultad() {
      const [data, setData] = useState(null);

      useEffect(() => {
        fetch(API('directivo.php?action=reportes'), { credentials: 'include' })
          .then(r => r.json())
          .then(d => setData(d.status === 'ok' ? d.data : null))
          .catch(() => setData(null));
      }, []);

      if (!data) return <Spinner/>;

      const { facultad, periodo, resumen, carreras, materias, profesores,
              distribucionNotas, asistencia } = data;

      const kpis = [
        { label: 'Carreras',        value: resumen.totalCarreras,      icon: '🎓' },
        { label: 'Estudiantes',     value: resumen.totalEstudiantes,   icon: '👩‍🎓' },
        { label: 'Profesores',      value: resumen.totalProfesores,    icon: '👨‍🏫' },
        { label: 'Materias',        value: resumen.totalMaterias,      icon: '📚' },
        { label: 'Inscripciones',   value: resumen.totalInscripciones, icon: '📝' },
        { label: 'Tasa aprobación', value: `${resumen.tasaAprobacion}%`, icon: '✅' },
        { label: 'Promedio',        value: fmtNota(resumen.promedioGeneral), icon: '📈' },
        { label: 'Asistencia',      value: fmtPct(asistencia.porcentaje), icon: '🗓️' },
      ];

      const notasChart = {
        labels: distribucionNotas.map(d => d.rango),
        datasets: [{
          label: 'Inscripciones',
          data: distribucionNotas.map(d => d.total),
          backgroundColor: ['#d64545', '#e3a008', '#2462b0', '#1a7a48'],
          borderRadius: 6,
          maxBarThickness: 54,
        }],
      };

      const carrerasChart = {
        labels: carreras.map(c => c.codigo),
        datasets: [{
          label: 'Estudiantes',
          data: carreras.map(c => c.estudiantes),
          backgroundColor: '#2462b0',
          borderRadius: 6,
          maxBarThickness: 42,
        }],
      };

      const asistenciaChart = {
        labels: ['Asistencias', 'Ausencias'],
        datasets: [{
          data: [asistencia.asistencias, asistencia.ausencias],
          backgroundColor: ['#1a7a48', '#d64545'],
          borderWidth: 0,
        }],
      };

      const chartCard = { padding: '18px' };
      const chartBox  = { height: 250, marginTop: 10 };
      const grid      = { display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(320px,1fr))', gap: 18, marginBottom: 18 };

      return (
        <div className="page">
          <div className="welcome-banner">
            <div>
              <div className="wb-label">Reportes académicos</div>
              <h1>📈 {facultad.nombre}</h1>
              <div className="wb-sub">
                {periodo.etiqueta} · Semana {periodo.semanaActual} de {periodo.totalSemanas}
                {periodo.inicio && periodo.fin ? ` · ${periodo.inicio} a ${periodo.fin}` : ''}
              </div>
            </div>
            <span className="badge">{periodo.etiqueta}</span>
          </div>

          <div className="stat-grid-6" style={{gridTemplateColumns:'repeat(auto-fit,minmax(150px,1fr))', marginBottom:18}}>
            {kpis.map(s => (
              <div key={s.label} className="card" style={{textAlign:'center',padding:'16px 10px'}}>
                <div style={{fontSize:'1.4rem',marginBottom:'4px'}}>{s.icon}</div>
                <div style={{fontSize:'1.5rem',fontWeight:700,color:'var(--navy)'}}>{s.value}</div>
                <div style={{fontSize:'0.8rem',color:'var(--muted)',marginTop:'2px'}}>{s.label}</div>
              </div>
            ))}
          </div>

          <div style={grid}>
            <div className="card" style={chartCard}>
              <h3 style={{marginTop:0}}>Distribución de notas finales</h3>
              <div style={chartBox}><ChartCanvas type="bar" data={notasChart}/></div>
            </div>
            <div className="card" style={chartCard}>
              <h3 style={{marginTop:0}}>Estudiantes por carrera</h3>
              <div style={chartBox}><ChartCanvas type="bar" data={carrerasChart}/></div>
            </div>
            <div className="card" style={chartCard}>
              <h3 style={{marginTop:0}}>Asistencia global</h3>
              <div style={chartBox}><ChartCanvas type="doughnut" data={asistenciaChart}/></div>
            </div>
          </div>

          <div className="card" style={{marginBottom:18}}>
            <h3 style={{marginTop:0}}>Reporte por carrera</h3>
            <table className="tbl">
              <thead>
                <tr>
                  <th>Carrera</th><th>Código</th><th>Estudiantes</th><th>Materias</th>
                  <th>Inscripciones</th><th>Aprobados</th><th>Tasa</th><th>Promedio</th>
                </tr>
              </thead>
              <tbody>
                {carreras.length === 0 && (
                  <tr><td colSpan="8" style={{textAlign:'center',color:'var(--muted)'}}>Sin carreras en la facultad</td></tr>
                )}
                {carreras.map(c => (
                  <tr key={c.idCarrera}>
                    <td style={{fontWeight:600}}>{c.nombre}</td>
                    <td>{c.codigo}</td>
                    <td>{c.estudiantes}</td>
                    <td>{c.materias}</td>
                    <td>{c.inscripciones}</td>
                    <td>{c.aprobados} / {c.calificadas}</td>
                    <td>{c.tasaAprobacion}%</td>
                    <td>{fmtNota(c.promedio)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="card" style={{marginBottom:18}}>
            <h3 style={{marginTop:0}}>Reporte por materia</h3>
            <table className="tbl">
              <thead>
                <tr>
                  <th>Materia</th><th>Carrera</th><th>Profesor</th><th>Inscritos</th>
                  <th>Aprobados</th><th>Reprobados</th><th>Promedio</th><th>Asistencia</th>
                </tr>
              </thead>
              <tbody>
                {materias.length === 0 && (
                  <tr><td colSpan="8" style={{textAlign:'center',color:'var(--muted)'}}>Sin materias en la facultad</td></tr>
                )}
                {materias.map(m => (
                  <tr key={m.idMateria}>
                    <td style={{fontWeight:600}}>{m.nombre} <span style={{color:'var(--muted)',fontWeight:400}}>({m.codigo})</span></td>
                    <td>{m.carrera || '—'}</td>
                    <td>{m.profesor || '—'}</td>
                    <td>{m.inscritos}</td>
                    <td>{m.aprobados}</td>
                    <td>{m.reprobados}</td>
                    <td>{fmtNota(m.promedio)}</td>
                    <td>{fmtPct(m.asistencia)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="card">
            <h3 style={{marginTop:0}}>Reporte por profesor</h3>
            <table className="tbl">
              <thead>
                <tr>
                  <th>Profesor</th><th>Código</th><th>Departamento</th>
                  <th>Materias</th><th>Estudiantes</th><th>Promedio</th><th>Asistencia</th>
                </tr>
              </thead>
              <tbody>
                {profesores.length === 0 && (
                  <tr><td colSpan="7" style={{textAlign:'center',color:'var(--muted)'}}>Sin profesores en la facultad</td></tr>
                )}
                {profesores.map(p => (
                  <tr key={p.idProfesor}>
                    <td style={{fontWeight:600}}>{p.nombre}<div style={{fontSize:12,color:'var(--muted)',fontWeight:400}}>{p.email}</div></td>
                    <td>{p.codigoProf}</td>
                    <td>{p.departamento || '—'}</td>
                    <td>{p.materias}</td>
                    <td>{p.estudiantes}</td>
                    <td>{fmtNota(p.promedio)}</td>
                    <td>{fmtPct(p.asistencia)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
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
            <span className="badge">{lista.length} · {S.facultad?.nombre || 'Mi facultad'}</span>
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
            <span className="badge">{lista.length} · {S.facultad?.nombre || 'Mi facultad'}</span>
          </div>
          <div className="card">
            <table className="tbl">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Email</th>
                  <th>Código</th>
                  <th>Carrera</th>
                  <th>Semestre</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                {lista.length === 0 && (
                  <tr><td colSpan="6" style={{textAlign:'center',color:'var(--muted)'}}>
                    Sin estudiantes registrados en esta facultad
                  </td></tr>
                )}
                {lista.map(e => (
                  <tr key={e.idUsuario}>
                    <td>{e.nombre}</td>
                    <td>{e.email}</td>
                    <td>{e.codigoEst || '—'}</td>
                    <td>{e.carrera || '—'}</td>
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
            <span className="badge">{lista.length} · {S.facultad?.nombre || 'Mi facultad'}</span>
          </div>
          <div className="card">
            <table className="tbl">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Código</th>
                  <th>Carrera</th>
                  <th>Créditos</th>
                  <th>Profesor</th>
                  <th>Inscritos</th>
                </tr>
              </thead>
              <tbody>
                {lista.length === 0 && (
                  <tr><td colSpan="6" style={{textAlign:'center',color:'var(--muted)'}}>
                    Sin materias registradas en esta facultad
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
                    <td>{m.carrera || '—'}</td>
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

    // ── Mensajes (UV-19) ─────────────────────────────────────────────
    function MensajesDirectivo() {
      const [enviados, setEnviados] = useState(null);
      const [form, setForm] = useState({ publico: 'ambos', asunto: '', contenido: '' });
      const [msg, setMsg] = useState(null);
      const [saving, setSaving] = useState(false);

      const cargar = () => {
        fetch(API('comunicados.php?action=enviados'), { credentials: 'include' })
          .then(r => r.json())
          .then(d => setEnviados(d.status === 'ok' ? d.data : []))
          .catch(() => setEnviados([]));
      };

      useEffect(() => { cargar(); }, []);

      const enviar = async () => {
        if (!form.asunto.trim() || !form.contenido.trim()) {
          return setMsg({ ok: false, text: 'Escribe el asunto y el mensaje.' });
        }
        setSaving(true);
        setMsg(null);
        try {
          const res = await fetch(API('comunicados.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ action: 'enviar', ...form }),
          });
          const data = await res.json();
          if (data.status === 'ok') {
            setMsg({ ok: true, text: data.mensaje });
            setForm({ publico: form.publico, asunto: '', contenido: '' });
            cargar();
          } else {
            setMsg({ ok: false, text: data.mensaje || 'No se pudo enviar.' });
          }
        } catch (e) {
          setMsg({ ok: false, text: 'Error de red al enviar el comunicado.' });
        } finally {
          setSaving(false);
        }
      };

      if (!enviados) return <Spinner/>;

      return (
        <div className="page">
          <div className="welcome-banner">
            <h1>✉️ Mensajes</h1>
            <span className="badge">{enviados.length} enviados</span>
          </div>

          <div className="card" style={{ marginBottom: 20 }}>
            <h3 style={{ marginTop: 0 }}>Nuevo comunicado</h3>

            <div className="form-group">
              <label>Público</label>
              <select className="form-input" value={form.publico}
                      onChange={e => setForm({ ...form, publico: e.target.value })}>
                <option value="ambos">Estudiantes y profesores</option>
                <option value="estudiantes">Solo estudiantes</option>
                <option value="profesores">Solo profesores</option>
              </select>
            </div>

            <div className="form-group" style={{ marginTop: 12 }}>
              <label>Asunto</label>
              <input className="form-input" maxLength={150} value={form.asunto}
                     onChange={e => setForm({ ...form, asunto: e.target.value })}
                     placeholder="Ej. Cambio de fecha del parcial" />
            </div>

            <div className="form-group" style={{ marginTop: 12 }}>
              <label>Mensaje</label>
              <textarea className="form-input" rows={5} value={form.contenido}
                        onChange={e => setForm({ ...form, contenido: e.target.value })}
                        placeholder="Escribe el aviso para tu facultad..." />
            </div>

            {msg && <div className={`alert ${msg.ok ? 'alert-success' : 'alert-error'}`} style={{ marginTop: 12 }}>{msg.text}</div>}

            <button className="btn btn-primary" style={{ marginTop: 12 }} onClick={enviar} disabled={saving}>
              {saving ? 'Enviando...' : 'Enviar comunicado'}
            </button>

            <p style={{ fontSize: 12, color: 'var(--muted)', marginTop: 10 }}>
              Se envía solo a estudiantes y profesores activos de tu facultad, y cada uno recibe una notificación.
            </p>
          </div>

          <div className="card">
            <h3 style={{ marginTop: 0 }}>Enviados</h3>
            <table className="tbl">
              <thead>
                <tr><th>Asunto</th><th>Público</th><th>Destinatarios</th><th>Leídos</th><th>Fecha</th></tr>
              </thead>
              <tbody>
                {enviados.length === 0 && (
                  <tr><td colSpan="5" style={{ textAlign: 'center', color: 'var(--muted)' }}>
                    Aún no has enviado comunicados.
                  </td></tr>
                )}
                {enviados.map(c => (
                  <tr key={c.idComunicado}>
                    <td style={{ fontWeight: 600 }}>{c.asunto}</td>
                    <td>{c.publico}</td>
                    <td>{c.destinatarios}</td>
                    <td>{c.leidos} / {c.destinatarios}</td>
                    <td>{new Date(c.creado_en.replace(' ', 'T')).toLocaleString('es-CO')}</td>
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
        { id: 'reportes',      ico: '📈',  lbl: 'Reportes'      },
        { id: 'profesores',    ico: '👨‍🏫', lbl: 'Profesores'    },
        { id: 'estudiantes',   ico: '👩‍🎓', lbl: 'Estudiantes'   },
        { id: 'materias',      ico: '📚',  lbl: 'Materias'       },
        { id: 'mensajes',      ico: '✉️',  lbl: 'Mensajes'       },
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
              <span className="tb-semester"><?= periodoEtiqueta() ?></span>
              <div className="tb-notif"><span>🔔</span></div>
            </div>
          </header>

          <main className="main">
            {vista === 'resumen'       && <ResumenFacultad/>}
            {vista === 'reportes'      && <ReportesFacultad/>}
            {vista === 'profesores'    && <Profesores/>}
            {vista === 'estudiantes'   && <Estudiantes/>}
            {vista === 'materias'      && <MateriasFacultad/>}
            {vista === 'mensajes'      && <MensajesDirectivo/>}
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