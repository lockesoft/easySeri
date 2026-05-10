<?php
// /public/scan.php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

include __DIR__ . '/../includes/header.php';

// Modo pantalla completa por query ?full=1
$FULL = isset($_GET['full']) && $_GET['full'] == '1';
if ($FULL):
?>
<style>
  nav.navbar { display: none !important; }
  .container.py-3 { padding:0 !important; margin:0 !important; max-width:100% !important; width:100% !important; }
  body { padding:0 !important; margin:0 !important; }
  .scan-card { margin-top:0 !important; }
</style>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const preview = document.getElementById('preview');
    const goFS = async () => { try {
      if (document.documentElement.requestFullscreen) await document.documentElement.requestFullscreen();
    } catch{} };
    preview?.addEventListener('click', goFS, { once:true });
  });
</script>
<?php endif; ?>

<style>
  #busyOverlay{position:fixed;inset:0;background:rgba(255,255,255,.65);display:none;align-items:center;justify-content:center;z-index:2000;backdrop-filter:blur(2px)}
  #busyOverlay.show{display:flex}
  #toastHost{position:fixed;left:50%;bottom:16px;transform:translateX(-50%);z-index:2050;width:min(680px,94vw)}
  .toast+.toast{margin-top:.5rem}
  .scan-card{max-width:1100px;margin-inline:auto; transition: box-shadow .15s ease}
  .scan-card.place { box-shadow:0 0 0 .2rem rgba(25,135,84,.25) }
  .scan-card.move  { box-shadow:0 0 0 .2rem rgba(255,193,7,.35) }

  .pill{border:1px solid #dee2e6;border-radius:999px;padding:.25rem .6rem;font-weight:600;background:#f8f9fa}
  .kpi{font-weight:700}
  .btn-cam{margin:.25rem}
  .btn-row{margin:.25rem}
  .muted{opacity:.6}
  #cameraBox{display:none}
  #cameraBox.active{display:block}
  #videoWrap{position:relative;border:1px solid #dee2e6;border-radius:.5rem;overflow:hidden}
  #preview{width:100%;height:auto;max-height:60vh;background:#000}
  #scanReticle{position:absolute;inset:15% 10%;border:2px dashed rgba(255,255,255,.8);border-radius:.5rem;pointer-events:none}
  #camToolbar{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.5rem}
  #crossPlantWarning{border-width:2px}
</style>

<div id="busyOverlay"><div class="text-center"><div class="spinner-border" role="status"></div><div class="mt-2 fw-semibold">Procesando…</div></div></div>
<div id="toastHost" aria-live="polite" aria-atomic="true"></div>

<div class="scan-card card shadow-sm place" id="scanCard">
  <div class="card-body">
    <!-- Cámara -->
    <div id="cameraBox" class="mb-3 active">
      <div id="videoWrap">
        <video id="preview" playsinline muted></video>
        <div id="scanReticle" aria-hidden="true"></div>
      </div>
      <div id="camToolbar">
        <button id="torchBtn" class="btn btn-outline-secondary btn-sm" disabled>🔦 Linterna</button>
        <button id="switchBtn" class="btn btn-outline-secondary btn-sm" disabled>🔄 Cambiar cámara</button>
        <input type="file" id="filePicker" accept="image/*" capture="environment" class="d-none">
        <button id="pickFileBtn" class="btn btn-outline-primary btn-sm d-none">📸 Tomar foto</button>
        <button id="stopCamBtn" class="btn btn-outline-danger btn-sm">■ Detener</button>
      </div>
    </div>

    <!-- Resumen -->
    <div id="entryInfo" class="d-none mb-3">
      <div class="d-flex flex-wrap gap-2 align-items-center w-100">
        <span class="pill">Entrada: <span id="entryNum" class="kpi"></span></span>
        <span class="pill">Pendientes: <span id="entryPending" class="kpi"></span> / <span id="entryTotal" class="kpi"></span></span>
        <span id="extraInfo" class="badge rounded-pill bg-light text-secondary border">—</span>

        <div class="ms-auto d-flex align-items-center gap-2">
          <button id="moveEntryBtn" class="btn btn-sm btn-warning d-none">↔️ Mover esta entrada</button>
          <button id="moveRowBtn"   class="btn btn-sm btn-warning d-none" disabled>🚚 Mover toda la fila (cargando…)</button>
          <button id="clearBtn" class="btn btn-outline-secondary btn-sm">Limpiar</button>
        </div>
      </div>
      <div id="moveRowHint" class="form-text text-muted d-none"></div>
      <div id="crossPlantWarning" class="alert alert-warning mt-3 mb-0 d-none"></div>
    </div>

    <!-- Paso 1: elegir cámara -->
    <div id="camsBox" class="d-none mb-3">
      <div class="fw-semibold mb-2">Elige una cámara</div>
      <div id="camsList"></div>
    </div>

    <!-- Paso 2: elegir fila -->
    <div id="rowsBox" class="d-none mb-3">
      <div class="fw-semibold mb-2">Elige una fila</div>
      <div id="rowsList"></div>
    </div>

    <!-- Paso 3: confirmación -->
    <div id="confirmBox" class="d-none">
      <div class="alert alert-info mb-3" id="confirmMsg">Confirma para ubicar/mover.</div>
      <button id="confirmBtn" class="btn btn-primary btn-lg w-100">✅ Confirmar</button>
    </div>
  </div>
</div>

<script src="https://unpkg.com/@zxing/browser@latest"></script>
<script>
/* Endpoints */
const API_COUNTS   = 'api/entry_counts.php';
const API_CAMERAS  = 'api/cameras.php';
const API_ROWS     = 'api/camera_rows.php';
const API_CONFIRM  = 'api/scan_confirm.php';
const API_PSTATUS  = 'api/pallet_status.php';
const API_MOVE     = 'api/move_confirm.php';

/* Toasts & Busy */
function showBusy(on){ document.getElementById('busyOverlay').classList.toggle('show', !!on); }
function toast(type, title, msg, delay=5000){
  const host = document.getElementById('toastHost');
  const el = document.createElement('div');
  el.className = 'toast align-items-center border-0'; el.dataset.bsAutohide='true'; el.dataset.bsDelay=String(delay);
  let hdrClass = 'bg-secondary text-white';
  if (type==='success') hdrClass = 'bg-success text-white';
  else if (type==='warning') hdrClass = 'bg-warning';
  else if (type==='error') hdrClass = 'bg-danger text-white';
  el.innerHTML = `
    <div class="toast-header ${hdrClass}">
      <strong class="me-auto">${title||''}</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>
    <div class="toast-body">${msg||''}</div>`;
  host.appendChild(el);
  new bootstrap.Toast(el).show();
  el.addEventListener('hidden.bs.toast',()=>el.remove());
}

/* Helpers */
async function fetchJSON(url, options){
  const res = await fetch(url, options);
  const ct = res.headers.get('content-type') || '';
  const text = await res.text();
  if (!res.ok) throw new Error(`HTTP ${res.status} ${res.statusText}: ${text.slice(0,180)}`);
  if (!ct.includes('application/json')) throw new Error(`Respuesta no JSON: ${text.slice(0,180)}`);
  try { return JSON.parse(text); } catch(e){ throw new Error(`JSON inválido: ${text.slice(0,180)}`); }
}
function arr(v){ return Array.isArray(v) ? v : []; }
function num(v){ return Number(v) || 0; }
function escapeHtml(s){return String(s ?? '').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&quot;',"'":'&#39;'}[m]))}
function placeContextText(ctx){
  if (!ctx) return '';
  const plant = ctx.plant_code || '—';
  const cam = ctx.camera_name || ('Cámara ' + (ctx.camera_id || '—'));
  const row = ctx.row_label || 'fila no definida';
  const pos = `F${ctx.row_idx || '—'}-C${ctx.col_idx || '—'} · Nivel ${ctx.level_idx || '—'}`;
  return `Planta ${plant} / ${cam} / ${row} / ${pos}`;
}
function crossPlantHtml(ctx){
  if (!ctx || ctx.same_as_active_plant !== false) return '';
  return `
    <strong>⚠ Atención: este palet ya estaba ubicado en otra planta.</strong><br>
    Ubicación actual: <strong>${escapeHtml(placeContextText(ctx))}</strong>.<br>
    Si confirmas, se cerrará esa ubicación anterior y se reubicará en la planta activa
    <strong>${escapeHtml(ctx.active_plant_code || '')}</strong>.
  `;
}

/* Estado */
let CURRENT_ENTRY = null;
let CHOSEN_CAMERA = null;
let CHOSEN_ROW    = null;
let MODE          = 'place';
let SRC_ROW       = null;
let CURRENT_PLACE_CONTEXT = null;

const scanCard   = document.getElementById('scanCard');

/* DOM */
const entryBox   = document.getElementById('entryInfo');
const entryNum   = document.getElementById('entryNum');
const entryTotal = document.getElementById('entryTotal');
const entryPend  = document.getElementById('entryPending');
const extraInfo  = document.getElementById('extraInfo');
const crossPlantWarning = document.getElementById('crossPlantWarning');

const clearBtn   = document.getElementById('clearBtn');
const moveRowBtn = document.getElementById('moveRowBtn');
const moveEntryBtn = document.getElementById('moveEntryBtn');
const moveRowHint= document.getElementById('moveRowHint');

const camsBox  = document.getElementById('camsBox');
const camsList = document.getElementById('camsList');
const rowsBox  = document.getElementById('rowsBox');
const rowsList = document.getElementById('rowsList');
const confirmBox= document.getElementById('confirmBox');
const confirmMsg= document.getElementById('confirmMsg');
const confirmBtn= document.getElementById('confirmBtn');

/* Cámara */
const cameraBox  = document.getElementById('cameraBox');
const preview    = document.getElementById('preview');
const torchBtn   = document.getElementById('torchBtn');
const switchBtn  = document.getElementById('switchBtn');
const stopCamBtn = document.getElementById('stopCamBtn');
const filePicker = document.getElementById('filePicker');
const pickFileBtn= document.getElementById('pickFileBtn');

let stream=null, usingBarcodeDetector=false, detector=null, zxingReader=null, currentDeviceId=null, torchOn=false, lastHitAt=0;

function applyModeStyles(){
  scanCard.classList.remove('place','move');
  if (MODE==='place') scanCard.classList.add('place');
  else scanCard.classList.add('move');

  const camBtns = camsList.querySelectorAll('button.btn-cam');
  camBtns.forEach(b=>{
    b.classList.remove('btn-outline-primary','btn-outline-warning');
    b.classList.add(MODE==='place' ? 'btn-outline-primary' : 'btn-outline-warning');
  });
  const rowBtns = rowsList.querySelectorAll('button.btn-row');
  rowBtns.forEach(b=>{
    b.classList.remove('btn-outline-secondary','btn-outline-warning');
    b.classList.add(MODE==='place' ? 'btn-outline-secondary' : 'btn-outline-warning');
  });
}

/* Cámara helpers */
async function ensureGetUserMedia() {
  if (navigator.mediaDevices?.getUserMedia) return true;
  const legacy = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
  if (!legacy) return false;
  if (!navigator.mediaDevices) navigator.mediaDevices = {};
  navigator.mediaDevices.getUserMedia = c => new Promise((res,rej)=> legacy.call(navigator,c,res,rej));
  return true;
}
async function setupTorchCapability(){
  torchBtn.disabled = true;
  if (!stream) return;
  const caps = stream.getVideoTracks()[0]?.getCapabilities?.();
  if (caps && 'torch' in caps) torchBtn.disabled = false;
}
async function startCamera(){
  try{
    const isSecure = location.protocol==='https:' || location.hostname==='localhost';
    if (!isSecure){
      toast('warning','HTTPS requerido','Usa “📸 Tomar foto”.');
      pickFileBtn.classList.remove('d-none');
    } else {
      const ok = await ensureGetUserMedia();
      if (ok) {
        const constraints = { video: currentDeviceId ? {deviceId:{exact:currentDeviceId}} : {facingMode:{ideal:'environment'}}, audio:false };
        stream = await navigator.mediaDevices.getUserMedia(constraints);
        preview.srcObject = stream; await preview.play();
        switchBtn.disabled=false; await setupTorchCapability();
      } else {
        pickFileBtn.classList.remove('d-none');
      }
    }
    cameraBox.classList.add('active');
    usingBarcodeDetector = ('BarcodeDetector' in window);
    if (usingBarcodeDetector){
      const formats = ['qr_code','ean_13','ean_8','code_128','code_39','itf','upc_a','upc_e','codabar','data_matrix','pdf417'];
      detector = new window.BarcodeDetector({formats}); loopDetect();
    } else {
      zxingReader = new ZXingBrowser.BrowserMultiFormatReader();
      const devices = await ZXingBrowser.BrowserCodeReader.listVideoInputDevices();
      if (!currentDeviceId && devices.length){
        const back = devices.find(d=>/back|rear|trase|environment/i.test(d.label));
        currentDeviceId = back?.deviceId || devices[devices.length-1].deviceId;
      }
      await zxingReader.decodeFromVideoDevice(currentDeviceId || undefined, preview, (result)=>{ if (result?.text) onDetected(result.text); });
    }
    pickFileBtn.classList.add('d-none');
  }catch(e){
    toast('error','No se pudo abrir la cámara', e.message||'');
    pickFileBtn.classList.remove('d-none'); cameraBox.classList.add('active');
  }
}
async function stopCamera(){
  try{
    if (zxingReader){ try{ zxingReader.reset(); }catch{} zxingReader=null; }
    if (preview){ try{ preview.pause(); preview.srcObject=null; }catch{} }
    if (stream){ stream.getTracks().forEach(t=>{ try{ t.stop(); }catch{} }); stream=null; }
    torchBtn.disabled = true; switchBtn.disabled = true; torchOn=false; cameraBox.classList.remove('active');
  }catch{}
}
async function loopDetect(){
  if (!detector || !preview || !stream) return;
  try{
    const bitmap = await createImageBitmap(preview);
    const codes = await detector.detect(bitmap);
    if (codes?.length){
      const best = codes.reduce((a,c)=> (c.rawValue?.length||0)>(a.rawValue?.length||0)?c:a, codes[0]);
      if (best?.rawValue) onDetected(best.rawValue);
    }
    setTimeout(loopDetect, 200);
  }catch{ setTimeout(loopDetect, 250); }
}
torchBtn.addEventListener('click', async ()=>{
  if (!stream) return;
  try{
    const t = stream.getVideoTracks()[0];
    torchOn = !torchOn; await t.applyConstraints({advanced:[{torch: torchOn}]});
    torchBtn.classList.toggle('btn-secondary', torchOn);
    torchBtn.classList.toggle('btn-outline-secondary', !torchOn);
  }catch{ torchOn=false; toast('warning','Linterna no disponible',''); }
});
switchBtn.addEventListener('click', async ()=>{
  try{
    const cams = (await navigator.mediaDevices.enumerateDevices()).filter(d=>d.kind==='videoinput');
    if (!cams.length) return;
    const idx = cams.findIndex(d=>d.deviceId===currentDeviceId);
    currentDeviceId = cams[(idx+1)%cams.length].deviceId;
    await stopCamera(); await startCamera();
  }catch(e){ toast('error','No se pudo cambiar de cámara', e.message||''); }
});
pickFileBtn.addEventListener('click', ()=> filePicker.click());
filePicker.addEventListener('change', async (ev)=>{
  const file = ev.target.files && ev.target.files[0]; if (!file) return;
  try{
    const url = URL.createObjectURL(file); const img = new Image();
    img.onload = async ()=>{
      try{
        const reader = new ZXingBrowser.BrowserMultiFormatReader();
        const res = await reader.decodeFromImageElement(img);
        if (res?.text) onDetected(res.text); else toast('warning','No se leyó código','');
      }catch{ toast('error','No se pudo leer',''); }finally{ URL.revokeObjectURL(url); filePicker.value=''; }
    }; img.src = url;
  }catch(e){ toast('error','Error con la foto', e.message||''); }
});
stopCamBtn.addEventListener('click', stopCamera);
window.addEventListener('pagehide', stopCamera);
window.addEventListener('beforeunload', stopCamera);
(async ()=>{ await startCamera(); })();

/* Flujo */
function resetFlow(){
  CURRENT_ENTRY=null; CHOSEN_CAMERA=null; CHOSEN_ROW=null; MODE='place'; SRC_ROW=null; CURRENT_PLACE_CONTEXT=null;
  entryBox.classList.add('d-none'); camsBox.classList.add('d-none'); rowsBox.classList.add('d-none'); confirmBox.classList.add('d-none');
  crossPlantWarning.classList.add('d-none'); crossPlantWarning.innerHTML='';
  camsList.innerHTML=''; rowsList.innerHTML=''; confirmMsg.textContent='';
  moveRowBtn.classList.add('d-none'); moveEntryBtn.classList.add('d-none'); moveRowHint.classList.add('d-none');
  moveRowBtn.disabled = true; moveRowBtn.textContent = '🚚 Mover toda la fila (cargando…)';
  applyModeStyles();
}
clearBtn?.addEventListener('click', resetFlow);

function normalizeBarcode(s){
  const v = String(s||'').trim();
  return (v.length>3 && /^\d{3}/.test(v)) ? v.slice(3) : v;
}

async function onDetected(text){
  const now = Date.now(); if (now-lastHitAt<1200) return; lastHitAt=now;
  const raw = String(text||'').trim(); if (!raw) return;
  const code = normalizeBarcode(raw);
  try{ navigator.vibrate?.(60); }catch{}
  await handleScanCode(code);
}

/* 1) Escaneo → status + contadores + decidir modo */
async function handleScanCode(palletCode){
  showBusy(true);
  try{
    const st = await fetchJSON(`${API_PSTATUS}?code=${encodeURIComponent(palletCode)}`);
    if (!st.ok) throw new Error(st.error || 'No se pudo resolver el palet');

    CURRENT_PLACE_CONTEXT = st.place_context || null;

    const ent = st.entrada_num || '';
    const counts = ent ? await fetchJSON(`${API_COUNTS}?entrada_num=${encodeURIComponent(ent)}`) : { ok:true, total:0, pending:0 };

    const total   = num(counts.total || 0);
    const pending = num(counts.pending || 0);
    const placed  = Math.max(0, total - pending);

    MODE = st.placed ? 'move' : 'place';
    CURRENT_ENTRY = { entrada_num: ent, total, pending, pallet_num: st.pallet_num || palletCode };

    entryNum.textContent   = ent || '—';
    entryTotal.textContent = total;
    entryPend.textContent  = pending;

    const baseInfo = `${st.variedad || st?.plegado?.variedad || '—'} / ${st.propietario || st?.entrada?.propietario || '—'} / ${st.entrada?.matricula || '—'}`;
    if (MODE === 'move'){
      extraInfo.innerHTML = `<span class="text-dark bg-warning px-2 py-1 rounded fw-semibold">MODO MOVER</span> · ${escapeHtml(baseInfo)}${ent?` · Ubicados: <b>${placed}</b>`:''}`;
    } else {
      extraInfo.innerHTML = `<span class="text-white bg-success px-2 py-1 rounded fw-semibold">MODO UBICAR</span> · ${escapeHtml(baseInfo)}`;
    }
    entryBox.classList.remove('d-none');
    applyModeStyles();

    const warningHtml = crossPlantHtml(CURRENT_PLACE_CONTEXT);
    if (warningHtml) {
      crossPlantWarning.innerHTML = warningHtml;
      crossPlantWarning.classList.remove('d-none');
      toast('warning','Ubicado en otra planta','Revisa el aviso antes de confirmar el traslado.', 8000);
    } else {
      crossPlantWarning.classList.add('d-none');
      crossPlantWarning.innerHTML = '';
    }

    // Mostrar SIEMPRE ambas opciones en modo mover
    moveRowBtn.classList.add('d-none');
    moveEntryBtn.classList.add('d-none');
    moveRowHint.classList.add('d-none');
    SRC_ROW = null;

    if (st.placed) {
      moveEntryBtn.classList.remove('d-none');

      // Usar row_info del propio pallet_status (sin endpoints nuevos)
      if (st.row_info && st.row_info.camera_id && st.row_info.row_group_id) {
        SRC_ROW = {
          camera_id: Number(st.row_info.camera_id),
          row_group_id: Number(st.row_info.row_group_id),
          label: String(st.row_info.label || 'Fila'),
          count: Number(st.row_info.count || 0),
          place_context: CURRENT_PLACE_CONTEXT
        };
        moveRowBtn.textContent = `🚚 Mover toda la fila «${SRC_ROW.label}» (${SRC_ROW.count})`;
        moveRowBtn.disabled = SRC_ROW.count <= 0;
      } else {
        moveRowBtn.textContent = '🚚 Mover toda la fila (no disponible)';
        moveRowBtn.disabled = true;
      }
      moveRowBtn.classList.remove('d-none');
      moveRowHint.textContent = 'Elige si mover esta entrada o la fila completa; luego selecciona cámara y fila destino.';
      moveRowHint.classList.remove('d-none');
    }

    await loadCameras();
  }catch(e){
    toast('error','Error', e.message||'');
  }finally{ showBusy(false); }
}

/* 1b) Botones de elección de modo mover */
moveEntryBtn.addEventListener('click', ()=>{
  MODE = 'move';
  applyModeStyles();
  toast('warning','Mover esta entrada','Elige cámara y fila destino.');
  if (camsBox.classList.contains('d-none')) loadCameras().catch(()=>{});
});
moveRowBtn.addEventListener('click', ()=>{
  if (moveRowBtn.disabled) return;
  if (!SRC_ROW) { toast('warning','Fila','No se pudo determinar la fila de origen'); return; }
  MODE = 'move_row';
  applyModeStyles();
  toast('warning','Mover fila completa', `Origen: «${escapeHtml(SRC_ROW.label)}». Elige cámara y fila destino.`);
  if (camsBox.classList.contains('d-none')) loadCameras().catch(()=>{});
});

/* 2) Cargar cámaras */
async function loadCameras(){
  camsList.innerHTML=''; rowsList.innerHTML=''; rowsBox.classList.add('d-none'); confirmBox.classList.add('d-none');
  const res = await fetchJSON(API_CAMERAS);
  if (!res.ok) throw new Error(res.error||'No pude obtener cámaras');
  const cameras = arr(res.cameras);
  cameras.forEach(c=>{
    const btn = document.createElement('button');
    btn.className = `btn ${MODE==='place' ? 'btn-outline-primary' : 'btn-outline-warning'} btn-cam`;
    btn.textContent = c.name;
    btn.addEventListener('click', ()=> selectCamera(c));
    camsList.appendChild(btn);
  });
  camsBox.classList.remove('d-none');
  document.getElementById('camsBox')?.scrollIntoView({behavior:'smooth', block:'start'});
}

/* 3) Elegir cámara → cargar filas */
async function selectCamera(cam){
  CHOSEN_CAMERA = { id: cam.id, name: cam.name, plant_code: cam.plant_code || null };
  showBusy(true);
  try{
    const res = await fetchJSON(`${API_ROWS}?camera_id=${encodeURIComponent(cam.id)}`);
    if (!res.ok) throw new Error(res.error||'No pude listar filas');
    rowsList.innerHTML='';
    const rows = arr(res.rows);
    rows.forEach(r=>{
      const btn = document.createElement('button');
      btn.className = `btn ${MODE==='place' ? 'btn-outline-secondary' : 'btn-outline-warning'} btn-row`;
      btn.innerHTML = `${escapeHtml(r.label)} <span class="badge text-bg-light">${num(r.free)}</span>`;
      if (num(r.free)<=0) btn.classList.add('muted');
      btn.disabled = (num(r.free)<=0);
      btn.addEventListener('click', ()=> selectRow(r));
      rowsList.appendChild(btn);
    });
    rowsBox.classList.remove('d-none');
    confirmBox.classList.add('d-none');
    document.getElementById('rowsBox')?.scrollIntoView({behavior:'smooth', block:'start'});
  }catch(e){ toast('error','Filas', e.message||''); }
  finally{ showBusy(false); }
}

/* 4) Elegir fila → preparar confirmación */
function selectRow(r){
  CHOSEN_ROW = { row_group_id: r.row_group_id, label: r.label, free: num(r.free) };

  const total   = CURRENT_ENTRY?.total ?? 0;
  const pending = CURRENT_ENTRY?.pending ?? 0;
  const placed  = Math.max(0, total - pending);

  let msg = '';
  if (MODE === 'place'){
    msg = (CHOSEN_ROW.free >= pending)
      ? `Cabe la entrada completa (pendientes): <b>${pending}</b> en <b>${escapeHtml(r.label)}</b> de <b>${escapeHtml(CHOSEN_CAMERA.name)}</b>.`
      : `Entrada parcial: caben <b>${CHOSEN_ROW.free}</b> de <b>${pending}</b> (pendientes) en <b>${escapeHtml(r.label)}</b> (${escapeHtml(CHOSEN_CAMERA.name)}).`;
  } else if (MODE === 'move') {
    if (placed <= 0 && CURRENT_ENTRY.entrada_num) {
      msg = `No hay palets ubicados de la entrada <b>${escapeHtml(CURRENT_ENTRY.entrada_num)}</b> para mover.`;
    } else {
      msg = `Se moverán palets ubicados de <b>${escapeHtml(CURRENT_ENTRY.entrada_num || 'esta lectura')}</b> hacia <b>${escapeHtml(r.label)}</b> (${escapeHtml(CHOSEN_CAMERA.name)}).`;
    }
  } else { // move_row
    const cnt = SRC_ROW?.count || 0;
    msg = `Mover <b>fila completa</b> «${escapeHtml(SRC_ROW?.label||'—')}» (${cnt} palet(es)) a <b>${escapeHtml(r.label)}</b> de <b>${escapeHtml(CHOSEN_CAMERA.name)}</b>.`;
  }

  const warningHtml = (MODE !== 'place') ? crossPlantHtml(CURRENT_PLACE_CONTEXT) : '';
  if (warningHtml) {
    msg += `<div class="alert alert-warning mt-3 mb-0">${warningHtml}</div>`;
  }

  confirmMsg.innerHTML = msg;
  confirmBox.classList.remove('d-none');
  document.getElementById('confirmBox')?.scrollIntoView({behavior:'smooth', block:'start'});
}

/* 5) Confirmar → ubicar o mover */
confirmBtn.addEventListener('click', async ()=>{
  if (!CHOSEN_CAMERA || !CHOSEN_ROW){
    toast('warning','Incompleto','Selecciona cámara y fila'); return;
  }
  showBusy(true);
  try{
    if (MODE === 'place'){
      const payload = {
        case: 1,
        entrada_num: CURRENT_ENTRY.entrada_num,
        camera_id: CHOSEN_CAMERA.id,
        row_group_id: CHOSEN_ROW.row_group_id
      };
      const data = await fetchJSON(API_CONFIRM, {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
      });
      if (!data.ok) throw new Error(data.error || 'No se pudo confirmar');
      toast('success','Ubicación confirmada', `Insertados ${num(data.inserted)||0} palet(es) en ${escapeHtml(CHOSEN_ROW.label)}.`);
    } else if (MODE === 'move') {
      const payload = {
        scope: 'entry',
        entrada_num: CURRENT_ENTRY.entrada_num,
        camera_id: CHOSEN_CAMERA.id,
        row_group_id: CHOSEN_ROW.row_group_id
      };
      const data = await fetchJSON(API_MOVE, {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
      });
      if (!data.ok) throw new Error(data.error || 'No se pudo mover');
      toast('success','Movimiento hecho', `Movidos ${num(data.moved)||0} palet(es) a ${escapeHtml(CHOSEN_ROW.label)}.`);
    } else { // move_row
      if (!SRC_ROW) throw new Error('No se conoce la fila origen');
      const payload = {
        scope: 'row',
        src_camera_id: SRC_ROW.camera_id,
        src_row_group_id: SRC_ROW.row_group_id,
        camera_id: CHOSEN_CAMERA.id,
        row_group_id: CHOSEN_ROW.row_group_id
      };
      const data = await fetchJSON(API_MOVE, {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
      });
      if (!data.ok) throw new Error(data.error || 'No se pudo mover la fila completa');
      toast('success','Fila movida', `Movidos ${num(data.moved)||0} palet(es) de «${escapeHtml(SRC_ROW.label)}» a «${escapeHtml(CHOSEN_ROW.label)}».`);
    }

    resetFlow();
    document.querySelector('.scan-card')?.scrollIntoView({behavior:'smooth', block:'start'});
  }catch(e){
    toast('error', MODE==='place' ? 'Confirmación' : (MODE==='move' ? 'Movimiento' : 'Movimiento de fila'), e.message||'');
  }finally{
    showBusy(false);
  }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
