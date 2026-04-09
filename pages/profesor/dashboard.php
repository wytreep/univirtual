<?php
require_once __DIR__.'/../../config/config.php';
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/View.php';

/**
 * ProfesorView — Panel del Profesor (SPA React)
 * Reemplaza: aula.php, actividades.php, materiales.php,
 *            calificar.php, reportes.php, notificaciones.php
 */
class ProfesorView extends View {
    private int $idProf;

    public function __construct() {
        parent::__construct('profesor');
        $this->idProf = (int)($_SESSION['idEspecifico'] ?? 0);
    }

    protected function sessionExtra(): array {
        return ['idProf' => $this->idProf];
    }

    public function render(): void {
        $s = $this->sessionJS();
        $this->html($s);
    }

    private function html(string $s): void { ?>
<!DOCTYPE html><html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>UNI-VIRTUAL — Profesor</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=DM+Serif+Display:ital@0;1&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<script src="https://unpkg.com/react@18/umd/react.development.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://meet.jit.si/external_api.js"></script>
<link rel="stylesheet" href="/univirtual/assets/css/panel.css">
</head>
<body>
<div id="root"></div>
<script>window.__S=<?= $s ?>;window.API='/univirtual/api/v1';window.BASE='/univirtual';</script>
<script type="text/babel">
const{useState,useEffect,useCallback,useRef}=React;
const api={
  get:(u)=>fetch(API+u,{credentials:'include'}).then(r=>r.json()),
  post:(u,b,isForm)=>fetch(API+u,{method:'POST',credentials:'include',...(isForm?{body:b}:{headers:{'Content-Type':'application/json'},body:JSON.stringify(b)})}).then(r=>r.json()),
  put:(u,b)=>fetch(API+u,{method:'PUT',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify(b)}).then(r=>r.json()),
};
const Spinner=()=><div className="loading-center"><div className="spinner"/><span>Cargando...</span></div>;
const Alert=({type,msg,onClose})=>msg?<div className={`alert alert-${type}`}>{msg}<span onClick={onClose} style={{cursor:'pointer',marginLeft:8}}>✕</span></div>:null;
const Modal=({title,onClose,children,footer,wide})=>(
  <div className="modal-overlay" onClick={e=>e.target===e.currentTarget&&onClose()}>
    <div className="modal" style={wide?{maxWidth:700}:{}}>
      <div className="modal-header"><span className="modal-title">{title}</span><button className="modal-close" onClick={onClose}>✕</button></div>
      <div className="modal-body">{children}</div>
      {footer&&<div className="modal-footer">{footer}</div>}
    </div>
  </div>
);

// ── JITSI MEET — Link directo (SCRUM-118) ─────────────────────────────────────────

// ── DASHBOARD PROFESOR ─────────────────────────────────────────────────
const Dashboard=({onAula,setVistaPrincipal})=>{
  const[data,setData]=useState(null);
  const{idProf,nombre}=window.__S;

  const cargar=useCallback(()=>{
    api.get(`/profesor.php?action=dashboard&idProf=${idProf}`)
      .then(r=>r.status==='ok'&&setData(r.data));
  },[idProf]);

  useEffect(()=>{
    cargar();
    // Polling de notificaciones cada 30s
    const t=setInterval(()=>{
      api.get(`/profesor.php?action=notificaciones&idProf=${idProf}`)
        .then(r=>r.status==='ok'&&setData(prev=>prev?{...prev,notificaciones:r.data}:prev));
    },30000);
    return()=>clearInterval(t);
  },[cargar,idProf]);
  if(!data)return<Spinner/>;
  return<div className="page">
    {/* Banner */}
    <div className="welcome-banner">
      <div>
        <div className="wb-label">Bienvenido de nuevo</div>
        <div className="wb-name">{nombre.split(' ')[0]} <em>{nombre.split(' ').slice(1).join(' ')}</em></div>
        <div className="wb-sub">Docente · 2026-I</div>
      </div>
      <div className="wb-stats">
        {[{n:data.totalMaterias,l:'Materias'},{n:data.totalSinCalificar,l:'Por calificar'},{n:data.totalEstudiantes,l:'Estudiantes'}].map(s=>(
          <div className="wb-stat" key={s.l}><div className="wb-stat-num">{s.n}</div><div className="wb-stat-lbl">{s.l}</div></div>
        ))}
      </div>
    </div>
    {/* Métricas */}
    <div className="metric-row">
      {[{ico:'📚',v:data.totalMaterias,l:'Materias activas',bg:'#e8f0fb',c:'#2462b0'},{ico:'⏳',v:data.totalSinCalificar,l:'Entregas por calificar',bg:'#fff3e0',c:'#e07b39'},{ico:'📄',v:data.totalMateriales,l:'Materiales publicados',bg:'#e8f5ee',c:'#1a7a48'}].map(m=>(
        <div className="metric-card" key={m.l}><div className="metric-ico" style={{background:m.bg}}>{m.ico}</div><div><div className="metric-val" style={{color:m.c}}>{m.v}</div><div className="metric-lbl">{m.l}</div></div></div>
      ))}
    </div>
    {/* Grid */}
    <div className="page-grid">
      <div>
        {/* Mis Materias */}
        <div className="card">
          <div className="card-header">
            <span className="card-title">📚 Mis Materias</span>
          </div>
          <div className="card-body">
            {data.materias.length===0&&<p style={{color:'var(--muted)',textAlign:'center',padding:16}}>Sin materias asignadas.</p>}
            {data.materias.map(m=>{
              const pct=Math.min(100,Math.round(((m.total_materiales||0)+(m.total_actividades||0))/10*100));
              return<div key={m.idMateria} className="mat-row" onClick={()=>onAula(m)}>
                <div className="mat-color" style={{background:m.color}}/>
                <div className="mat-info">
                  <div className="mat-name">{m.nombre}</div>
                  <div className="pbar-wrap" style={{marginTop:6}}><div className="pbar-fill" style={{width:`${pct}%`,background:m.color}}/></div>
                </div>
                <div className="mat-right">
                  <div className="mat-pct-num">{pct}%</div>
                  {m.sinCalificar>0?<span className="tag tag-warn">{m.sinCalificar} por calificar</span>:<span className="tag tag-ok">Al día</span>}
                </div>
              </div>;
            })}
          </div>
        </div>
        {/* Entregas recientes */}
        <div className="card">
          <div className="card-header"><span className="card-title">📥 Entregas Recientes</span></div>
          <div className="card-body">
            {(data.entregasRecientes||[]).length===0&&<p style={{color:'var(--muted)',textAlign:'center',padding:12}}>Sin entregas recientes.</p>}
            {(data.entregasRecientes||[]).map((e,i)=>(
              <div key={i} style={{display:'flex',alignItems:'center',gap:12,padding:'9px 0',borderBottom:'1px solid var(--border-lt)'}}>
                <div style={{width:34,height:34,borderRadius:8,background:'var(--bg)',display:'flex',alignItems:'center',justifyContent:'center',fontSize:16}}>📄</div>
                <div style={{flex:1}}>
                  <div style={{fontWeight:600,fontSize:13}}>{e.estudiante}</div>
                  <div style={{fontSize:11,color:'var(--muted)'}}>{e.actividad} · {e.materia}</div>
                </div>
                <span className={`tag ${e.nota?'tag-ok':'tag-warn'}`}>{e.nota?`Nota: ${e.nota}`:'Pendiente'}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
      {/* Sidebar derecho */}
      <div>
        {/* Novedades */}
        <div className="card">
          <div className="card-header">
            <span className="card-title">🔔 Novedades</span>
            <span className="card-link">Ver todas</span>
          </div>
          <div className="card-body" style={{padding:'8px 20px'}}>
            {(data.notificaciones||[]).length===0&&<p style={{color:'var(--muted)',fontSize:13,textAlign:'center',padding:12}}>Sin novedades</p>}
            {(data.notificaciones||[]).slice(0,5).map((n,i)=>{
              const colors={material:'#2462b0',tarea:'#e07b39',nota:'#1a7a48',foro:'#6d28d9',info:'#888',sistema:'#c0392b',asistencia:'#d4a843',videollamada:'#0891b2'};
              const icons={material:'📄',tarea:'📋',nota:'📊',foro:'💬',sistema:'⚙️',asistencia:'✅',videollamada:'🎥',info:'🔔'};
              return<div key={i} className="notif-item"
                onClick={()=>n.url&&n.url!=='#'&&(window.location.href=n.url)}
                style={{cursor:n.url&&n.url!=='#'?'pointer':'default',borderRadius:6,transition:'background .15s'}}>
                <div style={{display:'flex',gap:8,alignItems:'flex-start'}}>
                  <div style={{width:28,height:28,borderRadius:7,background:`${colors[n.tipo]||'#888'}18`,display:'flex',alignItems:'center',justifyContent:'center',fontSize:14,flexShrink:0}}>
                    {icons[n.tipo]||'🔔'}
                  </div>
                  <div style={{flex:1,minWidth:0}}>
                    <div className="notif-title" style={{fontWeight:n.leida?500:700}}>{n.titulo}</div>
                    <div className="notif-msg">{n.mensaje}</div>
                    <div className="notif-time">{n.creado_en?.slice(0,16).replace('T',' ')}</div>
                  </div>
                  {!n.leida&&<div style={{width:7,height:7,borderRadius:'50%',background:colors[n.tipo]||'#2462b0',marginTop:6,flexShrink:0}}/>}
                </div>
              </div>;
            })}
          </div>
        </div>
        {/* Acceso rápido */}
        <div className="card">
          <div className="card-header"><span className="card-title">⚡ Acceso Rápido</span></div>
          <div className="card-body">
            <div className="quick-grid">
              {[
                {ico:'📚',lbl:'Mis Materias',  fn:()=>document.querySelector('.mat-row')?.click()},
                {ico:'📋',lbl:'Por calificar', fn:()=>setVistaPrincipal&&setVistaPrincipal('calificar')},
                {ico:'📈',lbl:'Reportes',      fn:()=>setVistaPrincipal&&setVistaPrincipal('reportes')},
                {ico:'🔔',lbl:'Notificaciones',fn:()=>setVistaPrincipal&&setVistaPrincipal('notificaciones')},
              ].map(b=>(<div key={b.lbl} className="quick-btn" onClick={b.fn}><span className="quick-btn-ico">{b.ico}</span><span className="quick-btn-lbl">{b.lbl}</span></div>))}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>;
};

// ── AULA VIRTUAL ───────────────────────────────────────────────────────
const Aula=({materia,onBack})=>{
  const[tab,setTab]=useState('materiales');const[data,setData]=useState(null);const[alerta,setAlerta]=useState(null);const{idProf}=window.__S;
  // Estado de fecha para asistencia por clase
  const[fechaAsist,setFechaAsist]=useState(new Date().toISOString().split('T')[0]);
  const[asistenciaFecha,setAsistenciaFecha]=useState({});
  // Modales
  const[modalMat,setModalMat]=useState(false);const[modalAct,setModalAct]=useState(false);const[modalForo,setModalForo]=useState(false);const[modalCalif,setModalCalif]=useState(null);
  const[fmMat,setFmMat]=useState({titulo:'',tipo:'pdf',descripcion:''});const[fmAct,setFmAct]=useState({titulo:'',descripcion:'',fechaEntrega:'',puntaje:5});const[fmForo,setFmForo]=useState({titulo:'',descripcion:''});const[fmMsg,setFmMsg]=useState({});const[fmCalif,setFmCalif]=useState({nota:'',feedback:''});
  const[editNota,setEditNota]=useState({});const[saving,setSaving]=useState(false);
  const cargar=useCallback(()=>{api.get(`/profesor.php?action=aula&idAula=${materia.idAula}&idProf=${idProf}`).then(r=>r.status==='ok'&&setData(r.data));},[materia,idProf]);
  useEffect(()=>cargar(),[cargar]);

  // Cargar asistencia de la fecha seleccionada
  const cargarAsistFecha=useCallback(()=>{
    api.get(`/profesor.php?action=asistencia_fecha&idMateria=${materia.idMateria}&fecha=${fechaAsist}`)
      .then(r=>{if(r.status==='ok'){const m={};(r.data||[]).forEach(a=>m[a.idEstudiante]=a.asistio);setAsistenciaFecha(m);}});
  },[materia,fechaAsist]);
  useEffect(()=>{if(tab==='asistencia')cargarAsistFecha();},[tab,cargarAsistFecha]);
  const subirMat=async()=>{const inp=document.getElementById('mat-file');if(!inp?.files[0]||!fmMat.titulo)return setAlerta({type:'err',msg:'Título y archivo requeridos'});const fd=new FormData();fd.append('action','subir_material');fd.append('idAula',materia.idAula);fd.append('idProf',idProf);fd.append('titulo',fmMat.titulo);fd.append('tipo',fmMat.tipo);fd.append('descripcion',fmMat.descripcion);fd.append('archivo',inp.files[0]);setSaving(true);const r=await api.post('/profesor.php',fd,true);setSaving(false);if(r.status==='ok'){setModalMat(false);cargar();setAlerta({type:'ok',msg:'Material publicado'});}else setAlerta({type:'err',msg:r.mensaje});};
  const crearAct=async()=>{if(!fmAct.titulo||!fmAct.fechaEntrega)return setAlerta({type:'err',msg:'Título y fecha requeridos'});setSaving(true);const r=await api.post('/profesor.php',{action:'crear_actividad',idAula:materia.idAula,idProf,...fmAct});setSaving(false);if(r.status==='ok'){setModalAct(false);cargar();setAlerta({type:'ok',msg:'Actividad creada'});}else setAlerta({type:'err',msg:r.mensaje});};
  const crearForo=async()=>{if(!fmForo.titulo)return;setSaving(true);const r=await api.post('/profesor.php',{action:'crear_foro',idAula:materia.idAula,idProf,...fmForo});setSaving(false);if(r.status==='ok'){setModalForo(false);cargar();setAlerta({type:'ok',msg:'Foro creado'});}else setAlerta({type:'err',msg:r.mensaje});};
  const pubMsg=async(idForo)=>{const msg=fmMsg[idForo];if(!msg?.trim())return;await api.post('/profesor.php',{action:'publicar_mensaje',idForo,contenido:msg,idProf});setFmMsg({...fmMsg,[idForo]:''});cargar();};
  const guardarNota=async(idEstudiante,campo,val)=>{const v=parseFloat(val);if(isNaN(v)||v<0||v>5)return;await api.post('/profesor.php',{action:'guardar_nota',idMateria:materia.idMateria,idEstudiante,campo,nota:v});setEditNota({});cargar();};
  const califEntrega=async()=>{if(!fmCalif.nota)return;setSaving(true);await api.post('/profesor.php',{action:'calificar_entrega',idEntrega:modalCalif.idEntrega,nota:parseFloat(fmCalif.nota),feedback:fmCalif.feedback});setSaving(false);setModalCalif(null);setFmCalif({nota:'',feedback:''});cargar();setAlerta({type:'ok',msg:'Calificación guardada'});};
  const guardarAsist=async(idEstudiante,asistio)=>{
    // Optimistic update
    setAsistenciaFecha(prev=>({...prev,[idEstudiante]:asistio}));
    await api.post('/profesor.php',{action:'registrar_asistencia',idMateria:materia.idMateria,idEstudiante,fecha:fechaAsist,asistio});
  };

  // ── Videollamadas (Jitsi Meet) ──────────────────────────────────────────────
  const[vlLista,setVlLista]=useState([]);const[vlModal,setVlModal]=useState(false);
  const[vlActiva,setVlActiva]=useState(null); // Room name de la videollamada activa en el panel → eliminado, ahora es link directo
  const[fmVl,setFmVl]=useState({titulo:'',descripcion:'',programada_en:'',duracion_min:90});
  const cargarVL=useCallback(()=>{
    fetch(`/univirtual/api/v1/videollamadas.php?action=lista&idMateria=${materia.idMateria}`)
      .then(r=>r.json()).then(r=>r.status==='ok'&&setVlLista(r.data||[]));
  },[materia]);
  useEffect(()=>{if(tab==='videollamadas')cargarVL();},[tab,cargarVL]);
  const crearVL=async()=>{
    if(!fmVl.titulo||!fmVl.programada_en)return setAlerta({type:'err',msg:'Título y fecha/hora requeridos'});
    setSaving(true);
    const r=await fetch('/univirtual/api/v1/videollamadas.php',{
      method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',
      body:JSON.stringify({action:'crear',idMateria:materia.idMateria,...fmVl})
    }).then(r=>r.json());
    setSaving(false);
    if(r.status==='ok'){setVlModal(false);cargarVL();setAlerta({type:'ok',msg:'Videollamada creada. Estudiantes notificados.'});}
    else setAlerta({type:'err',msg:r.mensaje});
  };
  const finalizarVL=async(idVideoLlamada)=>{
    await fetch('/univirtual/api/v1/videollamadas.php',{
      method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',
      body:JSON.stringify({action:'finalizar',idVideoLlamada})
    }).then(r=>r.json());
    cargarVL();
  };
  const eliminarVL=async(idVideoLlamada)=>{
    if(!confirm('¿Eliminar esta videollamada?'))return;
    await fetch('/univirtual/api/v1/videollamadas.php',{
      method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',
      body:JSON.stringify({action:'eliminar',idVideoLlamada})
    }).then(r=>r.json());
    cargarVL();
  };

  const subirGrabacion=async(idVideoLlamada,archivo)=>{
    setSaving(true);
    const fd=new FormData();
    fd.append('action','subir_grabacion');
    fd.append('idVideoLlamada',idVideoLlamada);
    fd.append('idProf',idProf);
    fd.append('grabacion',archivo);
    const r=await fetch('/univirtual/api/v1/videollamadas.php',{
      method:'POST',credentials:'include',body:fd
    }).then(r=>r.json());
    setSaving(false);
    if(r.status==='ok'){cargarVL();setAlerta({type:'ok',msg:'Grabación subida correctamente'});}
    else setAlerta({type:'err',msg:r.mensaje||'Error al subir grabación'});
  };

  if(!data)return<Spinner/>;
  return<div className="page">
    <div className="aula-header">
      <div className="aula-banner">
        <div>
          <button onClick={onBack} style={{background:'rgba(255,255,255,.12)',border:'1px solid rgba(255,255,255,.2)',color:'rgba(255,255,255,.7)',borderRadius:6,padding:'4px 10px',cursor:'pointer',fontSize:12,marginBottom:10}}>← Volver</button>
          <div className="aula-name">{materia.nombre}</div>
          <div className="aula-code">{materia.codigo} · {data.totalEstudiantes} estudiantes · {data.totalMateriales} materiales</div>
        </div>
        <div style={{display:'flex',gap:10}}>
          {[{n:data.totalMateriales,l:'Materiales'},{n:data.totalActividades,l:'Actividades'},{n:data.totalForos,l:'Foros'}].map(s=>(
            <div key={s.l} className="wb-stat"><div className="wb-stat-num">{s.n}</div><div className="wb-stat-lbl">{s.l}</div></div>
          ))}
        </div>
      </div>
      <div style={{padding:'0 24px',borderTop:'1px solid var(--border-lt)'}}>
        <div className="tabs" style={{borderBottom:'none',marginBottom:0}}>
          {[{id:'materiales',ico:'📄'},{id:'actividades',ico:'📋'},{id:'foros',ico:'💬'},{id:'estudiantes',ico:'👥'},{id:'asistencia',ico:'✅'},{id:'videollamadas',ico:'🎥'}].map(t=>(
            <div key={t.id} className={`tab ${tab===t.id?'active':''}`} onClick={()=>setTab(t.id)}>{t.ico} {t.id.charAt(0).toUpperCase()+t.id.slice(1)}</div>
          ))}
        </div>
      </div>
    </div>
    <Alert type={alerta?.type} msg={alerta?.msg} onClose={()=>setAlerta(null)}/>

    {/* MATERIALES */}
    {tab==='materiales'&&<div>
      <div style={{display:'flex',justifyContent:'flex-end',marginBottom:14}}><button className="btn btn-primary" onClick={()=>setModalMat(true)}>📤 Publicar Material</button></div>
      {(data.materiales||[]).length===0&&<div className="card"><div style={{textAlign:'center',padding:36,color:'var(--muted)'}}>Sin materiales publicados aún.</div></div>}
      {(data.materiales||[]).map(m=>(
        <div key={m.idMaterial} className="card" style={{margin:'0 0 12px'}}>
          <div style={{padding:'14px 18px',display:'flex',alignItems:'center',gap:14}}>
            <div style={{width:40,height:40,borderRadius:9,background:{pdf:'#fdecea',video:'#e8f0fb',presentacion:'#e8f5ee'}[m.tipo]||'var(--bg)',display:'flex',alignItems:'center',justifyContent:'center',fontSize:20}}>{m.tipo==='pdf'?'📄':m.tipo==='video'?'▶️':'📊'}</div>
            <div style={{flex:1}}><div style={{fontWeight:600,fontSize:14}}>{m.titulo}</div><div style={{fontSize:12,color:'var(--muted)'}}>{m.descripcion} · {(m.tamano/1024/1024).toFixed(1)} MB · {m.descargas} descargas</div></div>
            <a href={`/univirtual/api/descargar.php?id=${m.idMaterial}&tipo=material&preview=1`} className="btn btn-outline btn-sm">⬇ Descargar</a>
          </div>
        </div>
      ))}
    </div>}

    {/* ACTIVIDADES */}
    {tab==='actividades'&&<div>
      <div style={{display:'flex',justifyContent:'flex-end',marginBottom:14}}><button className="btn btn-primary" onClick={()=>setModalAct(true)}>➕ Nueva Actividad</button></div>
      {(data.actividades||[]).length===0&&<div className="card"><div style={{textAlign:'center',padding:36,color:'var(--muted)'}}>Sin actividades creadas.</div></div>}
      {(data.actividades||[]).map(a=>{
        const venc=new Date(a.fechaEntrega)<new Date();
        return<div key={a.idActividad} className="card" style={{margin:'0 0 12px',borderLeft:`4px solid ${materia.color}`}}>
          <div style={{padding:'14px 18px'}}>
            <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start'}}>
              <div><div style={{fontWeight:700,fontSize:14}}>{a.titulo}</div><div style={{fontSize:12,color:'var(--muted)',marginTop:3}}>Vence: {new Date(a.fechaEntrega).toLocaleDateString('es-CO',{day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})} · Puntaje máx: {a.puntaje_max}</div></div>
              <div style={{display:'flex',gap:8,alignItems:'center'}}>
                <span className={`tag ${venc?'tag-err':'tag-ok'}`}>{venc?'Vencida':'Activa'}</span>
                <span className="tag tag-blue">{a.total_entregas} entregas</span>
              </div>
            </div>
            {a.descripcion&&<p style={{fontSize:13,color:'var(--text-lt)',marginTop:8}}>{a.descripcion}</p>}
          </div>
        </div>;
      })}
    </div>}

    {/* FOROS */}
    {tab==='foros'&&<div>
      <div style={{display:'flex',justifyContent:'flex-end',marginBottom:14}}><button className="btn btn-primary" onClick={()=>setModalForo(true)}>💬 Crear Foro</button></div>
      {(data.foros||[]).length===0&&<div className="card"><div style={{textAlign:'center',padding:36,color:'var(--muted)'}}>Sin foros creados aún.</div></div>}
      {(data.foros||[]).map(f=>(
        <div key={f.idForo} className="card" style={{margin:'0 0 14px'}}>
          <div className="card-header"><span className="card-title">💬 {f.titulo}</span><span className="tag tag-blue">{f.total_msgs} mensajes</span></div>
          <div className="card-body">
            <p style={{fontSize:13,color:'var(--muted)',marginBottom:12}}>{f.descripcion}</p>
            {(data.mensajes?.filter(m=>m.idForo===f.idForo)||[]).map((m,i)=>(
              <div key={i} style={{display:'flex',gap:10,marginBottom:10,padding:'8px 12px',background:m.rol_autor==='profesor'?'#f0f4ff':'var(--bg)',borderRadius:8,borderLeft:`3px solid ${m.rol_autor==='profesor'?'#2462b0':'var(--border)'}` }}>
                <div style={{width:30,height:30,borderRadius:'50%',background:m.rol_autor==='profesor'?'#2462b0':'var(--border)',display:'flex',alignItems:'center',justifyContent:'center',color:'#fff',fontSize:12,fontWeight:700,flexShrink:0}}>{m.autor?.charAt(0)}</div>
                <div><div style={{fontSize:12,fontWeight:600}}>{m.autor} <span style={{color:'var(--muted)',fontWeight:400}}>{m.publicado_en?.slice(0,10)}</span></div><div style={{fontSize:13,marginTop:3}}>{m.contenido}</div></div>
              </div>
            ))}
            <div style={{display:'flex',gap:8,marginTop:10}}>
              <input className="form-input" style={{flex:1}} placeholder="Escribe un mensaje..." value={fmMsg[f.idForo]||''} onChange={e=>setFmMsg({...fmMsg,[f.idForo]:e.target.value})} onKeyDown={e=>e.key==='Enter'&&pubMsg(f.idForo)}/>
              <button className="btn btn-primary btn-sm" onClick={()=>pubMsg(f.idForo)}>Enviar</button>
            </div>
          </div>
        </div>
      ))}
    </div>}

    {/* ESTUDIANTES / NOTAS */}
    {tab==='estudiantes'&&<div className="card">
      <div className="card-header"><span className="card-title">👥 Estudiantes y Calificaciones</span><span style={{fontSize:12,color:'var(--muted)'}}>Clic en celda para editar</span></div>
      <table className="tbl"><thead><tr><th>Estudiante</th><th>Código</th><th>Parcial 1</th><th>Parcial 2</th><th>Talleres</th><th>Final</th></tr></thead>
      <tbody>{(data.estudiantes||[]).map(e=>{
        const cols=['nota_parcial1','nota_parcial2','nota_talleres','nota_final'];
        return<tr key={e.idEstudiante}>
          <td style={{fontWeight:600}}>{e.nombre}</td>
          <td><code style={{fontSize:11}}>{e.codigoEst}</code></td>
          {cols.map(c=><td key={c} style={{textAlign:'center'}}>
            {editNota[`${e.idEstudiante}-${c}`]
              ?<input type="number" className="nota-input" autoFocus defaultValue={e[c]||''} min={0} max={5} step={0.1}
                  onBlur={ev=>guardarNota(e.idEstudiante,c,ev.target.value)}
                  onKeyDown={ev=>{if(ev.key==='Enter')guardarNota(e.idEstudiante,c,ev.target.value);if(ev.key==='Escape')setEditNota({});}}/>
              :<span className="nota-cell" onClick={()=>setEditNota({[`${e.idEstudiante}-${c}`]:true})} style={{color:e[c]>=3?'var(--green)':e[c]?'var(--red)':'var(--muted)'}}>
                {e[c]??'—'}
              </span>}
          </td>)}
        </tr>;
      })}</tbody></table>
    </div>}

    {/* ASISTENCIA POR CLASE */}
    {tab==='asistencia'&&<div>
      {/* Selector de fecha */}
      <div className="card" style={{marginBottom:14}}>
        <div style={{padding:'14px 20px',display:'flex',alignItems:'center',gap:16,flexWrap:'wrap'}}>
          <div style={{display:'flex',alignItems:'center',gap:10}}>
            <span style={{fontSize:13,fontWeight:600,color:'var(--navy)'}}>📅 Fecha de clase:</span>
            <input type="date" className="form-input" style={{width:170}}
              value={fechaAsist} max={new Date().toISOString().split('T')[0]}
              onChange={e=>setFechaAsist(e.target.value)}/>
          </div>
          <div style={{marginLeft:'auto',display:'flex',gap:8}}>
            <button className="btn btn-outline btn-sm" onClick={()=>{
              const todos={};(data.estudiantes||[]).forEach(e=>todos[e.idEstudiante]=1);
              setAsistenciaFecha(todos);
              (data.estudiantes||[]).forEach(e=>guardarAsist(e.idEstudiante,1));
            }}>✅ Todos presentes</button>
            <button className="btn btn-danger btn-sm" onClick={()=>{
              const todos={};(data.estudiantes||[]).forEach(e=>todos[e.idEstudiante]=0);
              setAsistenciaFecha(todos);
              (data.estudiantes||[]).forEach(e=>guardarAsist(e.idEstudiante,0));
            }}>❌ Todos ausentes</button>
          </div>
        </div>
        <div style={{padding:'4px 20px 12px',fontSize:12,color:'var(--muted)'}}>
          {new Date(fechaAsist+'T12:00:00').toLocaleDateString('es-CO',{weekday:'long',day:'numeric',month:'long',year:'numeric'})}
          {' · '}{Object.values(asistenciaFecha).filter(v=>v===1).length} presentes · {Object.values(asistenciaFecha).filter(v=>v===0&&v!==undefined).length} ausentes
        </div>
      </div>
      <div className="card">
        <table className="tbl">
          <thead><tr>
            <th>Estudiante</th><th>Código</th>
            <th style={{textAlign:'center'}}>Asistencia ({fechaAsist})</th>
            <th>% Total</th>
          </tr></thead>
          <tbody>{(data.estudiantes||[]).map(e=>{
            const asistio=asistenciaFecha[e.idEstudiante];
            return<tr key={e.idEstudiante}>
              <td style={{fontWeight:600}}>{e.nombre}</td>
              <td><code style={{fontSize:11}}>{e.codigoEst}</code></td>
              <td style={{textAlign:'center'}}>
                <label className="toggle">
                  <input type="checkbox"
                    checked={asistio===1||asistio===true}
                    onChange={ev=>{
                      const val=ev.target.checked?1:0;
                      setAsistenciaFecha(prev=>({...prev,[e.idEstudiante]:val}));
                      guardarAsist(e.idEstudiante,val);
                    }}/>
                  <span className="toggle-slider"/>
                </label>
              </td>
              <td><div style={{display:'flex',alignItems:'center',gap:8}}>
                <div className="pbar-wrap" style={{flex:1}}>
                  <div className="pbar-fill" style={{
                    width:`${e.pctAsistencia||0}%`,
                    background:(e.pctAsistencia||0)>=75?'var(--green)':'var(--red)'
                  }}/>
                </div>
                <span style={{fontSize:12,fontWeight:700,width:36,
                  color:(e.pctAsistencia||0)>=75?'var(--green)':'var(--red)'}}>
                  {e.pctAsistencia||0}%
                </span>
              </div></td>
            </tr>;
          })}</tbody>
        </table>
      </div>
    </div>}

    {/* ── TAB VIDEOLLAMADAS (Jitsi Meet) ──────────────────────────────── */}
    {tab==='videollamadas'&&<div>
      <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:16}}>
        <div>
          <div style={{fontSize:14,fontWeight:700,color:'var(--navy)'}}>Clases Virtuales — Jitsi Meet</div>
          <div style={{fontSize:12,color:'var(--muted)',marginTop:2}}>Las videollamadas usan meet.jit.si sin necesidad de cuenta ni instalación.</div>
        </div>
        <button className="btn btn-primary" onClick={()=>{setFmVl({titulo:'',descripcion:'',programada_en:'',duracion_min:90});setVlModal(true);}}>🎥 Programar Clase</button>
      </div>

      {vlLista.length===0&&<div className="card"><div style={{textAlign:'center',padding:40,color:'var(--muted)'}}>
        <div style={{fontSize:36,marginBottom:12}}>🎥</div>
        <div style={{fontWeight:600,marginBottom:6}}>Sin videollamadas programadas</div>
        <div style={{fontSize:13}}>Programa una clase virtual y los estudiantes serán notificados automáticamente.</div>
      </div></div>}

      {vlLista.map(vl=>{
        const enCurso=vl.estado==='en_curso';
        const programada=vl.estado==='programada';
        const finalizada=vl.estado==='finalizada';
        const badgeStyle={
          en_curso:  {background:'#e8f5ee',color:'#1a7a48',border:'1px solid #a8d5b5'},
          programada:{background:'#eef3fb',color:'#2462b0',border:'1px solid #b8d0f0'},
          finalizada:{background:'#f4f5f8',color:'#6b7a99',border:'1px solid #dde3f0'},
        }[vl.estado];
        const badgeTxt={en_curso:'🟢 En curso',programada:'🔵 Programada',finalizada:'⚫ Finalizada'}[vl.estado];
        const fecha=new Date(vl.programada_en);
        const fechaFmt=fecha.toLocaleDateString('es-CO',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
        const horaFmt=fecha.toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'});
        return<div key={vl.idVideoLlamada} className="card" style={{marginBottom:12,border:enCurso?'2px solid #1a7a48':'1px solid var(--border-lt)',position:'relative',overflow:'hidden'}}>
          {enCurso&&<div style={{position:'absolute',top:0,left:0,right:0,height:3,background:'linear-gradient(90deg,#1a7a48,#2ecc71)'}}/>}
          <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start',flexWrap:'wrap',gap:12}}>
            <div style={{flex:1}}>
              <div style={{display:'flex',alignItems:'center',gap:10,marginBottom:6}}>
                <span style={{fontSize:22}}>🎥</span>
                <div>
                  <div style={{fontWeight:700,fontSize:15,color:'var(--navy)'}}>{vl.titulo}</div>
                  <div style={{fontSize:12,color:'var(--muted)',marginTop:1,textTransform:'capitalize'}}>{fechaFmt} · {horaFmt} · {vl.duracion_min} min</div>
                </div>
              </div>
              {vl.descripcion&&<div style={{fontSize:13,color:'var(--muted)',marginBottom:8,paddingLeft:32}}>{vl.descripcion}</div>}
              <div style={{paddingLeft:32,display:'flex',alignItems:'center',gap:10,flexWrap:'wrap'}}>
                <span style={{...badgeStyle,padding:'3px 10px',borderRadius:20,fontSize:11,fontWeight:600}}>{badgeTxt}</span>
                {programada&&<span style={{fontSize:12,color:'var(--muted)'}}>Empieza en {vl.minutosParaInicio>59?Math.floor(vl.minutosParaInicio/60)+'h '+vl.minutosParaInicio%60+'min':vl.minutosParaInicio+' min'}</span>}
                <code style={{fontSize:10,color:'var(--muted)',background:'var(--bg)',padding:'2px 6px',borderRadius:4}}>{vl.roomName}</code>
              </div>
            </div>
            <div style={{display:'flex',gap:8,alignItems:'center',flexShrink:0}}>
              {!finalizada&&<a
                href={`https://meet.jit.si/${vl.roomName}`}
                target="_blank"
                rel="noopener noreferrer"
                className="btn btn-primary btn-sm"
              >
                🎥 Unirse a la clase
              </a>}
              {!finalizada&&<button className="btn btn-outline btn-sm" onClick={()=>finalizarVL(vl.idVideoLlamada)} title="Marcar como finalizada">✅ Finalizar</button>}
              {finalizada&&<label className="btn btn-outline btn-sm" style={{cursor:'pointer'}} title="Subir grabación">
                📼 Subir grabación
                <input type="file" accept="video/mp4" style={{display:'none'}} onChange={e=>e.target.files[0]&&subirGrabacion(vl.idVideoLlamada,e.target.files[0])}/>
              </label>}
              {(vl.grabaciones||[]).length>0&&<span style={{fontSize:12,color:'#1a7a48',fontWeight:600}}>
                🎞 {vl.grabaciones.length} grabación{vl.grabaciones.length>1?'es':''}
              </span>}
              <button className="btn btn-danger btn-sm" onClick={()=>eliminarVL(vl.idVideoLlamada)} title="Eliminar">🗑</button>
            </div>
          </div>
          {/* Grabaciones disponibles */}
          {(vl.grabaciones||[]).length>0&&<div style={{borderTop:'1px solid var(--border-lt)',padding:'10px 0 0',marginTop:10}}>
            <div style={{fontSize:12,fontWeight:600,color:'var(--muted)',marginBottom:8}}>🎞 Grabaciones disponibles:</div>
            {vl.grabaciones.map((g,gi)=><div key={gi} style={{display:'flex',alignItems:'center',gap:10,padding:'6px 0',borderBottom:'1px solid var(--border-lt)'}}>
              <span style={{fontSize:14}}>🎥</span>
              <div style={{flex:1}}>
                <div style={{fontSize:13,fontWeight:500}}>{g.nombre||`Grabación ${gi+1}`}</div>
                <div style={{fontSize:11,color:'var(--muted)'}}>{g.tamano_mb>0?`${g.tamano_mb} MB`:''} · {g.creada_en?.slice(0,10)}</div>
              </div>
              <a href={g.urlGrabacion} target="_blank" rel="noopener noreferrer" className="btn btn-outline btn-sm">⬇ Descargar</a>
            </div>)}
          </div>}
        </div>;
      })}

      {/* Modal crear videollamada */}
      {vlModal&&<Modal title="🎥 Programar Clase Virtual" onClose={()=>setVlModal(false)}
        footer={<><button className="btn btn-outline" onClick={()=>setVlModal(false)}>Cancelar</button>
                  <button className="btn btn-primary" onClick={crearVL} disabled={saving}>{saving?'Creando...':'🎥 Programar'}</button></>}>
        <div className="form-group">
          <label className="form-label">Título de la clase</label>
          <input className="form-input" placeholder="Ej: Clase 10 — Patrón Observer"
            value={fmVl.titulo} onChange={e=>setFmVl({...fmVl,titulo:e.target.value})}/>
        </div>
        <div className="form-group">
          <label className="form-label">Descripción (opcional)</label>
          <textarea className="form-input" rows={2} placeholder="Temas a tratar en la sesión..."
            value={fmVl.descripcion} onChange={e=>setFmVl({...fmVl,descripcion:e.target.value})}/>
        </div>
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:12}}>
          <div className="form-group">
            <label className="form-label">Fecha y hora</label>
            <input className="form-input" type="datetime-local"
              value={fmVl.programada_en} onChange={e=>setFmVl({...fmVl,programada_en:e.target.value})}/>
          </div>
          <div className="form-group">
            <label className="form-label">Duración (minutos)</label>
            <select className="form-input form-select" value={fmVl.duracion_min} onChange={e=>setFmVl({...fmVl,duracion_min:parseInt(e.target.value)})}>
              {[30,45,60,90,120,180].map(d=><option key={d} value={d}>{d} min</option>)}
            </select>
          </div>
        </div>
        <div style={{background:'#eef3fb',border:'1px solid #c5d8f5',borderRadius:8,padding:'10px 14px',fontSize:12,color:'#2462b0',marginTop:8}}>
          💡 La sala se crea en <strong>meet.jit.si</strong> — no requiere cuenta ni instalación. Los estudiantes recibirán notificación automática.
        </div>
      </Modal>}
    </div>}

    {/* Modal material */}
    {modalMat&&<Modal title="📤 Publicar Material" onClose={()=>setModalMat(false)} footer={<><button className="btn btn-outline" onClick={()=>setModalMat(false)}>Cancelar</button><button className="btn btn-primary" onClick={subirMat} disabled={saving}>{saving?'Subiendo...':'📤 Publicar'}</button></>}>
      <div className="form-group"><label className="form-label">Título</label><input className="form-input" value={fmMat.titulo} onChange={e=>setFmMat({...fmMat,titulo:e.target.value})}/></div>
      <div className="form-group"><label className="form-label">Tipo</label><select className="form-input form-select" value={fmMat.tipo} onChange={e=>setFmMat({...fmMat,tipo:e.target.value})}><option value="pdf">PDF</option><option value="video">Video</option><option value="presentacion">Presentación</option><option value="otro">Otro</option></select></div>
      <div className="form-group"><label className="form-label">Descripción</label><textarea className="form-input" rows={2} value={fmMat.descripcion} onChange={e=>setFmMat({...fmMat,descripcion:e.target.value})}/></div>
      <div className="form-group"><label className="form-label">Archivo (máx 200MB)</label><input id="mat-file" type="file" className="form-input" accept=".pdf,.pptx,.ppt,.doc,.docx,.mp4,.avi,.mov,.zip"/></div>
    </Modal>}
    {/* Modal actividad */}
    {modalAct&&<Modal title="➕ Nueva Actividad" onClose={()=>setModalAct(false)} footer={<><button className="btn btn-outline" onClick={()=>setModalAct(false)}>Cancelar</button><button className="btn btn-primary" onClick={crearAct} disabled={saving}>{saving?'Guardando...':'✅ Crear'}</button></>}>
      <div className="form-group"><label className="form-label">Título</label><input className="form-input" value={fmAct.titulo} onChange={e=>setFmAct({...fmAct,titulo:e.target.value})}/></div>
      <div className="form-group"><label className="form-label">Descripción</label><textarea className="form-input" rows={3} value={fmAct.descripcion} onChange={e=>setFmAct({...fmAct,descripcion:e.target.value})}/></div>
      <div className="form-grid"><div className="form-group"><label className="form-label">Fecha límite</label><input type="datetime-local" className="form-input" value={fmAct.fechaEntrega} onChange={e=>setFmAct({...fmAct,fechaEntrega:e.target.value})}/></div><div className="form-group"><label className="form-label">Puntaje máx</label><input type="number" className="form-input" min={0} max={10} step={0.5} value={fmAct.puntaje} onChange={e=>setFmAct({...fmAct,puntaje:e.target.value})}/></div></div>
    </Modal>}
    {/* Modal foro */}
    {modalForo&&<Modal title="💬 Crear Foro" onClose={()=>setModalForo(false)} footer={<><button className="btn btn-outline" onClick={()=>setModalForo(false)}>Cancelar</button><button className="btn btn-primary" onClick={crearForo} disabled={saving}>✅ Crear</button></>}>
      <div className="form-group"><label className="form-label">Título</label><input className="form-input" value={fmForo.titulo} onChange={e=>setFmForo({...fmForo,titulo:e.target.value})}/></div>
      <div className="form-group"><label className="form-label">Descripción</label><textarea className="form-input" rows={3} value={fmForo.descripcion} onChange={e=>setFmForo({...fmForo,descripcion:e.target.value})}/></div>
    </Modal>}
    {/* Modal calificar */}
    {modalCalif&&<Modal title={`📝 Calificar: ${modalCalif.estudiante}`} onClose={()=>setModalCalif(null)} footer={<><button className="btn btn-outline" onClick={()=>setModalCalif(null)}>Cancelar</button><button className="btn btn-primary" onClick={califEntrega} disabled={saving}>{saving?'Guardando...':'💾 Guardar Nota'}</button></>}>
      <div className="form-group"><label className="form-label">Nota (0–{modalCalif.puntaje_max||5})</label><input type="number" className="form-input" min={0} max={modalCalif.puntaje_max||5} step={0.1} value={fmCalif.nota} onChange={e=>setFmCalif({...fmCalif,nota:e.target.value})}/></div>
      <div className="form-group"><label className="form-label">Retroalimentación</label><textarea className="form-input" rows={3} value={fmCalif.feedback} onChange={e=>setFmCalif({...fmCalif,feedback:e.target.value})}/></div>
      {modalCalif.archivoEntrega&&<a href={`/univirtual/api/descargar.php?id=${modalCalif.idEntrega}&tipo=entrega&preview=1`} className="btn btn-outline btn-sm" target="_blank">📄 Ver entrega</a>}
    </Modal>}
  </div>;
};

// ── CALIFICAR (vista global) ───────────────────────────────────────────
const Calificar=()=>{
  const[mats,setMats]=useState([]);const[sel,setSel]=useState(null);const[entregas,setEntregas]=useState([]);const[modal,setModal]=useState(null);const[nota,setNota]=useState('');const[fb,setFb]=useState('');const{idProf}=window.__S;
  useEffect(()=>{api.get(`/profesor.php?action=dashboard&idProf=${idProf}`).then(r=>r.status==='ok'&&setMats(r.data.materias||[]));},[]);
  const verEntregas=async m=>{setSel(m);const r=await api.get(`/profesor.php?action=entregas&idAula=${m.idAula}&idProf=${idProf}`);if(r.status==='ok')setEntregas(r.data);};
  const guardar=async()=>{if(!nota)return;await api.post('/profesor.php',{action:'calificar_entrega',idEntrega:modal.idEntrega,nota:parseFloat(nota),feedback:fb});setModal(null);setNota('');setFb('');if(sel)verEntregas(sel);};
  return<div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}><div><div className="wb-label">Calificaciones</div><div className="wb-name" style={{fontSize:22}}>Calificar Entregas</div></div></div>
    <div style={{display:'grid',gridTemplateColumns:'240px 1fr',gap:18}}>
      <div className="card" style={{margin:0}}><div className="card-header"><span className="card-title">📚 Mis Materias</span></div>
        {mats.map(m=><div key={m.idMateria} onClick={()=>verEntregas(m)} style={{padding:'10px 16px',cursor:'pointer',borderBottom:'1px solid var(--border-lt)',background:sel?.idMateria===m.idMateria?'var(--bg)':'',borderLeft:`3px solid ${sel?.idMateria===m.idMateria?m.color:'transparent'}`}}><div style={{fontWeight:600,fontSize:13}}>{m.nombre}</div><div style={{fontSize:11,color:'var(--muted)'}}>{m.sinCalificar||0} por calificar</div></div>)}
      </div>
      <div>{!sel?<div className="card"><div style={{padding:40,textAlign:'center',color:'var(--muted)'}}>Selecciona una materia</div></div>:<div className="card" style={{margin:0}}>
        <div className="card-header"><span className="card-title">Entregas — {sel.nombre}</span><span className="tag tag-warn">{entregas.filter(e=>!e.nota).length} pendientes</span></div>
        <table className="tbl"><thead><tr><th>Estudiante</th><th>Actividad</th><th>Entregado</th><th>Nota</th><th></th></tr></thead>
        <tbody>{entregas.map(e=><tr key={e.idEntrega}>
          <td style={{fontWeight:600}}>{e.estudiante}</td><td style={{fontSize:13}}>{e.actividad}</td>
          <td style={{fontSize:12,color:'var(--muted)'}}>{new Date(e.entregado_en).toLocaleDateString('es-CO')}</td>
          <td>{e.nota?<span style={{fontWeight:700,color:'var(--green)'}}>{e.nota}</span>:<span className="tag tag-warn">Pendiente</span>}</td>
          <td><button className="btn btn-sm btn-primary" onClick={()=>setModal(e)}>✏️ Calificar</button></td>
        </tr>)}</tbody></table>
      </div>}</div>
    </div>
    {modal&&<Modal title={`Calificar: ${modal.estudiante}`} onClose={()=>setModal(null)} footer={<><button className="btn btn-outline" onClick={()=>setModal(null)}>Cancelar</button><button className="btn btn-primary" onClick={guardar}>💾 Guardar</button></>}>
      <div className="form-group"><label className="form-label">Nota</label><input type="number" className="form-input" min={0} max={5} step={0.1} value={nota} onChange={e=>setNota(e.target.value)}/></div>
      <div className="form-group"><label className="form-label">Retroalimentación</label><textarea className="form-input" rows={3} value={fb} onChange={e=>setFb(e.target.value)}/></div>
    </Modal>}
  </div>;
};

// ── REPORTES PROFESOR ──────────────────────────────────────────────────
const Reportes=()=>{
  const{idProf}=window.__S;const[mats,setMats]=useState([]);
  useEffect(()=>{api.get(`/profesor.php?action=dashboard&idProf=${idProf}`).then(r=>r.status==='ok'&&setMats(r.data.materias||[]));},[]);
  return<div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}><div><div className="wb-label">Análisis</div><div className="wb-name" style={{fontSize:22}}>Reportes</div></div></div>
    <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:18}}>
      {mats.map(m=>(
        <div key={m.idMateria} className="card" style={{margin:0,borderTop:`4px solid ${m.color}`}}>
          <div className="card-header"><span className="card-title">{m.nombre}</span></div>
          <div className="card-body">
            <div style={{display:'flex',gap:10,flexWrap:'wrap'}}>
              <a href={`/univirtual/api/reporte_asistencia.php?idAula=${m.idAula}&formato=xlsx`} target="_blank" className="btn btn-success btn-sm">📊 Asistencia XLSX</a>
              <a href={`/univirtual/api/reporte_notas.php?idMateria=${m.idMateria}&formato=xlsx`} target="_blank" className="btn btn-success btn-sm">📊 Notas XLSX</a>
              <a href={`/univirtual/api/reporte_asistencia.php?idAula=${m.idAula}&formato=pdf`} target="_blank" className="btn btn-primary btn-sm">📄 Asistencia PDF</a>
              <a href={`/univirtual/api/reporte_notas.php?idMateria=${m.idMateria}&formato=pdf`} target="_blank" className="btn btn-primary btn-sm">📄 Notas PDF</a>
            </div>
          </div>
        </div>
      ))}
    </div>
  </div>;
};

// ── APP PROFESOR ───────────────────────────────────────────────────────
// ── NOTIFICACIONES PROFESOR ────────────────────────────────────────────
const NotificacionesProf=({idProf})=>{
  const[data,setData]=useState(null);
  const colors={material:'#2462b0',tarea:'#e07b39',nota:'#1a7a48',foro:'#6d28d9',sistema:'#c0392b',info:'#888',asistencia:'#d4a843',videollamada:'#0891b2'};
  const icons={material:'📄',tarea:'📋',nota:'📊',foro:'💬',sistema:'⚙️',asistencia:'✅',videollamada:'🎥',info:'🔔'};
  const cargar=useCallback(()=>{
    api.get(`/profesor.php?action=notificaciones&idProf=${idProf}`).then(r=>r.status==='ok'&&setData(r.data));
  },[idProf]);
  useEffect(()=>{cargar();const t=setInterval(cargar,30000);return()=>clearInterval(t);},[cargar]);
  const marcarTodas=async()=>{
    await api.post('/profesor.php',{action:'marcar_notifs',idProf});
    setData(prev=>prev?.map(n=>({...n,leida:1})));
  };
  const handleClick=async(n)=>{
    if(!n.leida){
      await api.post('/profesor.php',{action:'marcar_notif',idNotif:n.idNotif,idProf});
      setData(prev=>prev?.map(x=>x.idNotif===n.idNotif?{...x,leida:1}:x));
    }
    if(n.url&&n.url!=='#') window.location.href=n.url;
  };
  if(!data)return<Spinner/>;
  const sinLeer=data.filter(n=>!n.leida).length;
  return<div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}>
      <div>
        <div className="wb-label">Sistema</div>
        <div className="wb-name" style={{fontSize:22}}>🔔 Notificaciones</div>
        <div style={{fontSize:13,color:'rgba(255,255,255,.6)',marginTop:4}}>Actualización automática cada 30 segundos</div>
      </div>
      <div className="wb-stats">
        <div className="wb-stat"><div className="wb-stat-num">{sinLeer}</div><div className="wb-stat-lbl">Sin leer</div></div>
        <div className="wb-stat"><div className="wb-stat-num">{data.length}</div><div className="wb-stat-lbl">Total</div></div>
      </div>
    </div>
    {sinLeer>0&&<div style={{display:'flex',justifyContent:'flex-end',marginBottom:12}}>
      <button className="btn btn-outline btn-sm" onClick={marcarTodas}>✓ Marcar todas como leídas</button>
    </div>}
    <div className="card">
      <div className="card-body" style={{padding:'8px 20px'}}>
        {data.length===0&&<p style={{textAlign:'center',padding:36,color:'var(--muted)'}}>Sin notificaciones.</p>}
        {data.map((n,i)=>(
          <div key={n.idNotif||i} className="notif-item"
            onClick={()=>handleClick(n)}
            style={{opacity:n.leida?0.7:1,cursor:n.url&&n.url!=='#'?'pointer':'default',
              background:!n.leida?'#fafbff':'transparent',borderRadius:8}}>
            <div style={{display:'flex',gap:12,alignItems:'flex-start'}}>
              <div style={{width:36,height:36,borderRadius:9,background:`${colors[n.tipo]||'#888'}20`,
                display:'flex',alignItems:'center',justifyContent:'center',fontSize:18,flexShrink:0}}>
                {icons[n.tipo]||'🔔'}
              </div>
              <div style={{flex:1,minWidth:0}}>
                <div className="notif-title" style={{fontWeight:n.leida?500:700}}>{n.titulo}</div>
                <div className="notif-msg">{n.mensaje}</div>
                <div style={{display:'flex',alignItems:'center',gap:8,marginTop:4}}>
                  <div className="notif-time">{n.creado_en?.slice(0,16).replace('T',' ')}</div>
                  {n.url&&n.url!=='#'&&<span style={{fontSize:11,color:'#2462b0'}}>→ Ver detalle</span>}
                </div>
              </div>
              {!n.leida&&<div style={{width:9,height:9,borderRadius:'50%',background:colors[n.tipo]||'#2462b0',marginTop:8,flexShrink:0}}/>}
            </div>
          </div>
        ))}
      </div>
    </div>
  </div>;
};

// ── CONFIGURACIÓN (compartida entre roles) ─────────────────────────────
const Configuracion=({rol})=>{
  const u=window.__S;
  const[form,setForm]=useState({nombre:u.nombre||'',email:u.email||''});
  const[pwd,setPwd]=useState({actual:'',nueva:'',confirmar:''});
  const[tab,setTab]=useState('perfil');
  const[msg,setMsg]=useState(null);
  const[saving,setSaving]=useState(false);

  const guardarPerfil=async()=>{
    if(!form.nombre.trim())return setMsg({type:'err',txt:'El nombre no puede estar vacío'});
    setSaving(true);
    const r=await api.post(`/${rol==='admin'?'usuarios':rol}.php`,{action:'actualizar_perfil',nombre:form.nombre});
    setSaving(false);
    setMsg(r.status==='ok'?{type:'ok',txt:'Perfil actualizado correctamente'}:{type:'err',txt:r.mensaje||'Error al guardar'});
    if(r.status==='ok') window.__S.nombre=form.nombre;
  };

  const cambiarPassword=async()=>{
    if(pwd.nueva.length<6)return setMsg({type:'err',txt:'La contraseña debe tener al menos 6 caracteres'});
    if(pwd.nueva!==pwd.confirmar)return setMsg({type:'err',txt:'Las contraseñas no coinciden'});
    if(!pwd.actual)return setMsg({type:'err',txt:'Ingresa tu contraseña actual'});
    setSaving(true);
    const r=await api.post(`/${rol==='admin'?'usuarios':rol}.php`,{action:'cambiar_password',actual:pwd.actual,nueva:pwd.nueva});
    setSaving(false);
    if(r.status==='ok'){setPwd({actual:'',nueva:'',confirmar:''});setMsg({type:'ok',txt:'Contraseña actualizada correctamente'});}
    else setMsg({type:'err',txt:r.mensaje||'Error al cambiar contraseña'});
  };

  return<div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}>
      <div>
        <div className="wb-label">Cuenta</div>
        <div className="wb-name" style={{fontSize:22}}>⚙️ Configuración</div>
      </div>
    </div>

    <div style={{display:'flex',gap:8,marginBottom:16}}>
      {[['perfil','👤 Perfil'],['password','🔑 Contraseña']].map(([id,lbl])=>(
        <button key={id} onClick={()=>{setTab(id);setMsg(null);}} style={{
          padding:'8px 18px',borderRadius:20,border:'none',cursor:'pointer',fontSize:13,fontWeight:600,
          background:tab===id?'#0d1f4e':'#fff',color:tab===id?'#fff':'#4a5568',
          boxShadow:'0 1px 4px rgba(0,0,0,.08)'}}>
          {lbl}
        </button>
      ))}
    </div>

    {msg&&<div style={{padding:'10px 16px',borderRadius:8,marginBottom:16,fontSize:13,
      background:msg.type==='ok'?'#e8f5ee':'#fdecea',
      color:msg.type==='ok'?'#1a7a48':'#c0392b',
      border:`1px solid ${msg.type==='ok'?'#a8d5b5':'#f5a7a7'}`}}>
      {msg.type==='ok'?'✅':'❌'} {msg.txt}
    </div>}

    {tab==='perfil'&&<div className="card">
      <div className="card-header"><span className="card-title">👤 Información del Perfil</span></div>
      <div className="card-body">
        <div style={{display:'flex',alignItems:'center',gap:20,padding:'16px 0',borderBottom:'1px solid var(--border-lt)',marginBottom:20}}>
          <div style={{width:72,height:72,borderRadius:'50%',background:'linear-gradient(135deg,#0d1f4e,#2462b0)',color:'#fff',display:'flex',alignItems:'center',justifyContent:'center',fontSize:26,fontWeight:700}}>
            {u.initials}
          </div>
          <div>
            <div style={{fontSize:18,fontWeight:700,color:'var(--navy)'}}>{u.nombre}</div>
            <div style={{fontSize:13,color:'var(--muted)',marginTop:2}}>{u.email||''}</div>
            <span style={{display:'inline-block',marginTop:6,padding:'2px 10px',borderRadius:12,fontSize:11,fontWeight:600,
              background:rol==='admin'?'#fdecea':rol==='directivo'?'#fff3e0':rol==='profesor'?'#eef3fb':'#e8f5ee',
              color:rol==='admin'?'#c0392b':rol==='directivo'?'#e07b39':rol==='profesor'?'#2462b0':'#1a7a48'}}>
              {rol.charAt(0).toUpperCase()+rol.slice(1)}
            </span>
          </div>
        </div>
        <div className="form-group">
          <label className="form-label">Nombre completo</label>
          <input className="form-input" value={form.nombre} onChange={e=>setForm({...form,nombre:e.target.value})}/>
        </div>
        <div className="form-group">
          <label className="form-label">Correo institucional</label>
          <input className="form-input" value={form.email} disabled style={{background:'var(--bg)',cursor:'not-allowed',color:'var(--muted)'}}/>
          <small style={{color:'var(--muted)',fontSize:11}}>El correo institucional no puede modificarse.</small>
        </div>
        <button className="btn btn-primary" onClick={guardarPerfil} disabled={saving}>
          {saving?'Guardando...':'💾 Guardar cambios'}
        </button>
      </div>
    </div>}

    {tab==='password'&&<div className="card">
      <div className="card-header"><span className="card-title">🔑 Cambiar Contraseña</span></div>
      <div className="card-body">
        <div style={{padding:'12px 16px',background:'#eef3fb',borderRadius:8,fontSize:13,color:'#2462b0',marginBottom:20,border:'1px solid #b8d0f0'}}>
          💡 La contraseña debe tener mínimo 6 caracteres. Se recomienda combinar letras, números y símbolos.
        </div>
        {[['actual','Contraseña actual','Tu contraseña actual'],['nueva','Nueva contraseña','Mínimo 6 caracteres'],['confirmar','Confirmar nueva contraseña','Repite la nueva contraseña']].map(([k,lbl,ph])=>(
          <div key={k} className="form-group">
            <label className="form-label">{lbl}</label>
            <input className="form-input" type="password" placeholder={ph}
              value={pwd[k]} onChange={e=>setPwd({...pwd,[k]:e.target.value})}/>
          </div>
        ))}
        <button className="btn btn-primary" onClick={cambiarPassword} disabled={saving}>
          {saving?'Cambiando...':'🔑 Cambiar contraseña'}
        </button>
      </div>
    </div>}
  </div>;
};

// ── MATERIAS (Vista separada) ──────────────────────────────────────────
const MateriasProf=({onAula})=>{
  const[mats,setMats]=useState(null);
  const{idProf}=window.__S;
  useEffect(()=>{
    api.get(`/profesor.php?action=dashboard&idProf=${idProf}`)
      .then(r=>r.status==='ok'&&setMats(r.data.materias));
  },[idProf]);
  if(!mats)return<Spinner/>;
  return<div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}>
      <div><div className="wb-label">Académico</div><div className="wb-name" style={{fontSize:22}}>📚 Mis Materias</div></div>
    </div>
    <div className="card" style={{maxWidth:800}}>
      <div className="card-body">
        {mats.length===0&&<p style={{color:'var(--muted)',textAlign:'center',padding:36}}>Sin materias asignadas.</p>}
        {mats.map(m=>{
          const pct=Math.min(100,Math.round(((m.total_materiales||0)+(m.total_actividades||0))/10*100));
          return<div key={m.idMateria} className="mat-row" onClick={()=>{onAula(m);window.scrollTo(0,0);}} style={{padding:'16px 20px',marginBottom:12,border:`1px solid var(--border-lt)`,borderRadius:12,background:'#fafbff',cursor:'pointer',display:'flex',alignItems:'center'}}>
            <div className="mat-color" style={{background:m.color,width:16,height:16,borderRadius:4}}/>
            <div className="mat-info" style={{marginLeft:16,flex:1}}>
              <div className="mat-name" style={{fontSize:16,fontWeight:700,color:'var(--navy)'}}>{m.nombre}</div>
              <div style={{fontSize:13,color:'var(--muted)',marginTop:4}}>Código: <strong>{m.codigo}</strong></div>
              <div className="pbar-wrap" style={{marginTop:10,height:6,background:'var(--border-lt)'}}><div className="pbar-fill" style={{width:`${pct}%`,background:m.color}}/></div>
            </div>
            <div className="mat-right" style={{textAlign:'right',marginLeft:20}}>
              {m.sinCalificar>0?<span className="tag tag-warn" style={{fontSize:12,padding:'4px 10px'}}>{m.sinCalificar} por calificar</span>:<span className="tag tag-ok" style={{fontSize:12,padding:'4px 10px'}}>Al día</span>}
              <div style={{fontSize:12,marginTop:8,color:'var(--muted)'}}>{m.total_materiales||0} recusos · {m.total_actividades||0} act.</div>
            </div>
          </div>;
        })}
      </div>
    </div>
  </div>;
};

// ── APP PROFESOR ───────────────────────────────────────────────────────
const ProfesorApp=()=>{
  const q = new URLSearchParams(window.location.search);
  const[vista,setVista]=useState(q.get('view') || 'dashboard');
  const[aulaActiva,setAulaActiva]=useState(null);
  const[badgeNotifs,setBadgeNotifs]=useState(0);
  const{nombre,initials,idProf}=window.__S;
  const nav=[
    {id:'dashboard',     ico:'📊',lbl:'Dashboard'},
    {id:'materias',      ico:'📚',lbl:'Mis Materias'},
    {id:'calificar',     ico:'✏️', lbl:'Calificar'},
    {id:'reportes',      ico:'📈',lbl:'Reportes'},
    {id:'notificaciones',ico:'🔔',lbl:'Notificaciones',badge:badgeNotifs},
    {id:'configuracion', ico:'⚙️', lbl:'Configuración'},
  ];
  const irAula=m=>{setAulaActiva(m);setVista('aula');};
  const titulo={dashboard:'Dashboard',materias:'Mis Materias',aula:aulaActiva?.nombre||'Aula Virtual',
    calificar:'Calificar',reportes:'Reportes',notificaciones:'Notificaciones',
    configuracion:'Configuración'}[vista]||'';
  return<>
    <aside className="sidebar">
      <div className="sb-brand"><div className="sb-brand-name">UNI<em>VIRTUAL</em></div><div className="sb-brand-sub">Plataforma Académica</div></div>
      <div className="sb-user"><div className="sb-avatar">{initials}</div><div><div className="sb-user-name">{nombre.split(' ').slice(0,2).join(' ')}</div><div className="sb-user-role">Profesor</div></div></div>
      <div className="sb-section">Principal</div>
      <nav className="sb-nav">
        {nav.map(n=><div key={n.id} className={`sb-item ${vista===n.id?'active':''}`} onClick={()=>{setVista(n.id);setAulaActiva(null);}}>
          <span className="sb-item-ico">{n.ico}</span>
          <span className="sb-item-lbl">{n.lbl}</span>
          {n.badge>0&&<span style={{marginLeft:'auto',background:'#c0392b',color:'#fff',borderRadius:10,padding:'1px 6px',fontSize:10,fontWeight:700}}>{n.badge}</span>}
        </div>)}
        {aulaActiva&&<div className="sb-item active" style={{background:'rgba(212,168,67,.08)',marginTop:4}}>
          <span className="sb-item-ico">🏫</span><span className="sb-item-lbl" style={{fontSize:12}}>{aulaActiva.nombre.slice(0,20)}</span>
        </div>}
      </nav>
      <div className="sb-logout"><a href="/univirtual/pages/auth/logout.php" className="sb-logout-btn"><span className="sb-logout-ico">↩</span><span>Cerrar sesión</span></a></div>
    </aside>
    <header className="topbar">
      <div className="tb-left">
        <div className="tb-breadcrumb"><span>UNI-VIRTUAL</span><span>›</span><strong>Profesor</strong>{vista==='aula'&&<><span>›</span><strong>{aulaActiva?.nombre}</strong></>}</div>
        <div className="tb-title">{titulo}</div>
      </div>
      <div className="tb-right">
        <span className="tb-semester">2026-I</span>
        <div className="tb-notif" onClick={()=>setVista('notificaciones')} style={{cursor:'pointer'}}>
          <span>🔔</span>
          {badgeNotifs>0&&<div className="tb-notif-dot"/>}
        </div>
        <div style={{width:32,height:32,borderRadius:'50%',background:'#d4a843',color:'#0d1f4e',display:'flex',alignItems:'center',justifyContent:'center',fontWeight:700,fontSize:13,cursor:'pointer'}} onClick={()=>setVista('configuracion')}>{initials}</div>
      </div>
    </header>
    <main className="main">
      {vista==='dashboard'&&<Dashboard onAula={irAula} setVistaPrincipal={setVista}/>}
      {vista==='materias'&&<MateriasProf onAula={irAula}/>}
      {vista==='aula'&&aulaActiva&&<Aula materia={aulaActiva} onBack={()=>setVista('dashboard')}/>}
      {vista==='calificar'&&<Calificar/>}
      {vista==='reportes'&&<Reportes/>}
      {vista==='notificaciones'&&<NotificacionesProf idProf={idProf}/>}
      {vista==='configuracion'&&<Configuracion rol="profesor"/>}
    </main>
  </>;
};
ReactDOM.createRoot(document.getElementById('root')).render(<ProfesorApp/>);
</script>
</body>
</html>
<?php
    }
}
(new ProfesorView())->render();