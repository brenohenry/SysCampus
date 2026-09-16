const API='php/';

async function req(file,opts={}){
 const r=await fetch(API+file,{
  credentials:'same-origin',
  cache:'no-store',
  ...opts,
  headers:{
   'Content-Type':'application/json',
   ...(opts.headers||{})
  }
 });
 let d={};
 try{d=await r.json()}catch{}
 if(!r.ok||d.ok===false)throw new Error(d.error||'Operação não concluída');
 return d
}

function toast(msg){
 const t=document.createElement('div');
 t.className='toast';
 t.textContent=msg;
 document.body.appendChild(t);
 setTimeout(()=>t.remove(),2800)
}

function esc(v){
 return String(v??'').replace(/[&<>'"]/g,c=>({
  '&':'&amp;',
  '<':'&lt;',
  '>':'&gt;',
  "'":'&#39;',
  '"':'&quot;'
 }[c]))
}

async function ensureUser(){
 const d=await req('auth.php?action=me');
 if(!d.user){
  location.href='index.html';
  return null
 }
 const el=document.querySelector('#userName');
 if(el)el.textContent=d.user.name;

 document.querySelectorAll('.admin-link').forEach(a=>
  a.classList.toggle('hidden',d.user.role!=='ADMIN')
 );

 return d.user
}

function setupCommon(){
 document.querySelectorAll('[data-logout]').forEach(b=>
  b.onclick=async()=>{
   await req('auth.php?action=logout');
   location.href='index.html'
  }
 );

 document.querySelectorAll('[data-toggle-password]').forEach(b=>
  b.onclick=()=>{
   const i=b.parentElement.querySelector('input');
   i.type=i.type==='password'?'text':'password'
  }
 );

 document.querySelectorAll('[data-role-login]').forEach(a=>
  a.onclick=e=>{
   e.preventDefault();
   setLoginRole(a.dataset.roleLogin||'USER');
   document.querySelector('#login')?.scrollIntoView({behavior:'smooth'})
  }
 )
}

function setLoginRole(role){
 role=role==='ADMIN'?'ADMIN':'USER';

 const input=document.querySelector('#loginRole');
 const context=document.querySelector('#loginContext');
 const msg=document.querySelector('#loginMsg');

 if(input)input.value=role;

 if(context)
  context.textContent=role==='ADMIN'
   ?'Área do Administrador'
   :'Área do usuário';

 if(msg)msg.textContent='';

 const f=document.querySelector('#loginForm');
 if(f)f.dataset.role=role
}

async function initHome(){
 setupCommon();

 const f=document.querySelector('#loginForm');
 if(!f)return;

 const params=new URLSearchParams(location.search);

 setLoginRole(
  params.get('role')==='ADMIN'
   ?'ADMIN'
   :'USER'
 );

 f.onsubmit=async e=>{
  e.preventDefault();

  const fd=new FormData(f);
  const msg=document.querySelector('#loginMsg');

  try{
   const d=await req(
    'auth.php?action=login',
    {
     method:'POST',
     body:JSON.stringify(Object.fromEntries(fd))
    }
   );

   toast('Login efetuado');

   location.href=d.user.role==='ADMIN'
    ?'admin.html'
    :'dashboard.html';

  }catch(err){
   msg.textContent=err.message
  }
 }
}

async function initDashboard(){
 const u=await ensureUser();
 if(!u)return;

 const d=await req('dashboard.php');

 const c=document.querySelector('#pageContent');

 c.innerHTML=`
 <div class="cards">
  <div class="card metric">
   Salas ativas
   <strong>${d.metrics.rooms}</strong>
  </div>

  <div class="card metric">
   Equipamentos
   <strong>${d.metrics.equipment}</strong>
  </div>

  <div class="card metric">
   Minhas reservas
   <strong>${d.metrics.my_reservations}</strong>
  </div>

  <div class="card metric">
   Utilizadores
   <strong>${d.metrics.users}</strong>
  </div>
 </div>

 <div class="panel" style="margin-top:20px">
  <h2>Próximas reservas</h2>

  <div class="table-wrap">
   <table class="table">
    <thead>
     <tr>
      <th>Recurso</th>
      <th>Início</th>
      <th>Fim</th>
      <th>Finalidade</th>
     </tr>
    </thead>

    <tbody>
     ${
      d.upcoming.map(r=>`
       <tr>
        <td>${esc(r.room_name||r.equipment_name)}</td>
        <td>${esc(r.start_at)}</td>
        <td>${esc(r.end_at)}</td>
        <td>${esc(r.purpose)}</td>
       </tr>
      `).join('')
      ||
      '<tr><td colspan="4">Nenhuma reserva futura.</td></tr>'
     }
    </tbody>
   </table>
  </div>
 </div>
 `
}

function modal(title,body){
 const m=document.createElement('div');

 m.className='modal';

 m.innerHTML=`
 <div class="modal-box">
  <div class="modal-head">
   <h2>${title}</h2>
   <button class="close">×</button>
  </div>
  ${body}
 </div>
 `;

 document.body.append(m);

 m.querySelector('.close').onclick=()=>m.remove();

 return m
}

async function initRooms(){
 const u=await ensureUser();
 if(!u)return;

 const c=document.querySelector('#pageContent');
 const d=await req('rooms.php');

 const render=()=>{
  c.innerHTML=`
  <p class="hint" style="margin-bottom:12px">
   Recursos com histórico de reservas podem ser inativados para preservar o histórico.
   Recursos sem reservas podem ser excluídos definitivamente.
  </p>

  <div class="actions" style="margin-bottom:14px">
   ${
    u.role==='ADMIN'
     ?'<button class="btn primary" id="new">Nova sala</button>'
     :''
   }
  </div>

  <div class="table-wrap">
   <table class="table">
    <thead>
     <tr>
      <th>Número</th>
      <th>Nome</th>
      <th>Capacidade</th>
      <th>Localização</th>
      <th>Recursos</th>
      <th></th>
     </tr>
    </thead>

    <tbody>
     ${
      d.items.map(r=>`
       <tr>
        <td>${esc(r.number)}</td>
        <td>${esc(r.name)}</td>
        <td>${r.capacity}</td>
        <td>${esc(r.location)}</td>
        <td>${esc(r.resources)}</td>

        <td>
         ${
          u.role==='ADMIN'
           ?`
            <button class="btn" data-edit="${r.id}">
             Editar
            </button>

            ${
             r.reservation_count>0
              ?`
               <button
                class="btn danger"
                data-del="${r.id}">
                Inativar
               </button>
              `
              :`
               <button
                class="btn danger"
                data-hard-del="${r.id}">
                Excluir
               </button>
              `
            }
           `
           :''
         }
        </td>
       </tr>
      `).join('')
     }
    </tbody>
   </table>
  </div>
  `;

  if(u.role==='ADMIN')
   c.querySelector('#new').onclick=()=>roomForm();

  c.querySelectorAll('[data-edit]').forEach(b=>
   b.onclick=()=>
    roomForm(
     d.items.find(x=>x.id==b.dataset.edit)
    )
  );

  c.querySelectorAll('[data-del]').forEach(b=>
   b.onclick=async()=>{
    if(confirm('Inativar esta sala?')){
     try{
      await req(
       'rooms.php',
       {
        method:'POST',
        body:JSON.stringify({
         action:'delete',
         id:b.dataset.del
        })
       }
      );

      toast('Sala inativada');
      initRooms();

     }catch(e){
      alert(e.message)
     }
    }
   }
  );

  c.querySelectorAll('[data-hard-del]').forEach(b=>
   b.onclick=async()=>{
    if(confirm(
     'Excluir esta sala definitivamente? Esta ação não pode ser desfeita.'
    )){
     try{
      await req(
       'rooms.php',
       {
        method:'POST',
        body:JSON.stringify({
         action:'hard_delete',
         id:b.dataset.hardDel
        })
       }
      );

      toast('Sala excluída');
      initRooms();

     }catch(e){
      alert(e.message)
     }
    }
   }
  )
 };

 function roomForm(r={}){
  const m=modal(
   r.id?'Editar sala':'Nova sala',
   `
   <form id="rf">

    <div class="form-grid">

     <label class="field">
      <span>Número</span>
      <input
       name="number"
       value="${esc(r.number)}"
       required>
     </label>

     <label class="field">
      <span>Nome</span>
      <input
       name="name"
       value="${esc(r.name)}"
       required>
     </label>

     <label class="field">
      <span>Capacidade</span>
      <input
       name="capacity"
       type="number"
       min="1"
       value="${r.capacity||1}"
       required>
     </label>

     <label class="field">
      <span>Localização</span>
      <input
       name="location"
       value="${esc(r.location)}"
       required>
     </label>

     <label class="field full">
      <span>Recursos</span>
      <textarea name="resources">${esc(r.resources)}</textarea>
     </label>

    </div>

    <button class="btn primary">
     Salvar
    </button>

   </form>
   `
  );

  m.querySelector('form').onsubmit=async e=>{
   e.preventDefault();

   const x=Object.fromEntries(
    new FormData(e.target)
   );

   x.id=r.id||0;

   await req(
    'rooms.php',
    {
     method:'POST',
     body:JSON.stringify(x)
    }
   );

   m.remove();

   toast('Sala salva');

   initRooms()
  }
 }

 render()
}

async function initEquipment(){
 const u=await ensureUser();
 if(!u)return;

 const c=document.querySelector('#pageContent');
 const d=await req('equipment.php');

 const render=()=>{
  c.innerHTML=`
  <p class="hint" style="margin-bottom:12px">
   Recursos com histórico de reservas podem ser inativados para preservar o histórico.
   Recursos sem reservas podem ser excluídos definitivamente.
  </p>

  <div class="actions" style="margin-bottom:14px">
   ${
    u.role==='ADMIN'
     ?'<button class="btn primary" id="new">Novo equipamento</button>'
     :''
   }
  </div>

  <div class="table-wrap">
   <table class="table">

    <thead>
     <tr>
      <th>Código</th>
      <th>Nome</th>
      <th>Descrição</th>
      <th>Local</th>
      <th></th>
     </tr>
    </thead>

    <tbody>
     ${
      d.items.map(r=>`
       <tr>

        <td>${esc(r.code)}</td>

        <td>${esc(r.name)}</td>

        <td>${esc(r.description)}</td>

        <td>${esc(r.location)}</td>

        <td>
         ${
          u.role==='ADMIN'
           ?`
            <button
             class="btn"
             data-edit="${r.id}">
             Editar
            </button>

            ${
             r.reservation_count>0
              ?`
               <button
                class="btn danger"
                data-del="${r.id}">
                Inativar
               </button>
              `
              :`
               <button
                class="btn danger"
                data-hard-del="${r.id}">
                Excluir
               </button>
              `
            }
           `
           :''
         }
        </td>

       </tr>
      `).join('')
      ||
      '<tr><td colspan="5">Nenhum equipamento encontrado.</td></tr>'
     }
    </tbody>

   </table>
  </div>
  `;

  if(u.role==='ADMIN')
   c.querySelector('#new').onclick=()=>form();

  c.querySelectorAll('[data-edit]').forEach(b=>
   b.onclick=()=>
    form(
     d.items.find(x=>x.id==b.dataset.edit)
    )
  );

  c.querySelectorAll('[data-del]').forEach(b=>
   b.onclick=async()=>{
    if(confirm('Inativar equipamento?')){
     try{
      await req(
       'equipment.php',
       {
        method:'POST',
        body:JSON.stringify({
         action:'delete',
         id:b.dataset.del
        })
       }
      );

      toast('Equipamento inativado');
      initEquipment();

     }catch(e){
      alert(e.message)
     }
    }
   }
  );

  c.querySelectorAll('[data-hard-del]').forEach(b=>
   b.onclick=async()=>{
    if(confirm(
     'Excluir este equipamento definitivamente? Esta ação não pode ser desfeita.'
    )){
     try{
      await req(
       'equipment.php',
       {
        method:'POST',
        body:JSON.stringify({
         action:'hard_delete',
         id:b.dataset.hardDel
        })
       }
      );

      toast('Equipamento excluído');
      initEquipment();

     }catch(e){
      alert(e.message)
     }
    }
   }
  )
 };

 function form(r={}){
  const m=modal(
   r.id?'Editar equipamento':'Novo equipamento',
   `
   <form>

    <div class="form-grid">

     <label class="field">
      <span>Código</span>
      <input
       name="code"
       value="${esc(r.code||'')}"
       required>
     </label>

     <label class="field">
      <span>Nome</span>
      <input
       name="name"
       value="${esc(r.name||'')}"
       required>
     </label>

     <label class="field full">
      <span>Descrição</span>
      <textarea
       name="description"
       required>${esc(r.description||'')}</textarea>
     </label>

     <label class="field">
      <span>Localização</span>
      <input
       name="location"
       value="${esc(r.location||'')}">
     </label>

    </div>

    <button class="btn primary">
     Salvar
    </button>

   </form>
   `
  );

  m.querySelector('form').onsubmit=async e=>{
   e.preventDefault();

   const x=Object.fromEntries(
    new FormData(e.target)
   );

   x.id=r.id||0;

   await req(
    'equipment.php',
    {
     method:'POST',
     body:JSON.stringify(x)
    }
   );

   m.remove();

   toast('Equipamento salvo');

   initEquipment()
  }
 }

 render()
}

async function initReservations(){

 const u=await ensureUser();
 if(!u)return;

 const [
  rooms,
  equip,
  res,
  obs
 ]=await Promise.all([
  req('rooms.php'),
  req('equipment.php'),
  req('reservations.php'),
  req('observations.php')
 ]);

 const reportsByReservation={};

 (obs.items||[]).forEach(x=>{
  if(x.reservation_id)
   reportsByReservation[x.reservation_id]=x
 });

 const c=document.querySelector('#pageContent');

 c.innerHTML=`
 <div class="actions" style="margin-bottom:14px">
  <button class="btn primary" id="new">
   Nova reserva
  </button>
 </div>

 <div class="table-wrap">

  <table class="table">

   <thead>
    <tr>
     <th>Recurso</th>
     <th>Início</th>
     <th>Fim</th>
     <th>Finalidade</th>
     <th>Status</th>
     <th>Ações</th>
    </tr>
   </thead>

   <tbody>

    ${
     res.items.map(r=>{
      const rp=reportsByReservation[r.id];

      return `
       <tr>

        <td>
         ${esc(r.room_name||r.equipment_name)}
        </td>

        <td>
         ${esc(r.start_at)}
        </td>

        <td>
         ${esc(r.end_at)}
        </td>

        <td>
         ${esc(r.purpose)}
        </td>

        <td>
         ${esc(r.status)}
        </td>

        <td class="actions">

         ${
          r.status==='ACTIVE'
           ?`
            <button
             class="btn danger"
             data-cancel="${r.id}">
             Cancelar
            </button>
           `
           :''
         }

         <button
          class="btn"
          data-report="${r.id}"
          data-resource="${esc(r.room_name||r.equipment_name)}">

          ${rp?'Reportado':'Reportar'}

         </button>

        </td>

       </tr>
      `
     }).join('')
     ||
     '<tr><td colspan="6">Nenhuma reserva encontrada.</td></tr>'
    }

   </tbody>

  </table>

 </div>

 <div class="panel" style="margin-top:20px">

  <h2>Meus reportes</h2>

  <div class="table-wrap">

   <table class="table">

    <thead>
     <tr>
      <th>Data</th>
      <th>Reserva</th>
      <th>Recurso</th>
      <th>Reporte</th>
      <th>Status</th>
      <th>Resposta do administrador</th>
     </tr>
    </thead>

    <tbody>

     ${
      (obs.items||[]).map(x=>`
       <tr>

        <td>${esc(x.created_at)}</td>

        <td>#${esc(x.reservation_id||'—')}</td>

        <td>${esc(x.room_name||x.equipment_name||'—')}</td>

        <td>${esc(x.message)}</td>

        <td>${esc(x.status)}</td>

        <td>
         ${
          (x.replies||[]).map(r=>`
           <div style="margin-bottom:8px">
            <strong>${esc(r.admin_name)}:</strong>
            ${esc(r.message)}
            <br>
            <small>${esc(r.created_at)}</small>
           </div>
          `).join('')
          ||
          '<span class="muted">Aguardando resposta.</span>'
         }
        </td>

       </tr>
      `).join('')
      ||
      '<tr><td colspan="6">Nenhum reporte enviado.</td></tr>'
     }

    </tbody>

   </table>

  </div>

 </div>
 `;

 c.querySelector('#new').onclick=()=>
  reservationForm(
   rooms.items.filter(r=>r.active),
   equip.items.filter(r=>r.active)
  );

 c.querySelectorAll('[data-cancel]').forEach(b=>
  b.onclick=async()=>{
   if(confirm('Cancelar esta reserva?')){
    try{
     await req(
      'reservations.php',
      {
       method:'POST',
       body:JSON.stringify({
        action:'cancel',
        id:b.dataset.cancel
       })
      }
     );

     toast('Reserva cancelada');

     initReservations();

    }catch(e){
     alert(e.message)
    }
   }
  }
 );

 c.querySelectorAll('[data-report]').forEach(b=>
  b.onclick=()=>
   reportForm(
    b.dataset.report,
    b.dataset.resource
   )
 );

 function reportForm(reservationId,resource){

  const m=modal(
   'Reportar',
   `
   <form>

    <div class="panel" style="margin-bottom:14px">

     <strong>Reserva vinculada</strong>

     <p style="margin:6px 0 0">
      ${esc(resource)}
      —
      reserva #${esc(reservationId)}
     </p>

    </div>

    <label class="field">

     <span>Mensagem</span>

     <textarea
      name="message"
      placeholder="Escreva o que deseja comunicar sobre esta reserva."
      required></textarea>

    </label>

    <button class="btn primary">
     Enviar reporte
    </button>

   </form>
   `
  );

  m.querySelector('form').onsubmit=async e=>{
   e.preventDefault();

   try{

    await req(
     'observations.php',
     {
      method:'POST',
      body:JSON.stringify({
       action:'save',
       reservation_id:Number(reservationId),
       message:e.target.message.value
      })
     }
    );

    m.remove();

    toast('Reporte enviado');

    initReservations();

   }catch(err){
    alert(err.message)
   }
  }
 }
}

function reservationForm(
 ROOMS=[],
 EQUIP=[],
 onSaved=null
){

 const m=modal(
  'Nova reserva',
  `
  <form>

   <div class="form-grid">

    <label class="field">

     <span>Tipo</span>

     <select name="type" id="rtype">

      <option value="room">
       Sala
      </option>

      <option value="equipment">
       Equipamento
      </option>

     </select>

    </label>

    <label class="field" id="resourceField">

     <span>Recurso</span>

     <select
      name="room_id"
      id="resource">
     </select>

    </label>

    <label class="field">

     <span>Data disponível</span>

     <select
      name="reservation_date"
      id="reservationDate"
      required>

      <option value="">
       Selecione o recurso primeiro
      </option>

     </select>

    </label>

    <label class="field">

     <span>Horário de início disponível</span>

     <select
      name="start_time"
      id="startTime"
      required
      disabled>

      <option value="">
       Selecione a data
      </option>

     </select>

    </label>

    <label class="field">

     <span>Tempo de uso</span>

     <select
      name="duration_slots"
      id="durationSlots"
      required
      disabled>

      <option value="">
       Selecione o horário de início
      </option>

     </select>

    </label>

    <label class="field full">

     <span>Finalidade</span>

     <textarea
      name="purpose"
      required></textarea>

    </label>

   </div>

   <p class="hint">
    Cada período de uso tem 50 minutos.
    Você pode escolher 1 período (50 min) ou,
    no máximo, 2 períodos consecutivos (100 min).
   </p>

   <button class="btn primary">
    Reservar
   </button>

  </form>
  `
 );

 const form=m.querySelector('form');

 const type=m.querySelector('#rtype');

 const field=m.querySelector('#resourceField');

 const dateSel=m.querySelector('#reservationDate');

 const startSel=m.querySelector('#startTime');

 const durationSel=m.querySelector('#durationSlots');

 function setResource(){

  const isRoom=type.value==='room';

  field.querySelector('span').textContent='Recurso';

  field.querySelector('select').name=
   isRoom?'room_id':'equipment_id';

  field.querySelector('select').innerHTML=
   isRoom
    ?ROOMS.map(r=>`
      <option value="${r.id}">
       ${esc(r.number)} — ${esc(r.name)}
      </option>
     `).join('')
    :EQUIP.map(r=>`
      <option value="${r.id}">
       ${esc(r.code)} — ${esc(r.name)}
      </option>
     `).join('');

  loadDates()
 }

 async function loadDates(){

  const resource=m.querySelector('#resource');

  dateSel.innerHTML=
   '<option value="">Carregando datas disponíveis...</option>';

  dateSel.disabled=true;

  startSel.disabled=true;

  durationSel.disabled=true;

  if(!resource?.value){

   dateSel.innerHTML=
    '<option value="">Nenhum recurso disponível</option>';

   return
  }

  try{

   const d=await req(
    'reservations.php?action=availability_dates&type='+
    encodeURIComponent(type.value)+
    '&resource_id='+
    encodeURIComponent(resource.value)+
    '&days=30'
   );

   dateSel.innerHTML=
    d.dates.map(x=>{

     const [y,mo,da]=x.split('-');

     return `
      <option value="${x}">
       ${da}/${mo}/${y}
      </option>
     `

    }).join('')
    ||
    '<option value="">Nenhuma data disponível</option>';

   dateSel.disabled=!d.dates.length;

   if(d.dates.length)
    await loadSlots();

  }catch(e){

   dateSel.innerHTML=
    `<option value="">${esc(e.message)}</option>`
  }
 }

 let currentSlots=[];

 async function loadSlots(){

  const resource=m.querySelector('#resource');

  if(!resource.value||!dateSel.value)
   return;

  startSel.innerHTML=
   '<option value="">Carregando horários...</option>';

  startSel.disabled=true;

  durationSel.innerHTML=
   '<option value="">Selecione o horário de início</option>';

  durationSel.disabled=true;

  try{

   const d=await req(
    'reservations.php?action=availability&type='+
    encodeURIComponent(type.value)+
    '&resource_id='+
    encodeURIComponent(resource.value)+
    '&date='+
    encodeURIComponent(dateSel.value)
   );

   currentSlots=d.slots||[];

   startSel.innerHTML=
    currentSlots.map(x=>`
     <option value="${x.start}">
      ${x.start}
     </option>
    `).join('')
    ||
    '<option value="">Nenhum horário disponível</option>';

   startSel.disabled=!currentSlots.length;

   if(currentSlots.length)
    loadDurations();

  }catch(e){

   startSel.innerHTML=
    `<option value="">${esc(e.message)}</option>`
  }
 }

 function loadDurations(){

  const idx=currentSlots.findIndex(
   x=>x.start===startSel.value
  );

  if(idx<0){

   durationSel.innerHTML=
    '<option value="">Selecione o horário de início</option>';

   durationSel.disabled=true;

   return
  }

  const options=[
   '<option value="1">50 minutos</option>'
  ];

  if(
   currentSlots[idx+1] &&
   currentSlots[idx].end===
   currentSlots[idx+1].start
  ){

   options.push(
    '<option value="2">100 minutos (2 períodos)</option>'
   )
  }

  durationSel.innerHTML=options.join('');

  durationSel.disabled=false
 }

 type.onchange=()=>setResource();

 field.querySelector('select')
  .addEventListener('change',loadDates);

 dateSel.onchange=loadSlots;

 startSel.onchange=loadDurations;

 setResource();

 form.onsubmit=async e=>{

  e.preventDefault();

  const x=Object.fromEntries(
   new FormData(form)
  );

  delete x.type;

  const date=x.reservation_date;

  const startTime=x.start_time;

  const slots=Number(
   x.duration_slots||0
  );

  if(!date||!startTime||!slots){

   alert(
    'Selecione uma data, um horário e 1 ou 2 períodos disponíveis.'
   );

   return
  }

  const idx=currentSlots.findIndex(
   v=>v.start===startTime
  );

  if(
   idx<0||
   slots>2||
   !currentSlots[idx]
  ){

   alert(
    'O período selecionado não está mais disponível.'
   );

   return
  }

  let endTime=currentSlots[idx].end;

  if(slots===2){

   if(
    !currentSlots[idx+1]||
    currentSlots[idx].end!==
    currentSlots[idx+1].start
   ){

    alert(
     'Não há dois períodos consecutivos disponíveis nesse horário.'
    );

    return
   }

   endTime=currentSlots[idx+1].end
  }

  x.start_at=
   `${date} ${startTime}:00`;

  x.end_at=
   `${date} ${endTime}:00`;

  delete x.reservation_date;
  delete x.start_time;
  delete x.duration_slots;

  /*
   * CORREÇÃO:
   * informa explicitamente ao PHP que esta requisição
   * deve criar uma nova reserva.
   */
  x.action='create';

  try{

   const saved=await req(
    'reservations.php',
    {
     method:'POST',
     body:JSON.stringify(x)
    }
   );

   m.remove();

   toast(
    `Reserva criada (#${saved.id})`
   );

   if(typeof onSaved==='function'){

    await onSaved();

   }else{

    await initReservations()
   }

  }catch(err){

   alert(err.message);

   await loadDates()
  }
 }
}

async function initObservations(){

 const u=await ensureUser();
 if(!u)return;

 if(u.role!=='ADMIN'){

  location.href='reservas.html';

  return
 }

 const d=await req('observations.php');

 const c=document.querySelector('#pageContent');

 c.innerHTML=`
 <div class="page-head">

  <div>

   <p class="eyebrow">
    COMUNICAÇÕES VINCULADAS ÀS RESERVAS
   </p>

   <h1>
    Reportes
   </h1>

  </div>

 </div>

 <div class="table-wrap">

  <table class="table">

   <thead>

    <tr>
     <th>Data</th>
     <th>Usuário</th>
     <th>Reserva</th>
     <th>Recurso</th>
     <th>Reporte</th>
     <th>Respostas</th>
     <th>Status</th>
     <th>Ações</th>
    </tr>

   </thead>

   <tbody>

    ${
     (d.items||[]).map(x=>`
      <tr>

       <td>
        ${esc(x.created_at)}
       </td>

       <td>
        ${esc(x.user_name)}
       </td>

       <td>
        #${esc(x.reservation_id||'—')}
       </td>

       <td>
        ${esc(x.room_name||x.equipment_name||'—')}
       </td>

       <td>
        ${esc(x.message)}
       </td>

       <td>

        ${
         (x.replies||[]).map(r=>`
          <div style="margin-bottom:8px">

           <strong>
            ${esc(r.admin_name)}:
           </strong>

           ${esc(r.message)}

           <br>

           <small>
            ${esc(r.created_at)}
           </small>

          </div>
         `).join('')
         ||
         '<span class="muted">Nenhuma resposta.</span>'
        }

       </td>

       <td>
        ${esc(x.status)}
       </td>

       <td>

        <button
         class="btn"
         data-reply="${x.id}">
         Responder
        </button>

        ${
         x.status==='OPEN'
          ?`
           <button
            class="btn danger"
            data-close="${x.id}">
            Encerrar
           </button>
          `
          :''
        }

       </td>

      </tr>
     `).join('')
     ||
     '<tr><td colspan="8">Nenhum reporte encontrado.</td></tr>'
    }

   </tbody>

  </table>

 </div>
 `;

 c.querySelectorAll('[data-reply]').forEach(b=>
  b.onclick=()=>
   replyForm(b.dataset.reply)
 );

 c.querySelectorAll('[data-close]').forEach(b=>
  b.onclick=async()=>{
   if(confirm('Encerrar este reporte?')){

    try{

     await req(
      'observations.php',
      {
       method:'POST',
       body:JSON.stringify({
        action:'close',
        id:b.dataset.close
       })
      }
     );

     toast('Reporte encerrado');

     initObservations();

    }catch(e){

     alert(e.message)
    }
   }
  }
 );

 function replyForm(id){

  const m=modal(
   'Responder reporte',
   `
   <form>

    <p class="hint">
     A resposta ficará vinculada a este reporte
     e será exibida ao usuário.
    </p>

    <label class="field">

     <span>
      Resposta
     </span>

     <textarea
      name="message"
      placeholder="Digite a resposta ao usuário."
      required></textarea>

    </label>

    <button class="btn primary">
     Enviar resposta
    </button>

   </form>
   `
  );

  m.querySelector('form').onsubmit=async e=>{

   e.preventDefault();

   try{

    await req(
     'observations.php',
     {
      method:'POST',
      body:JSON.stringify({
       action:'reply',
       notification_id:Number(id),
       message:e.target.message.value
      })
     }
    );

    m.remove();

    toast('Resposta enviada ao usuário');

    initObservations();

   }catch(err){

    alert(err.message)
   }
  }
 }
}

async function initAdmin(){

 const u=await ensureUser();

 if(!u||u.role!=='ADMIN'){

  location.href='dashboard.html';

  return
 }

 const [
  users,
  reservations,
  rooms,
  equip
 ]=await Promise.all([
  req('users.php'),
  req('reservations.php'),
  req('rooms.php'),
  req('equipment.php')
 ]);

 const c=document.querySelector('#pageContent');

 c.innerHTML=`

 <div class="actions" style="margin-bottom:14px">

  <button
   class="btn primary"
   id="new">
   Novo usuário
  </button>

  <button
   class="btn primary"
   id="newReservation">
   Nova reserva
  </button>

 </div>

 <div class="panel" style="margin-bottom:20px">

  <h2>
   Usuários
  </h2>

  <div class="table-wrap">

   <table class="table">

    <thead>

     <tr>
      <th>Nome</th>
      <th>E-mail</th>
      <th>Perfil</th>
      <th>Ativo</th>
      <th></th>
     </tr>

    </thead>

    <tbody>

     ${
      users.items.map(x=>`
       <tr>

        <td>
         ${esc(x.name)}
        </td>

        <td>
         ${esc(x.email)}
        </td>

        <td>
         ${esc(x.role)}
        </td>

        <td>
         ${x.active?'Sim':'Não'}
        </td>

        <td>

         <button
          class="btn"
          data-edit="${x.id}">
          Editar
         </button>

         <button
          class="btn danger"
          data-del="${x.id}">
          Inativar
         </button>

        </td>

       </tr>
      `).join('')
      ||
      '<tr><td colspan="5">Nenhum usuário encontrado.</td></tr>'
     }

    </tbody>

   </table>

  </div>

 </div>

 <div class="panel">

  <h2>
   Todas as reservas
  </h2>

  <div class="table-wrap">

   <table class="table">

    <thead>

     <tr>
      <th>Usuário</th>
      <th>Recurso</th>
      <th>Início</th>
      <th>Fim</th>
      <th>Finalidade</th>
      <th>Status</th>
      <th></th>
     </tr>

    </thead>

    <tbody>

     ${
      reservations.items.map(r=>`
       <tr>

        <td>
         ${esc(r.user_name||'—')}
        </td>

        <td>
         ${esc(r.room_name||r.equipment_name||'—')}
        </td>

        <td>
         ${esc(r.start_at)}
        </td>

        <td>
         ${esc(r.end_at)}
        </td>

        <td>
         ${esc(r.purpose)}
        </td>

        <td>
         ${esc(r.status)}
        </td>

        <td>

         ${
          r.status==='ACTIVE'
           ?`
            <button
             class="btn danger"
             data-cancel-res="${r.id}">
             Cancelar
            </button>
           `
           :''
         }

        </td>

       </tr>
      `).join('')
      ||
      '<tr><td colspan="7">Nenhuma reserva encontrada.</td></tr>'
     }

    </tbody>

   </table>

  </div>

 </div>
 `;

 c.querySelector('#new').onclick=()=>form();

 c.querySelector('#newReservation').onclick=()=>
  reservationForm(
   rooms.items.filter(r=>r.active),
   equip.items.filter(r=>r.active),
   ()=>initAdmin()
  );

 c.querySelectorAll('[data-edit]').forEach(b=>
  b.onclick=()=>
   form(
    users.items.find(x=>x.id==b.dataset.edit)
   )
 );

 c.querySelectorAll('[data-del]').forEach(b=>
  b.onclick=async()=>{
   if(confirm('Inativar usuário?')){

    try{

     await req(
      'users.php',
      {
       method:'POST',
       body:JSON.stringify({
        action:'delete',
        id:b.dataset.del
       })
      }
     );

     toast('Usuário inativado');

     initAdmin();

    }catch(e){

     alert(e.message)
    }
   }
  }
 );

 c.querySelectorAll('[data-cancel-res]').forEach(b=>
  b.onclick=async()=>{
   if(confirm('Cancelar esta reserva?')){

    try{

     await req(
      'reservations.php',
      {
       method:'POST',
       body:JSON.stringify({
        action:'cancel',
        id:b.dataset.cancelRes
       })
      }
     );

     toast('Reserva cancelada');

     initAdmin();

    }catch(e){

     alert(e.message)
    }
   }
  }
 );

 function form(x={}){

  const m=modal(
   x.id?'Editar usuário':'Novo usuário',
   `
   <form>

    <div class="form-grid">

     <label class="field">

      <span>
       Nome
      </span>

      <input
       name="name"
       value="${esc(x.name)}"
       required>

     </label>

     <label class="field">

      <span>
       E-mail
      </span>

      <input
       name="email"
       type="email"
       value="${esc(x.email)}"
       required>

     </label>

     <label class="field">

      <span>
       Perfil
      </span>

      <select name="role">

       <option
        value="USER"
        ${x.role==='USER'?'selected':''}>
        USER
       </option>

       <option
        value="ADMIN"
        ${x.role==='ADMIN'?'selected':''}>
        ADMIN
       </option>

      </select>

     </label>

     <label class="field">

      <span>
       Senha ${x.id?'(opcional)':''}
      </span>

      <input
       name="password"
       type="password">

     </label>

     <label class="field">

      <span>
       Ativo
      </span>

      <select name="active">

       <option
        value="1"
        ${x.active?'selected':''}>
        Sim
       </option>

       <option
        value="0"
        ${!x.active?'selected':''}>
        Não
       </option>

      </select>

     </label>

    </div>

    <button class="btn primary">
     Salvar
    </button>

   </form>
   `
  );

  m.querySelector('form').onsubmit=async e=>{

   e.preventDefault();

   try{

    const z=Object.fromEntries(
     new FormData(e.target)
    );

    z.id=x.id||0;

    z.active=z.active==='1';

    await req(
     'users.php',
     {
      method:'POST',
      body:JSON.stringify(z)
     }
    );

    m.remove();

    toast('Usuário salvo');

    initAdmin();

   }catch(err){

    alert(err.message)
   }
  }
 }
}

async function initReports(){

 const u=await ensureUser();

 if(!u||u.role!=='ADMIN'){

  location.href='dashboard.html';

  return
 }

 const d=await req('reports.php');

 document.querySelector('#pageContent').innerHTML=`

 <div class="grid">

  <div class="panel">

   <h2>
    Reservas por recurso
   </h2>

   <table class="table">

    <thead>

     <tr>
      <th>Recurso</th>
      <th>Total</th>
     </tr>

    </thead>

    <tbody>

     ${
      d.by_resource.map(x=>`
       <tr>

        <td>
         ${esc(x.resource)}
        </td>

        <td>
         ${x.total}
        </td>

       </tr>
      `).join('')
     }

    </tbody>

   </table>

  </div>

  <div class="panel">

   <h2>
    Reservas por status
   </h2>

   <table class="table">

    <thead>

     <tr>
      <th>Status</th>
      <th>Total</th>
     </tr>

    </thead>

    <tbody>

     ${
      d.by_status.map(x=>`
       <tr>

        <td>
         ${esc(x.status)}
        </td>

        <td>
         ${x.total}
        </td>

       </tr>
      `).join('')
     }

    </tbody>

   </table>

  </div>

 </div>
 `
}

async function initAudit(){

 const u=await ensureUser();

 if(!u||u.role!=='ADMIN'){

  location.href='dashboard.html';

  return
 }

 const d=await req('audit.php');

 document.querySelector('#pageContent').innerHTML=`

 <div class="table-wrap">

  <table class="table">

   <thead>

    <tr>
     <th>Data</th>
     <th>Usuário</th>
     <th>Ação</th>
     <th>Entidade</th>
     <th>ID</th>
     <th>Detalhes</th>
    </tr>

   </thead>

   <tbody>

    ${
     d.items.map(x=>`
      <tr>

       <td>
        ${esc(x.created_at)}
       </td>

       <td>
        ${esc(x.user_name||'Sistema')}
       </td>

       <td>
        ${esc(x.action)}
       </td>

       <td>
        ${esc(x.entity)}
       </td>

       <td>
        ${esc(x.entity_id)}
       </td>

       <td>
        ${esc(x.details)}
       </td>

      </tr>
     `).join('')
    }

   </tbody>

  </table>

 </div>
 `
}

setupCommon();

const path=location.pathname.split('/').pop();

(async()=>{

 try{

  if(path==='index.html'||path==='')

   await initHome();

  else if(path==='dashboard.html')

   await initDashboard();

  else if(path==='salas.html')

   await initRooms();

  else if(path==='equipamentos.html')

   await initEquipment();

  else if(path==='reservas.html')

   await initReservations();

  else if(path==='observacoes.html')

   await initObservations();

  else if(path==='admin.html')

   await initAdmin();

  else if(path==='relatorios.html')

   await initReports();

  else if(path==='auditoria.html')

   await initAudit();

 }catch(e){

  console.error(e);

  if(document.querySelector('#pageContent'))

   document.querySelector('#pageContent').innerHTML=
    '<div class="panel"><strong>Erro:</strong> '+
    esc(e.message)+
    '</div>'
 }
})();

document.addEventListener('click',async ev=>{

 const hard=ev.target.closest(
  '[data-room-hard-delete]'
 );

 if(hard){

  const id=hard.dataset.roomHardDelete;

  if(!confirm(
   'Excluir esta sala definitivamente? Esta ação não pode ser desfeita.'
  ))
   return;

  const r=await api(
   'rooms.php',
   {
    action:'hard_delete',
    id
   }
  );

  if(!r.ok)
   throw new Error(
    r.message||
    'Não foi possível excluir a sala.'
   );

  alert(
   'Sala excluída definitivamente.'
  );

  location.reload();

  return
 }

 const soft=ev.target.closest(
  '[data-room-inactivate]'
 );

 if(soft){

  const id=soft.dataset.roomInactivate;

  if(!confirm(
   'Inativar esta sala?'
  ))
   return;

  const r=await api(
   'rooms.php',
   {
    action:'delete',
    id
   }
  );

  if(!r.ok)
   throw new Error(
    r.message||
    'Não foi possível inativar a sala.'
   );

  location.reload();
 }
});
