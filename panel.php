<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$user = getUser();
$initial = strtoupper(substr($user['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AHS SMS Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#060810;--surface:#0d1117;--surface2:#111820;--border:#1c2333;
  --accent:#4f8eff;--accent2:#7c3aed;
  --green:#3fb950;--red:#f85149;--yellow:#e3b341;
  --text:#e6edf3;--muted:#7d8590;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
/* Topbar */
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 32px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.topbar-logo{display:flex;align-items:center;gap:10px;font-family:'Syne',sans-serif;font-weight:800;font-size:16px;}
.topbar-logo .dot{width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));box-shadow:0 0 8px rgba(79,142,255,.6);}
.topbar-right{display:flex;align-items:center;gap:12px;}
.user-chip{display:flex;align-items:center;gap:8px;background:var(--surface2);border:1px solid var(--border);border-radius:20px;padding:5px 14px 5px 8px;font-size:13px;}
.user-chip .avatar{width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;font-family:'Syne',sans-serif;}
.logout-btn{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:13px;padding:6px 12px;border-radius:8px;border:1px solid var(--border);transition:all .15s;}
.logout-btn:hover{color:var(--red);border-color:rgba(248,81,73,.3);}
/* Layout */
.layout{display:grid;grid-template-columns:340px 1fr;gap:0;min-height:calc(100vh - 60px);}
/* Sidebar */
.sidebar{background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:sticky;top:60px;height:calc(100vh - 60px);overflow:hidden;}
.sidebar-head{padding:16px 20px;border-bottom:1px solid var(--border);}
.sidebar-head h2{font-family:'Syne',sans-serif;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:10px;}
.search-wrap{position:relative;}
.search-wrap .icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none;}
.search-input{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:9px 12px 9px 34px;font-size:13px;font-family:inherit;color:var(--text);outline:none;transition:border-color .2s;}
.search-input:focus{border-color:rgba(79,142,255,.4);}
.search-input::placeholder{color:#3d4450;}
.range-list{flex:1;overflow-y:auto;padding:8px;}
.range-list::-webkit-scrollbar{width:4px;}
.range-list::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.range-item{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:10px;cursor:pointer;transition:all .15s;margin-bottom:2px;border:1px solid transparent;}
.range-item:hover{background:var(--surface2);}
.range-item.active{background:rgba(79,142,255,.1);border-color:rgba(79,142,255,.2);}
.range-item.active .range-name{color:var(--accent);}
.range-name{font-size:13px;font-weight:500;flex:1;}
.range-payout{font-size:11px;font-weight:600;color:var(--green);background:rgba(63,185,80,.1);border-radius:6px;padding:2px 7px;margin-left:8px;flex-shrink:0;}
.loading-ranges{display:flex;align-items:center;justify-content:center;gap:10px;padding:40px;color:var(--muted);font-size:13px;}
.spinner{width:16px;height:16px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
/* Main */
.main{padding:32px;display:flex;flex-direction:column;gap:24px;}
.empty-state{flex:1;display:flex;align-items:center;justify-content:center;min-height:400px;}
.empty-inner{text-align:center;color:var(--muted);}
.empty-inner .icon{font-size:56px;margin-bottom:16px;opacity:.5;}
.empty-inner h3{font-family:'Syne',sans-serif;font-size:18px;color:var(--text);margin-bottom:8px;}
.empty-inner p{font-size:14px;line-height:1.6;}
/* Alloc card */
.alloc-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:28px;animation:fadeIn .25s ease;}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.alloc-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border);}
.alloc-title{font-family:'Syne',sans-serif;font-size:18px;font-weight:700;}
.alloc-sub{font-size:12px;color:var(--muted);margin-top:3px;}

/* Range availability count badge */
.count-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600;font-family:'DM Mono',monospace;border:1px solid var(--border);background:var(--surface2);color:var(--muted);transition:all .2s;}
.count-badge.good{background:rgba(63,185,80,.10);border-color:rgba(63,185,80,.30);color:var(--green);}
.count-badge.warn{background:rgba(227,179,65,.10);border-color:rgba(227,179,65,.30);color:var(--yellow);}
.count-badge.low{background:rgba(248,81,73,.10);border-color:rgba(248,81,73,.30);color:var(--red);}
.count-badge.error{background:rgba(248,81,73,.06);border-color:rgba(248,81,73,.25);color:var(--red);}
.count-badge .count-icon{font-size:13px;}
.count-badge .count-main{font-size:13px;}
.count-badge .count-main b{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;}
.count-badge .count-sub{font-size:11px;opacity:.7;}
.spinner-tiny{width:12px;height:12px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .6s linear infinite;display:inline-block;vertical-align:middle;}
@keyframes spin{to{transform:rotate(360deg)}}
.close-btn{background:none;border:1px solid var(--border);border-radius:8px;padding:6px 10px;color:var(--muted);cursor:pointer;font-size:18px;transition:all .15s;}
.close-btn:hover{color:var(--red);border-color:rgba(248,81,73,.3);}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.field-label{font-size:11px;font-weight:500;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;display:block;}
/* Qty */
.qty-wrap{display:flex;align-items:center;border:1px solid var(--border);border-radius:10px;overflow:hidden;}
.qty-btn{width:40px;height:42px;border:none;background:var(--bg);color:var(--text);font-size:18px;cursor:pointer;transition:background .15s;flex-shrink:0;}
.qty-btn:hover{background:var(--surface2);}
.qty-input{flex:1;border:none;background:var(--bg);text-align:center;font-size:16px;font-weight:600;font-family:'Syne',sans-serif;color:var(--text);outline:none;padding:11px 4px;}
/* Payterms */
.payterm-grid{display:flex;flex-wrap:wrap;gap:6px;}
.pt-pill{padding:7px 14px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--muted);font-size:12px;font-weight:500;cursor:pointer;transition:all .15s;font-family:inherit;}
.pt-pill:hover{border-color:rgba(79,142,255,.4);color:var(--text);}
.pt-pill.active{background:rgba(79,142,255,.12);border-color:rgba(79,142,255,.4);color:var(--accent);}
/* Payout display */
.payout-display{background:rgba(63,185,80,.08);border:1px solid rgba(63,185,80,.2);border-radius:10px;padding:11px 14px;display:flex;align-items:center;justify-content:space-between;}
.payout-val{font-size:18px;font-weight:700;color:var(--green);font-family:'Syne',sans-serif;}
.payout-note{font-size:11px;color:var(--muted);}
/* Alloc btn */
.alloc-btn{width:100%;margin-top:20px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:700;font-family:'Syne',sans-serif;letter-spacing:.3px;cursor:pointer;transition:opacity .2s,transform .1s;position:relative;overflow:hidden;}
.alloc-btn::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.08),transparent);}
.alloc-btn:hover{opacity:.9;}.alloc-btn:active{transform:scale(.99);}.alloc-btn:disabled{opacity:.4;cursor:not-allowed;}
/* Result */
.result-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:28px;animation:fadeIn .25s ease;}
.result-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.result-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;}
.result-actions{display:flex;gap:8px;}
.action-btn{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;font-family:inherit;cursor:pointer;transition:all .15s;border:1px solid var(--border);background:var(--surface2);color:var(--text);}
.action-btn:hover{border-color:rgba(79,142,255,.4);color:var(--accent);}
.action-btn.green:hover{border-color:rgba(63,185,80,.4);color:var(--green);}
.numbers-box{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:16px;max-height:400px;overflow-y:auto;font-family:'DM Mono','Courier New',monospace;font-size:14px;line-height:1;}
.numbers-box::-webkit-scrollbar{width:4px;}
.numbers-box::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.num-row{display:flex;align-items:center;justify-content:space-between;padding:7px 4px;border-bottom:1px solid rgba(28,35,51,.8);color:var(--accent2);}
.num-row:last-child{border-bottom:none;}
.copy-num{background:none;border:none;color:var(--muted);cursor:pointer;font-size:12px;opacity:0;transition:opacity .15s;padding:2px 6px;border-radius:4px;}
.num-row:hover .copy-num{opacity:1;}
.num-row:hover .copy-num:hover{background:var(--surface2);color:var(--accent);}
/* Toast */
.toasts{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;}
.toast{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:12px 16px;font-size:13px;min-width:240px;display:flex;align-items:center;gap:10px;box-shadow:0 8px 32px rgba(0,0,0,.4);animation:toastIn .3s cubic-bezier(.22,1,.36,1);}
.toast.success{border-color:rgba(63,185,80,.3);}
.toast.error{border-color:rgba(248,81,73,.3);}
@keyframes toastIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
@media(max-width:768px){.layout{grid-template-columns:1fr;}.sidebar{position:static;height:300px;}.main{padding:16px;}.form-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-logo"><div class="dot"></div> AHS SMS Panel</div>
  <div class="topbar-right">
    <div class="user-chip">
      <div class="avatar"><?= $initial ?></div>
      <?= htmlspecialchars($user['username']) ?>
    </div>
    <a href="/logout.php" class="logout-btn">🚪 Logout</a>
  </div>
</div>

<div class="layout">
  <div class="sidebar">
    <div class="sidebar-head">
      <h2>📋 Available Ranges</h2>
      <div class="search-wrap">
        <span class="icon">🔍</span>
        <input class="search-input" id="searchInput" placeholder="Search range..." oninput="filterRanges()">
      </div>
    </div>
    <div class="range-list" id="rangeList">
      <div class="loading-ranges"><div class="spinner"></div> Loading ranges...</div>
    </div>
  </div>

  <div class="main" id="main">
    <div class="empty-state">
      <div class="empty-inner">
        <div class="icon">📦</div>
        <h3>Select a Range</h3>
        <p>Choose a number range from the left<br>to start allocating numbers to your account.</p>
      </div>
    </div>
  </div>
</div>

<div class="toasts" id="toasts"></div>

<script>
const PAYTERMS = [
  {id:"1",label:"Daily"},{id:"2",label:"Weekly"},{id:"3",label:"Weekly7"},
  {id:"4",label:"BiWeekly"},{id:"5",label:"BiWeekly30"},{id:"6",label:"Monthly15"},
  {id:"7",label:"Monthly30"},{id:"8",label:"Monthly60"},
];
const CC_MAP = {
  afghanistan:'93',algeria:'213',angola:'244',cambodia:'855',vietnam:'84',
  kenya:'254',malaysia:'60',nigeria:'234',ethiopia:'251',tanzania:'255',
  uganda:'256',zimbabwe:'263',zambia:'260',cameroon:'237',senegal:'221',
  sri:'94',lanka:'94',bangladesh:'880',pakistan:'92',india:'91',
  indonesia:'62',philippines:'63',thailand:'66',nepal:'977',myanmar:'95',
  russia:'7',turkey:'90',ukraine:'380',uae:'971',iraq:'964',
  jordan:'962',kuwait:'965',oman:'968',guinea:'224',mauritania:'222',morocco:'212',
  comoros:'269',laos:'856',
};

let allRanges=[], filteredRanges=[], selectedRange=null, selectedPayterm="2";
let allocatedNumbers=[], ccAdded=false, detectedCC='', qty=10;

// Load ranges
(async()=>{
  const r = await fetch('/api/ranges.php');
  const d = await r.json();
  allRanges = d.ranges||[];
  filteredRanges = [...allRanges];
  renderRanges();
})();

function filterRanges(){
  const q=document.getElementById('searchInput').value.toLowerCase();
  filteredRanges=q?allRanges.filter(r=>r.name.toLowerCase().includes(q)):[...allRanges];
  renderRanges();
}

function renderRanges(){
  const el=document.getElementById('rangeList');
  if(!filteredRanges.length){el.innerHTML='<div class="loading-ranges" style="color:var(--muted)">No ranges found</div>';return;}
  el.innerHTML=filteredRanges.map(r=>`
    <div class="range-item ${selectedRange?.id===r.id?'active':''}" onclick='selectRange(${JSON.stringify(r)})' id="ri-${r.id}">
      <span class="range-name">📦 ${r.name}</span>
      <span class="range-payout">$${r.payout}</span>
    </div>`).join('');
}

function selectRange(range){
  selectedRange=range; allocatedNumbers=[]; ccAdded=false;
  detectedCC=detectCC(range.name);
  document.querySelectorAll('.range-item').forEach(e=>e.classList.remove('active'));
  const item=document.getElementById(`ri-${range.id}`);
  if(item)item.classList.add('active');
  renderAllocatePanel();
  fetchRangeCount(range.id);
}

/* ─── Range availability count (with 30s cache) ───────────────────────── */
const rangeCountCache=new Map(); // rangeId -> {data, ts}
const COUNT_CACHE_MS=30000;

async function fetchRangeCount(rangeId){
  const badge=document.getElementById('rangeCountBadge');
  if(!badge) return;

  // Use cache if fresh
  const cached=rangeCountCache.get(rangeId);
  if(cached && (Date.now()-cached.ts) < COUNT_CACHE_MS){
    renderRangeCount(cached.data);
    return;
  }

  try{
    const r=await fetch(`/api/range_count.php?range_id=${rangeId}`);
    const d=await r.json();
    if(!d.success){
      badge.className='count-badge error';
      badge.innerHTML='<span class="count-icon">⚠️</span><span>Could not check</span>';
      return;
    }
    rangeCountCache.set(rangeId, {data:d, ts:Date.now()});
    // Only render if user is still on the same range
    if(selectedRange && selectedRange.id===rangeId) renderRangeCount(d);
  }catch(e){
    badge.className='count-badge error';
    badge.innerHTML='<span class="count-icon">⚠️</span><span>Error</span>';
  }
}

function renderRangeCount(d){
  const badge=document.getElementById('rangeCountBadge');
  if(!badge) return;
  const avail=d.available||0;
  const total=d.total||0;
  const cls = avail>50 ? 'good' : (avail>10 ? 'warn' : 'low');
  badge.className=`count-badge ${cls}`;
  badge.innerHTML=`
    <span class="count-icon">${avail>0?'✅':'❌'}</span>
    <span class="count-main"><b>${avail}</b> available</span>
    
  `;
}

function detectCC(name){
  const lower=name.toLowerCase();
  const sorted=Object.entries(CC_MAP).sort((a,b)=>b[0].length-a[0].length);
  for(const[k,v] of sorted) if(lower.includes(k)) return v;
  return '';
}

function clearSelection(){
  selectedRange=null; allocatedNumbers=[];
  document.querySelectorAll('.range-item').forEach(e=>e.classList.remove('active'));
  document.getElementById('main').innerHTML=`
    <div class="empty-state"><div class="empty-inner">
      <div class="icon">📦</div><h3>Select a Range</h3>
      <p>Choose a number range from the left<br>to start allocating numbers to your account.</p>
    </div></div>`;
}

function renderAllocatePanel(){
  const paytermHtml=PAYTERMS.map(p=>`<button class="pt-pill ${p.id===selectedPayterm?'active':''}" onclick="selectPayterm('${p.id}',this)">${p.label}</button>`).join('');
  document.getElementById('main').innerHTML=`
    <div class="alloc-card">
      <div class="alloc-header">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;flex:1;min-width:0;">
          <div style="min-width:0;">
            <div class="alloc-title">📦 ${selectedRange.name}</div>
            <div class="alloc-sub">Payout · $${selectedRange.payout}/number</div>
          </div>
          <div id="rangeCountBadge" class="count-badge">
            <div class="spinner-tiny"></div>
            <span>Checking availability...</span>
          </div>
        </div>
        <button class="close-btn" onclick="clearSelection()">×</button>
      </div>
      <div class="form-grid">
        <div>
          <span class="field-label">Quantity</span>
          <div class="qty-wrap">
            <button class="qty-btn" onclick="changeQty(-1)">−</button>
            <input class="qty-input" type="number" id="qtyInput" value="10" min="1" max="30" onchange="qty=Math.max(1,Math.min(30,parseInt(this.value)||1));this.value=qty;">
            <button class="qty-btn" onclick="changeQty(1)">+</button>
          </div>
        </div>
        <div>
          <span class="field-label">Your Payout</span>
          <div class="payout-display">
            <span class="payout-val">$${selectedRange.payout}</span>
            <span class="payout-note">per number</span>
          </div>
        </div>
      </div>
      <div style="margin-top:16px;">
        <span class="field-label">Payterm</span>
        <div class="payterm-grid">${paytermHtml}</div>
      </div>
      <button class="alloc-btn" onclick="allocate()" id="allocBtn">🚀 Allocate ${qty} Numbers</button>
    </div>
    <div id="resultSection"></div>`;
  qty=10;
}

function changeQty(d){
  qty=Math.max(1,Math.min(30,qty+d));
  document.getElementById('qtyInput').value=qty;
  document.getElementById('allocBtn').textContent=`🚀 Allocate ${qty} Numbers`;
}

function selectPayterm(id,btn){
  selectedPayterm=id;
  document.querySelectorAll('.pt-pill').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
}

async function allocate(){
  const btn=document.getElementById('allocBtn');
  btn.disabled=true; btn.textContent='⏳ Allocating...';
  qty=Math.max(1,Math.min(30,parseInt(document.getElementById('qtyInput').value)||10));

  try{
    const fd=new FormData();
    fd.append('range_id',selectedRange.id);
    fd.append('payterm',selectedPayterm);
    fd.append('payout',selectedRange.payout);
    fd.append('qty',qty);

    const r=await fetch('/api/allocate.php',{method:'POST',body:fd});
    const d=await r.json();

    if(d.success){
      showToast('✅ Numbers allocated successfully!','success');
      btn.textContent='⏳ Fetching numbers...';
      // Backend will sleep + retry up to 4 times to get fresh numbers
      const nr=await fetch(`/api/numbers.php?range_id=${selectedRange.id}&qty=${qty}&just_allocated=1`);
      const nd=await nr.json();
      allocatedNumbers=nd.numbers||[];
      ccAdded=false;
      if(nd.partial){
        showToast(`⚠️ Got ${allocatedNumbers.length}/${qty} numbers. Try Refresh to fetch the rest.`,'error');
      }
      renderResult();
      // Invalidate count cache and refresh badge to reflect new availability
      rangeCountCache.delete(selectedRange.id);
      fetchRangeCount(selectedRange.id);
    } else {
      showToast(d.error||'Allocation failed','error');
    }
  } catch(e){showToast('Error: '+e.message,'error');}

  if(btn){btn.disabled=false;btn.textContent=`🚀 Allocate ${qty} Numbers`;}
}

function renderResult(){
  const el=document.getElementById('resultSection');
  if(!el)return;
  const ccBtn=detectedCC?`<button class="action-btn" onclick="toggleCC()">${ccAdded?'➖ Remove CC':'➕ +'+detectedCC}</button>`:'';
  const numsHtml=allocatedNumbers.map(n=>`
    <div class="num-row">
      <span>${n}</span>
      <button class="copy-num" onclick="copyNum('${n}',this)">📋</button>
    </div>`).join('');
  el.innerHTML=`
    <div class="result-card">
      <div class="result-header">
        <div class="result-title">📱 Allocated Numbers (${allocatedNumbers.length})</div>
        <div class="result-actions">
          ${ccBtn}
          <button class="action-btn green" onclick="copyAll()">📋 Copy All</button>
        </div>
      </div>
      <div class="numbers-box" id="numbersBox">
        ${numsHtml||'<div style="color:var(--muted);text-align:center;padding:20px;">No numbers found</div>'}
      </div>
    </div>`;
}

function toggleCC(){
  if(ccAdded){
    allocatedNumbers=allocatedNumbers.map(n=>{n=n.replace(/^\+/,'');if(detectedCC&&n.startsWith(detectedCC))n=n.slice(detectedCC.length);return n;});
    ccAdded=false;
  } else {
    allocatedNumbers=allocatedNumbers.map(n=>{n=n.replace(/^\+/,'');if(n.startsWith(detectedCC))n=n.slice(detectedCC.length);return `+${detectedCC}${n}`;});
    ccAdded=true;
  }
  renderResult();
}

function copyNum(num,btn){navigator.clipboard.writeText(num);btn.textContent='✓';setTimeout(()=>btn.textContent='📋',1500);}
function copyAll(){navigator.clipboard.writeText(allocatedNumbers.join('\n'));showToast('✅ Copied to clipboard!','success');}

function showToast(msg,type='success'){
  const t=document.createElement('div');
  t.className=`toast ${type}`;
  t.innerHTML=`<span>${type==='success'?'✅':'❌'}</span>${msg}`;
  document.getElementById('toasts').appendChild(t);
  setTimeout(()=>t.style.opacity='0',3500);
  setTimeout(()=>t.remove(),4000);
}
</script>
</body>
</html>