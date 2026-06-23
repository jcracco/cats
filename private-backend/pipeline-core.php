<?php /* _pipeline-core.php — included by index.php. Never open directly. */ ?>
// ── Constants & helpers ──────────────────────────────────────────────────────
const COLORS  = { applied:"#94a3b8", recruiter:"#fbbf24", screening:"#818cf8", round:"#3b82f6", finalRound:"#a855f7", rejected:"#f87171", ghosted:"#fb7185", offer:"#4ade80", accepted:"#22c55e", withdrawn:"#64748b" };
const LABEL_W = 195;
const TODAY   = (() => { const d = new Date(); d.setHours(0,0,0,0); return d; })();
const API     = "api.php";

const pd   = s => s ? new Date(s) : null;
const dBw  = (a,b) => { const da=typeof a==="string"?new Date(a):a, db=typeof b==="string"?new Date(b):b; if(!da||!db) return null; return Math.round((db-da)/86400000); };
const rc   = r => r>=80?"#4ade80":r>=65?"#fbbf24":"#f87171";
const rb   = r => r>=80?"rgba(74,222,128,0.12)":r>=65?"rgba(251,191,36,0.12)":"rgba(248,113,113,0.12)";
const fmt  = d => d ? new Date(d).toISOString().slice(0,10) : "";
// Local date helper — avoids UTC offset showing tomorrow in non-UTC timezones
const localToday = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,"0")}-${String(d.getDate()).padStart(2,"0")}`;
};


// API helper — routes to mockApi in demo mode, real fetch in production
async function api(action, method="GET", body=null, params={}) {
  if (window.IS_DEMO && window.mockApi) {
    return window.mockApi(action, method, body, params);
  }
  const qs = new URLSearchParams({action, ...params}).toString();
  const opts = { method, headers:{"Content-Type":"application/json"} };
  if (body) opts.body = JSON.stringify(body);
  const r = await fetch(`${API}?${qs}`, opts);
  const j = await r.json();
  if (!j.ok) throw new Error(j.error || "API error");
  return j.data;
}

// Status config
const STATUS_CONFIG = {
  "Applied":      { cls:"badge-applied",      group:"pending",   label:"Applied"      },
  "Interviewing": { cls:"badge-interviewing",  group:"active",    label:"Interviewing" },
  "Not Selected": { cls:"badge-not-selected",  group:"closed_no", label:"Not Selected" },
  "No Answer":    { cls:"badge-no-answer",     group:"closed_no", label:"No Answer"    },
  "Rejected":     { cls:"badge-rejected",      group:"closed_rec",label:"Rejected"     },
  "Ghosted":      { cls:"badge-ghosted",       group:"closed_rec",label:"Ghosted"      },
  "Withdrawn":    { cls:"badge-withdrawn",     group:"closed_wdr",label:"Withdrawn"    },
  "Offer":        { cls:"badge-offer",         group:"active",    label:"Offer"        },
  "Accepted":     { cls:"badge-accepted",      group:"closed_pos",label:"Accepted"     },
};
const GROUP_LABELS = {
  active:     "Active",
  pending:    "Pending",
  closed_pos: "Closed — Positive",
  closed_rec: "Reached Recruiter",
  closed_no:  "Did Not Progress",
  closed_wdr: "Withdrawn",
};
const GROUP_COLORS = {
  active:     "#34d399",
  pending:    "#60a5fa",
  closed_pos: "#4ade80",
  closed_rec: "#f87171",
  closed_no:  "#6b7280",
  closed_wdr: "#94a3b8",
};
const ALL_STATUSES = Object.keys(STATUS_CONFIG);
const STATUS_TRANSITIONS = {
  "Applied":      ["Interviewing","Not Selected","No Answer","Withdrawn"],
  "Interviewing": ["Rejected","Ghosted","Withdrawn","Offer"],
  "Not Selected": ["Interviewing"],
  "No Answer":    ["Interviewing","Not Selected"],
  "Rejected":     ["Interviewing"],
  "Ghosted":      ["Interviewing","Rejected"],
  "Withdrawn":    ["Interviewing"],
  "Offer":        ["Withdrawn","Accepted"],
  "Accepted":     [],
};
const RESUME_VERSIONS = ["TPM","SPO","TIM","Custom"];
const STATUS_DOT_COLORS = {
  "Applied":      "#94a3b8",
  "Interviewing": "#3b82f6",
  "Offer":        "#4ade80",
  "Accepted":     "#22c55e",
  "Rejected":     "#f87171",
  "Ghosted":      "#fb7185",
  "Not Selected": "#fb923c",
  "No Answer":    "#d97706",
  "Withdrawn":    "#64748b",
};
const SOURCE_COLORS = {
  "Company website":   { bg:"rgba(168,85,247,0.12)",  color:"#c084fc",  border:"rgba(168,85,247,0.3)"  },
  "LinkedIn":          { bg:"rgba(14,165,233,0.12)",  color:"#38bdf8",  border:"rgba(14,165,233,0.3)"  },
  "Recruiter Outreach":{ bg:"rgba(251,191,36,0.12)",  color:"#f59e0b",  border:"rgba(251,191,36,0.3)"  },
  "Referral":          { bg:"rgba(52,211,153,0.12)",  color:"#34d399",  border:"rgba(52,211,153,0.3)"  },
  "Dice":              { bg:"rgba(239,68,68,0.12)",   color:"#f87171",  border:"rgba(239,68,68,0.3)"   },
  "Recruiting Agency": { bg:"rgba(148,163,184,0.12)", color:"#94a3b8",  border:"rgba(148,163,184,0.3)" },
  "Indeed":            { bg:"rgba(99,102,241,0.13)",  color:"#818cf8",  border:"rgba(99,102,241,0.3)"  },
  "Cybercoders":       { bg:"rgba(236,72,153,0.12)",  color:"#f472b6",  border:"rgba(236,72,153,0.3)"  },
  "Jobgether":         { bg:"rgba(16,185,129,0.15)",  color:"#10b981",  border:"rgba(16,185,129,0.4)"  },
  "BuiltIn":           { bg:"rgba(139,92,246,0.12)",  color:"#a78bfa",  border:"rgba(139,92,246,0.3)"  },
  "Hiring Café":       { bg:"rgba(180,83,9,0.12)",    color:"#d97706",  border:"rgba(180,83,9,0.3)"    },
};
const SOURCES = ["BuiltIn","Company website","Cybercoders","Dice","Hiring Café","Indeed","Jobgether","LinkedIn","Recruiter Outreach","Recruiting Agency","Referral","Other"];
const APPLIED_THROUGH = ["LinkedIn Easy Apply","Indeed","Dice","Cybercoders","Workday","Email","Recruiting Firm Portal","Other/Unknown","ADP","ApplyToJob","Ashby","Avature","Bamboo","Breezy","Dayforce","Dover","Eightfold","Gem","Greenhouse","Hirebridge","Humi","JazzHR","Jobvite","Kronos","Kula","Lever","Oracle","Oracle Cloud","Paycom","Paycor","Paylocity","Pinpoint","Rippling","SAP SuccessFactors","SmartRecruiters","Taleo","Teamtailor","Trakstar","UltiPro","Workable","iCIMS"];
const INTERVIEW_TYPES = ["Phone","Async","Home Assessment","Zoom","MS Teams","Google Meet","On Site"];

// ── Shared: StatusBadge ───────────────────────────────────────────────────────
function StatusBadge({ status }) {
  const cfg = STATUS_CONFIG[status] || STATUS_CONFIG["Applied"];
  return <span className={`badge ${cfg.cls}`}>{cfg.label}</span>;
}

// ── Shared: Dot (timeline) ────────────────────────────────────────────────────
function Dot({ x, color, glow, size=9, border=false }) {
  return <div style={{ position:"absolute", left:`${x}%`, top:"50%", width:size, height:size, borderRadius:"50%", background:color, transform:"translate(-50%,-50%)", zIndex:2, boxShadow:glow?`0 0 10px ${color}`:"none", border:border?"2px solid var(--bg)":"none", transition:"box-shadow 0.15s" }} />;
}

// ── Shared: StatBox ───────────────────────────────────────────────────────────
function StatBox({ label, value, editable, onChange, pct, pctLabel }) {
  return (
    <div className="stat-card">
      <div className="stat-card-label">{label}</div>
      <div style={{ display:"flex", alignItems:"baseline", gap:8 }}>
        {editable
          ? <input type="number" value={value} onChange={e=>onChange(Math.max(1,parseInt(e.target.value)||1))} style={{ fontSize:18, fontWeight:700, color:"var(--text-primary)", background:"transparent", border:"none", outline:"none", width:72, fontFamily:"inherit", padding:0 }} />
          : <span className="stat-card-value">{value}</span>}
        {pct && <span className="stat-card-pct">{pct}%</span>}
      </div>
      <div className="stat-card-sub">{pctLabel || " "}</div>
    </div>
  );
}

// ── Shared: FormField ─────────────────────────────────────────────────────────
function FormField({ label, children }) {
  return (
    <div className="form-field">
      <label className="form-label">{label}</label>
      {children}
    </div>
  );
}

// ── Timeline: buildTimelineData ───────────────────────────────────────────────
function buildTimelineData(sorted) {
  // If any application was accepted the search is over — stop the axis at data end, not today
  const searchClosed = sorted.some(i => i.status === "Accepted");

  const allDates = sorted.flatMap(i=>[i.applied,i.recruiter,i.screening,...(i.rounds||[]),i.rejected,i.offer_date]).filter(Boolean).map(d=>new Date(d));
  const earliest = new Date(Math.min(...allDates));
  const START    = new Date(earliest.getFullYear(), earliest.getMonth(), 1);
  const latestMs = searchClosed ? Math.max(...allDates) : Math.max(...allDates, TODAY);
  const latest   = new Date(latestMs);
  latest.setDate(latest.getDate() + (searchClosed ? 7 : 20));
  const END = new Date(latest.getFullYear(), latest.getMonth()+1, 1);
  const TMS = END - START;
  const toX = d => ((d-START)/TMS)*100;
  const monthLabels = [];
  let md = new Date(START);
  while(md<=END){ monthLabels.push({label:md.toLocaleString("default",{month:"short",year:"2-digit"}),x:toX(new Date(md))}); md=new Date(md.getFullYear(),md.getMonth()+1,1); }
  // When search is closed, treat pending apps as ghosted (don't extend their band to today)
  const wins = sorted.map(i=>{ const lastAct = i.screening?pd(i.screening):i.recruiter?pd(i.recruiter):pd(i.applied); return { start:pd(i.recruiter)||pd(i.applied), end:i.rejected?pd(i.rejected):(i.pending&&!searchClosed)?TODAY:new Date(lastAct.getTime()+14*86400000) }; }).filter(w=>w.start).sort((a,b)=>a.start-b.start);
  const merged=[];
  for(const w of wins){ if(!merged.length||w.start>merged[merged.length-1].end) merged.push({...w}); else merged[merged.length-1].end=new Date(Math.max(merged[merged.length-1].end,w.end)); }
  const zones=[];
  for(let i=1;i<merged.length;i++){ const d=dBw(merged[i-1].end,merged[i].start); if(d>0) zones.push({start:merged[i-1].end,end:merged[i].start,days:d}); }
  // Only show the trailing "current gap" when the search is still active
  const last=merged[merged.length-1];
  if(!searchClosed&&last&&last.end<TODAY) zones.push({start:last.end,end:TODAY,days:dBw(last.end,TODAY),current:true});
  return { toX, monthLabels, zones };
}

// ── Timeline: buildStats ──────────────────────────────────────────────────────
function buildStats(sorted) {
  const withI      = sorted.filter(d=>d.rounds&&d.rounds.length>0).length;
  const ghost      = sorted.filter(d=>!d.rejected&&!d.pending).length;
  const r2nd       = sorted.filter(d=>d.rounds&&d.rounds.length>=2).length;
  const totalIntvw = sorted.reduce((s,i)=>s+1+(i.rounds?i.rounds.length:0), 0);
  const avgR       = Math.round(sorted.reduce((s,i)=>s+(dBw(i.applied,i.recruiter)||0),0)/sorted.length);
  const avgS       = Math.round(sorted.reduce((s,i)=>s+(dBw(i.applied,i.screening)||0),0)/sorted.length);
  const maxR       = Math.max(...sorted.map(d=>d.rounds?d.rounds.length:0));
  const maxRC      = sorted.filter(d=>d.rounds&&d.rounds.length===maxR).map(d=>d.company).join(", ");
  const completed  = sorted.filter(d => d.rejected && typeof d.rejected==="string" && d.rejected.length>=8);
  const durations  = completed.map(d=>({company:d.company,days:dBw(d.applied,d.rejected)})).filter(d=>d.days!==null&&d.days>0);
  const shortest   = durations.length ? durations.reduce((a,b)=>a.days<=b.days?a:b) : null;
  const longest    = durations.length ? durations.reduce((a,b)=>a.days>=b.days?a:b) : null;
  const reachedFinal = sorted.filter(d => (d.rounds_full||[]).some(r=>r.is_final_round)).length;
  return { withI, ghost, r2nd, totalIntvw, avgR, avgS, maxR, maxRC, shortest, longest, reachedFinal };
}

// ── Timeline: Legend ──────────────────────────────────────────────────────────
function Legend({ showEditHint=false }) {
  return (
    <div style={{ display:"flex", gap:18, flexWrap:"wrap", padding:"9px 14px", background:"var(--stat-bg)", border:"1px solid var(--border)", borderRadius:8, width:"fit-content" }}>
      {[["Applied",COLORS.applied],["Recruiter",COLORS.recruiter],["Screening",COLORS.screening],["Interview Round",COLORS.round],["Final Round",COLORS.finalRound],["Offer",COLORS.offer],["Rejected",COLORS.rejected],["Accepted",COLORS.accepted],["Withdrawn",COLORS.withdrawn],["Ghosted",COLORS.ghosted]].map(([l,c])=>(
        <div key={l} style={{ display:"flex", alignItems:"center", gap:6, fontSize:11 }}>
          <div style={{ width:8, height:8, borderRadius:"50%", background:c, boxShadow:`0 0 5px ${c}80` }} />
          <span style={{ color:"var(--text-secondary)" }}>{l}</span>
        </div>
      ))}
      <div style={{ display:"flex", alignItems:"center", gap:6, fontSize:11 }}>
        <div style={{ width:18, height:2, background:"repeating-linear-gradient(90deg,rgba(239,68,68,0.5) 0,rgba(239,68,68,0.5) 4px,transparent 4px,transparent 8px)" }} />
        <span style={{ color:"var(--text-secondary)" }}>Gap</span>
        {showEditHint && <span style={{ color:"var(--text-dim)", fontSize:10, borderLeft:"1px solid var(--border)", paddingLeft:10, marginLeft:4 }}>click any row to edit</span>}
      </div>
    </div>
  );
}

// ── Timeline: Tooltip ─────────────────────────────────────────────────────────
function TimelineTooltip({ item, tipPos }) {
  if (!item) return null;
  const total  = item.rejected ? dBw(item.applied,item.rejected) : null;
  const sameRS = item.recruiter===item.screening;
  const rounds = item.rounds || [];
  return (
    <div style={{ position:"fixed", left:tipPos.x+14, top:tipPos.y-10, background:"var(--tooltip-bg)", border:"1px solid var(--border)", borderRadius:10, padding:"12px 16px", fontSize:12, zIndex:400, pointerEvents:"none", maxWidth:310, boxShadow:"0 12px 40px rgba(0,0,0,0.6)" }}>
      <div style={{ fontWeight:700, color:"var(--text-primary)", marginBottom:2, fontSize:13 }}>{item.company}</div>
      <div style={{ color:"var(--text-muted)", marginBottom:8, fontSize:11 }}>{item.position}</div>
      <div style={{ display:"flex", flexDirection:"column", gap:4 }}>
        <div style={{ color:COLORS.applied }}>● Applied: {item.applied}</div>
        {item.recruiter && <div style={{ color:COLORS.recruiter }}>● Recruiter: {item.recruiter} <span style={{ color:"var(--text-dim)", fontSize:11 }}>({dBw(item.applied,item.recruiter)}d)</span></div>}
        {item.screening && !sameRS && <div style={{ color:COLORS.screening }}>● Screening: {item.screening} <span style={{ color:"var(--text-dim)", fontSize:11 }}>({dBw(item.recruiter,item.screening)}d later)</span></div>}
        {item.screening && sameRS  && <div style={{ color:COLORS.screening }}>● Screening: {item.screening} <span style={{ color:"var(--text-dim)", fontSize:11 }}>(same day)</span></div>}
        {rounds.map((r,ri)=>{ const prev=ri===0?item.screening:rounds[ri-1]; const isFinal=item.rounds_full&&item.rounds_full[ri]&&item.rounds_full[ri].is_final_round; return <div key={ri} style={{ color:isFinal?COLORS.finalRound:COLORS.round }}>● Round {ri+1}{isFinal?" (Final)":""}: {r} <span style={{ color:"var(--text-dim)", fontSize:11 }}>({dBw(prev,r)}d later)</span></div>; })}
        {item.offer_date && <div style={{ color:COLORS.offer }}>● Offer: {item.offer_date} <span style={{ color:"var(--text-dim)", fontSize:11 }}>({dBw(rounds.length>0?rounds[rounds.length-1]:item.screening,item.offer_date)}d later)</span></div>}
        {item.rejected
          ? <div style={{ color:item.status==="Accepted"?COLORS.accepted:item.status==="Withdrawn"?COLORS.withdrawn:COLORS.rejected }}>● {item.status==="Accepted"?"Accepted":item.status==="Withdrawn"?"Withdrawn":"Rejected"}: {item.rejected} <span style={{ color:"var(--text-dim)", fontSize:11 }}>({dBw(item.offer_date?item.offer_date:rounds.length>0?rounds[rounds.length-1]:item.screening,item.rejected)}d later)</span></div>
          : item.pending
            ? <div style={{ color:"#3b82f6" }}>● Active / Pending</div>
            : <div style={{ color:COLORS.ghosted }}>● Ghosted</div>
        }
        {(() => {
          const lastEvent  = rounds.length>0 ? rounds[rounds.length-1] : (item.screening || item.recruiter || item.applied);
          const lastEventDate = pd(lastEvent);
          const ghostDur   = lastEventDate ? dBw(item.applied, new Date(lastEventDate.getTime()+14*86400000)) : dBw(item.applied, TODAY);
          const pendingDur = dBw(item.applied, TODAY);
          const durValue   = item.rejected ? total : item.pending ? pendingDur : ghostDur;
          const durLabel   = "Duration";
          return (
            <div style={{ marginTop:6, paddingTop:6, borderTop:"1px solid var(--border)", display:"flex", justifyContent:"space-between" }}>
              <span><span style={{ color:"var(--text-muted)" }}>Rating: </span><span style={{ color:rc(item.rating), fontWeight:700 }}>{item.rating}/100</span></span>
              <span><span style={{ color:"var(--text-muted)" }}>{durLabel}: </span><span style={{ color:"var(--text-secondary)" }}>{durValue}d</span></span>
            </div>
          );
        })()}
      </div>
    </div>
  );
}

// ── Timeline: PipelineRow ─────────────────────────────────────────────────────
function PipelineRow({ item, hovered, setHov, setTipPos, toX, onRowClick, onLinkClick }) {
  const rounds = item.rounds || [];
  const aX    = toX(pd(item.applied));
  const rX    = item.recruiter ? toX(pd(item.recruiter)) : null;
  const sX    = item.screening ? toX(pd(item.screening)) : null;
  const pen   = !!item.pending;
  const acc   = item.status === "Accepted";
  const closedColor = item.rejected
    ? (item.status==="Accepted" ? COLORS.accepted : item.status==="Withdrawn" ? COLORS.withdrawn : COLORS.rejected)
    : null;
  // Only ghosted if there was actual activity (recruiter contact or screening)
  const hasActivity = !!(item.recruiter || item.screening || rounds.length > 0);
  const gho   = hasActivity && !item.rejected && !pen;
  const lastActivityDate = rounds.length>0 ? pd(rounds[rounds.length-1]) : item.screening ? pd(item.screening) : item.recruiter ? pd(item.recruiter) : pd(item.applied);
  const gDate = new Date(lastActivityDate.getTime()+14*86400000);
  const end   = item.rejected ? pd(item.rejected) : pen ? TODAY : gDate;
  const eX    = toX(end);
  const hov   = hovered===item.id;
  const sameRS= item.recruiter===item.screening;
  const lrDate= rounds.length>0 ? pd(rounds[rounds.length-1]) : item.screening ? pd(item.screening) : item.recruiter ? pd(item.recruiter) : pd(item.applied);
  const lrX   = toX(lrDate);

  return (
    <div key={item.id}
      style={{ display:"flex", alignItems:"center", height:44, marginBottom:3, borderRadius:6,
        cursor:onRowClick?"pointer":"default", position:"relative", zIndex:1,
        background:hov?(pen?"rgba(59,130,246,0.10)":acc?"rgba(34,197,94,0.08)":"rgba(148,163,184,0.06)"):(pen?"rgba(59,130,246,0.04)":acc?"rgba(34,197,94,0.03)":"transparent"),
        borderLeft:hov?(pen?"2px solid rgba(59,130,246,0.60)":acc?"2px solid rgba(34,197,94,0.50)":"2px solid rgba(148,163,184,0.25)"):(pen?"2px solid rgba(59,130,246,0.25)":acc?"2px solid rgba(34,197,94,0.20)":"2px solid transparent"),
        transition:"background 0.12s" }}
      onMouseEnter={e=>{setHov(item.id);setTipPos({x:e.clientX,y:e.clientY});}}
      onMouseLeave={()=>setHov(null)}
      onMouseMove={e=>setTipPos({x:e.clientX,y:e.clientY})}
      onClick={onRowClick?()=>onRowClick(item):undefined}
    >
      <div style={{ width:LABEL_W-2, flexShrink:0, paddingRight:14, textAlign:"right", display:"flex", flexDirection:"column", alignItems:"flex-end", justifyContent:"center" }}>
        <div style={{ display:"flex", alignItems:"center", gap:5 }}>
          {(pen || acc) && <div style={{ width:6, height:6, borderRadius:"50%", background:pen?"#3b82f6":"#22c55e", boxShadow:pen?"0 0 6px #3b82f6":"0 0 6px #22c55e", flexShrink:0 }} />}
          <div style={{ fontSize:12, fontWeight:600, color:hov?"var(--text-primary)":(pen||acc)?"var(--text-active)":"var(--text-dim)", whiteSpace:"nowrap", overflow:"hidden", textOverflow:"ellipsis", transition:"color 0.12s", maxWidth:(pen||acc)?"calc(100% - 11px)":"100%" }}>{item.company}</div>
        </div>
        <div className={`${item.rating>=65&&item.rating<80?"rating-badge-mid":""}`} style={{ display:"inline-block", fontSize:10, fontWeight:700, color:rc(item.rating), background:rb(item.rating), padding:"1px 5px", borderRadius:3, marginTop:1 }}>{item.rating}</div>
      </div>
      <div style={{ flex:1, position:"relative", height:44 }}>
        {gho ? (<>
          <div style={{ position:"absolute", left:`${aX}%`, width:`${Math.max(lrX-aX,0.3)}%`, top:"50%", height:3, transform:"translateY(-50%)", background:`linear-gradient(90deg,${COLORS.applied}50,${COLORS.screening}50)`, borderRadius:2 }} />
          <div style={{ position:"absolute", left:`${lrX}%`, width:`${Math.max(toX(gDate)-lrX,0.3)}%`, top:"50%", height:3, transform:"translateY(-50%)", background:"repeating-linear-gradient(90deg,#4b5563 0,#4b5563 5px,transparent 5px,transparent 10px)", borderRadius:2 }} />
        </>) : (
          <div style={{ position:"absolute", left:`${aX}%`, width:`${Math.max(eX-aX,0.3)}%`, top:"50%", height:3, transform:"translateY(-50%)", background:item.rejected?`linear-gradient(90deg,${COLORS.applied}50,${closedColor}50)`:`linear-gradient(90deg,${COLORS.applied}50,${COLORS.round}50)`, borderRadius:2 }} />
        )}
        <Dot x={aX} color={COLORS.applied} glow={hov} size={9} />
        {!sameRS && item.recruiter && <Dot x={rX} color={COLORS.recruiter} glow={hov} size={8} border={true} />}
        {item.screening && <Dot x={sX} color={COLORS.screening} glow={hov} size={10} />}
        {rounds.map((r,ri)=>{ const isFinal=item.rounds_full&&item.rounds_full[ri]&&item.rounds_full[ri].is_final_round; return <Dot key={ri} x={toX(pd(r))} color={isFinal?COLORS.finalRound:COLORS.round} glow={hov} size={10} border={true} />; })}
        {item.rejected && <Dot x={eX} color={closedColor} glow={hov} size={10} />}
        {gho && <Dot x={toX(gDate)} color={COLORS.ghosted} glow={hov} size={8} />}
        {item.offer_date && <Dot x={toX(pd(item.offer_date))} color={COLORS.offer} glow={hov} size={10} border={true} />}
      </div>
    </div>
  );
}

// ── Timeline: PipelineGrid ────────────────────────────────────────────────────
function PipelineGrid({ sorted, hovered, setHov, tipPos, setTipPos, zones, toX, monthLabels, onRowClick, onLinkClick }) {
  return (
    <div style={{ overflowX:"auto" }}>
      <div style={{ minWidth:760 }}>
        <div style={{ display:"flex", marginLeft:LABEL_W, position:"relative", height:22, marginBottom:6 }}>
          {monthLabels.map(({label,x})=><div key={label} style={{ position:"absolute", left:`${x}%`, fontSize:10, color:"var(--text-muted)", transform:"translateX(-50%)", whiteSpace:"nowrap", letterSpacing:1 }}>{label}</div>)}
        </div>
        <div style={{ position:"relative" }}>
          <div style={{ position:"absolute", top:0, left:LABEL_W, right:0, bottom:0, pointerEvents:"none", zIndex:0 }}>
            {monthLabels.map(({label,x})=><div key={label} style={{ position:"absolute", left:`${x}%`, top:0, bottom:0, width:1, background:"var(--grid-line)" }} />)}
            {zones.map((z,zi)=>(
              <div key={zi}>
                <div style={{ position:"absolute", left:`${toX(z.start)}%`, width:`${Math.max(toX(z.end)-toX(z.start),0)}%`, top:0, bottom:0, background:"repeating-linear-gradient(90deg,rgba(239,68,68,0.04) 0,rgba(239,68,68,0.04) 8px,transparent 8px,transparent 16px)", borderLeft:"1px dashed rgba(239,68,68,0.3)" }} />
                <div style={{ position:"absolute", left:`calc(${toX(z.start)}% + 4px)`, top:6, fontSize:9, color:"rgba(239,68,68,0.55)", letterSpacing:2, textTransform:"uppercase", whiteSpace:"nowrap" }}>
                  {z.current?`empty · ${dBw(z.start,TODAY)}d and counting`:`${z.days}d gap`}
                </div>
              </div>
            ))}
            <div style={{ position:"absolute", left:`${toX(TODAY)}%`, top:0, bottom:0, borderLeft:"1px dashed rgba(96,165,250,0.4)" }} />
            <div style={{ position:"absolute", left:`calc(${toX(TODAY)}% + 4px)`, top:4, fontSize:9, color:"rgba(96,165,250,0.7)", letterSpacing:2, textTransform:"uppercase" }}>today</div>
          </div>
          {sorted.map(item=><PipelineRow key={item.id} item={item} hovered={hovered} setHov={setHov} setTipPos={setTipPos} toX={toX} onRowClick={onRowClick} onLinkClick={onLinkClick} />)}
        </div>
      </div>
    </div>
  );
}

// ── Timeline: StatsRow ────────────────────────────────────────────────────────
function TimelineStatsRow({ sorted, zones, totalApps }) {
  const { withI, ghost, r2nd, totalIntvw, avgR, avgS, maxR, maxRC, shortest, longest, reachedFinal } = buildStats(sorted);
  const screenings = sorted.length;
  const [showMore, setShowMore] = useState(false);
  return (
    <div style={{ marginBottom:16 }}>
      <div className="stats-bar" style={{ marginBottom:0 }}>
        <StatGroup label="Pipeline Funnel">
          <StatBox label="Total Applications"     value={totalApps ?? "…"} />
          <StatBox label="Screenings"             value={screenings} pct={totalApps?((screenings/totalApps)*100).toFixed(1):null} pctLabel="of applications" />
          <StatBox label="Reached Hiring Manager" value={withI}      pct={screenings?((withI/screenings)*100).toFixed(0):null}    pctLabel="of screenings" />
          <StatBox label="Passed 1st Round"       value={r2nd}       pct={withI?((r2nd/withI)*100).toFixed(0):null}              pctLabel="of HM interviews" />
          <StatBox label="Reached Final Round"    value={reachedFinal} pct={withI?((reachedFinal/withI)*100).toFixed(0):null}    pctLabel="of HM interviews" />
        </StatGroup>
        <StatGroup label="‎">
          <StatBox label="Total Interviews" value={totalIntvw} />
        </StatGroup>
        <button onClick={()=>setShowMore(m=>!m)} style={{ alignSelf:"flex-end", background:"none", border:"none", color:"var(--text-dim)", fontSize:10, cursor:"pointer", letterSpacing:0.5, padding:"6px 4px", whiteSpace:"nowrap" }}>
          {showMore ? "Less ▲" : "More ▼"}
        </button>
      </div>
      {showMore && (
        <div className="stats-bar" style={{ marginTop:6, marginBottom:0, animation:"dropdownIn 0.14s ease" }}>
          <StatGroup label="Process Time">
            <StatBox label="Avg → Recruiter"  value={`${avgR}d`} />
            <StatBox label="Avg → Screening"  value={`${avgS}d`} />
            {longest  && <StatBox label="Longest Process"  value={`${longest.days}d`} pctLabel={longest.company} />}
            {shortest && <StatBox label="Shortest Process" value={`${shortest.days}d`} pctLabel={shortest.company} />}
          </StatGroup>
          <StatGroup label="Interview Activity">
            <StatBox label="Pipeline Gaps" value={zones.length} />
            <StatBox label="Ghosted"       value={ghost} />
            <StatBox label="Most Rounds"   value={`${maxRC} ×${maxR}`} />
          </StatGroup>
        </div>
      )}
    </div>
  );
}

// ── Applications: StatsBar ────────────────────────────────────────────────────
function StatGroup({ children, label }) {
  return (
    <div className="stat-group">
      {label && <div className="stat-group-label">{label}</div>}
      <div className="stat-group-cards">{children}</div>
    </div>
  );
}

function AppStatsBar({ stats }) {
  if (!stats) return <div className="stats-bar"><div className="stat-card"><div className="stat-card-value">…</div></div></div>;
  const t = stats.total || 1;
  const [showMore, setShowMore] = useState(false);
  return (
    <div style={{ marginBottom:16 }}>
      <div className="stats-bar" style={{ marginBottom:0 }}>
        <StatGroup label="‎">
          <StatBox label="Total Applications" value={stats.total} />
        </StatGroup>
        <StatGroup label="Pipeline">
          <StatBox label="Active"            value={stats.active}           pct={((stats.active/t)*100).toFixed(1)}                                                           pctLabel="of total" />
          <StatBox label="Reached Recruiter" value={stats.reached_recruiter} pct={stats.reached_recruiter!=null?((stats.reached_recruiter/t)*100).toFixed(1):null}            pctLabel="of total" />
        </StatGroup>
        <StatGroup label="Activity">
          <StatBox label="This Week" value={stats.this_week} />
        </StatGroup>
        <button onClick={()=>setShowMore(m=>!m)} style={{ alignSelf:"flex-end", background:"none", border:"none", color:"var(--text-dim)", fontSize:10, cursor:"pointer", letterSpacing:0.5, padding:"6px 4px", whiteSpace:"nowrap" }}>
          {showMore ? "Less ▲" : "More ▼"}
        </button>
      </div>
      {showMore && (
        <div className="stats-bar" style={{ marginTop:6, marginBottom:0, animation:"dropdownIn 0.14s ease" }}>
          <StatGroup label="Pipeline">
            <StatBox label="Did Not Progress" value={stats.closed_no_prog} pct={((stats.closed_no_prog/t)*100).toFixed(1)} pctLabel="of total" />
          </StatGroup>
          <StatGroup label="Activity">
            <StatBox label="This Month"  value={stats.this_month} />
            <StatBox label="Avg / Week"  value={stats.avg_week ?? "—"} />
            <StatBox label="Avg / Month" value={stats.avg_month ?? "—"} />
          </StatGroup>
          <StatGroup label="Quality">
            <StatBox label="Avg Rating" value={stats.avg_rating ?? "—"} />
          </StatGroup>
        </div>
      )}
    </div>
  );
}

// ── Applications: Filters ─────────────────────────────────────────────────────
// Multi-select dropdown component
function MultiDropdown({ label, options, value, onChange }) {
  const [open, setOpen] = useState(false);
  const ref = useRef(null);
  useEffect(()=>{
    const h = e => { if(ref.current && !ref.current.contains(e.target)) setOpen(false); };
    document.addEventListener("mousedown", h); return()=>document.removeEventListener("mousedown", h);
  },[]);
  const toggle = v => onChange(value.includes(v) ? value.filter(x=>x!==v) : [...value, v]);
  const active = value.length > 0;
  return (
    <div ref={ref} style={{ position:"relative" }}>
      <button onClick={()=>setOpen(o=>!o)} className={`pill ${active?"active":""}`} style={{ display:"flex", alignItems:"center", gap:5 }}>
        {label}
        <span style={{ minWidth:26, textAlign:"center", fontSize:10 }}>{active?`(${value.length})`:""}</span>
        <span style={{ fontSize:9, opacity:0.7 }}>{open?"▲":"▼"}</span>
      </button>
      {open && (
        <div style={{ position:"absolute", top:"calc(100% + 4px)", left:0, background:"var(--modal-bg)", border:"1px solid var(--border)", borderRadius:8, padding:"4px 0", zIndex:200, minWidth:210, maxHeight:300, overflowY:"auto", boxShadow:"0 8px 24px rgba(0,0,0,0.3)" }}>
          {value.length > 0
            ? <button onClick={()=>onChange([])} style={{ width:"100%", background:"none", border:"none", borderBottom:"1px solid var(--border)", color:"var(--text-muted)", fontFamily:"inherit", fontSize:10, padding:"5px 12px", cursor:"pointer", textAlign:"left", letterSpacing:1 }}>CLEAR</button>
            : <div style={{ borderBottom:"1px solid var(--border)", color:"var(--text-dim)", fontFamily:"inherit", fontSize:10, padding:"5px 12px", letterSpacing:1 }}>SHOWING ALL</div>
          }
          {options.map(o=>(
            <label key={o} style={{ display:"flex", alignItems:"center", gap:8, padding:"7px 12px", cursor:"pointer", fontSize:12, color:value.includes(o)?"var(--text-primary)":"var(--text-secondary)" }}
              onMouseEnter={e=>e.currentTarget.style.background="var(--table-row-hover)"}
              onMouseLeave={e=>e.currentTarget.style.background="none"}>
              <input type="checkbox" checked={value.includes(o)} onChange={()=>toggle(o)} style={{ cursor:"pointer" }} />
              {o}
            </label>
          ))}
        </div>
      )}
    </div>
  );
}

// Status MultiDropdown with colored dots
function StatusDropdown({ value, onChange }) {
  const options = ALL_STATUSES.map(s => ({ value:s, label:STATUS_CONFIG[s].label, color:STATUS_DOT_COLORS[s] }));
  const [open, setOpen] = useState(false);
  const ref = useRef(null);
  useEffect(()=>{
    const h = e => { if(ref.current && !ref.current.contains(e.target)) setOpen(false); };
    document.addEventListener("mousedown", h); return()=>document.removeEventListener("mousedown", h);
  },[]);
  const toggle = v => onChange(value.includes(v) ? value.filter(x=>x!==v) : [...value, v]);
  const active = value.length > 0;
  return (
    <div ref={ref} style={{ position:"relative" }}>
      <button onClick={()=>setOpen(o=>!o)} className={`pill ${active?"active":""}`} style={{ display:"flex", alignItems:"center", gap:5 }}>
        Status
        <span style={{ minWidth:26, textAlign:"center", fontSize:10 }}>{active?`(${value.length})`:""}</span>
        <span style={{ fontSize:9, opacity:0.7 }}>{open?"▲":"▼"}</span>
      </button>
      {open && (
        <div style={{ position:"absolute", top:"calc(100% + 4px)", left:0, background:"var(--modal-bg)", border:"1px solid var(--border)", borderRadius:8, padding:"4px 0", zIndex:200, minWidth:180, boxShadow:"0 8px 24px rgba(0,0,0,0.3)" }}>
          {value.length > 0
            ? <button onClick={()=>onChange([])} style={{ width:"100%", background:"none", border:"none", borderBottom:"1px solid var(--border)", color:"var(--text-muted)", fontFamily:"inherit", fontSize:10, padding:"5px 12px", cursor:"pointer", textAlign:"left", letterSpacing:1 }}>CLEAR</button>
            : <div style={{ borderBottom:"1px solid var(--border)", color:"var(--text-dim)", fontFamily:"inherit", fontSize:10, padding:"5px 12px", letterSpacing:1 }}>SHOWING ALL</div>
          }
          {options.map(o=>(
            <label key={o.value} style={{ display:"flex", alignItems:"center", gap:8, padding:"7px 12px", cursor:"pointer", fontSize:12, color:value.includes(o.value)?"var(--text-primary)":"var(--text-secondary)" }}
              onMouseEnter={e=>e.currentTarget.style.background="var(--table-row-hover)"}
              onMouseLeave={e=>e.currentTarget.style.background="none"}>
              <input type="checkbox" checked={value.includes(o.value)} onChange={()=>toggle(o.value)} style={{ cursor:"pointer" }} />
              <span style={{ width:7, height:7, borderRadius:"50%", background:o.color, flexShrink:0, display:"inline-block", boxShadow:`0 0 3px ${o.color}80` }} />
              {o.label}
            </label>
          ))}
        </div>
      )}
    </div>
  );
}

function DateRangePicker({ dateFrom, setDateFrom, dateTo, setDateTo }) {
  const active = dateFrom || dateTo;
  return (
    <div style={{ display:"flex", alignItems:"center", gap:6 }}>
      <span style={{ fontSize:10, color:"var(--text-muted)", whiteSpace:"nowrap" }}>From</span>
      <input type="date" value={dateFrom||""} onChange={e=>setDateFrom(e.target.value||null)}
        style={{ background:"var(--input-bg)", border:"1px solid var(--input-border)", borderRadius:6,
          color:"var(--text-primary)", fontFamily:"inherit", fontSize:11, padding:"5px 8px", outline:"none", cursor:"pointer" }} />
      <span style={{ fontSize:10, color:"var(--text-muted)" }}>to</span>
      <input type="date" value={dateTo||""} onChange={e=>setDateTo(e.target.value||null)}
        style={{ background:"var(--input-bg)", border:"1px solid var(--input-border)", borderRadius:6,
          color:"var(--text-primary)", fontFamily:"inherit", fontSize:11, padding:"5px 8px", outline:"none", cursor:"pointer" }} />
      <button onClick={()=>{ setDateFrom(null); setDateTo(null); }} style={{ background:"none", border:"none", color:"var(--text-muted)", cursor:active?"pointer":"default", fontSize:12, padding:0, lineHeight:1, opacity:active?1:0, pointerEvents:active?"auto":"none" }}>✕</button>
    </div>
  );
}

function SalarySlider({ salaryMin, setSalaryMin, salaryType, setSalaryType }) {
  const maxY = 250; const maxH = 150; // K yearly, /h hourly
  const max = salaryType === "Yearly" ? maxY : maxH;
  const step = salaryType === "Yearly" ? 10 : 5;
  const label = salaryMin > 0 ? `≥ ${salaryMin}${salaryType==="Hourly"?"/h":""}` : "Any";
  return (
    <div style={{ display:"flex", alignItems:"center", gap:8 }}>
      <span style={{ fontSize:10, color:"var(--text-muted)", whiteSpace:"nowrap" }}>Salary ≥</span>
      <input type="range" min={0} max={max} step={step} value={salaryMin} onChange={e=>setSalaryMin(Number(e.target.value))}
        style={{ width:80, cursor:"pointer" }} />
      <span style={{ fontSize:10, fontWeight:700, minWidth:28, color: salaryMin>0?"#60a5fa":"var(--text-muted)" }}>
        {label}
      </span>
      <button onClick={()=>setSalaryMin(0)} style={{ background:"none", border:"none", color:"var(--text-muted)", cursor:salaryMin>0?"pointer":"default", fontSize:12, padding:0, lineHeight:1, opacity:salaryMin>0?1:0, pointerEvents:salaryMin>0?"auto":"none" }}>✕</button>
      <div style={{ display:"flex", gap:6 }}>
        {["Yearly","Hourly"].map(t=>(
          <label key={t} style={{ display:"flex", alignItems:"center", gap:3, fontSize:10, color:salaryType===t?"var(--text-primary)":"var(--text-muted)", cursor:"pointer" }}>
            <input type="radio" name="salaryType" checked={salaryType===t} onChange={()=>{ setSalaryType(t); setSalaryMin(0); }} style={{ cursor:"pointer", accentColor:"#3b82f6" }} />
            {t==="Yearly"?"$/y":"$/h"}
          </label>
        ))}
      </div>
    </div>
  );
}

function ExtrasDropdown({ value, onChange }) {
  // value = { coverLetter:[], outreach:[], location:[] }
  // A section is "active" when exactly 1 of its 2 options is selected
  const [open, setOpen] = useState(false);
  const ref = useRef(null);
  useEffect(()=>{
    const h = e => { if(ref.current && !ref.current.contains(e.target)) setOpen(false); };
    document.addEventListener("mousedown", h); return()=>document.removeEventListener("mousedown", h);
  },[]);

  const activeCount =
    (value.coverLetter.length === 1 ? 1 : 0) +
    (value.outreach.length    === 1 ? 1 : 0) +
    (value.location.length    === 1 ? 1 : 0);
  const hasAny = activeCount > 0;

  const toggle = (key, v) => {
    const cur  = value[key];
    const next = cur.includes(v) ? cur.filter(x=>x!==v) : [...cur, v];
    onChange({ ...value, [key]: next });
  };
  const clearAll = () => onChange({ coverLetter:[], outreach:[], location:[] });

  const Section = ({ label, fkey, opts }) => (
    <>
      <div style={{ fontSize:10, color:"var(--text-muted)", letterSpacing:1.5, padding:"8px 12px 3px", fontWeight:600 }}>{label}</div>
      {opts.map(o => (
        <label key={o.v} style={{ display:"flex", alignItems:"center", gap:8, padding:"6px 12px", cursor:"pointer", fontSize:12, color:value[fkey].includes(o.v)?"var(--text-primary)":"var(--text-secondary)" }}
          onMouseEnter={e=>e.currentTarget.style.background="var(--table-row-hover)"}
          onMouseLeave={e=>e.currentTarget.style.background="none"}>
          <input type="checkbox" checked={value[fkey].includes(o.v)} onChange={()=>toggle(fkey, o.v)} style={{ cursor:"pointer" }} />
          {o.label}
        </label>
      ))}
    </>
  );

  return (
    <div ref={ref} style={{ position:"relative" }}>
      <button onClick={()=>setOpen(o=>!o)} className={`pill ${hasAny?"active":""}`} style={{ display:"flex", alignItems:"center", gap:5 }}>
        Extras
        <span style={{ minWidth:26, textAlign:"center", fontSize:10 }}>{hasAny?`(${activeCount})`:""}</span>
        <span style={{ fontSize:9, opacity:0.7 }}>{open?"▲":"▼"}</span>
      </button>
      {open && (
        <div style={{ position:"absolute", top:"calc(100% + 4px)", left:0, background:"var(--modal-bg)", border:"1px solid var(--border)", borderRadius:8, padding:"4px 0", zIndex:200, minWidth:170, boxShadow:"0 8px 24px rgba(0,0,0,0.3)" }}>
          {hasAny
            ? <button onClick={clearAll} style={{ width:"100%", background:"none", border:"none", borderBottom:"1px solid var(--border)", color:"var(--text-muted)", fontFamily:"inherit", fontSize:10, padding:"5px 12px", cursor:"pointer", textAlign:"left", letterSpacing:1 }}>CLEAR</button>
            : <div style={{ borderBottom:"1px solid var(--border)", color:"var(--text-dim)", fontFamily:"inherit", fontSize:10, padding:"5px 12px", letterSpacing:1 }}>SHOWING ALL</div>
          }
          <Section label="COVER LETTER" fkey="coverLetter" opts={[{v:"yes",label:"Yes"},{v:"no",label:"No"}]} />
          <div style={{ height:1, background:"var(--border)", margin:"4px 0" }} />
          <Section label="OUTREACH" fkey="outreach" opts={[{v:"yes",label:"Yes"},{v:"no",label:"No"}]} />
          <div style={{ height:1, background:"var(--border)", margin:"4px 0" }} />
          <Section label="LOCATION" fkey="location" opts={[{v:"Remote",label:"Remote"},{v:"Hybrid",label:"Hybrid"}]} />
        </div>
      )}
    </div>
  );
}

// ── Filter bar (second row, shown when Filters is toggled) ────────────────────
function FilterBar({ statusFilter, setStatusFilter, resumeFilter, setResumeFilter, sourceFilter, setSourceFilter, appliedFilter, setAppliedFilter, allSources, allAppliedThrough, ratingMin, setRatingMin, salaryMin, setSalaryMin, salaryType, setSalaryType, dateFrom, setDateFrom, dateTo, setDateTo, extrasFilter, setExtrasFilter, onResetAll }) {
  return (
    <div className="filters" style={{ marginTop:-8, marginBottom:16, animation:"dropdownIn 0.14s ease" }}>
      <StatusDropdown value={statusFilter} onChange={setStatusFilter} />
      <MultiDropdown label="Resume" options={RESUME_VERSIONS} value={resumeFilter} onChange={setResumeFilter} />
      <MultiDropdown label="Source" options={allSources} value={sourceFilter} onChange={setSourceFilter} />
      <MultiDropdown label="Applied Via" options={allAppliedThrough} value={appliedFilter} onChange={setAppliedFilter} />
      <ExtrasDropdown value={extrasFilter} onChange={setExtrasFilter} />
      <div style={{ display:"flex", alignItems:"center", gap:8 }}>
        <span style={{ fontSize:10, color:"var(--text-muted)", whiteSpace:"nowrap" }}>Rating ≥</span>
        <input type="range" min={0} max={100} step={5} value={ratingMin} onChange={e=>setRatingMin(Number(e.target.value))} style={{ width:80, cursor:"pointer" }} />
        <span style={{ fontSize:10, fontWeight:700, minWidth:22, textAlign:"center", color:ratingMin>=80?"#4ade80":ratingMin>=65?"#d97706":"var(--text-muted)" }}>
          {ratingMin > 0 ? ratingMin : "All"}
        </span>
        <button onClick={()=>setRatingMin(0)} style={{ background:"none", border:"none", color:"var(--text-muted)", fontSize:12, padding:0, lineHeight:1, opacity:ratingMin>0?1:0, cursor:ratingMin>0?"pointer":"default", pointerEvents:ratingMin>0?"auto":"none" }}>✕</button>
      </div>
      <SalarySlider salaryMin={salaryMin} setSalaryMin={setSalaryMin} salaryType={salaryType} setSalaryType={setSalaryType} />
      <DateRangePicker dateFrom={dateFrom} setDateFrom={setDateFrom} dateTo={dateTo} setDateTo={setDateTo} />
      <button onClick={onResetAll} className="pill" style={{ fontSize:10, color:"var(--text-muted)", letterSpacing:0.5 }}>Clear all</button>
    </div>
  );
}

// ── Slim toolbar (search + filters toggle + grouped + sort) ───────────────────
function AppFilters({ search, setSearch, showFilters, setShowFilters, activeFilterCount, grouped, setGrouped, sort, setSort, resultCount, showCount }) {
  return (
    <div className="filters" style={{ flexWrap:"nowrap" }}>
      <div style={{ position:"relative", display:"inline-flex", alignItems:"center" }}>
        <input className="filter-search" placeholder="Search company or role…" value={search} onChange={e=>setSearch(e.target.value)} style={{ paddingRight: search ? 28 : 12 }} />
        {search && (
          <button onClick={()=>setSearch("")} style={{ position:"absolute", right:8, background:"none", border:"none", color:"var(--text-muted)", cursor:"pointer", fontSize:14, lineHeight:1, padding:0 }}>✕</button>
        )}
      </div>
      <button onClick={()=>setShowFilters(f=>!f)} className={`pill${activeFilterCount>0?" active":""}`} style={{ display:"flex", alignItems:"center", gap:6, minWidth:100 }}>
        <SlidersHorizontal size={12} />
        Filters
        <span style={{ minWidth:22, textAlign:"center", fontSize:10 }}>{activeFilterCount>0?`(${activeFilterCount})`:""}</span>
        <span style={{ fontSize:9, opacity:0.6 }}>{showFilters?"▲":"▼"}</span>
      </button>
      <button onClick={()=>setGrouped(g=>!g)} className={`pill${grouped?" active":""}`} style={{ minWidth:82 }}>
        {grouped ? "⊞ Grouped" : "≡ Flat"}
      </button>
      <select className="sort-select" value={sort} onChange={e=>setSort(e.target.value)}>
        <option value="date_desc">Date ↓</option>
        <option value="date_asc">Date ↑</option>
        <option value="rating_desc">Rating ↓</option>
      </select>
      <span style={{ fontSize:11, color:"var(--text-muted)", opacity:showCount?1:0, pointerEvents:"none", whiteSpace:"nowrap", transition:"opacity 0.15s" }}>
        Showing <strong style={{ color:"var(--text-primary)" }}>{resultCount ?? 0}</strong> result{resultCount!==1?"s":""}
      </span>
    </div>
  );
}

function AppTable({ apps, onRowClick, onStatusChange, grouped=true, sort="date_desc", hasFilters=false }) {
  const [collapsed, setCollapsed]   = useState({});
  const [ctxMenu, setCtxMenu]       = useState(null); // {appId, x, y}
  const toggleGroup = g => setCollapsed(c => ({...c, [g]: !c[g]}));

  // Close context menu on outside click
  useEffect(()=>{
    const h = () => setCtxMenu(null);
    window.addEventListener("click", h);
    return () => window.removeEventListener("click", h);
  }, []);

  if (!apps) return <div className="empty-state">Loading…</div>;
  if (apps.length === 0) return <div className="empty-state">{hasFilters ? "No applications match your filters." : "No applications yet. Click + Add to get started."}</div>;

  const GROUP_ORDER = ["active","pending","closed_pos","closed_rec","closed_no","closed_wdr"];
  const rows = [];
  if (grouped) {
    const groups = {};
    apps.forEach(a => {
      const g = STATUS_CONFIG[a.status]?.group || "pending";
      if (!groups[g]) groups[g] = [];
      groups[g].push(a);
    });
    GROUP_ORDER.forEach(g => {
      if (!groups[g]?.length) return;
      rows.push({ type:"header", g, count: groups[g].length });
      if (!collapsed[g]) groups[g].forEach(a => rows.push({ type:"row", a }));
    });
  } else {
    const sorted = [...apps].sort((a, b) => {
      if (sort === "date_asc")    return (a.date_applied || "").localeCompare(b.date_applied || "");
      if (sort === "rating_desc") return (b.rating || 0) - (a.rating || 0) || (b.date_applied || "").localeCompare(a.date_applied || "");
      return (b.date_applied || "").localeCompare(a.date_applied || ""); // date_desc default
    });
    sorted.forEach(a => rows.push({ type:"row", a }));
  }

  const displayCompany = a => {
    const co = (a.company || "").trim();
    const firm = (a.recruiting_firm || "").trim();
    if (co) return co;
    if (firm) return firm;
    return "—";
  };
  const displayFirmDot = a => a.via_recruiting_firm && a.recruiting_firm ? (
    <span title={`Via: ${a.recruiting_firm}`} style={{ display:"inline-flex", alignItems:"center", justifyContent:"center", width:16, height:16, marginLeft:2, verticalAlign:"middle", cursor:"help", flexShrink:0 }}>
      <span style={{ display:"inline-block", width:6, height:6, borderRadius:"50%", background:"#fbbf24", boxShadow:"0 0 4px #fbbf2480" }} />
    </span>
  ) : null;
  const displayOutreachDot = a => a.has_outreach ? (
    <span title={a.outreach_notes ? `Outreach: ${a.outreach_notes}` : "Outreach performed"} style={{ display:"inline-flex", alignItems:"center", justifyContent:"center", width:16, height:16, marginLeft:2, verticalAlign:"middle", cursor:"help", flexShrink:0 }}>
      <span style={{ display:"inline-block", width:6, height:6, borderRadius:"50%", background:"#3b82f6", boxShadow:"0 0 4px #3b82f680" }} />
    </span>
  ) : null;
  const displaySalary  = a => {
    const val = a.salary_listed || "";
    if (!val) return a.salary_requested ? `(req: ${a.salary_requested}${a.salary_type==="Hourly"?" /h":""})` : "";
    return a.salary_type === "Hourly" ? val + " /h ⏱" : val;
  };
  const salaryTooltip = a => {
    const parts = [];
    if (a.salary_listed)    parts.push(`Listed: ${a.salary_listed}${a.salary_type==="Hourly"?" /h":""}`);
    if (a.salary_requested) parts.push(`Requested: ${a.salary_requested}${a.salary_type==="Hourly"?" /h":""}`);
    return parts.join(" · ");
  };
  const displayLoc     = a => a.location_type === "Hybrid" ? `Hybrid${a.hybrid_location?` (${a.hybrid_location})`:""}` : "Remote";

  return (
    <>
    <div className="app-table-wrap">
      <table className="app-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Company</th>
            <th>Role</th>
            <th>Status</th>
            <th>Source</th>
            <th>Via</th>
            <th>Resume</th>
            <th>Rating</th>
            <th>Location</th>
            <th>Salary</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((r,i) => {
            if (r.type === "header") return (
              <tr key={`g-${r.g}`} className="group-header" onClick={()=>toggleGroup(r.g)} style={{ cursor:"pointer" }}>
                <td colSpan={10}>
                  <span style={{ display:"inline-block", width:8, height:8, borderRadius:"50%", background:GROUP_COLORS[r.g], boxShadow:`0 0 5px ${GROUP_COLORS[r.g]}80`, marginRight:8, verticalAlign:"middle" }} />
                  {GROUP_LABELS[r.g]}
                  <span style={{ marginLeft:8, fontSize:10, color:"var(--text-dim)" }}>({r.count})</span>
                  <span style={{ float:"right", fontSize:10, color:"var(--text-dim)" }}>{collapsed[r.g] ? "▶" : "▼"}</span>
                </td>
              </tr>
            );
            const a = r.a;
            return (
              <tr key={a.id} onClick={()=>onRowClick&&onRowClick(a)} onContextMenu={e=>{e.preventDefault();setCtxMenu({appId:a.id,status:a.status,x:e.clientX,y:e.clientY});}}>
                <td className="col-date">{a.date_applied}</td>
                <td className="col-company" title={displayCompany(a)}>{displayCompany(a)}{displayFirmDot(a)}</td>
                <td className="col-title"  title={a.job_title}>{a.job_title}</td>
                <td><StatusBadge status={a.status} /></td>
                <td>{a.source ? (() => {
                  const sc = SOURCE_COLORS[a.source];
                  return <span className="source-pill" style={sc?{background:sc.bg,color:sc.color,borderColor:sc.border}:{}}>{a.source}</span>;
                })() : "—"}</td>
                <td>{a.applied_through || "—"}{displayOutreachDot(a)}</td>
                <td>{a.resume_version || "—"}{a.cover_letter ? <span title="Cover letter added" style={{marginLeft:4,color:"var(--text-muted)",cursor:"help"}}><FileText size={13} /></span> : null}</td>
                <td className="col-rating"><span className={a.rating>=65&&a.rating<80?"rating-badge-mid":""} style={{ display:"inline-block", fontSize:10, fontWeight:700, color:rc(a.rating), background:rb(a.rating), padding:"1px 5px", borderRadius:3 }}>{a.rating ?? "—"}</span></td>
                <td>{displayLoc(a)}</td>
                <td className="col-salary" title={salaryTooltip(a)}>{displaySalary(a) || "—"}</td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>

    {/* Context menu — rendered via portal to avoid table DOM constraints */}
    {ctxMenu && ReactDOM.createPortal(
      <div
        style={{ position:"fixed", left:ctxMenu.x, top:ctxMenu.y, background:"var(--modal-bg)",
          border:"1px solid var(--border)", borderRadius:8, padding:"4px 0", zIndex:900,
          boxShadow:"0 8px 24px rgba(0,0,0,0.4)", minWidth:160 }}
        onClick={e=>e.stopPropagation()}>
        <div style={{ display:"flex",alignItems:"center",gap:7,padding:"6px 14px 8px",borderBottom:"1px solid var(--border)" }}>
          <span style={{ width:7,height:7,borderRadius:"50%",background:STATUS_DOT_COLORS[ctxMenu.status],flexShrink:0,display:"inline-block" }} />
          <span style={{ fontSize:11,color:"var(--text-primary)",fontWeight:600 }}>{ctxMenu.status}</span>
        </div>
        {(STATUS_TRANSITIONS[ctxMenu.status]||[]).length === 0
          ? <div style={{ fontSize:11,color:"var(--text-dim)",padding:"8px 14px" }}>No transitions available</div>
          : (STATUS_TRANSITIONS[ctxMenu.status]||[]).map(s=>(
          <button key={s}
            onClick={()=>{ onStatusChange&&onStatusChange(ctxMenu.appId, s); setCtxMenu(null); }}
            style={{ display:"flex", alignItems:"center", gap:8, width:"100%", background:"none",
              border:"none", color:"var(--text-secondary)", fontFamily:"inherit", fontSize:12,
              padding:"7px 14px", cursor:"pointer", textAlign:"left" }}
            onMouseEnter={e=>e.currentTarget.style.background="var(--table-row-hover)"}
            onMouseLeave={e=>e.currentTarget.style.background="none"}>
            <span style={{ width:7,height:7,borderRadius:"50%",background:STATUS_DOT_COLORS[s],flexShrink:0,display:"inline-block" }} />
            {s}
          </button>
        ))}
      </div>,
      document.body
    )}
    </>
  );
}

// ── Applications Tab ──────────────────────────────────────────────────────────
function ApplicationsTab({ isAuth, onOpenApp, refreshKey, onStatusChange }) {
  const [apps, setApps]               = useState(null);
  const [stats, setStats]             = useState(null);
  const [search, setSearch]           = useState("");
  const [statusFilter, setStatusF]    = useState([]);
  const [resumeFilter, setResumeF]    = useState([]);
  const [sort, setSort]               = useState("date_desc");
  const [appliedFilter, setAppliedF]  = useState([]);
  const [grouped, setGrouped]         = useState(true);
  const [ratingMin, setRatingMin]     = useState(0);
  const [dateFrom, setDateFrom]       = useState(null);
  const [dateTo, setDateTo]           = useState(null);
  const [sourceFilter, setSourceF]    = useState([]);
  const [allSources, setAllSources]   = useState([...SOURCES]);
  const [salaryMin, setSalaryMin]     = useState(0);
  const [salaryType, setSalaryType]   = useState('Yearly');
  const [allAppliedThrough, setAllAT] = useState([...APPLIED_THROUGH]);
  const [extrasFilter, setExtrasF]    = useState({ coverLetter:[], outreach:[], location:[] });
  const [showFilters, setShowFilters] = useState(false);

  const activeFilterCount =
    (statusFilter.length > 0 ? 1 : 0) +
    (resumeFilter.length > 0 ? 1 : 0) +
    (sourceFilter.length > 0 ? 1 : 0) +
    (appliedFilter.length > 0 ? 1 : 0) +
    (ratingMin > 0 ? 1 : 0) +
    (salaryMin > 0 ? 1 : 0) +
    ((dateFrom || dateTo) ? 1 : 0) +
    (extrasFilter.coverLetter.length === 1 ? 1 : 0) +
    (extrasFilter.outreach.length    === 1 ? 1 : 0) +
    (extrasFilter.location.length    === 1 ? 1 : 0);

  const onResetAll = () => {
    setStatusF([]); setResumeF([]); setSourceF([]); setAppliedF([]);
    setRatingMin(0); setSalaryMin(0); setDateFrom(null); setDateTo(null);
    setExtrasF({ coverLetter:[], outreach:[], location:[] });
  };

  useEffect(() => {
    api("stats").then(setStats).catch(console.error);
    // Load all distinct source + applied_through values from DB
    api("applications","GET",null,{sort:"date_desc"}).then(apps => {
      // Applied through
      const atVals = [...new Set((apps||[]).map(a=>a.applied_through).filter(Boolean))].sort();
      const merged = [...new Set([...APPLIED_THROUGH, ...atVals])].sort((a,b)=>{
        const pinned = ["LinkedIn Easy Apply","Indeed","Dice","Cybercoders","Workday","Email","Recruiting Firm Portal","Other/Unknown"];
        const ai = pinned.indexOf(a), bi = pinned.indexOf(b);
        if(ai>=0 && bi>=0) return ai-bi;
        if(ai>=0) return -1; if(bi>=0) return 1;
        return a.localeCompare(b);
      });
      setAllAT(merged);
      // Sources
      const srcVals = [...new Set((apps||[]).map(a=>a.source).filter(Boolean))].sort();
      const mergedSrc = [...new Set([...SOURCES, ...srcVals])].sort((a,b)=>a==="Other"?1:b==="Other"?-1:a.localeCompare(b));
      setAllSources(mergedSrc);
    }).catch(console.error);
  }, [refreshKey]);

  useEffect(() => {
    const params = { sort };
    if (search)              params.search = search;
    if (statusFilter.length) params.status = statusFilter.join(",");
    if (resumeFilter.length) params.resume = resumeFilter.join(",");
    if (appliedFilter.length) params.applied = appliedFilter.join(",");
    if (ratingMin > 0) params.rating_min = ratingMin;
    if (dateFrom) params.date_from = dateFrom;
    if (dateTo)   params.date_to   = dateTo;
    if (sourceFilter.length) params.source_filter = sourceFilter.join(',');
    if (salaryMin > 0) { params.salary_min = salaryMin; params.salary_type_filter = salaryType; }
    if (extrasFilter.coverLetter.length === 1) params.cover_letter_filter = extrasFilter.coverLetter[0];
    if (extrasFilter.outreach.length    === 1) params.outreach_filter     = extrasFilter.outreach[0];
    if (extrasFilter.location.length    === 1) params.location_filter     = extrasFilter.location[0];
    api("applications", "GET", null, params).then(setApps).catch(console.error);
  }, [search, statusFilter, resumeFilter, appliedFilter, ratingMin, dateFrom, dateTo, sourceFilter, salaryMin, salaryType, sort, extrasFilter, refreshKey]);

  return (
    <div>
      <AppStatsBar stats={stats} />
      <AppFilters
        search={search} setSearch={setSearch}
        showFilters={showFilters} setShowFilters={setShowFilters}
        activeFilterCount={activeFilterCount}
        grouped={grouped} setGrouped={setGrouped}
        sort={sort} setSort={setSort}
        resultCount={apps !== null ? apps.length : null}
        showCount={!!(apps !== null && (search || activeFilterCount > 0))}
      />
      {showFilters && (
        <FilterBar
          statusFilter={statusFilter} setStatusFilter={setStatusF}
          resumeFilter={resumeFilter} setResumeFilter={setResumeF}
          sourceFilter={sourceFilter} setSourceFilter={setSourceF}
          appliedFilter={appliedFilter} setAppliedFilter={setAppliedF}
          allSources={allSources} allAppliedThrough={allAppliedThrough}
          ratingMin={ratingMin} setRatingMin={setRatingMin}
          salaryMin={salaryMin} setSalaryMin={setSalaryMin}
          salaryType={salaryType} setSalaryType={setSalaryType}
          dateFrom={dateFrom} setDateFrom={setDateFrom}
          dateTo={dateTo} setDateTo={setDateTo}
          extrasFilter={extrasFilter} setExtrasFilter={setExtrasF}
          onResetAll={onResetAll}
        />
      )}
      <AppTable apps={apps} onRowClick={onOpenApp} onStatusChange={isAuth ? onStatusChange : null} grouped={grouped} sort={sort}
        hasFilters={!!(search || activeFilterCount > 0)} />
    </div>
  );
}

// ── Shared Apps View (public read-only, client-side stats, no auth) ──────────
function SharedAppsView({ allApps }) {
  const stats = useMemo(() => {
    const total = allApps.length;
    if (!total) return null;
    const active            = allApps.filter(a=>['Interviewing','Offer'].includes(a.status)).length;
    const reached_recruiter = allApps.filter(a=>a.date_recruiter).length;
    const ratings           = allApps.filter(a=>a.rating!=null).map(a=>a.rating);
    const avg_rating        = ratings.length ? Math.round(ratings.reduce((s,r)=>s+r,0)/ratings.length*10)/10 : null;
    const closed_positive   = allApps.filter(a=>a.status==='Accepted').length;
    const closed_reached    = allApps.filter(a=>['Rejected','Ghosted'].includes(a.status)).length;
    const closed_no_prog    = allApps.filter(a=>['Not Selected','No Answer','Withdrawn'].includes(a.status)).length;
    const todayStr          = localToday();
    const wkStart           = (()=>{ const d=new Date(); const day=d.getDay(); d.setDate(d.getDate()-(day===0?6:day-1)); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; })();
    const moStart           = todayStr.slice(0,7)+'-01';
    const this_week         = allApps.filter(a=>a.date_applied>=wkStart).length;
    const this_month        = allApps.filter(a=>a.date_applied>=moStart).length;
    const first             = allApps.reduce((m,a)=>(!m||a.date_applied<m)?a.date_applied:m, null);
    const msElapsed         = first ? Math.max(0, new Date()-new Date(first)) : 0;
    const avg_week          = msElapsed ? Math.round(total/Math.max(1,msElapsed/604800000)*10)/10 : null;
    const avg_month         = msElapsed ? Math.round(total/Math.max(1,msElapsed/2629800000)*10)/10 : null;
    return { total, active, reached_recruiter, reached_final_round:0, avg_rating, closed_positive, closed_reached, closed_no_prog, this_week, this_month, avg_week, avg_month };
  }, [allApps]);

  return (
    <div>
      <AppStatsBar stats={stats} />
      <AppTable apps={allApps} grouped={true} />
    </div>
  );
}

// ── Shared Timeline View (read-only, no auth, derives data from share API) ───
function SharedTimelineView({ allApps }) {
  const entries = useMemo(() =>
    allApps
      .filter(a => a.timeline_id)
      .map(a => ({
        id:             a.timeline_id,
        company:        (a.company && a.company.trim()) ? a.company : (a.recruiting_firm || ""),
        position:       a.job_title,
        rating:         a.rating,
        applied:        a.date_applied,
        recruiter:      a.date_recruiter,
        screening:      a.date_screening,
        rejected:       a.date_closed,
        offer_date:     a.offer_date || null,
        status:         a.status,
        pending:        !!a.pending,
        rounds:         (a.rounds || []).map(r => r.interview_date).filter(Boolean),
        rounds_full:    a.rounds || [],
        application_id: a.id,
      }))
  , [allApps]);

  const [hovered, setHov]   = useState(null);
  const [tipPos, setTipPos] = useState({x:0,y:0});
  const [sortDir, setSortDir] = useState("desc");

  if (!entries.length) return <div className="empty-state">No timeline entries in shared data.</div>;

  const sorted = [...entries].sort((a,b)=> sortDir==="asc" ? pd(a.applied)-pd(b.applied) : pd(b.applied)-pd(a.applied));
  const { toX, monthLabels, zones } = buildTimelineData(sorted);
  const hoveredItem = sorted.find(i=>i.id===hovered)||null;
  const todayStr = TODAY.toLocaleDateString("en-US",{month:"short",day:"numeric",year:"numeric"});

  return (
    <div>
      <TimelineStatsRow sorted={sorted} zones={zones} totalApps={allApps.length} />
      <div style={{ display:"flex", alignItems:"center", gap:12, marginBottom:6 }}>
        <span style={{ fontSize:11, color:"var(--text-dim)" }}>{todayStr}</span>
        <select className="sort-select" value={sortDir} onChange={e=>setSortDir(e.target.value)}>
          <option value="desc">Newest first</option>
          <option value="asc">Oldest first</option>
        </select>
      </div>
      <PipelineGrid sorted={sorted} hovered={hovered} setHov={setHov} tipPos={tipPos} setTipPos={setTipPos} zones={zones} toX={toX} monthLabels={monthLabels} onRowClick={null} onLinkClick={null} />
      {hoveredItem && <TimelineTooltip item={hoveredItem} tipPos={tipPos} />}
      <div style={{ marginTop:20 }}>
        <Legend showEditHint={false} />
      </div>
    </div>
  );
}

// ── Timeline Tab ──────────────────────────────────────────────────────────────
function TimelineTab({ isAuth, onRowClick, onLinkClick, refreshKey }) {
  const [entries, setEntries]   = useState([]);
  const [loading, setLoading]   = useState(true);
  const [hovered, setHov]       = useState(null);
  const [tipPos, setTipPos]     = useState({x:0,y:0});
  const [totalApps, setTotalApps] = useState(null);
  const [sortDir, setSortDir]   = useState("desc");

  useEffect(() => {
    api("stats").then(s => setTotalApps(s?.total ?? null)).catch(()=>{});
  }, [refreshKey]);

  useEffect(() => {
    setLoading(true);
    api("timeline").then(data => {
      const normalized = (data||[]).map(e => ({
        id:        e.id,
        company:   (e.company && e.company.trim()) ? e.company : (e.recruiting_firm || ""),
        position:  e.position,
        rating:    e.rating,
        applied:   e.date_applied,
        recruiter: e.date_recruiter,
        screening: e.date_screening,
        rejected:  e.date_closed,
        offer_date: e.offer_date||null,
        status:    e.status,
        pending:   !!e.pending,
        rounds:      (e.rounds_full||[]).map(r=>r.interview_date).filter(Boolean),
        rounds_full: e.rounds_full||[],
        application_id: e.application_id,
      }));
      setEntries(normalized);
      setLoading(false);
    }).catch(err => { console.error(err); setLoading(false); });
  }, [refreshKey]);

  if (loading) return <div className="empty-state">Loading timeline…</div>;
  if (!entries.length) return <div className="empty-state">No timeline entries yet.</div>;

  const sorted = [...entries].sort((a,b)=> sortDir==="asc" ? pd(a.applied)-pd(b.applied) : pd(b.applied)-pd(a.applied));
  const { toX, monthLabels, zones } = buildTimelineData(sorted);
  const hoveredItem = sorted.find(i=>i.id===hovered)||null;
  const todayStr = TODAY.toLocaleDateString("en-US",{month:"short",day:"numeric",year:"numeric"});

  return (
    <div>
      <TimelineStatsRow sorted={sorted} zones={zones} totalApps={totalApps} />
      <div style={{ display:"flex", alignItems:"center", gap:12, marginBottom:6 }}>
        <span style={{ fontSize:11, color:"var(--text-dim)" }}>{todayStr}</span>
        <select className="sort-select" value={sortDir} onChange={e=>setSortDir(e.target.value)}>
          <option value="desc">Newest first</option>
          <option value="asc">Oldest first</option>
        </select>
      </div>
      <PipelineGrid sorted={sorted} hovered={hovered} setHov={setHov} tipPos={tipPos} setTipPos={setTipPos} zones={zones} toX={toX} monthLabels={monthLabels} onRowClick={isAuth?onRowClick:null} onLinkClick={onLinkClick} />
      {hoveredItem && <TimelineTooltip item={hoveredItem} tipPos={tipPos} />}
      <div style={{ marginTop:20 }}>
        <Legend showEditHint={isAuth} />
      </div>
    </div>
  );
}