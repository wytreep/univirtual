<?php
require_once __DIR__.'/../../config/config.php';
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/View.php';

/**
 * AdminView — Panel de Administración
 * Clase responsable de: autenticación, inyección de sesión y renderizado HTML.
 * Toda la lógica de negocio → api/v1/ (Controllers)
 */
class AdminView extends View {

    public function __construct() {
        parent::__construct('admin');
    }

    public function render(): void {
        $s = $this->sessionJS();
        $this->html($s);
    }

    private function html(string $s): void { ?>
<!DOCTYPE html><html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>UNI-VIRTUAL — Administración</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=DM+Serif+Display:ital@0;1&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<script src="https://unpkg.com/react@18/umd/react.development.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="/univirtual/assets/css/panel.css">
</head>
<body>
<div id="root"></div>
<script>window.__S=<?= $s ?>;window.API='/univirtual/api/v1';window.BASE='/univirtual';</script>
<script type="text/babel">
const{useState,useEffect,useCallback,useRef}=React;
const api={
  get:(u)=>fetch(API+u,{credentials:'include'}).then(r=>r.json()),
  post:(u,b)=>fetch(API+u,{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify(b)}).then(r=>r.json()),
  put:(u,b)=>fetch(API+u,{method:'PUT',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify(b)}).then(r=>r.json()),
  del:(u)=>fetch(API+u,{method:'DELETE',credentials:'include'}).then(r=>r.json()),
};
const exportCSV=(rows,name)=>{if(!rows?.length)return;const k=Object.keys(rows[0]);const csv=[k.join(','),...rows.map(r=>k.map(c=>`"${String(r[c]??'').replace(/"/g,'""')}"`).join(','))].join('\n');const a=Object.assign(document.createElement('a'),{href:'data:text/csv;charset=utf-8,\uFEFF'+encodeURIComponent(csv),download:`${name}_${new Date().toISOString().slice(0,10)}.csv`});document.body.appendChild(a);a.click();document.body.removeChild(a);};
const ChartCanvas=({type,data,options={}})=>{const r=useRef();const i=useRef();useEffect(()=>{if(!r.current||!data)return;if(i.current){i.current.destroy();i.current=null;}i.current=new Chart(r.current,{type,data,options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{font:{family:'DM Sans',size:11}}}},...options}});return()=>{if(i.current)i.current.destroy();};},[data]);return<canvas ref={r} style={{width:'100%',height:'100%'}}/>;};
const Spinner=()=><div className="loading-center"><div className="spinner"/><span>Cargando...</span></div>;
const Alert=({type,msg,onClose})=>msg?<div className={`alert alert-${type}`}>{msg}<span onClick={onClose} style={{cursor:'pointer',marginLeft:8}}>✕</span></div>:null;
const Modal=({title,onClose,children,footer})=>(
  <div className="modal-overlay" onClick={e=>e.target===e.currentTarget&&onClose()}>
    <div className="modal">
      <div className="modal-header"><span className="modal-title">{title}</span><button className="modal-close" onClick={onClose}>✕</button></div>
      <div className="modal-body">{children}</div>
      {footer&&<div className="modal-footer">{footer}</div>}
    </div>
  </div>
);
const rolBadge=r=>{const m={estudiante:'bdg-est',profesor:'bdg-prof',admin:'bdg-adm'};return<span className={`bdg ${m[r]||'bdg-est'}`}>{r}</span>;};

// ── DASHBOARD ADMIN ───────────────────────────────────────────────────────
const Dashboard=()=>{
  const[stats,setStats]=useState(null);
  useEffect(()=>{api.get('/stats.php').then(r=>r.status==='ok'&&setStats(r.data));},[]);
  if(!stats)return<Spinner/>;
  const C=['#0d1f4e','#2462b0','#1a7a48','#d4a843','#c0392b','#6d28d9'];
  const dRoles={labels:stats.roles.map(r=>r.rol),datasets:[{data:stats.roles.map(r=>r.total),backgroundColor:C,borderWidth:2,borderColor:'#fff'}]};
  const dMat={labels:stats.topMaterias.map(m=>m.nombre.slice(0,12)),datasets:[{label:'Inscritos',data:stats.topMaterias.map(m=>m.inscritos),backgroundColor:'#2462b0',borderRadius:5}]};
  const dAct={labels:['Activos','Inactivos'],datasets:[{data:[stats.usuarios.activos,stats.usuarios.inactivos],backgroundColor:['#1a7a48','#c0392b'],borderWidth:2,borderColor:'#fff'}]};
  const dEnt={labels:stats.actividadReciente.map(d=>d.dia?.slice(5)),datasets:[{label:'Entregas',data:stats.actividadReciente.map(d=>d.entregas),borderColor:'#2462b0',backgroundColor:'rgba(36,98,176,.1)',tension:.4,fill:true,pointRadius:4}]};
  const sc={plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}};
  return<div className="page">
    {/* Banner */}
    <div className="welcome-banner" style={{marginBottom:22}}>
      <div>
        <div className="wb-label">Panel de Administración</div>
        <div className="wb-name">UNI-<em>VIRTUAL</em></div>
        <div className="wb-sub">Sistema académico virtual — 2026-I</div>
      </div>
      <div className="wb-stats">
        {[{n:stats.totales.usuarios,l:'Usuarios'},{n:stats.totales.materias,l:'Materias'},{n:stats.sinCalificar,l:'Sin calificar'}].map(s=>(
          <div className="wb-stat" key={s.l}><div className="wb-stat-num">{s.n}</div><div className="wb-stat-lbl">{s.l}</div></div>
        ))}
      </div>
    </div>
    {/* Stats 6-col */}
    <div className="stat-grid-6">
      {[{i:'👥',l:'Usuarios',v:stats.totales.usuarios,bg:'#e8f0fb',c:'#2462b0'},{i:'🎓',l:'Estudiantes',v:stats.totales.estudiantes,bg:'#e8f5ee',c:'#1a7a48'},{i:'👨‍🏫',l:'Profesores',v:stats.totales.profesores,bg:'#fff3e0',c:'#e07b39'},{i:'📚',l:'Materias',v:stats.totales.materias,bg:'#f0ebff',c:'#6d28d9'},{i:'📄',l:'Materiales',v:stats.totales.materiales,bg:'#e8f0fb',c:'#2462b0'},{i:'⏳',l:'Sin calificar',v:stats.sinCalificar,bg:'#fdecea',c:'#c0392b'}].map(s=>(
        <div className="stat-card" key={s.l}>
          <div className="stat-ico" style={{background:s.bg}}>{s.i}</div>
          <div><div className="stat-val" style={{color:s.c}}>{s.v}</div><div className="stat-lbl">{s.l}</div></div>
        </div>
      ))}
    </div>
    {/* Charts */}
    <div className="charts-grid">
      {[{t:'Distribución por Rol',ch:<ChartCanvas type="doughnut" data={dRoles}/>,d:stats.roles},{t:'Top Materias por Inscritos',ch:<ChartCanvas type="bar" data={dMat} options={sc}/>,d:stats.topMaterias},{t:'Usuarios Activos',ch:<ChartCanvas type="pie" data={dAct}/>,d:[stats.usuarios]},{t:'Entregas 7 días',ch:<ChartCanvas type="line" data={dEnt} options={sc}/>,d:stats.actividadReciente}].map(({t,ch,d})=>(
        <div className="card" key={t} style={{margin:0}}>
          <div className="card-header"><span className="card-title">{t}</span><button className="btn btn-sm btn-outline" onClick={()=>exportCSV(d,t.replace(/ /g,'_'))}>⬇ CSV</button></div>
          <div className="card-body" style={{height:220}}>{ch}</div>
        </div>
      ))}
    </div>
    {/* Métricas rápidas + Top materias */}
    <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:18}}>
      <div className="card" style={{margin:0}}>
        <div className="card-header"><span className="card-title">📈 Métricas del Sistema</span></div>
        <div className="card-body">
          {[{l:'Prom. estudiantes/materia',v:stats.totales.materias>0?(stats.totales.estudiantes/stats.totales.materias).toFixed(1):'—'},{l:'Entregas pendientes',v:stats.sinCalificar},{l:'Usuarios inactivos',v:stats.usuarios.inactivos},{l:'Materias sin inscritos',v:stats.topMaterias.filter(m=>m.inscritos===0).length}].map(m=>(
            <div key={m.l} style={{display:'flex',justifyContent:'space-between',padding:'9px 0',borderBottom:'1px solid var(--border-lt)'}}>
              <span style={{fontSize:13,color:'var(--muted)'}}>{m.l}</span>
              <span style={{fontSize:15,fontWeight:700,color:'var(--navy)'}}>{m.v}</span>
            </div>
          ))}
        </div>
      </div>
      <div className="card" style={{margin:0}}>
        <div className="card-header"><span className="card-title">🏆 Top Materias</span></div>
        <div className="card-body">
          {stats.topMaterias.slice(0,5).map((m,i)=>{
            const pct=Math.round((m.inscritos/(stats.topMaterias[0]?.inscritos||1))*100);
            return<div key={m.idMateria} style={{marginBottom:11}}>
              <div style={{display:'flex',justifyContent:'space-between',marginBottom:4}}>
                <span style={{fontSize:13,fontWeight:600}}>{i+1}. {m.nombre}</span>
                <span style={{fontSize:12,color:'var(--muted)'}}>{m.inscritos}</span>
              </div>
              <div className="pbar-wrap"><div className="pbar-fill" style={{width:`${pct}%`,background:'#2462b0'}}/></div>
            </div>;
          })}
        </div>
      </div>
    </div>
  </div>;
};

// ── USUARIOS ──────────────────────────────────────────────────────────────
const Usuarios=()=>{
  const[users,setUsers]=useState([]);const[loading,setLoading]=useState(true);const[buscar,setBuscar]=useState('');const[filtroRol,setFiltroRol]=useState('');const[modal,setModal]=useState(null);const[form,setForm]=useState({});const[alerta,setAlerta]=useState(null);const[saving,setSaving]=useState(false);
  const cargar=useCallback(()=>{setLoading(true);api.get('/usuarios.php').then(r=>{if(r.status==='ok')setUsers(r.data);setLoading(false);});},[]);
  useEffect(()=>cargar(),[cargar]);
  const guardar=async()=>{if(!form.nombre||!form.email)return setAlerta({type:'err',msg:'Nombre y email requeridos'});setSaving(true);const res=modal==='nuevo'?await api.post('/usuarios.php',form):await api.put(`/usuarios.php?id=${form.idUsuario}`,form);setSaving(false);if(res.status==='ok'){setModal(null);cargar();setAlerta({type:'ok',msg:modal==='nuevo'?'Usuario creado':'Actualizado'});}else setAlerta({type:'err',msg:res.mensaje});};
  const toggle=async u=>{await api.put(`/usuarios.php?id=${u.idUsuario}`,{...u,activo:u.activo?0:1});cargar();};
  const eliminar=async id=>{if(!confirm('¿Eliminar?'))return;const r=await api.del(`/usuarios.php?id=${id}`);if(r.status==='ok'){cargar();setAlerta({type:'ok',msg:'Eliminado'});}else setAlerta({type:'err',msg:r.mensaje});};
  const filtrados=users.filter(u=>{const q=buscar.toLowerCase();return(u.nombre.toLowerCase().includes(q)||u.email.toLowerCase().includes(q))&&(!filtroRol||u.rol===filtroRol);});
  return<div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}>
      <div><div className="wb-label">Gestión</div><div className="wb-name" style={{fontSize:22}}>Usuarios del Sistema</div></div>
      <div className="wb-stats"><div className="wb-stat"><div className="wb-stat-num">{users.length}</div><div className="wb-stat-lbl">Total</div></div></div>
    </div>
    <Alert type={alerta?.type} msg={alerta?.msg} onClose={()=>setAlerta(null)}/>
    <div className="search-bar">
      <input className="search-input" placeholder="Buscar por nombre o email..." value={buscar} onChange={e=>setBuscar(e.target.value)}/>
      <select className="form-input form-select" style={{width:160}} value={filtroRol} onChange={e=>setFiltroRol(e.target.value)}><option value="">Todos</option><option value="estudiante">Estudiante</option><option value="profesor">Profesor</option><option value="admin">Admin</option></select>
      <button className="btn btn-success btn-sm" onClick={()=>exportCSV(filtrados,'usuarios')}>⬇ CSV</button>
      <button className="btn btn-primary" onClick={()=>{setForm({nombre:'',email:'',password:'',rol:'estudiante',activo:1});setModal('nuevo');}}>➕ Nuevo</button>
    </div>
    {loading?<Spinner/>:<div className="card">
      <div className="card-header"><span className="card-title">👥 Usuarios ({filtrados.length})</span></div>
      <table className="tbl"><thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Código/Depto</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>{filtrados.map(u=><tr key={u.idUsuario}>
        <td style={{fontWeight:600}}>{u.nombre}</td><td style={{fontSize:13,color:'var(--muted)'}}>{u.email}</td><td>{rolBadge(u.rol)}</td>
        <td style={{fontFamily:'monospace',fontSize:12}}>{u.codigoEst||u.departamento||'—'}</td>
        <td><span className={`bdg ${u.activo?'bdg-on':'bdg-off'}`} style={{cursor:'pointer'}} onClick={()=>toggle(u)}>{u.activo?'Activo':'Inactivo'}</span></td>
        <td><div style={{display:'flex',gap:5}}><button className="btn btn-sm btn-outline" onClick={()=>{setForm({...u});setModal('editar');}}>✏️</button><button className="btn btn-sm btn-danger" onClick={()=>eliminar(u.idUsuario)}>🗑</button></div></td>
      </tr>)}{filtrados.length===0&&<tr><td colSpan={6} style={{textAlign:'center',padding:24,color:'var(--muted)'}}>Sin resultados</td></tr>}</tbody></table>
    </div>}
    {(modal==='nuevo'||modal==='editar')&&<Modal title={modal==='nuevo'?'➕ Nuevo Usuario':'✏️ Editar'} onClose={()=>setModal(null)} footer={<><button className="btn btn-outline" onClick={()=>setModal(null)}>Cancelar</button><button className="btn btn-primary" onClick={guardar} disabled={saving}>{saving?'Guardando...':'💾 Guardar'}</button></>}>
      <div className="form-grid"><div className="form-group"><label className="form-label">Nombre</label><input className="form-input" value={form.nombre||''} onChange={e=>setForm({...form,nombre:e.target.value})}/></div><div className="form-group"><label className="form-label">Email</label><input type="email" className="form-input" value={form.email||''} onChange={e=>setForm({...form,email:e.target.value})}/></div></div>
      {modal==='nuevo'&&<div className="form-group"><label className="form-label">Contraseña</label><input type="password" className="form-input" onChange={e=>setForm({...form,password:e.target.value})}/></div>}
      <div className="form-grid"><div className="form-group"><label className="form-label">Rol</label><select className="form-input form-select" value={form.rol||'estudiante'} onChange={e=>setForm({...form,rol:e.target.value})}><option value="estudiante">Estudiante</option><option value="profesor">Profesor</option><option value="admin">Admin</option></select></div><div className="form-group"><label className="form-label">Estado</label><select className="form-input form-select" value={form.activo??1} onChange={e=>setForm({...form,activo:+e.target.value})}><option value={1}>Activo</option><option value={0}>Inactivo</option></select></div></div>
      {form.rol==='estudiante'&&<div className="form-grid"><div className="form-group"><label className="form-label">Código</label><input className="form-input" value={form.codigoEst||''} onChange={e=>setForm({...form,codigoEst:e.target.value})} placeholder="EST-2026-001"/></div><div className="form-group"><label className="form-label">Semestre</label><select className="form-input form-select" value={form.semestre||1} onChange={e=>setForm({...form,semestre:+e.target.value})}>{[1,2,3,4,5,6,7,8,9,10].map(s=><option key={s} value={s}>{s}</option>)}</select></div></div>}
      {form.rol==='profesor'&&<div className="form-group"><label className="form-label">Departamento</label><input className="form-input" value={form.departamento||''} onChange={e=>setForm({...form,departamento:e.target.value})}/></div>}
    </Modal>}
  </div>;
};

// ── MATERIAS ──────────────────────────────────────────────────────────────
const Materias=()=>{
  const[mats,setMats]=useState([]);const[loading,setLoading]=useState(true);const[buscar,setBuscar]=useState('');const[modal,setModal]=useState(null);const[form,setForm]=useState({});const[profes,setProfes]=useState([]);const[alerta,setAlerta]=useState(null);const[saving,setSaving]=useState(false);
  const cargar=useCallback(()=>{setLoading(true);Promise.all([api.get('/materias.php'),api.get('/materias.php?action=profesores')]).then(([rm,rp])=>{if(rm.status==='ok')setMats(rm.data);if(rp.status==='ok')setProfes(rp.data);setLoading(false);});},[]);
  useEffect(()=>cargar(),[cargar]);
  const guardar=async()=>{if(!form.nombre||!form.codigo)return;setSaving(true);const res=modal==='nuevo'?await api.post('/materias.php',form):await api.put(`/materias.php?id=${form.idMateria}`,form);setSaving(false);if(res.status==='ok'){setModal(null);cargar();setAlerta({type:'ok',msg:'Guardado'});}else setAlerta({type:'err',msg:res.mensaje});};
  const eliminar=async id=>{if(!confirm('¿Eliminar?'))return;await api.del(`/materias.php?id=${id}`);cargar();};
  const fil=mats.filter(m=>m.nombre.toLowerCase().includes(buscar.toLowerCase())||m.codigo.toLowerCase().includes(buscar.toLowerCase()));
  return<div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}>
      <div><div className="wb-label">Gestión</div><div className="wb-name" style={{fontSize:22}}>Materias Académicas</div></div>
      <div className="wb-stats"><div className="wb-stat"><div className="wb-stat-num">{mats.length}</div><div className="wb-stat-lbl">Materias</div></div></div>
    </div>
    <Alert type={alerta?.type} msg={alerta?.msg} onClose={()=>setAlerta(null)}/>
    <div className="search-bar">
      <input className="search-input" placeholder="Buscar nombre o código..." value={buscar} onChange={e=>setBuscar(e.target.value)}/>
      <button className="btn btn-success btn-sm" onClick={()=>exportCSV(fil,'materias')}>⬇ CSV</button>
      <button className="btn btn-primary" onClick={()=>{setForm({nombre:'',codigo:'',creditos:3,color:'#2462b0',activa:1});setModal('nuevo');}}>➕ Nueva</button>
    </div>
    {loading?<Spinner/>:<div className="card">
      <div className="card-header"><span className="card-title">📚 Materias ({fil.length})</span></div>
      <table className="tbl"><thead><tr><th>Materia</th><th>Código</th><th>Créditos</th><th>Profesor</th><th>Inscritos</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>{fil.map(m=><tr key={m.idMateria}>
        <td><div style={{display:'flex',alignItems:'center',gap:8}}><div style={{width:12,height:12,borderRadius:3,background:m.color}}/><span style={{fontWeight:600}}>{m.nombre}</span></div></td>
        <td><code style={{fontSize:12}}>{m.codigo}</code></td><td style={{textAlign:'center'}}>{m.creditos}</td>
        <td style={{fontSize:13}}>{m.profesor||<span style={{color:'var(--muted)'}}>—</span>}</td>
        <td style={{textAlign:'center'}}><span className="bdg bdg-est">{m.inscritos||0}</span></td>
        <td><span className={`bdg ${m.activa?'bdg-on':'bdg-off'}`}>{m.activa?'Activa':'Inactiva'}</span></td>
        <td><div style={{display:'flex',gap:5}}><button className="btn btn-sm btn-outline" onClick={()=>{setForm({...m});setModal('editar');}}>✏️</button><button className="btn btn-sm btn-danger" onClick={()=>eliminar(m.idMateria)}>🗑</button></div></td>
      </tr>)}</tbody></table>
    </div>}
    {(modal==='nuevo'||modal==='editar')&&<Modal title={modal==='nuevo'?'➕ Nueva Materia':'✏️ Editar'} onClose={()=>setModal(null)} footer={<><button className="btn btn-outline" onClick={()=>setModal(null)}>Cancelar</button><button className="btn btn-primary" onClick={guardar} disabled={saving}>{saving?'Guardando...':'💾 Guardar'}</button></>}>
      <div className="form-grid"><div className="form-group"><label className="form-label">Nombre</label><input className="form-input" value={form.nombre||''} onChange={e=>setForm({...form,nombre:e.target.value})}/></div><div className="form-group"><label className="form-label">Código</label><input className="form-input" value={form.codigo||''} onChange={e=>setForm({...form,codigo:e.target.value})}/></div></div>
      <div className="form-group"><label className="form-label">Descripción</label><textarea className="form-input" rows={2} value={form.descripcion||''} onChange={e=>setForm({...form,descripcion:e.target.value})}/></div>
      <div className="form-grid">
        <div className="form-group"><label className="form-label">Créditos</label><input type="number" className="form-input" min={1} max={10} value={form.creditos||3} onChange={e=>setForm({...form,creditos:+e.target.value})}/></div>
        <div className="form-group">
          <label className="form-label">Créditos mínimos <span style={{fontSize:11,color:'var(--muted)',fontWeight:400}}>(para matricularse)</span></label>
          <input type="number" className="form-input" min={0} max={200} value={form.creditos_minimos||0} onChange={e=>setForm({...form,creditos_minimos:+e.target.value})} placeholder="0 = sin requisito"/>
        </div>
      </div>
      <div className="form-grid"><div className="form-group"><label className="form-label">Color</label><div style={{display:'flex',gap:6,alignItems:'center'}}><input type="color" value={form.color||'#2462b0'} onChange={e=>setForm({...form,color:e.target.value})} style={{width:40,height:36,borderRadius:6,border:'1px solid var(--border)',cursor:'pointer'}}/><input className="form-input" value={form.color||'#2462b0'} onChange={e=>setForm({...form,color:e.target.value})} style={{fontFamily:'monospace'}}/></div></div><div className="form-group"><label className="form-label">Semestre</label><input type="number" className="form-input" min={1} max={10} value={form.semestre||1} onChange={e=>setForm({...form,semestre:+e.target.value})}/></div></div>
      <div className="form-grid">
        <div className="form-group">
          <label className="form-label">Profesor asignado</label>
          {/* SOLO profesores — el back filtra por rol='profesor' */}
          <select className="form-input form-select" value={form.idProfesor||''} onChange={e=>setForm({...form,idProfesor:e.target.value})}>
            <option value="">Sin asignar</option>
            {profes.map(p=><option key={p.idProfesor} value={p.idProfesor}>{p.nombre} — {p.departamento||p.codigoProf}</option>)}
          </select>
        </div>
        <div className="form-group"><label className="form-label">Estado</label><select className="form-input form-select" value={form.activa??1} onChange={e=>setForm({...form,activa:+e.target.value})}><option value={1}>Activa</option><option value={0}>Inactiva</option></select></div>
      </div>
    </Modal>}
  </div>;
};

// ── INSCRIPCIONES ─────────────────────────────────────────────────────────
const Inscripciones=()=>{
  const[mats,setMats]=useState([]);const[sel,setSel]=useState(null);const[ins,setIns]=useState([]);const[disp,setDisp]=useState([]);const[tab,setTab]=useState('ins');const[alerta,setAlerta]=useState(null);const[loading,setLoading]=useState(false);
  useEffect(()=>{api.get('/materias.php').then(r=>{if(r.status==='ok'){setMats(r.data);if(r.data[0])selMat(r.data[0]);}});},[]);

  const selMat=async m=>{
    setSel(m);setLoading(true);
    const[ri,rd]=await Promise.all([
      api.get(`/inscripciones.php?idMateria=${m.idMateria}&tipo=inscritos`),
      // tipo=disponibles ya filtra en el back los que NO están inscritos
      api.get(`/inscripciones.php?idMateria=${m.idMateria}&tipo=disponibles`)
    ]);
    if(ri.status==='ok')setIns(ri.data);
    if(rd.status==='ok')setDisp(rd.data);
    setLoading(false);
  };

  const inscribir=async(id,creditosEst)=>{
    // Validación de créditos mínimos en el frontend
    if(sel.creditos_minimos>0 && creditosEst<sel.creditos_minimos){
      return setAlerta({type:'err',msg:`El estudiante necesita ${sel.creditos_minimos} créditos aprobados para esta materia (tiene ${creditosEst}).`});
    }
    const r=await api.post('/inscripciones.php',{idMateria:sel.idMateria,idEstudiante:id});
    if(r.status==='ok'){selMat(sel);setAlerta({type:'ok',msg:'Estudiante inscrito correctamente'});}
    else setAlerta({type:'err',msg:r.mensaje});
  };

  const desinscribir=async id=>{
    if(!confirm('¿Desinscribir al estudiante de esta materia?'))return;
    const r=await api.del(`/inscripciones.php?idMateria=${sel.idMateria}&idEstudiante=${id}`);
    if(r.status==='ok'){selMat(sel);setAlerta({type:'ok',msg:'Desinscrito correctamente'});}
    else setAlerta({type:'err',msg:r.mensaje});
  };

  return<div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}>
      <div>
        <div className="wb-label">Gestión</div>
        <div className="wb-name" style={{fontSize:22}}>Inscripciones</div>
        {sel?.creditos_minimos>0&&<div style={{fontSize:12,color:'rgba(255,255,255,.7)',marginTop:4}}>
          ⚠ Esta materia requiere mínimo {sel.creditos_minimos} créditos aprobados
        </div>}
      </div>
    </div>
    <Alert type={alerta?.type} msg={alerta?.msg} onClose={()=>setAlerta(null)}/>
    <div style={{display:'grid',gridTemplateColumns:'260px 1fr',gap:18}}>
      {/* Lista de materias */}
      <div className="card" style={{margin:0}}>
        <div className="card-header"><span className="card-title">📚 Materias</span></div>
        <div style={{maxHeight:460,overflowY:'auto'}}>
          {mats.map(m=><div key={m.idMateria} onClick={()=>selMat(m)} style={{
            padding:'10px 16px',cursor:'pointer',borderBottom:'1px solid var(--border-lt)',
            background:sel?.idMateria===m.idMateria?'var(--bg)':'transparent',
            borderLeft:sel?.idMateria===m.idMateria?`3px solid ${m.color}`:'3px solid transparent'
          }}>
            <div style={{fontWeight:600,fontSize:13}}>{m.nombre}</div>
            <div style={{fontSize:11,color:'var(--muted)',display:'flex',gap:8}}>
              <span>{m.codigo}</span>
              <span>·</span>
              <span>{m.inscritos||0} inscritos</span>
              {m.creditos_minimos>0&&<><span>·</span><span style={{color:'#d4a843'}}>Mín. {m.creditos_minimos} cred.</span></>}
            </div>
          </div>)}
        </div>
      </div>

      {/* Panel derecho */}
      <div>{!sel
        ?<div className="card"><div style={{padding:40,textAlign:'center',color:'var(--muted)'}}>Selecciona una materia</div></div>
        :<>
          <div style={{marginBottom:10,display:'flex',alignItems:'center',gap:8}}>
            <div style={{width:14,height:14,borderRadius:4,background:sel.color}}/>
            <span style={{fontWeight:700}}>{sel.nombre}</span>
            <span style={{fontSize:12,color:'var(--muted)'}}>· {sel.creditos} créditos</span>
            {sel.creditos_minimos>0&&<span style={{fontSize:11,background:'#fff3e0',color:'#e07b39',padding:'2px 8px',borderRadius:12,fontWeight:600}}>
              Requiere {sel.creditos_minimos} cred. aprobados
            </span>}
            <button className="btn btn-sm btn-success" style={{marginLeft:'auto'}}
              onClick={()=>exportCSV(ins,`inscritos_${sel.codigo}`)}>⬇ CSV</button>
          </div>
          <div className="tabs">
            <div className={`tab ${tab==='ins'?'active':''}`} onClick={()=>setTab('ins')}>
              Inscritos ({ins.length})
            </div>
            <div className={`tab ${tab==='add'?'active':''}`} onClick={()=>setTab('add')}>
              Agregar disponibles ({disp.length})
            </div>
          </div>
          {loading?<Spinner/>:tab==='ins'
            ?<div className="card">
              {ins.length===0&&<div style={{padding:32,textAlign:'center',color:'var(--muted)'}}>Sin estudiantes inscritos.</div>}
              <table className="tbl"><thead><tr>
                <th>Estudiante</th><th>Código</th><th>Créditos ap.</th><th>P1</th><th>P2</th><th>Final</th><th></th>
              </tr></thead><tbody>
              {ins.map(e=><tr key={e.idEstudiante}>
                <td style={{fontWeight:600}}>{e.nombre}</td>
                <td><code style={{fontSize:11}}>{e.codigoEst}</code></td>
                <td style={{textAlign:'center',fontSize:12}}>{e.creditos_aprobados||0}</td>
                <td>{e.nota_parcial1??'—'}</td>
                <td>{e.nota_parcial2??'—'}</td>
                <td style={{fontWeight:700,color:e.nota_final>=3?'var(--green)':e.nota_final?'var(--red)':'var(--muted)'}}>{e.nota_final??'—'}</td>
                <td><button className="btn btn-sm btn-danger" title="Desinscribir" onClick={()=>desinscribir(e.idEstudiante)}>✕</button></td>
              </tr>)}
              </tbody></table>
            </div>
            :<div className="card">
              {disp.length===0&&<div style={{padding:32,textAlign:'center',color:'var(--muted)'}}>
                Todos los estudiantes activos ya están inscritos en esta materia.
              </div>}
              <table className="tbl"><thead><tr>
                <th>Estudiante</th><th>Código</th><th>Sem.</th><th>Créditos ap.</th><th>Cumple req.</th><th></th>
              </tr></thead><tbody>
              {disp.map(e=>{
                const cumple=!sel.creditos_minimos||e.creditos_aprobados>=sel.creditos_minimos;
                return<tr key={e.idEstudiante} style={{opacity:cumple?1:0.6}}>
                  <td style={{fontWeight:600}}>{e.nombre}</td>
                  <td><code style={{fontSize:11}}>{e.codigoEst}</code></td>
                  <td style={{textAlign:'center'}}>{e.semestre||'—'}</td>
                  <td style={{textAlign:'center'}}>{e.creditos_aprobados||0}</td>
                  <td style={{textAlign:'center'}}>
                    {cumple
                      ?<span style={{color:'#1a7a48',fontWeight:600}}>✅ Sí</span>
                      :<span style={{color:'#e07b39',fontWeight:600,fontSize:11}} title={`Necesita ${sel.creditos_minimos} cred.`}>⚠ No ({e.creditos_aprobados||0}/{sel.creditos_minimos})</span>
                    }
                  </td>
                  <td>
                    <button className={`btn btn-sm ${cumple?'btn-primary':'btn-outline'}`}
                      onClick={()=>inscribir(e.idEstudiante,e.creditos_aprobados||0)}
                      title={cumple?'Inscribir':'No cumple requisitos de créditos'}>
                      + Inscribir
                    </button>
                  </td>
                </tr>;
              })}
              </tbody></table>
            </div>
          }
        </>}
      </div>
    </div>
  </div>;
};

// ── REPORTES ADMIN ────────────────────────────────────────────────────────
const Reportes=()=>{
  const[mats,setMats]=useState([]);const[tipo,setTipo]=useState('materias');const[idM,setIdM]=useState('');const[desde,setDesde]=useState(new Date(new Date().getFullYear(),new Date().getMonth(),1).toISOString().split('T')[0]);const[hasta,setHasta]=useState(new Date().toISOString().split('T')[0]);const[data,setData]=useState(null);const[loading,setLoading]=useState(false);const[chartD,setChartD]=useState(null);
  useEffect(()=>{api.get('/materias.php').then(r=>{if(r.status==='ok'){setMats(r.data);if(r.data[0])setIdM(r.data[0].idMateria);}});gen('materias');},[]);
  const gen=async(t=tipo)=>{setLoading(true);setData(null);setChartD(null);let url=`/reportes.php?tipo=${t}`;if(t!=='materias')url+=`&idMateria=${idM}&desde=${desde}&hasta=${hasta}`;const r=await api.get(url);if(r.status==='ok'){setData(r.data);if(t==='materias'&&r.data.length)setChartD({labels:r.data.map(m=>m.nombre.slice(0,10)),datasets:[{label:'Inscritos',data:r.data.map(m=>m.inscritos),backgroundColor:'#2462b0',borderRadius:4},{label:'Entregas',data:r.data.map(m=>m.entregas),backgroundColor:'#1a7a48',borderRadius:4}]});else if(t==='notas'&&r.data.estudiantes?.length)setChartD({labels:r.data.estudiantes.map(e=>e.nombre.split(' ')[0]),datasets:[{label:'Nota Final',data:r.data.estudiantes.map(e=>e.nota_final||0),backgroundColor:r.data.estudiantes.map(e=>(e.nota_final||0)>=3?'#1a7a48':'#c0392b'),borderRadius:4}]});}setLoading(false);};
  return<div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}><div><div className="wb-label">Análisis</div><div className="wb-name" style={{fontSize:22}}>Reportes del Sistema</div></div></div>
    <div className="card"><div className="card-header"><span className="card-title">🔍 Filtros</span></div><div className="card-body"><div style={{display:'flex',gap:14,flexWrap:'wrap',alignItems:'flex-end'}}>
      <div className="form-group" style={{margin:0}}><label className="form-label">Tipo</label><select className="form-input form-select" style={{width:220}} value={tipo} onChange={e=>setTipo(e.target.value)}><option value="materias">📚 Resumen materias</option><option value="asistencia">✅ Asistencia</option><option value="notas">📊 Notas</option></select></div>
      {tipo!=='materias'&&<><div className="form-group" style={{margin:0}}><label className="form-label">Materia</label><select className="form-input form-select" style={{width:180}} value={idM} onChange={e=>setIdM(e.target.value)}>{mats.map(m=><option key={m.idMateria} value={m.idMateria}>{m.nombre}</option>)}</select></div><div className="form-group" style={{margin:0}}><label className="form-label">Desde</label><input type="date" className="form-input" style={{width:150}} value={desde} onChange={e=>setDesde(e.target.value)}/></div><div className="form-group" style={{margin:0}}><label className="form-label">Hasta</label><input type="date" className="form-input" style={{width:150}} value={hasta} onChange={e=>setHasta(e.target.value)}/></div></>}
      <button className="btn btn-primary" onClick={()=>gen()} style={{marginBottom:0}}>🔍 Generar</button>
      {data&&<button className="btn btn-success btn-sm" onClick={()=>exportCSV(Array.isArray(data)?data:data.estudiantes,`reporte_${tipo}`)}>⬇ CSV</button>}
    </div></div></div>
    {loading&&<Spinner/>}
    {chartD&&<div className="card"><div className="card-header"><span className="card-title">📈 Visualización</span></div><div className="card-body" style={{height:240}}><ChartCanvas type="bar" data={chartD} options={{plugins:{legend:{display:true}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}}/></div></div>}
    {data&&tipo==='materias'&&<div className="card"><div className="card-header"><span className="card-title">Resumen de Materias</span><button className="btn btn-sm btn-outline" onClick={()=>exportCSV(data,'materias')}>⬇ CSV</button></div><table className="tbl"><thead><tr><th>Materia</th><th>Profesor</th><th>Inscritos</th><th>Materiales</th><th>Actividades</th><th>Entregas</th><th>Sin calificar</th></tr></thead><tbody>{data.map(m=><tr key={m.idMateria}><td><div style={{display:'flex',alignItems:'center',gap:7}}><div style={{width:10,height:10,borderRadius:2,background:m.color}}/><span style={{fontWeight:600}}>{m.nombre}</span></div></td><td style={{fontSize:13}}>{m.profesor||'—'}</td><td style={{textAlign:'center'}}><span className="bdg bdg-est">{m.inscritos}</span></td><td style={{textAlign:'center'}}>{m.materiales}</td><td style={{textAlign:'center'}}>{m.actividades}</td><td style={{textAlign:'center'}}>{m.entregas}</td><td style={{textAlign:'center'}}>{m.sin_calificar>0?<span className="bdg bdg-off">{m.sin_calificar}</span>:<span className="bdg bdg-on">0</span>}</td></tr>)}</tbody></table></div>}
    {data&&tipo==='notas'&&<div className="card"><div className="card-header"><span className="card-title">Notas — {data.materia?.nombre}</span></div><table className="tbl"><thead><tr><th>Estudiante</th><th>P1</th><th>P2</th><th>Talleres</th><th>Final</th><th>Estado</th></tr></thead><tbody>{data.estudiantes?.map(e=><tr key={e.codigoEst}><td style={{fontWeight:600}}>{e.nombre}</td><td>{e.nota_parcial1??'—'}</td><td>{e.nota_parcial2??'—'}</td><td>{e.nota_talleres??'—'}</td><td style={{fontWeight:700,color:e.nota_final>=3?'var(--green)':e.nota_final?'var(--red)':'var(--muted)'}}>{e.nota_final??'—'}</td><td>{e.nota_final?<span className={`bdg ${e.nota_final>=3?'bdg-on':'bdg-off'}`}>{e.nota_final>=3?'Aprobado':'Reprobado'}</span>:<span className="bdg" style={{background:'var(--bg)',color:'var(--muted)'}}>Pendiente</span>}</td></tr>)}</tbody></table></div>}
    {data&&tipo==='asistencia'&&<div className="card"><div className="card-header"><span className="card-title">Asistencia — {data.materia?.nombre}</span></div><table className="tbl"><thead><tr><th>Estudiante</th><th>Presentes</th><th>Ausentes</th><th>Total</th><th>%</th></tr></thead><tbody>{data.estudiantes?.map(e=><tr key={e.codigoEst}><td style={{fontWeight:600}}>{e.nombre}</td><td style={{color:'var(--green)',fontWeight:600}}>{e.presentes}</td><td style={{color:'var(--red)',fontWeight:600}}>{e.ausentes}</td><td>{e.total_clases}</td><td><div style={{display:'flex',alignItems:'center',gap:8}}><div className="pbar-wrap" style={{flex:1}}><div className="pbar-fill" style={{width:`${e.porcentaje}%`,background:e.porcentaje>=75?'var(--green)':'var(--red)'}}/></div><span style={{fontSize:12,fontWeight:600,width:38,color:e.porcentaje>=75?'var(--green)':'var(--red)'}}>{e.porcentaje}%</span></div></td></tr>)}</tbody></table></div>}
  </div>;
};

// ── CONFIGURACION ADMIN ────────────────────────────────────────────────────────
const Configuracion=()=>{
  const[conf,setConf]=useState([]);const[loading,setLoading]=useState(true);const[alerta,setAlerta]=useState(null);const[saving,setSaving]=useState(false);
  useEffect(()=>{api.get('/configuracion.php').then(r=>{if(r.status==='ok')setConf(r.data);setLoading(false);}).catch(()=>setLoading(false));},[]);
  const guardar=async()=>{
    setSaving(true);
    const res=await api.put('/configuracion.php', { configs: conf });
    setSaving(false);
    if(res.status==='ok') setAlerta({type:'ok',msg:'Configuración guardada'});
    else setAlerta({type:'err',msg:res.mensaje||'Error al guardar'});
  };
  return <div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}><div><div className="wb-label">Administración</div><div className="wb-name" style={{fontSize:22}}>Configuración del Sistema</div></div></div>
    <Alert type={alerta?.type} msg={alerta?.msg} onClose={()=>setAlerta(null)}/>
    <div className="card">
      <div className="card-header"><span className="card-title">⚙️ Ajustes Globales</span></div>
      <div className="card-body">
        {loading?<Spinner/>:conf.length > 0 ? <div className="form-grid">
          {conf.map((c, i)=><div key={c.clave} className="form-group" style={{gridColumn: '1 / -1', marginBottom: 12}}>
            <label className="form-label">{c.descripcion || c.clave}</label>
            {c.tipo==='booleano'?
              <select className="form-input form-select" value={c.valor} onChange={e=>{const nc=[...conf];nc[i].valor=e.target.value;setConf(nc);}}>
                <option value="1">Sí (Activado)</option><option value="0">No (Desactivado)</option>
              </select>
            : <input type={c.tipo==='numero'?'number':'text'} className="form-input" value={c.valor} onChange={e=>{const nc=[...conf];nc[i].valor=e.target.value;setConf(nc);}}/>}
          </div>)}
        </div> : <div style={{padding:20, color:'var(--muted)'}}>No se pudo cargar la configuración o no hay registros.</div>}
        <div style={{marginTop:20}}><button className="btn btn-primary" onClick={guardar} disabled={saving||loading}>{saving?'Guardando...':'💾 Guardar Cambios'}</button></div>
      </div>
    </div>
  </div>;
};

// ── NOTIFICACIONES ADMIN ────────────────────────────────────────────────────────
const Notificaciones=()=>{
  const[data,setData]=useState(null);
  const colors={sistema:'#c0392b',info:'#888',asistencia:'#d4a843'};
  const icons={sistema:'⚙️',info:'🔔',asistencia:'✅'};

  const cargarNotifs=useCallback(()=>{
    api.get(`/notificaciones.php`).then(r=>r.status==='ok'&&setData(r.data));
  },[]);

  useEffect(()=>{
    cargarNotifs();
    const interval=setInterval(cargarNotifs,30000);
    return()=>clearInterval(interval);
  },[cargarNotifs]);

  const handleClick=async(n)=>{
    if(!n.leida){
      await api.post('/notificaciones.php',{action:'marcar_una',idNotif:n.idNotif});
      setData(prev=>prev.map(x=>x.idNotif===n.idNotif?{...x,leida:1}:x));
    }
    if(n.url&&n.url.trim()&&n.url!=='#'){
      window.location.href=n.url;
    }
  };

  if(!data)return<Spinner/>;
  const sinLeer=data.filter(n=>!n.leida).length;
  return<div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}>
      <div>
        <div className="wb-label">Sistema</div>
        <div className="wb-name" style={{fontSize:22}}>🔔 Notificaciones</div>
      </div>
      <div className="wb-stats">
        <div className="wb-stat"><div className="wb-stat-num">{sinLeer}</div><div className="wb-stat-lbl">Sin leer</div></div>
      </div>
    </div>
    {sinLeer>0&&<div style={{display:'flex',justifyContent:'flex-end',marginBottom:12}}>
      <button className="btn btn-outline btn-sm" onClick={async()=>{
        await api.post('/notificaciones.php',{action:'marcar_todas'});
        setData(prev=>prev.map(n=>({...n,leida:1})));
      }}>✓ Marcar todas como leídas</button>
    </div>}
    <div className="card">
      <div className="card-body" style={{padding:'8px 20px'}}>
        {data.length===0&&<p style={{textAlign:'center',padding:36,color:'var(--muted)'}}>Sin notificaciones.</p>}
        {data.map((n,i)=>(
          <div key={n.idNotif||i} className="notif-item" onClick={()=>handleClick(n)}
            style={{opacity:n.leida?0.7:1,cursor:n.url&&n.url!=='#'?'pointer':'default',background:!n.leida?'#fafbff':'transparent',borderRadius:8,transition:'background .2s'}}>
            <div style={{display:'flex',gap:12,alignItems:'flex-start'}}>
              <div style={{width:36,height:36,borderRadius:9,background:`${colors[n.tipo]||'#888'}20`,display:'flex',alignItems:'center',justifyContent:'center',fontSize:18,flexShrink:0}}>{icons[n.tipo]||'🔔'}</div>
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

// ── VIDEOLLAMADAS ADMIN ───────────────────────────────────────────────────────
const VideollamadasAdmin=()=>{
  const[vlLista,setVlLista]=useState([]);
  const[loading,setLoading]=useState(true);
  useEffect(()=>{
    api.get('/videollamadas.php?action=lista').then(r=>{
      if(r.status==='ok') setVlLista(r.data||[]);
      setLoading(false);
    }).catch(()=>setLoading(false));
  },[]);
  
  if(loading)return<Spinner/>;
  return<div className="page">
    <div className="welcome-banner" style={{padding:'20px 28px',marginBottom:20}}>
      <div><div className="wb-label">Monitoreo</div><div className="wb-name" style={{fontSize:22}}>🎥 Clases Virtuales</div></div>
    </div>
    <div className="card">
      <div className="card-header"><span className="card-title">Registro de Videollamadas</span></div>
      <table className="tbl">
        <thead><tr><th>Materia</th><th>Profesor</th><th>Tema</th><th>Fecha</th><th>Estado</th></tr></thead>
        <tbody>
          {vlLista.length===0&&<tr><td colSpan="5" style={{textAlign:'center',padding:24,color:'var(--muted)'}}>No hay videollamadas registradas</td></tr>}
          {vlLista.map(v=>{
            const estado={en_curso:<span className="tag tag-ok" style={{background:'#e8f5ee',color:'#1a7a48'}}>En curso</span>, programada:<span className="tag tag-blue">Programada</span>, finalizada:<span className="tag tag-err" style={{background:'#f4f5f8',color:'#6b7a99'}}>Finalizada</span>};
            return <tr key={v.idVideoLlamada}>
              <td><div style={{fontWeight:600}}>{v.materia}</div><code style={{fontSize:11}}>{v.codigo}</code></td>
              <td>{v.nombreProfesor}</td>
              <td>{v.titulo}</td>
              <td style={{fontSize:12,color:'var(--muted)'}}>{v.programada_en.slice(0,16).replace('T',' ')}<br/>{v.duracion_min} min</td>
              <td>{estado[v.estado]||<span className="tag tag-err" style={{background:'#f4f5f8',color:'#6b7a99'}}>Finalizada</span>}</td>
            </tr>;
          })}
        </tbody>
      </table>
    </div>
  </div>;
};

// ── APP ADMIN ─────────────────────────────────────────────────────────────
const AdminApp=()=>{
  const q = new URLSearchParams(window.location.search);
  const[vista,setVista]=useState(q.get('view') || 'dashboard');
  const[badgeNotifs,setBadgeNotifs]=useState(0);
  useEffect(()=>{api.get('/notificaciones.php').then(r=>{if(r.status==='ok')setBadgeNotifs(r.data.filter(n=>!n.leida).length);});},[]);
  const{nombre,initials}=window.__S;
  const nav=[{id:'dashboard',ico:'📊',lbl:'Dashboard'},{id:'usuarios',ico:'👥',lbl:'Usuarios'},{id:'materias',ico:'📚',lbl:'Materias'},{id:'inscripciones',ico:'📋',lbl:'Inscripciones'},{id:'videollamadas',ico:'🎥',lbl:'Clases Virtuales'},{id:'reportes',ico:'📈',lbl:'Reportes'},{id:'configuracion',ico:'⚙️',lbl:'Configuración'},{id:'notificaciones',ico:'🔔',lbl:'Notificaciones', badge:badgeNotifs}];
  const vistas={dashboard:<Dashboard/>,usuarios:<Usuarios/>,materias:<Materias/>,inscripciones:<Inscripciones/>,videollamadas:<VideollamadasAdmin/>,reportes:<Reportes/>,configuracion:<Configuracion/>,notificaciones:<Notificaciones/>};
  const titulo=nav.find(n=>n.id===vista)?.lbl||'Admin';
  return<>
    {/* Sidebar */}
    <aside className="sidebar">
      <div className="sb-brand"><div className="sb-brand-name">UNI<em>VIRTUAL</em></div><div className="sb-brand-sub">Plataforma Académica</div></div>
      <div className="sb-user"><div className="sb-avatar">{initials}</div><div><div className="sb-user-name">{nombre.split(' ').slice(0,2).join(' ')}</div><div className="sb-user-role">Administrador</div></div></div>
      <div className="sb-section">Principal</div>
      <nav className="sb-nav">
        {nav.map(n=><div key={n.id} className={`sb-item ${vista===n.id?'active':''}`} onClick={()=>setVista(n.id)}>
          <span className="sb-item-ico">{n.ico}</span>
          <span className="sb-item-lbl">{n.lbl}</span>
          {n.badge>0&&<span style={{marginLeft:'auto',background:'#c0392b',color:'#fff',borderRadius:10,padding:'1px 6px',fontSize:10,fontWeight:700}}>{n.badge}</span>}
        </div>)}
      </nav>
      <div className="sb-logout"><a href="/univirtual/pages/auth/logout.php" className="sb-logout-btn"><span className="sb-logout-ico">↩</span><span>Cerrar sesión</span></a></div>
    </aside>
    {/* Topbar */}
    <header className="topbar">
      <div className="tb-left">
        <div className="tb-breadcrumb"><span>UNI-VIRTUAL</span><span>›</span><strong>Administración</strong></div>
        <div className="tb-title">{titulo}</div>
      </div>
      <div className="tb-right">
        <span className="tb-semester">2026-I</span>
        <div className="tb-notif" style={{cursor:'pointer'}} onClick={()=>setVista('notificaciones')}>
          <span>🔔</span>{badgeNotifs>0&&<div className="tb-notif-dot"/>}
        </div>
      </div>
    </header>
    {/* Contenido */}
    <main className="main">{vistas[vista]||<Dashboard/>}</main>
  </>;
};

ReactDOM.createRoot(document.getElementById('root')).render(<AdminApp/>);
</script>
</body>
</html>
<?php
    }
}
(new AdminView())->render();