
import * as THREE from 'three';
import { createClient } from '@supabase/supabase-js';

const PUBLIC_CONFIG = await fetch('/api/config', { headers:{ Accept:'application/json' } })
  .then(r=>r.ok?r.json():{})
  .catch(()=>({}));
const supabase = PUBLIC_CONFIG.supabaseUrl && PUBLIC_CONFIG.supabaseAnonKey
  ? createClient(PUBLIC_CONFIG.supabaseUrl, PUBLIC_CONFIG.supabaseAnonKey, {
      auth:{ persistSession:true, autoRefreshToken:true, detectSessionInUrl:true }
    })
  : null;
const API='/api/tmdb', IMG='https://image.tmdb.org/t/p/';
const $=s=>document.querySelector(s);
if(window.lucide)window.lucide.createIcons();
let systemReduce=matchMedia('(prefers-reduced-motion: reduce)').matches, reduce=systemReduce;
const esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const yr=m=>(m.release_date||m.first_air_date||'').slice(0,4), rate=m=>m.vote_average?m.vote_average.toFixed(1):'–';
const mediaTitle=m=>m.title||m.name||'Untitled';
const mediaType=m=>m?.media_type||S.mediaType||'movie';
const SETTINGS_DEFAULTS={
  profileName:'Guest',profileNote:'',favorites:[],theme:'dark',accent:'#f5a524',sort:'popularity.desc',mediaType:'movie',
  font:'geist',density:'comfortable',rows:3,posterSize:1,sensitivity:1,
  drift:true,driftSpeed:1,showMini:true,showChips:true,showHint:true,
  motion:'system',defaultView:'wall'
};
const S={q:'',genre:'',y1:'',y2:'',rt:0,sort:'popularity.desc',view:'wall',...SETTINGS_DEFAULTS};
try{
  const saved=JSON.parse(localStorage.getItem('mw-settings')||'{}');
  Object.keys(SETTINGS_DEFAULTS).forEach(k=>{if(saved[k]!==undefined)S[k]=saved[k]});
}catch{}
S.favorites=Array.isArray(S.favorites)?S.favorites.map(Number).filter(Number.isFinite):[];
S.rows=Math.max(2,Math.min(5,Number(S.rows)||3));
S.posterSize=Math.max(.75,Math.min(1.25,Number(S.posterSize)||1));
S.sensitivity=Math.max(.5,Math.min(2,Number(S.sensitivity)||1));
S.driftSpeed=Math.max(.25,Math.min(2,Number(S.driftSpeed)||1));
if(!['dark','light','system'].includes(S.theme))S.theme='dark';
if(!['geist','inter','system'].includes(S.font))S.font='geist';
if(!['comfortable','compact'].includes(S.density))S.density='comfortable';
if(!['system','full','reduced'].includes(S.motion))S.motion='system';
if(!['wall','list'].includes(S.defaultView))S.defaultView='wall';
if(!['movie','tv'].includes(S.mediaType))S.mediaType='movie';
S.view=S.defaultView;
const emptyLibrary=()=>({list:[],hist:[],prog:{}});
const progressKey=m=>`${m.media_type||'movie'}:${m.id}:${m.media_type==='tv'?(m.season_number??1):0}:${m.media_type==='tv'?(m.episode_number??1):0}`;
const libraryKey=m=>`${m.media_type||'movie'}:${m.id}`;
const copyLibrary=value=>{
  const list=(Array.isArray(value?.list)?value.list:[]).map(m=>({...m,media_type:m.media_type||'movie'}));
  const hist=(Array.isArray(value?.hist)?value.hist:[]).map(m=>({...m,media_type:m.media_type||'movie'}));
  const prog={};
  if(value?.prog&&typeof value.prog==='object')Object.values(value.prog).forEach(p=>{const item={...p,media_type:p.media_type||'movie'};prog[progressKey(item)]=item});
  return{list,hist,prog};
};
const PROFILE_STORE_KEY='mw-profiles';
let cloudUser=null,cloudReady=false,cloudHydrating=false,cloudHydratedUserId=null,cloudSaveTimer=0;
function cloudAccountRecord(user){
  const meta=user?.user_metadata||{};
  return {
    id:user?.id||null,
    email:user?.email||null,
    display_name:meta.full_name||meta.name||meta.user_name||user?.email||'Reel user',
    avatar_url:meta.avatar_url||meta.picture||null,
    provider:user?.app_metadata?.provider||'oauth'
  };
}
function queueCloudSave(){
  if(!supabase||!cloudUser||!cloudReady||cloudHydrating)return;
  clearTimeout(cloudSaveTimer);
  cloudSaveTimer=setTimeout(()=>{void pushCloudState()},900);
}
async function pushCloudState(){
  if(!supabase||!cloudUser||!cloudReady||cloudHydrating)return;
  let settings={};
  try{settings=JSON.parse(localStorage.getItem('mw-settings')||'{}')}catch{}
  const state={profiles,activeProfileId,settings};
  const {error}=await supabase.from('reel_account_state').upsert({
    user_id:cloudUser.id,
    account:cloudAccountRecord(cloudUser),
    state,
    updated_at:new Date().toISOString()
  },{onConflict:'user_id'});
  if(error){
    console.error('Reel cloud sync failed:',error.message);
    syncAuthScreen('Signed in, but cloud sync failed. Check the Supabase table and policies.');
    return false;
  }
  syncAuthScreen();
  return true;
}
const AVATARS=[
  {bg:'#344b60',skin:'#e7b58e',hair:'#352d35',shirt:'#8eacc4'},
  {bg:'#69514d',skin:'#f1c7a5',hair:'#3a2927',shirt:'#d18b77'},
  {bg:'#4d5a4b',skin:'#d59a75',hair:'#242b29',shirt:'#9aaf84'},
  {bg:'#5c5070',skin:'#f0c8a8',hair:'#4a3247',shirt:'#b49ac9'},
  {bg:'#625d42',skin:'#d9a27f',hair:'#302a25',shirt:'#c6b778'},
  {bg:'#395d5a',skin:'#e9bc98',hair:'#2f2527',shirt:'#79a8a1'},
  {bg:'#694c63',skin:'#b97958',hair:'#241d24',shirt:'#c08cab'},
  {bg:'#4f5668',skin:'#edc8aa',hair:'#373945',shirt:'#9aa9c7'}
];
const avatarMarkup=index=>{
  const a=AVATARS[Math.abs(Number(index)||0)%AVATARS.length];
  const hair=index%3===0?'M22 39c0-19 12-29 27-29s27 10 27 29v9H22z':
    index%3===1?'M21 42c0-20 12-32 28-32 17 0 29 12 29 32l-7 8H28z':
    'M20 40c1-20 13-30 29-30s28 10 29 30l-9 5H28z';
  return `<svg viewBox="0 0 100 100" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" rx="18" fill="${a.bg}"/><circle cx="50" cy="50" r="37" fill="rgba(255,255,255,.06)"/><path d="M13 102c3-22 17-32 37-32s34 10 37 32" fill="${a.shirt}"/><path d="${hair}" fill="${a.hair}"/><ellipse cx="50" cy="48" rx="22" ry="27" fill="${a.skin}"/><path d="${hair}" fill="${a.hair}"/><path d="M39 51h2m18 0h2" stroke="#30241f" stroke-width="2.5" stroke-linecap="round"/><path d="M44 63q6 5 12 0" fill="none" stroke="#8f5143" stroke-width="2" stroke-linecap="round"/><path d="M28 43q4-9 12-11m21 0q8 2 12 11" stroke="${a.hair}" stroke-width="4" fill="none" stroke-linecap="round"/></svg>`;
};
let profiles=[],activeProfileId='guest',activeProfile=null;
const persistProfiles=()=>{
  try{localStorage.setItem(PROFILE_STORE_KEY,JSON.stringify(profiles))}catch{}
  queueCloudSave();
};
const normalizeProfile=(p,i)=>({
  id:String(p?.id||('profile-'+i)),
  name:String(p?.name||'Guest').trim().slice(0,24)||'Guest',
  avatar:Math.abs(Number(p?.avatar)||0)%AVATARS.length,
  note:String(p?.note||'').slice(0,42),
  favorites:Array.isArray(p?.favorites)?p.favorites.map(Number).filter(Number.isFinite):[],
  lib:copyLibrary(p?.lib||p?.library)
});
try{
  const stored=JSON.parse(localStorage.getItem(PROFILE_STORE_KEY)||'null');
  if(Array.isArray(stored))profiles=stored.slice(0,8).map(normalizeProfile);
}catch{}
if(!profiles.length){
  let oldLib=emptyLibrary(),oldSettings={};
  try{oldLib=copyLibrary(JSON.parse(localStorage.getItem('mw-lib')||'{}'))}catch{}
  try{oldSettings=JSON.parse(localStorage.getItem('mw-settings')||'{}')}catch{}
  profiles=[{id:'guest',name:'Guest',avatar:0,note:'',favorites:Array.isArray(oldSettings.favorites)?oldSettings.favorites:[],lib:oldLib}];
  persistProfiles();
}
try{activeProfileId=localStorage.getItem('mw-active-profile')||profiles[0].id}catch{activeProfileId=profiles[0].id}
activeProfile=profiles.find(p=>p.id===activeProfileId)||profiles[0];
activeProfileId=activeProfile.id;
S.profileName=activeProfile.name;S.profileNote=activeProfile.note;S.favorites=[...activeProfile.favorites];
const saveActiveProfileMetadata=()=>{
  if(!activeProfile)return;
  activeProfile.name=(S.profileName||'Guest').trim().slice(0,24)||'Guest';
  activeProfile.note=(S.profileNote||'').slice(0,42);
  activeProfile.favorites=[...new Set((S.favorites||[]).map(Number).filter(Number.isFinite))];
  persistProfiles();
};
let genres=[],movies=[],reqId=0;
const savePrefs=()=>{
  const data={};Object.keys(SETTINGS_DEFAULTS).filter(k=>!['profileName','profileNote','favorites'].includes(k)).forEach(k=>data[k]=S[k]);
  try{localStorage.setItem('mw-settings',JSON.stringify(data))}catch{}
  saveActiveProfileMetadata();
};
const SORTS=[['personalized','For you'],['popularity.desc','Popular'],['vote_average.desc','Top rated'],['primary_release_date.desc','Newest']];
try{S.sort=localStorage.getItem('mw-sort')||S.sort}catch{}

/* ---------- Appearance ---------- */
const PRE=['#f5a524','#ff6b5b','#7cc4ff','#a78bfa','#4ade80','#f472b6','#22d3ee','#facc15','#c084fc'];
function setAccent(hex,save=true){ if(!/^#[0-9a-f]{6}$/i.test(hex))return;
  const r=document.documentElement.style,[R,G,B]=[1,3,5].map(i=>parseInt(hex.slice(i,i+2),16)),L=(.2126*R+.7152*G+.0722*B)/255;
  r.setProperty('--accent',hex);r.setProperty('--accent-fg',L>.55?'#0a0a0a':'#fafafa');$('#hex').value=hex;S.accent=hex;
  document.querySelectorAll('.sw').forEach(b=>b.setAttribute('aria-pressed',b.dataset.c===hex.toLowerCase()));
  if(save){try{localStorage.setItem('mw-accent',hex)}catch{}savePrefs()} }
function setTheme(t,save=true){
  S.theme=t;
  const actual=t==='system'?(matchMedia('(prefers-color-scheme: light)').matches?'light':'dark'):t;
  document.documentElement.dataset.theme=actual;
  document.querySelectorAll('[data-theme-set]').forEach(b=>b.setAttribute('aria-pressed',b.dataset.themeSet===t));
  if(save){try{localStorage.setItem('mw-theme',t)}catch{}savePrefs()}
}
function setFont(v,save=true){S.font=v;document.documentElement.dataset.font=v;document.querySelectorAll('[data-font-set]').forEach(b=>b.setAttribute('aria-pressed',b.dataset.fontSet===v));if(save)savePrefs()}
function setDensity(v,save=true){S.density=v;document.documentElement.dataset.density=v;document.querySelectorAll('[data-density-set]').forEach(b=>b.setAttribute('aria-pressed',b.dataset.densitySet===v));if(save)savePrefs()}
function applyMotion(){
  reduce=S.motion==='reduced'||(S.motion==='system'&&systemReduce);
  document.documentElement.classList.toggle('reduce-motion',reduce);
  document.querySelectorAll('[data-motion-set]').forEach(b=>b.setAttribute('aria-pressed',b.dataset.motionSet===S.motion));
}
function updateProfile(){
  const name=S.profileName.trim()||'Guest';
  $('#brand-user').textContent='for '+name;
  $('#brand-note').textContent=S.profileNote.trim();
  $('#nav-profile-name').textContent=name;
  $('#nav-avatar').innerHTML=avatarMarkup(activeProfile?.avatar||0);
}
function renderFavoriteGenres(){
  const host=$('#fav-genres');if(!host)return;
  host.innerHTML=genres.length?genres.map(g=>`<button class="favorite-chip" data-favorite="${g.id}" aria-pressed="${S.favorites.includes(g.id)}">${esc(g.name)}</button>`).join(''):'<span class="empty">Genres are unavailable right now.</span>';
}
$('#sws').innerHTML=PRE.map(c=>`<button class="sw" data-c="${c}" style="background:${c}" aria-label="Use accent ${c}" aria-pressed="false"></button>`).join('');
$('#sws').onclick=e=>{const b=e.target.closest('.sw');if(b)setAccent(b.dataset.c)};
$('#hex').onchange=e=>{let v=e.target.value.trim();if(v[0]!=='#')v='#'+v;setAccent(v)};
$('#rst').onclick=()=>{setAccent(PRE[0]);try{localStorage.removeItem('mw-accent')}catch{}};
document.querySelectorAll('[data-theme-set]').forEach(b=>b.onclick=()=>setTheme(b.dataset.themeSet));
let sa=S.accent||PRE[0],st=S.theme||'dark';
try{sa=localStorage.getItem('mw-accent')||sa;st=localStorage.getItem('mw-theme')||st}catch{}
S.theme=st;setAccent(sa,false);setTheme(st,false);setFont(S.font,false);setDensity(S.density,false);applyMotion();
matchMedia('(prefers-color-scheme: light)').addEventListener?.('change',()=>{if(S.theme==='system')setTheme('system',false)});
matchMedia('(prefers-reduced-motion: reduce)').addEventListener?.('change',e=>{systemReduce=e.matches;if(S.motion==='system')applyMotion()});

/* ---------- Library: watchlist, history, progress ---------- */
let lib=copyLibrary(activeProfile.lib),cur=null;
const saveLib=()=>{if(!activeProfile)return;activeProfile.lib=copyLibrary(lib);persistProfiles();};
const snap=m=>({id:m.id,title:mediaTitle(m),name:m.name,media_type:mediaType(m),poster_path:m.poster_path,backdrop_path:m.backdrop_path,release_date:m.release_date,first_air_date:m.first_air_date,vote_average:m.vote_average,season_number:m.season_number,episode_number:m.episode_number});
const inList=m=>{const key=typeof m==='object'?libraryKey(m):`${S.mediaType}:${m}`;return lib.list.some(x=>libraryKey(x)===key)},chipTxt=m=>rate(m)+(inList(m)?' · Saved':'');
const meta=(id,type=S.mediaType,season,episode)=>{
  if(type==='tv'&&season!==''&&episode!==''){
    const exact=lib.prog[progressKey({id,media_type:type,season_number:+season,episode_number:+episode})];
    if(exact)return exact;
  }
  return lib.list.find(x=>libraryKey(x)===`${type}:${id}`)||lib.hist.find(x=>libraryKey(x)===`${type}:${id}`)||Object.values(lib.prog).find(x=>x.id==id&&mediaType(x)===type);
};
const fmt=s=>{s=Math.round(s||0);const h=Math.floor(s/3600),m=Math.floor(s%3600/60);return h?`${h}h ${m}m`:`${m}m`};
const when=t=>new Date(t).toLocaleDateString(undefined,{month:'short',day:'numeric'});
function syncWl(){const b=$('#wl'),on=cur&&inList(cur);b.setAttribute('aria-pressed',!!on);b.textContent=on?'On watchlist':'Add to watchlist'}
function toggleList(m){lib.list=inList(m)?lib.list.filter(x=>libraryKey(x)!==libraryKey(m)):[snap(m),...lib.list];saveLib();syncWl();renderLib();wall.refresh()}
$('#wl').onclick=()=>cur&&toggleList(cur);
const act=(a,m,t)=>`<button class="lnk" data-act="${a}" data-id="${m.id}" data-type="${mediaType(m)}" data-season="${m.season_number??''}" data-episode="${m.episode_number??''}">${t}</button>`;
const lrow=(m,sub,acts,bar='')=>`<div class="lr"><img alt="" loading="lazy" src="${m.poster_path?IMG+'w92'+m.poster_path:''}"><div class="lt"><b>${esc(m.title)}</b><span>${sub}</span>${bar}</div><div class="la">${acts}</div></div>`;
function renderLib(){
  const empty=t=>`<p class="empty">${t}</p>`;
  $('#c-list').textContent=lib.list.length||'';$('#c-hist').textContent=lib.hist.length||'';
  $('#p-list').innerHTML=lib.list.length?lib.list.map(m=>lrow(m,`${mediaType(m)==='tv'?'TV show':'Movie'} · ${yr(m)} · ${rate(m)}`,act('play',m,'Play')+act('open',m,'Details')+act('rmlist',m,'Remove'))).join(''):empty('Nothing saved yet. Open a title and choose Add to watchlist.');
  $('#p-hist').innerHTML=lib.hist.length?lib.hist.map(m=>lrow(m,`${mediaType(m)==='tv'?'TV show':'Movie'} · ${yr(m)} · Watched ${when(m.at)}`,act('play',m,'Play again')+act('open',m,'Details')+act('rmhist',m,'Remove'))).join(''):empty('Titles you play will show up here.');
  const pr=Object.values(lib.prog).sort((a,b)=>b.at-a.at),done=pr.filter(p=>p.done).length,secs=pr.reduce((n,p)=>n+(p.done?p.d:p.t),0);
  $('#p-stats').innerHTML=[[pr.length,'Episodes / titles'],[done,'Finished'],[fmt(secs),'Time watched']].map(([v,l])=>`<div class="stat"><b>${v}</b><span>${l}</span></div>`).join('');
  $('#p-prog').innerHTML=pr.length?pr.map(p=>{const pct=Math.min(100,Math.round(p.t/Math.max(1,p.d)*100)),ep=mediaType(p)==='tv'?` · S${p.season_number} E${p.episode_number}`:'';
    return lrow(p,`${mediaType(p)==='tv'?'TV show':'Movie'}${ep} · ${p.done?'Finished':`${fmt(p.t)} of ${fmt(p.d)} · ${pct}%`}`,act('play',p,p.done?'Play again':'Resume')+act('open',p,'Details')+act('rmprog',p,'Remove'),`<div class="pb"><i style="width:${p.done?100:pct}%"></i></div>`)}).join(''):empty('Progress is tracked as you watch. Start something to see it here.')}
const stg=$('#settings');
document.querySelectorAll('.tab').forEach(t=>t.onclick=()=>{document.querySelectorAll('.tab').forEach(x=>x.setAttribute('aria-selected',x===t));
  document.querySelectorAll('[data-pane]').forEach(p=>p.hidden=p.dataset.pane!==t.dataset.tab);renderLib()});
document.querySelector('[data-motion-open="#settings"]').addEventListener('click',renderLib);
stg.addEventListener('click',e=>{const b=e.target.closest('[data-act]');if(!b)return;const id=+b.dataset.id,type=b.dataset.type||'movie',a=b.dataset.act,season=b.dataset.season,episode=b.dataset.episode,m=meta(id,type,season,episode);
  if(a==='rmlist'){lib.list=lib.list.filter(x=>libraryKey(x)!==`${type}:${id}`);wall.refresh();syncWl()}
  else if(a==='rmhist')lib.hist=lib.hist.filter(x=>libraryKey(x)!==`${type}:${id}`);
  else if(a==='rmprog'){if(type==='tv'&&season!==''&&episode!=='')delete lib.prog[progressKey({id,media_type:type,season_number:+season,episode_number:+episode})];else Object.keys(lib.prog).forEach(k=>{if(lib.prog[k].id==id&&mediaType(lib.prog[k])===type)delete lib.prog[k]})}
  else if(m){Motion.setOpen(stg,false);if(a==='open')openDetail(id,null,m,type);else startPlay(m);return}
  saveLib();renderLib()});
$('#clr-h').onclick=()=>{lib.hist=[];saveLib();renderLib()};
$('#exp').onclick=()=>{
  const prefs={};Object.keys(SETTINGS_DEFAULTS).forEach(k=>prefs[k]=S[k]);
  saveActiveProfileMetadata();
  const backup={version:3,exportedAt:new Date().toISOString(),profiles,activeProfileId,settings:Object.fromEntries(Object.entries(prefs).filter(([k])=>!['profileName','profileNote','favorites'].includes(k)))};
  const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([JSON.stringify(backup,null,2)],{type:'application/json'}));
  a.download='reel-backup.json';a.click();setTimeout(()=>URL.revokeObjectURL(a.href),1000);
};
$('#imp-btn').onclick=()=>$('#imp').click();
$('#imp').onchange=async e=>{
  const file=e.target.files?.[0];if(!file)return;
  try{
    const data=JSON.parse(await file.text());
    if(Array.isArray(data.profiles)&&data.profiles.length){
      profiles=data.profiles.slice(0,8).map(normalizeProfile);
      activeProfile=profiles.find(p=>p.id===data.activeProfileId)||profiles[0];
      activeProfileId=activeProfile.id;
      lib=copyLibrary(activeProfile.lib);
      try{localStorage.setItem('mw-active-profile',activeProfileId)}catch{}
      persistProfiles();
      S.profileName=activeProfile.name;S.profileNote=activeProfile.note;S.favorites=[...activeProfile.favorites];
    }else{
      const incoming=data.library||data;
      if(Array.isArray(incoming.list))lib.list=incoming.list;
      if(Array.isArray(incoming.hist))lib.hist=incoming.hist;
      if(incoming.prog&&typeof incoming.prog==='object')lib.prog=incoming.prog;
      if(data.settings?.favorites)S.favorites=data.settings.favorites;
      activeProfile.lib=copyLibrary(lib);activeProfile.favorites=[...S.favorites];persistProfiles();
    }
    if(data.settings&&typeof data.settings==='object'){
      Object.keys(SETTINGS_DEFAULTS).filter(k=>!['profileName','profileNote','favorites'].includes(k)).forEach(k=>{if(data.settings[k]!==undefined)S[k]=data.settings[k]});
      S.rows=Math.max(2,Math.min(5,Number(S.rows)||3));
      S.posterSize=Math.max(.75,Math.min(1.25,Number(S.posterSize)||1));
      S.sensitivity=Math.max(.5,Math.min(2,Number(S.sensitivity)||1));
      S.driftSpeed=Math.max(.25,Math.min(2,Number(S.driftSpeed)||1));
      S.view=S.defaultView;
      setAccent(S.accent||PRE[0]);setTheme(S.theme);setFont(S.font);setDensity(S.density);applyMotion();
      updateProfile();renderFavoriteGenres();wall.configure();syncPreferenceControls();syncView();
    }
    saveLib();savePrefs();renderLib();syncWl();renderProfileChooser();syncMediaTypeControls();fetchGenres();load();
  }catch{$('#p-prog').innerHTML='<p class="empty">This backup could not be read. Check that it is a valid Reel JSON backup.</p>'}
  e.target.value='';
};
let wipeT;function disarm(){const b=$('#wipe');delete b.dataset.arm;b.textContent='Clear library';clearTimeout(wipeT)}
$('#wipe').onclick=e=>{const b=e.currentTarget;if(b.dataset.arm){lib={list:[],hist:[],prog:{}};saveLib();renderLib();wall.refresh();syncWl();disarm()}else{b.dataset.arm=1;b.textContent='Click again to confirm';wipeT=setTimeout(disarm,4000)}};
const drift=$('#drift');function syncDrift(){drift.setAttribute('aria-pressed',S.drift);drift.textContent=S.drift?'Auto-drift on':'Auto-drift off'}
try{if(localStorage.getItem('mw-drift')==='0')S.drift=false}catch{}syncDrift();
drift.onclick=()=>{S.drift=!S.drift;try{localStorage.setItem('mw-drift',S.drift?'1':'0')}catch{}syncDrift();savePrefs()};

/* ---------- Data ---------- */
async function api(path,params={}){
  const u=new URL(API,location.origin);u.searchParams.set('path',path);
  for(const[k,v]of Object.entries(params))if(v!==''&&v!=null)u.searchParams.set(k,v);
  const r=await fetch(u,{headers:{Accept:'application/json'}});
  if(!r.ok)throw new Error(r.status);
  return r.json()
}
async function load(){
  const id=++reqId;setStatus('loading');
  try{
    const pages=S.q?[1,2,3]:[1,2,3,4,5];let ok=0,all=[];
    const type=S.mediaType,endpoint=type==='tv'?'tv':'movie',newest=type==='tv'?'first_air_date.desc':'primary_release_date.desc',dateField=type==='tv'?'first_air_date':'primary_release_date';
    const rs=await Promise.all(pages.map(p=>(S.q?api('/search/'+endpoint,{query:S.q,page:p,include_adult:false}):
      api('/discover/'+endpoint,{page:p,include_adult:false,sort_by:S.sort==='personalized'?'popularity.desc':S.sort==='primary_release_date.desc'?newest:S.sort,with_genres:S.genre,
        [dateField+'.gte']:S.y1?S.y1+'-01-01':'',[dateField+'.lte']:S.y2?S.y2+'-12-31':(S.sort.startsWith('primary')?new Date().toISOString().slice(0,10):''),
        'vote_average.gte':S.rt||'','vote_count.gte':S.sort.startsWith('vote')?300:S.sort.startsWith('primary')?15:50}))
      .then(r=>{ok++;return r.results||[]}).catch(()=>[])));
    if(id!==reqId)return; if(!ok||(!rs.some(r=>r.length)&&ok<pages.length&&false))throw 0;
    const seen=new Set();all=rs.flat().filter(m=>m.poster_path&&!seen.has(m.id)&&seen.add(m.id));
    if(S.q){ all=all.filter(m=>(!S.genre||m.genre_ids?.includes(+S.genre))&&(!S.y1||+yr(m)>=+S.y1)&&(!S.y2||+yr(m)<=+S.y2)&&m.vote_average>=S.rt);
      const k=S.sort.startsWith('vote')?m=>m.vote_count>20?m.vote_average:0:S.sort.startsWith('primary')?m=>m.release_date||m.first_air_date||'':null;
      if(k)all.sort((a,b)=>k(a)<k(b)?1:-1); }
    all=all.map(m=>({...m,media_type:type}));
    if(S.sort==='personalized'&&S.favorites.length){
      const fav=new Set(S.favorites);
      const score=m=>(m.genre_ids||[]).reduce((n,id)=>n+(fav.has(id)?1:0),0);
      all.sort((a,b)=>score(b)-score(a)||(b.popularity||0)-(a.popularity||0));
    }
    movies=all;render();
  }catch{ if(id!==reqId)return; movies=[];wall.set([]);renderList();setStatus('error') }
}
function setStatus(k){const s=$('#status'),noun=S.mediaType==='tv'?'TV shows':'movies';
  if(k==='loading')s.innerHTML='<div class="m-shimmer-text">Loading '+noun+'</div>';
  else if(k==='error')s.innerHTML='<div><p>Could not reach TMDB. Check your connection and try again.</p><button class="btn" id="retry">Retry</button></div>';
  else if(k==='empty')s.innerHTML='<div><p>No '+noun+' match these filters.</p><button class="btn" id="clr2">Clear filters</button></div>';
  else s.innerHTML=''; }
$('#status').onclick=e=>{if(e.target.id==='retry')load();if(e.target.id==='clr2')clearFilters()};
function render(){setStatus(movies.length?'':'empty');wall.set(movies);renderList()}
function renderList(){$('#ol').innerHTML=movies.map(m=>`<li><button class="row-i m-reveal" data-id="${m.id}" data-type="${S.mediaType}"><img loading="lazy" alt="" src="${IMG}w92${m.poster_path}"><span class="t"><b>${esc(mediaTitle(m))}</b><span>${yr(m)} · ${esc(gnames(m))}</span></span><span class="r">${rate(m)}</span></button></li>`).join('')}
const gnames=m=>(m.genre_ids||[]).map(i=>genres.find(g=>g.id===i)?.name).filter(Boolean).slice(0,3).join(', ');
$('#ol').onclick=e=>{const b=e.target.closest('.row-i');if(b)openDetail(+b.dataset.id,b,null,b.dataset.type||S.mediaType)};

/* ---------- Filters ---------- */
function drawFilters(){
  $('#f-sort').innerHTML=SORTS.map(([v,l])=>`<button class="opt" data-sort="${v}" aria-pressed="${S.sort===v}">${l}</button>`).join('');
  $('#f-genre').innerHTML=[{id:'',name:'All genres'},...genres].map(g=>`<button class="opt" data-genre="${g.id}" aria-pressed="${S.genre==g.id}">${esc(g.name)}</button>`).join('');
  $('#rv').textContent=S.rt?`${(+S.rt).toFixed(1)} and up`:'Any'}
$('#filters').addEventListener('click',e=>{const b=e.target.closest('.opt');if(!b)return;
  if('sort'in b.dataset)S.sort=b.dataset.sort;if('genre'in b.dataset)S.genre=b.dataset.genre;
  try{localStorage.setItem('mw-sort',S.sort)}catch{}savePrefs();drawFilters();load()});
let dt;const later=()=>{clearTimeout(dt);dt=setTimeout(load,350)};
function fetchGenres(){const endpoint=S.mediaType==='tv'?'tv':'movie',requested=S.mediaType;api('/genre/'+endpoint+'/list').then(r=>{if(requested!==S.mediaType)return;genres=r.genres||[];drawFilters();renderList();renderFavoriteGenres()}).catch(()=>{})}
function syncMediaTypeControls(){
  document.querySelectorAll('[data-media-type]').forEach(b=>b.setAttribute('aria-pressed',b.dataset.mediaType===S.mediaType));
  $('#q').placeholder=S.mediaType==='tv'?'Search TV shows':'Search movies';
}
function setMediaType(type){
  if(!['movie','tv'].includes(type)||type===S.mediaType)return;
  S.mediaType=type;S.genre='';S.q='';S.sort='popularity.desc';$('#q').value='';$('#qc').hidden=true;
  try{localStorage.setItem('mw-sort',S.sort)}catch{}savePrefs();
  syncMediaTypeControls();drawFilters();fetchGenres();load();
}
document.querySelectorAll('[data-media-type]').forEach(b=>b.onclick=()=>setMediaType(b.dataset.mediaType));
const yearIn=(el,k)=>el.oninput=()=>{el.value=el.value.replace(/\D/g,'');S[k]=el.value.length===4?el.value:'';if(el.value.length===4||!el.value)later()};
yearIn($('#y1'),'y1');yearIn($('#y2'),'y2');
$('#rt').oninput=e=>{S.rt=+e.target.value;$('#rv').textContent=S.rt?`${S.rt.toFixed(1)} and up`:'Any';later()};
function clearFilters(){Object.assign(S,{genre:'',y1:'',y2:'',rt:0,sort:'popularity.desc'});try{localStorage.setItem('mw-sort',S.sort)}catch{}savePrefs();$('#y1').value=$('#y2').value='';$('#rt').value=0;drawFilters();load()}
$('#reset').onclick=clearFilters;$('#fbtn').onclick=()=>document.body.classList.toggle('fopen');
$('#q').oninput=e=>{S.q=e.target.value.trim();$('#qc').hidden=!e.target.value;later()};
$('#qc').onclick=()=>{$('#q').value='';S.q='';$('#qc').hidden=true;$('#q').focus();load()};
addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();$('#q').focus();$('#q').select()}
  if(e.key==='Escape'){if(!$('#player').hidden)Motion.setOpen($('#player'),false,null,true);else if(!$('#dlg').hidden)Motion.setOpen($('#dlg'),false,null,true);else if(!$('#settings').hidden)Motion.setOpen($('#settings'),false,null,true);else if(document.activeElement===$('#q'))$('#q').blur()}});
function syncView(){
  const wallView=S.view==='wall';
  document.querySelectorAll('[data-view]').forEach(x=>x.setAttribute('aria-pressed',x.dataset.view===S.view));
  $('#list').hidden=wallView;$('#stage').style.visibility=wallView?'':'hidden';
  $('#hint').style.display=wallView&&S.showHint?'':'none';
  $('#caption').style.display=wallView?'':'none';
  $('#chips').style.display=wallView&&S.showChips?'':'none';
  $('#mini').style.display=wallView&&S.showMini?'':'none';
}
document.querySelectorAll('[data-view]').forEach(b=>b.onclick=()=>{S.view=b.dataset.view;syncView()});

/* ---------- Detail ---------- */
const dlg=$('#dlg');
let openId=0;
async function openDetail(id,trigger,fallback,typeOverride){
  const type=typeOverride||mediaType(fallback||{media_type:S.mediaType}),m=movies.find(x=>x.id===id&&mediaType(x)===type)||fallback||{};openId=type+':'+id;cur=snap({...m,id,media_type:type});syncWl();
  $('#d-title').textContent=mediaTitle(m);$('#d-over').textContent=m.overview||'No description available.';$('#d-tag').textContent='';
  $('#d-img').src=m.poster_path?IMG+'w342'+m.poster_path:'';
  $('#d-hero').style.backgroundImage=m.backdrop_path?`url(${IMG}w780${m.backdrop_path})`:'';
  $('#d-meta').innerHTML=`${yr(m)} · <b>${rate(m)}</b>`;$('#d-more').innerHTML='';$('#d-castw').hidden=true;
  $('#tv-controls').hidden=type!=='tv';$('#play-label').textContent=type==='tv'?'Play episode':'Play movie';$('#tv-season').innerHTML='';$('#tv-episode').innerHTML='';
  Motion.setOpen(dlg,true,trigger);dlg.querySelector('.m-dialog__panel').scrollTop=0;
  try{const d=await api('/'+type+'/'+id,{append_to_response:'credits,videos'});
    if(openId!==type+':'+id)return;cur={...snap(d),media_type:type};syncWl();
    const runtime=d.runtime||(d.episode_run_time||[])[0];
    const t=runtime?` · ${Math.floor(runtime/60)}h ${runtime%60}m`:'';
    if(type==='tv'){
      const seasons=(d.seasons||[]).filter(x=>x.season_number>=0),initial=seasons.find(x=>x.season_number>0)||seasons[0];
      $('#tv-season').innerHTML=seasons.map(x=>`<option value="${x.season_number}">${esc(x.name||('Season '+x.season_number))}</option>`).join('');
      if(initial){$('#tv-season').value=initial.season_number;await loadSeason(id,+initial.season_number)}
    }
    $('#d-meta').innerHTML=`${yr(d)}${t} · <b>${rate(d)}</b>${d.vote_count?` <span>(${d.vote_count.toLocaleString()} votes)</span>`:''}`;
    $('#d-tag').textContent=d.tagline||'';$('#d-over').textContent=d.overview||m.overview||'No description available.';
    const creator=(d.created_by||[]).map(c=>c.name).join(', '),dir=(d.credits?.crew||[]).filter(c=>c.job==='Director').map(c=>c.name).join(', ');
    const v=(d.videos?.results||[]).find(v=>v.site==='YouTube'&&v.type==='Trailer');
    const row=(k,val)=>val?`<dt>${k}</dt><dd>${val}</dd>`:'';
    $('#d-more').innerHTML=(type==='tv'?row('Created by',esc(creator))+row('Seasons',String(d.number_of_seasons||0))+row('Episodes',String(d.number_of_episodes||0))+row('First aired',esc(d.first_air_date))+row('Last aired',esc(d.last_air_date)):row('Director',esc(dir))+row('Released',esc(d.release_date)))+row('Genres',esc((d.genres||[]).map(g=>g.name).join(', ')))+
      row('Trailer',v?`<a href="https://www.youtube.com/watch?v=${esc(v.key)}" target="_blank" rel="noopener">Watch on YouTube</a>`:'');
    const cast=(d.credits?.cast||[]).slice(0,12);
    $('#d-cast').innerHTML=cast.map(c=>`<div class="c">${c.profile_path?`<img loading="lazy" alt="" src="${IMG}w185${c.profile_path}">`:'<div class="ph"></div>'}<b>${esc(c.name)}</b><span>${esc(c.character||'')}</span></div>`).join('');
    $('#d-castw').hidden=!cast.length;
  }catch{if(openId===type+':'+id)$('#d-more').innerHTML='<dt>Details</dt><dd>Cast, episodes or trailer could not be loaded.</dd>'}
}
let seasonReq=0;
async function loadSeason(id,season){
  const request=++seasonReq;$('#tv-episode').innerHTML='<option>Loading episodes…</option>';
  try{
    const data=await api('/tv/'+id+'/season/'+season);
    if(request!==seasonReq||openId!=='tv:'+id)return;
    const episodes=data.episodes||[];
    $('#tv-episode').innerHTML=episodes.map(ep=>`<option value="${ep.episode_number}">E${ep.episode_number} · ${esc(ep.name||('Episode '+ep.episode_number))}</option>`).join('');
    if(episodes.length){const prior=Object.values(lib.prog).filter(p=>p.id==id&&mediaType(p)==='tv'&&p.season_number===season&&!p.done).sort((a,b)=>b.at-a.at)[0];
      const selected=prior?.episode_number||episodes[0].episode_number;$('#tv-episode').value=String(selected);cur={...cur,season_number:season,episode_number:selected}}
  }catch{if(request===seasonReq)$('#tv-episode').innerHTML='<option value="1">Episode list unavailable</option>'}
}
$('#tv-season').onchange=()=>openId.startsWith('tv:')&&loadSeason(+openId.slice(3),+$('#tv-season').value);
$('#tv-episode').onchange=()=>{if(cur&&cur.media_type==='tv')cur={...cur,season_number:+$('#tv-season').value,episode_number:+$('#tv-episode').value}};

/* ---------- Player: full-screen CineSrc embed ---------- */
const pl=$('#player'),pf=$('#pf');
let playing=null,lastT=0,lastD=0,lastSave=0;
function saveProg(done){if(!playing||!lastD)return;lib.prog[progressKey(playing)]={...playing,t:lastT,d:lastD,at:Date.now(),done:!!done||lastT/lastD>.92};saveLib();renderLib()}
function startPlay(m){
  const type=mediaType(m);let item={...snap(m),season_number:m.season_number??1,episode_number:m.episode_number??1};
  if(type==='tv'&&(m.season_number==null||m.episode_number==null)){const recent=Object.values(lib.prog).filter(p=>p.id==m.id&&mediaType(p)==='tv'&&!p.done).sort((a,b)=>b.at-a.at)[0];if(recent)item={...item,season_number:recent.season_number,episode_number:recent.episode_number}}
  playing=item;const pr=lib.prog[progressKey(playing)];lastT=pr?.t||0;lastD=pr?.d||0;lastSave=0;
  lib.hist=[{...playing,at:Date.now()},...lib.hist.filter(x=>libraryKey(x)!==libraryKey(playing))].slice(0,100);saveLib();
  const acc=getComputedStyle(document.documentElement).getPropertyValue('--accent').trim();
  let u=type==='tv'?`https://cinesrc.st/embed/tv/${m.id}?s=${playing.season_number}&e=${playing.episode_number}&color=${encodeURIComponent(acc)}&back=close`:`https://cinesrc.st/embed/movie/${m.id}?color=${encodeURIComponent(acc)}&back=close`;
  if(pr&&pr.t>30&&!pr.done)u+=`&t=${Math.floor(pr.t)}`;
  Motion.setOpen(pl,true,$('#play'));pf.src=u
}
$('#play').onclick=()=>cur&&startPlay(cur);
new MutationObserver(()=>{if(pl.hidden||pl.dataset.state==='closing'){if(playing){saveProg();playing=null}pf.src='about:blank'}}).observe(pl,{attributes:true,attributeFilter:['hidden','data-state']});
addEventListener('message',e=>{if(e.origin!=='https://cinesrc.st')return;const d=e.data||{};
  if(d.type==='cinesrc:timeupdate'&&playing){lastT=d.currentTime;lastD=d.duration;if(Date.now()-lastSave>4000){lastSave=Date.now();saveProg()}}
  else if(d.type==='cinesrc:pause')saveProg();
  else if(d.type==='cinesrc:ended'){lastT=lastD||lastT;saveProg(true)}
  else if(d.type==='cinesrc:nextepisode'&&playing?.media_type==='tv'){saveProg(true);playing={...playing,season_number:+d.season||playing.season_number,episode_number:+d.episode||playing.episode_number+1};lastT=0;lastD=0;lastSave=0}
  else if(d.type==='cinesrc:close')Motion.setOpen(pl,false,null,true)});
/* tilt the poster in the overlay toward the pointer */
const di=$('#d-img');if(!reduce){di.addEventListener('pointermove',e=>{const r=di.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;
  di.style.transform=`perspective(700px) rotateY(${x*18}deg) rotateX(${-y*18}deg) scale(1.03)`});di.addEventListener('pointerleave',()=>di.style.transform='')}

/* ---------- Three.js wall ---------- */
const wall=(()=>{
  const stage=$('#stage'),PW=1,PH=1.5,TAU=Math.PI*2;let rows=Math.max(2,Math.min(5,Number(S.rows)||3)),FOV=62;
  const renderer=new THREE.WebGLRenderer({antialias:true,alpha:true});renderer.setPixelRatio(Math.min(devicePixelRatio,2));stage.appendChild(renderer.domElement);
  const scene=new THREE.Scene(),cam=new THREE.PerspectiveCamera(FOV,1,.1,60),group=new THREE.Group();scene.add(group);
  const geo=new THREE.PlaneGeometry(PW,PH),loader=new THREE.TextureLoader();loader.setCrossOrigin('anonymous');
  const ray=new THREE.Raycaster(),ptr=new THREE.Vector2(9,9);
  let items=[],yaw=0,vel=0,dir=1,lim=Infinity,loop=true,wallR=6,drag=false,moved=0,lastX=0,hover=null,inside=false,px=0,py=0,focusIt=null,pending=false,fov=FOV;
  const clamp=(v,a,b)=>Math.min(b,Math.max(a,v)),wrap=a=>((a+Math.PI)%TAU+TAU)%TAU-Math.PI;
  function resize(){const w=stage.clientWidth,h=stage.clientHeight;renderer.setSize(w,h);cam.aspect=w/h;FOV=w/h<1?78:62;cam.updateProjectionMatrix()}
  new ResizeObserver(resize).observe(stage);resize();
  function set(list){
    items.forEach(i=>i.dying=true);queue.length=0;hover=null;focusIt=null;caption(null);
    const n=list.length,cols=Math.max(Math.ceil(n/rows),8),R=Math.max(6,cols*(PW+.14)/TAU),step=(PW+.14)/R,arc=cols*step,now=performance.now()/1000;
    loop=arc>=TAU-.05;wallR=R;lim=loop?Infinity:Math.max(0,arc/2-.9);yaw=0;vel=0;
    list.forEach((m,i)=>{const col=Math.floor(i/rows),row=i%rows,a=(col+(row%2)*.5)*step-(loop?0:arc/2),y=(row-(rows-1)/2)*(PH+.14);
      const holder=new THREE.Object3D();holder.position.set(Math.sin(a)*R,y,-Math.cos(a)*R);holder.lookAt(0,0,0); /* bowl: outer rows lean toward you */
      const mat=new THREE.MeshBasicMaterial({color:0x171717,transparent:true,opacity:0,toneMapped:false});
      const mesh=new THREE.Mesh(geo,mat);mesh.position.z=reduce?0:-3;holder.add(mesh);group.add(holder);
      const it={holder,mesh,mat,m,a,y,dying:false,start:reduce?0:now+.15+Math.min(i,60)*.012};mesh.userData.it=it;items.push(it);
      it.chip=chipEl(m);queue.push(it);
    });
    queue.sort((x,y)=>Math.abs(wrap(x.a))-Math.abs(wrap(y.a)));pump();
  }
  function pick(){group.updateMatrixWorld(true);ray.setFromCamera(ptr,cam);
    return ray.intersectObjects(items.filter(i=>!i.dying).map(i=>i.mesh),false)[0]||null}
  function caption(m){const c=$('#caption');if(!m){c.classList.remove('on');return}
    $('#cap-t').textContent=mediaTitle(m);$('#cap-m').innerHTML=`${yr(m)} · <b>${rate(m)}</b>${gnames(m)?' · '+esc(gnames(m)):''}`;c.classList.add('on')}
  function setPtr(e){const r=stage.getBoundingClientRect();ptr.set((e.clientX-r.left)/r.width*2-1,-((e.clientY-r.top)/r.height)*2+1);px=ptr.x;py=ptr.y}
  /* click: camera flies to the poster, then the details overlay opens */
  function focus(it){if(pending||!dlg.hidden)return;focusIt=it;pending=true;caption(null);
    setTimeout(()=>{pending=false;openDetail(it.m.id)},reduce?0:450)}
  stage.addEventListener('pointerdown',e=>{document.body.classList.remove('fopen');drag=true;moved=0;lastX=e.clientX;vel=0;stage.classList.add('drag');stage.setPointerCapture(e.pointerId);setPtr(e)});
  stage.addEventListener('pointermove',e=>{inside=true;setPtr(e);if(!drag)return;const dx=e.clientX-lastX;lastX=e.clientX;moved+=Math.abs(dx);yaw-=dx*.0042*S.sensitivity;vel=-dx*.0042*30*S.sensitivity});
  stage.addEventListener('pointerup',e=>{drag=false;stage.classList.remove('drag');setPtr(e);
    if(moved<5){const h=pick();if(h)focus(h.object.userData.it)}});
  stage.addEventListener('pointerleave',()=>{inside=false;hover=null;caption(null)});
  stage.addEventListener('wheel',e=>{e.preventDefault();vel+=(e.deltaY+e.deltaX)*.0016*S.sensitivity},{passive:false});

  /* poster textures: limited concurrency, centre-first, retries, title placeholder as last resort */
  const queue=[],TRIES=['w342','w185','w500'];let active=0;
  function placeholder(m){const c=document.createElement('canvas');c.width=342;c.height=513;const g=c.getContext('2d');g.fillStyle='#171717';g.fillRect(0,0,342,513);
    g.fillStyle='#a3a3a3';g.font='500 26px Geist, sans-serif';g.textAlign='center';let line='',y=230;
    for(const w of mediaTitle(m).split(' ')){if(line&&g.measureText(line+w).width>290){g.fillText(line.trim(),171,y);y+=32;line=''}line+=w+' '}g.fillText(line.trim(),171,y);return new THREE.CanvasTexture(c)}
  function apply(it,t){if(it.dying)return;t.colorSpace=THREE.SRGBColorSpace;t.anisotropy=4;it.mat.map=t;it.mat.color.set(0xffffff);it.mat.needsUpdate=true}
  function fetchTex(it,n,done){if(it.dying)return done();if(n>=TRIES.length){apply(it,placeholder(it.m));return done()}
    const img=new Image();img.crossOrigin='anonymous';let over=false;
    const next=()=>{if(over)return;over=true;clearTimeout(to);img.onload=img.onerror=null;setTimeout(()=>fetchTex(it,n+1,done),n?500:0)};
    const to=setTimeout(next,12000);
    img.onload=()=>{if(over)return;over=true;clearTimeout(to);const t=new THREE.Texture(img);t.needsUpdate=true;apply(it,t);done()};
    img.onerror=next;img.src=IMG+TRIES[n]+it.m.poster_path}
  function pump(){while(active<8&&queue.length){const it=queue.shift();if(it.dying)continue;active++;fetchTex(it,0,()=>{active--;pump()})}}
  /* 3D to 2D overlays: chips pinned to posters, a card anchored to the hovered poster, a position map */
  const chipsEl=$('#chips'),chipEl=m=>{const e=document.createElement('div');e.className='chip';e.textContent=chipTxt(m);chipsEl.appendChild(e);return e};
  const vv=new THREE.Vector3(),mini=$('#mini'),mc=mini.getContext('2d');let fc=0,mcol='#f5f5f5',acol='#f5a524';
  const scr=(mesh,x,y)=>{vv.set(x,y,0);mesh.localToWorld(vv);return vv.project(cam)};
  function drawMini(){const z=96,c=z/2,r=z/2-12;mc.setTransform(2,0,0,2,0,0);mc.clearRect(0,0,z,z);
    if(fc++%20===0){const cs=getComputedStyle(document.documentElement);mcol=cs.getPropertyValue('--text').trim()||mcol;acol=cs.getPropertyValue('--accent').trim()||acol}
    mc.globalAlpha=.1;mc.fillStyle=mcol;mc.beginPath();mc.moveTo(c,c);mc.arc(c,c,r+6,-Math.PI/2+yaw-.84,-Math.PI/2+yaw+.84);mc.fill();
    mc.globalAlpha=.16;mc.strokeStyle=mcol;mc.lineWidth=1;mc.beginPath();mc.arc(c,c,r,0,TAU);mc.stroke();
    for(const it of items){if(it.dying)continue;const on=it===hover||it===focusIt;mc.globalAlpha=on?1:.4;mc.fillStyle=on?acol:mcol;
      mc.beginPath();mc.arc(c+Math.sin(it.a)*r,c-Math.cos(it.a)*r,on?3:1.4,0,TAU);mc.fill()}mc.globalAlpha=1}
  function overlays(){const W=stage.clientWidth,H=stage.clientHeight;
    for(const it of items){const c=it.chip,show=!it.dying&&it.mat.opacity>.3&&(it.edge??9)<.62;
      if(!show){if(c.style.opacity!=='0')c.style.opacity='0';continue}
      const p=scr(it.mesh,-.42,-.68);c.style.opacity=Math.min(1,it.mat.opacity*1.2);
      c.style.transform=`translate3d(${((p.x+1)/2*W).toFixed(1)}px,${((1-p.y)/2*H).toFixed(1)}px,0) translateY(-100%)`}
    if(hover){let p=scr(hover.mesh,.5,.3),sx=(p.x+1)/2*W;const flip=sx>W*.66;if(flip){p=scr(hover.mesh,-.5,.3);sx=(p.x+1)/2*W}
      const sy=Math.min(H-90,Math.max(90,(1-p.y)/2*H)),c=$('#caption');c.classList.toggle('flip',flip);
      c.style.transform=`translate3d(${(flip?sx-290:sx+30).toFixed(1)}px,${sy.toFixed(1)}px,0)`}
    drawMini()}
  let last=performance.now();
  function frame(tms){requestAnimationFrame(frame);const dt=Math.min((tms-last)/1000,.05),now=tms/1000;last=tms;
    if(S.view!=='wall')return;const k=1-Math.exp(-dt*8),open=!dlg.hidden;
    if(focusIt&&!pending&&dlg.hidden)focusIt=null;
    if(focusIt){const d=loop?wrap(focusIt.a-yaw):focusIt.a-yaw;yaw+=d*(1-Math.exp(-dt*5));vel=0}
    else if(!drag){yaw+=vel*dt;vel*=Math.exp(-dt*3.2);
      if(!reduce&&S.drift&&!hover&&!open&&Math.abs(vel)<.03&&items.length)yaw+=dir*.016*dt*S.driftSpeed}
    if(yaw>lim){yaw=lim;dir=-1;vel=0}else if(yaw<-lim){yaw=-lim;dir=1;vel=0}
    group.rotation.y=yaw;
    const fp=focusIt?Math.atan2(focusIt.y,wallR)*.9:0,still=reduce||focusIt;
    cam.rotation.y+=((still?0:-px*.05)-cam.rotation.y)*k;cam.rotation.x+=((still?fp:py*.035)-cam.rotation.x)*k;
    cam.rotation.z+=((reduce?0:clamp(-vel*.005,-.035,.035))-cam.rotation.z)*k; /* banking while dragging */
    const tf=focusIt&&!reduce?44:FOV;fov+=(tf-fov)*k*.7;if(Math.abs(cam.fov-fov)>.01){cam.fov=fov;cam.updateProjectionMatrix()}
    
    let hit=null;if(inside&&!drag&&!open&&!focusIt){hit=pick();const it=hit?hit.object.userData.it:null;
      if(it!==hover){hover=it;caption(it?it.m:null);stage.style.cursor=it?'pointer':''}}
    const lean=reduce?0:clamp(-vel*.045,-.3,.3);
    for(const it of items){const h=hit&&hit.object===it.mesh,f=it===focusIt,live=now>=it.start;
      const edge=Math.abs(wrap(it.a-yaw)),vig=1-clamp((edge-.55)/.45,0,1)*.8;it.edge=edge; /* soft vignette toward the sides */
      const tgt=it.dying||!live?0:(f||h)?1:(focusIt?.2:(hover?.5:1)*vig);
      it.mat.opacity+=(tgt-it.mat.opacity)*(reduce?1:k*.7);
      const base=Math.max(.75,Math.min(1.25,Number(S.posterSize)||1)),ts=base*((h||f)&&!reduce?(f?1.18:1.07):1),s=it.mesh.scale.x+(ts-it.mesh.scale.x)*k;it.mesh.scale.set(s,s,1);
      const tz=!live?-3:reduce?0:f?.9:h?.28:0;it.mesh.position.z+=(tz-it.mesh.position.z)*(live?k:k*.5);
      it.mesh.rotation.y+=(((h&&!reduce)?(hit.uv.x-.5)*.3:lean)-it.mesh.rotation.y)*k;
      it.mesh.rotation.x+=(((h&&!reduce)?-(hit.uv.y-.5)*.3:0)-it.mesh.rotation.x)*k}
    items=items.filter(it=>{if(it.dying&&it.mat.opacity<.02){group.remove(it.holder);it.chip.remove();it.mat.map?.dispose();it.mat.dispose();return false}return true});
    renderer.render(scene,cam);overlays()}
  requestAnimationFrame(frame);
  return{set,refresh(){for(const it of items)if(!it.dying)it.chip.textContent=chipTxt(it.m)},configure(){rows=Math.max(2,Math.min(5,Number(S.rows)||3));if(movies.length)set(movies)}};
})();

/* ---------- Personalization and settings controls ---------- */
let managingProfiles=false,editingProfileId=null,selectedAvatar=0;
function renderProfileChooser(){
  const host=$('#profile-grid');if(!host)return;
  const tiles=profiles.map((p,i)=>`<div class="profile-tile" style="--profile-index:${i}">
    <button class="profile-choice" data-profile-select="${esc(p.id)}" aria-current="${p.id===activeProfileId}" aria-label="Continue as ${esc(p.name)}">
      <span class="profile-portrait">${avatarMarkup(p.avatar)}</span><strong>${esc(p.name)}</strong>
    </button>
    ${managingProfiles?`<div class="profile-tile-actions"><button data-profile-edit="${esc(p.id)}">Edit</button>${profiles.length>1?`<button data-profile-delete="${esc(p.id)}">Remove</button>`:''}</div>`:''}
  </div>`).join('');
  const add=profiles.length<8?`<div class="profile-tile">
    <button class="profile-choice profile-add" id="add-profile">
      <span class="profile-portrait"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span><strong>Add profile</strong>
    </button>
  </div>`:'';
  host.innerHTML=tiles+add;
  $('#profile-screen').classList.toggle('profile-managing',managingProfiles);
  $('#manage-profiles').textContent=managingProfiles?'Done':'Manage profiles';
  $('#manage-profiles').hidden=!!editingProfileId;
}
function openProfileEditor(id=null){
  editingProfileId=id;
  const p=id?profiles.find(x=>x.id===id):null;
  selectedAvatar=p?.avatar??(profiles.length%AVATARS.length);
  $('#profile-editor-title').textContent=p?'Edit profile':'Create profile';
  $('#profile-editor-name').value=p?.name||'';
  $('#profile-error').textContent='';
  $('#avatar-options').innerHTML=AVATARS.map((a,i)=>`<button class="avatar-option" data-avatar-choice="${i}" aria-label="Avatar ${i+1}" aria-pressed="${i===selectedAvatar}">${avatarMarkup(i)}</button>`).join('');
  $('#profile-grid').hidden=true;
  $('#profile-editor').hidden=false;
  $('#manage-profiles').hidden=true;
  $('#profile-editor-name').focus();
}
function closeProfileEditor(){
  editingProfileId=null;
  $('#profile-editor').hidden=true;
  $('#profile-grid').hidden=false;
  $('#manage-profiles').hidden=false;
  $('#profile-error').textContent='';
}
let profileScreenTimer=0;
function showProfilePicker(manage=false){
  clearTimeout(profileScreenTimer);
  managingProfiles=manage;
  closeProfileEditor();
  renderProfileChooser();
  const screen=$('#profile-screen');
  screen.classList.remove('is-closing');
  screen.hidden=false;
}
function hideProfilePicker(){
  const screen=$('#profile-screen');
  screen.classList.add('is-closing');
  clearTimeout(profileScreenTimer);
  profileScreenTimer=setTimeout(()=>{screen.hidden=true;screen.classList.remove('is-closing')},200);
}
function activateProfile(id){
  const next=profiles.find(p=>p.id===id);if(!next)return;
  if(activeProfile){
    saveActiveProfileMetadata();
    saveLib();
  }
  activeProfile=next;activeProfileId=next.id;
  S.profileName=next.name;S.profileNote=next.note;S.favorites=[...next.favorites];
  lib=copyLibrary(next.lib);cur=null;
  try{localStorage.setItem('mw-active-profile',activeProfileId)}catch{}
  saveActiveProfileMetadata();
  hideProfilePicker();
  document.body.classList.remove('fopen');
  updateProfile();syncPreferenceControls();renderLib();syncWl();wall.refresh();load();
}
$('#profile-grid').addEventListener('click',e=>{
  const select=e.target.closest('[data-profile-select]');
  if(select){if(!managingProfiles)activateProfile(select.dataset.profileSelect);return}
  const edit=e.target.closest('[data-profile-edit]');
  if(edit){openProfileEditor(edit.dataset.profileEdit);return}
  const remove=e.target.closest('[data-profile-delete]');
  if(remove){
    const id=remove.dataset.profileDelete;
    if(remove.dataset.confirm!=='yes'){
      remove.dataset.confirm='yes';remove.textContent='Confirm?';
      setTimeout(()=>{if(remove.isConnected){delete remove.dataset.confirm;remove.textContent='Remove'}},3500);
      return;
    }
    if(profiles.length<=1){$('#profile-error').textContent='Keep at least one profile.';return}
    profiles=profiles.filter(p=>p.id!==id);
    if(activeProfileId===id){
      activeProfile=profiles[0];activeProfileId=activeProfile.id;
      lib=copyLibrary(activeProfile.lib);S.profileName=activeProfile.name;S.profileNote=activeProfile.note;S.favorites=[...activeProfile.favorites];
      try{localStorage.setItem('mw-active-profile',activeProfileId)}catch{}
      updateProfile();syncPreferenceControls();renderLib();syncWl();wall.refresh();load();
    }
    persistProfiles();renderProfileChooser();return;
  }
  if(e.target.closest('#add-profile'))openProfileEditor();
});
$('#avatar-options').addEventListener('click',e=>{
  const b=e.target.closest('[data-avatar-choice]');if(!b)return;
  selectedAvatar=+b.dataset.avatarChoice;
  $('#avatar-options').querySelectorAll('[data-avatar-choice]').forEach(x=>x.setAttribute('aria-pressed',x===b));
});
$('#manage-profiles').onclick=()=>{managingProfiles=!managingProfiles;renderProfileChooser()};
$('#profile-cancel').onclick=closeProfileEditor;
$('#profile-save').onclick=()=>{
  const name=$('#profile-editor-name').value.trim();
  if(!name){$('#profile-error').textContent='Enter a profile name to continue.';$('#profile-editor-name').focus();return}
  if(editingProfileId){
    const p=profiles.find(x=>x.id===editingProfileId);if(!p)return;
    p.name=name.slice(0,24);p.avatar=selectedAvatar;
    if(p.id===activeProfileId){S.profileName=p.name;updateProfile();saveActiveProfileMetadata();syncPreferenceControls()}
  }else{
    if(profiles.length>=8){$('#profile-error').textContent='You can create up to 8 profiles.';return}
    const p={id:'profile-'+Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,7),name:name.slice(0,24),avatar:selectedAvatar,note:'',favorites:[],lib:emptyLibrary()};
    profiles.push(p);persistProfiles();managingProfiles=false;activateProfile(p.id);closeProfileEditor();renderProfileChooser();return;
  }
  persistProfiles();closeProfileEditor();renderProfileChooser();
};
$('#profile-switch').onclick=()=>showProfilePicker(false);
$('#switch-profile-settings').onclick=()=>{Motion.setOpen(stg,false,null,true);showProfilePicker(false)};
$('#manage-profiles-settings').onclick=()=>{Motion.setOpen(stg,false,null,true);showProfilePicker(true)};

function syncPreferenceControls(){
  $('#profile-name').value=S.profileName;$('#profile-note').value=S.profileNote;
  $('#wall-rows').value=S.rows;$('#wall-rows-v').textContent=S.rows;
  $('#poster-size').value=Math.round(S.posterSize*100);$('#poster-size-v').textContent=Math.round(S.posterSize*100)+'%';
  $('#sensitivity').value=Math.round(S.sensitivity*100);$('#sensitivity-v').textContent=Math.round(S.sensitivity*100)+'%';
  $('#drift-speed').value=Math.round(S.driftSpeed*100);$('#drift-speed-v').textContent=Math.round(S.driftSpeed*100)+'%';
  $('#toggle-mini').setAttribute('aria-pressed',S.showMini);$('#toggle-mini').textContent=S.showMini?'Mini-map on':'Mini-map off';
  $('#toggle-chips').setAttribute('aria-pressed',S.showChips);$('#toggle-chips').textContent=S.showChips?'Poster labels on':'Poster labels off';
  $('#toggle-hint').setAttribute('aria-pressed',S.showHint);$('#toggle-hint').textContent=S.showHint?'Navigation hint on':'Navigation hint off';
  document.querySelectorAll('[data-default-view]').forEach(b=>b.setAttribute('aria-pressed',b.dataset.defaultView===S.defaultView));
  syncDrift();updateProfile();renderFavoriteGenres();syncView();
}
function setDefaultView(v){S.defaultView=v;S.view=v;syncPreferenceControls();savePrefs()}
document.querySelectorAll('[data-font-set]').forEach(b=>b.onclick=()=>setFont(b.dataset.fontSet));
document.querySelectorAll('[data-density-set]').forEach(b=>b.onclick=()=>setDensity(b.dataset.densitySet));
document.querySelectorAll('[data-motion-set]').forEach(b=>b.onclick=()=>{S.motion=b.dataset.motionSet;applyMotion();savePrefs()});
document.querySelectorAll('[data-default-view]').forEach(b=>b.onclick=()=>setDefaultView(b.dataset.defaultView));
$('#wall-rows').oninput=e=>{S.rows=+e.target.value;$('#wall-rows-v').textContent=S.rows;wall.configure();savePrefs()};
$('#poster-size').oninput=e=>{S.posterSize=+e.target.value/100;$('#poster-size-v').textContent=e.target.value+'%';savePrefs()};
$('#sensitivity').oninput=e=>{S.sensitivity=+e.target.value/100;$('#sensitivity-v').textContent=e.target.value+'%';savePrefs()};
$('#drift-speed').oninput=e=>{S.driftSpeed=+e.target.value/100;$('#drift-speed-v').textContent=e.target.value+'%';savePrefs()};
$('#toggle-mini').onclick=()=>{S.showMini=!S.showMini;syncPreferenceControls();savePrefs()};
$('#toggle-chips').onclick=()=>{S.showChips=!S.showChips;syncPreferenceControls();savePrefs()};
$('#toggle-hint').onclick=()=>{S.showHint=!S.showHint;syncPreferenceControls();savePrefs()};
$('#profile-name').oninput=e=>{S.profileName=e.target.value;updateProfile();savePrefs()};
$('#profile-note').oninput=e=>{S.profileNote=e.target.value;updateProfile();savePrefs()};
$('#fav-genres').onclick=e=>{
  const b=e.target.closest('[data-favorite]');if(!b)return;
  const id=+b.dataset.favorite;
  S.favorites=S.favorites.includes(id)?S.favorites.filter(x=>x!==id):[...S.favorites,id];
  renderFavoriteGenres();savePrefs();if(S.sort==='personalized')load();
};
$('#clear-favs').onclick=()=>{S.favorites=[];renderFavoriteGenres();savePrefs();if(S.sort==='personalized')load()};
$('#reset-prefs').onclick=()=>{
  Object.keys(SETTINGS_DEFAULTS).filter(k=>!['profileName','profileNote','favorites'].includes(k)).forEach(k=>S[k]=SETTINGS_DEFAULTS[k]);
  S.profileName=activeProfile.name;S.profileNote=activeProfile.note;S.favorites=[...activeProfile.favorites];
  S.view=S.defaultView;
  try{localStorage.removeItem('mw-settings');localStorage.removeItem('mw-accent');localStorage.removeItem('mw-theme');localStorage.removeItem('mw-drift');localStorage.removeItem('mw-sort')}catch{}
  setAccent(PRE[0],false);setTheme('dark',false);setFont('geist',false);setDensity('comfortable',false);S.motion='system';applyMotion();
  drawFilters();syncPreferenceControls();wall.configure();syncMediaTypeControls();fetchGenres();savePrefs();load();
};


/* ---------- Account authentication and cloud storage ---------- */
function syncAuthScreen(message=''){
  const status=$('#account-status'),actions=$('#auth-actions'),signOut=$('#sign-out');
  if(!status||!actions||!signOut)return;
  document.querySelectorAll('[data-auth-provider]').forEach(b=>{b.disabled=!supabase});
  if(!supabase){
    status.textContent=message||'Guest mode. Add Supabase settings in Netlify to enable Google and Discord sign-in.';
    actions.hidden=false;signOut.hidden=true;return;
  }
  if(cloudUser){
    const account=cloudAccountRecord(cloudUser);
    status.textContent=message||('Signed in as '+(account.email||account.display_name)+'. Profiles, watchlists and progress sync to your account.');
    actions.hidden=true;signOut.hidden=false;
  }else{
    status.textContent=message||'Guest mode. Sign in to sync profiles and viewing data across devices.';
    actions.hidden=false;signOut.hidden=true;
  }
}
async function signInWithProvider(provider){
  if(!supabase){syncAuthScreen('Sign-in is not configured. Add SUPABASE_URL and SUPABASE_ANON_KEY in Netlify.');return}
  const buttons=[...document.querySelectorAll('[data-auth-provider]')];
  buttons.forEach(b=>b.disabled=true);
  syncAuthScreen('Opening '+(provider==='google'?'Google':'Discord')+' sign-in…');
  const {error}=await supabase.auth.signInWithOAuth({
    provider,
    options:{redirectTo:new URL('/',location.origin).toString()}
  });
  if(error){
    syncAuthScreen('Could not start sign-in: '+error.message);
    buttons.forEach(b=>b.disabled=false);
  }
}
async function resetToGuestAfterSignOut(){
  profiles=[{id:'guest',name:'Guest',avatar:0,note:'',favorites:[],lib:emptyLibrary()}];
  activeProfile=profiles[0];activeProfileId='guest';lib=copyLibrary(activeProfile.lib);
  S.profileName='Guest';S.profileNote='';S.favorites=[];
  try{
    localStorage.setItem(PROFILE_STORE_KEY,JSON.stringify(profiles));
    localStorage.setItem('mw-active-profile','guest');
  }catch{}
  saveActiveProfileMetadata();
  updateProfile();renderProfileChooser();syncPreferenceControls();renderLib();syncWl();wall.refresh();load();
}
async function handleAuthSession(session){
  if(!session?.user){
    const wasCloudUser=!!cloudHydratedUserId;
    cloudUser=null;cloudReady=false;cloudHydrating=false;cloudHydratedUserId=null;
    if(wasCloudUser)await resetToGuestAfterSignOut();
    syncAuthScreen();return;
  }
  const user=session.user;
  if(cloudHydratedUserId===user.id&&cloudReady){cloudUser=user;syncAuthScreen();return}
  cloudUser=user;cloudReady=false;cloudHydrating=true;
  syncAuthScreen('Loading your account…');
  try{
    const {data,error}=await supabase.from('reel_account_state')
      .select('state')
      .eq('user_id',user.id)
      .maybeSingle();
    if(error)throw error;
    if(data?.state&&Array.isArray(data.state.profiles)&&data.state.profiles.length){
      profiles=data.state.profiles.slice(0,8).map(normalizeProfile);
      activeProfile=profiles.find(p=>p.id===data.state.activeProfileId)||profiles[0];
      activeProfileId=activeProfile.id;
      lib=copyLibrary(activeProfile.lib);
      try{
        localStorage.setItem(PROFILE_STORE_KEY,JSON.stringify(profiles));
        localStorage.setItem('mw-active-profile',activeProfileId);
      }catch{}
      if(data.state.settings&&typeof data.state.settings==='object'){
        try{localStorage.setItem('mw-settings',JSON.stringify(data.state.settings))}catch{}
        Object.keys(SETTINGS_DEFAULTS).filter(k=>!['profileName','profileNote','favorites'].includes(k))
          .forEach(k=>{if(data.state.settings[k]!==undefined)S[k]=data.state.settings[k]});
      }
      S.profileName=activeProfile.name;S.profileNote=activeProfile.note;S.favorites=[...activeProfile.favorites];
      S.view=S.defaultView;
      setAccent(S.accent||PRE[0],false);setTheme(S.theme||'dark',false);
      setFont(S.font||'geist',false);setDensity(S.density||'comfortable',false);applyMotion();
    }else{
      /* First sign-in imports this browser's existing Guest profiles and library. */
      activeProfile.lib=copyLibrary(lib);
    }
    cloudHydratedUserId=user.id;
    cloudHydrating=false;
    cloudReady=true;
    if(!data?.state)await pushCloudState();
    updateProfile();renderProfileChooser();syncPreferenceControls();renderLib();syncWl();
    syncMediaTypeControls();fetchGenres();wall.refresh();load();
    syncAuthScreen();
  }catch(error){
    console.error('Reel account sync failed:',error);
    cloudHydrating=false;cloudReady=false;
    syncAuthScreen('Signed in, but cloud storage is not ready. Apply supabase/schema.sql and reload.');
  }
}
document.querySelectorAll('[data-auth-provider]').forEach(button=>{
  button.addEventListener('click',()=>signInWithProvider(button.dataset.authProvider));
});
$('#sign-out').addEventListener('click',async()=>{
  if(!supabase)return;
  $('#sign-out').disabled=true;
  await pushCloudState();
  const {error}=await supabase.auth.signOut();
  if(error){syncAuthScreen('Sign out failed: '+error.message);$('#sign-out').disabled=false}
});
syncAuthScreen();
if(supabase){
  supabase.auth.onAuthStateChange((event,session)=>{
    if(['INITIAL_SESSION','SIGNED_IN','SIGNED_OUT','TOKEN_REFRESHED','USER_UPDATED'].includes(event)){
      window.setTimeout(()=>{void handleAuthSession(session)},0);
    }
  });
}

syncPreferenceControls();

/* ---------- Start ---------- */
renderProfileChooser();drawFilters();renderLib();load();
syncMediaTypeControls();fetchGenres();
