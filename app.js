
const apiBase = location.pathname.includes('/pages/') ? '../api/' : 'api/';
const menu=document.getElementById("mobileMenu");
const nav=document.getElementById("topNav");
if(menu&&nav)menu.addEventListener("click",()=>nav.classList.toggle("open"));

const overlay=document.getElementById("statusOverlay");
const drawer=document.getElementById("statusDrawer");
const close=document.querySelector("[data-status-close]");
function openStatus(){if(drawer&&overlay){drawer.classList.add("open");overlay.classList.add("open");document.body.style.overflow="hidden";}}
function closeStatus(){if(drawer&&overlay){drawer.classList.remove("open");overlay.classList.remove("open");document.body.style.overflow="";}}
document.querySelectorAll("[data-status-open]").forEach(el=>el.addEventListener("click",e=>{e.preventDefault();openStatus();}));
if(close)close.addEventListener("click",closeStatus);
if(overlay)overlay.addEventListener("click",closeStatus);
document.addEventListener("keydown",e=>{if(e.key==="Escape")closeStatus();});

async function updateDrawerStatus(){
  try{
    const r=await fetch(apiBase+'server-status.php?code=m4zzggv',{cache:'no-store'});
    if(!r.ok) throw new Error('status');
    const d=await r.json();
    const redm=document.getElementById('redmStatusCard');
    const cfx=document.getElementById('cfxStatusCard');
    const rt=document.getElementById('redmStatusText');
    const ct=document.getElementById('cfxStatusText');
    const badge=document.getElementById('classicBadge');
    const sv=document.getElementById('classicServerValue');
    const pv=document.getElementById('classicPlayersValue');
    const lu=document.getElementById('statusLastUpdate');
    if(redm){redm.classList.toggle('online',!!d.online);redm.classList.toggle('offline',!d.online);redm.classList.remove('checking');}
    if(rt)rt.textContent=d.online?'ONLINE':'OFFLINE';
    if(cfx){cfx.classList.toggle('online',!!d.cfxAccessible);cfx.classList.toggle('offline',!d.cfxAccessible);cfx.classList.remove('checking');}
    if(ct)ct.textContent=d.cfxAccessible?'ACESSÍVEL':'INDISPONÍVEL';
    if(badge){badge.className='status-badge '+(d.online?'online':'offline');badge.textContent=d.online?'Online':'Offline';}
    if(sv){sv.className='status-value '+(d.online?'online':'offline');sv.innerHTML='<i></i>'+(d.online?'Online':'Offline');}
    if(pv){pv.className='status-value '+(d.online?'online':'offline');pv.innerHTML='<i></i>'+((d.players??'--')+' online');}
    if(lu)lu.textContent=new Date().toLocaleTimeString('pt-BR');
  }catch(e){
    const rt=document.getElementById('redmStatusText'),ct=document.getElementById('cfxStatusText');
    if(rt)rt.textContent='INDISPONÍVEL';
    if(ct)ct.textContent='INDISPONÍVEL';
  }
}
updateDrawerStatus();
setInterval(updateDrawerStatus,60000);
