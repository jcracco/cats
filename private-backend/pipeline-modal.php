<?php /* _pipeline-modal.php — included by index.php. Never open directly. */ ?>
// ── LoginModal ────────────────────────────────────────────────────────────────
function LoginModal({ onSuccess, onClose, hint=null }) {
  const [user, setUser] = useState("");
  const [pass, setPass] = useState("");
  const [err,  setErr]  = useState("");
  const [busy, setBusy] = useState(false);

  const submit = async () => {
    if (!user||!pass) return;
    setBusy(true); setErr("");
    try {
      await api("login","POST",{username:user,password:pass});
      onSuccess();
    } catch { setErr("Invalid username or password."); }
    setBusy(false);
  };

  useEffect(()=>{
    const h=e=>{if(e.key==="Escape")onClose();};
    window.addEventListener("keydown",h); return()=>window.removeEventListener("keydown",h);
  },[]);

  return (
    <>
      <div className="modal-backdrop" onClick={onClose} style={{ position:"fixed",inset:0,background:"rgba(0,0,0,0.6)",zIndex:500 }} />
      <div className="login-modal" style={{ zIndex:501 }}>
        <div style={{ fontSize:10,letterSpacing:5,color:"var(--eyebrow)",textTransform:"uppercase",fontWeight:600,marginBottom:8 }}>CATS</div>
        <h2 style={{ fontSize:20,fontWeight:700,color:"var(--text-primary)",marginBottom:16 }}>Admin Login</h2>
        {hint}
        <FormField label="Username">
          <input className="form-input" value={user} onChange={e=>setUser(e.target.value)} onKeyDown={e=>e.key==="Enter"&&submit()} autoFocus />
        </FormField>
        <FormField label="Password">
          <input className="form-input" type="password" value={pass} onChange={e=>setPass(e.target.value)} onKeyDown={e=>e.key==="Enter"&&submit()} />
        </FormField>
        {err && <div style={{ fontSize:11,color:"#f87171",marginBottom:10 }}>{err}</div>}
        <div className="modal-actions">
          <button className="btn-primary" style={{ flex:1,padding:"10px 0",fontSize:13 }} onClick={submit} disabled={busy}>
            {busy?"Checking…":"Login"}
          </button>
          <button className="btn-secondary" onClick={onClose}>Cancel</button>
        </div>
      </div>
    </>
  );
}

// ── DeleteConfirm ─────────────────────────────────────────────────────────────
function DeleteConfirm({ label, onConfirm, onCancel }) {
  const [val, setVal] = useState("");
  return (
    <>
      <div className="modal-backdrop" onClick={onCancel} style={{ position:"fixed",inset:0,background:"rgba(0,0,0,0.7)",zIndex:600 }} />
      <div style={{ position:"fixed",top:"50%",left:"50%",transform:"translate(-50%,-50%)",width:340,background:"var(--modal-bg)",border:"1px solid rgba(239,68,68,0.3)",borderRadius:12,padding:28,zIndex:601,boxShadow:"0 24px 80px rgba(0,0,0,0.8)" }}>
        <h3 style={{ color:"#f87171",fontSize:15,marginBottom:8 }}>Delete {label}?</h3>
        <p style={{ fontSize:12,color:"var(--text-muted)",marginBottom:16,lineHeight:1.6 }}>
          This will permanently delete this entry and all linked data. Type <strong style={{ color:"var(--text-primary)" }}>DELETE</strong> to confirm.
        </p>
        <input className="form-input" value={val} onChange={e=>setVal(e.target.value)} placeholder="Type DELETE" style={{ marginBottom:14 }} />
        <div className="modal-actions">
          <button className="btn-danger" style={{ flex:1 }} disabled={val!=="DELETE"} onClick={onConfirm}>Delete</button>
          <button className="btn-secondary" onClick={onCancel}>Cancel</button>
        </div>
      </div>
    </>
  );
}

// ── AppModal ──────────────────────────────────────────────────────────────────
// defaultTab: "info" | "interview"
function AppModal({ appId, isAuth, onClose, onSaved, onDeleted, defaultTab="info" }) {
  const isNew = appId === null;
  const [tab, setTab]         = useState(defaultTab);
  const [editing, setEditing] = useState(isNew);
  const [busy, setBusy]       = useState(false);
  const [saving, setSaving]   = useState(false);
  const [delConf, setDelConf] = useState(false);
  const [showJD, setShowJD]   = useState(isNew);
  const [copiedJD, setCopiedJD] = useState(false);

  const emptyForm = () => ({
    date_applied: localToday(),
    company:"", via_recruiting_firm:false, recruiting_firm:"",
    job_title:"", location_type:"Remote", hybrid_location:"", days_onsite:"",
    source:"", applied_through:"", resume_version:"", rating:"",
    status:"Applied", job_id:"", job_link:"", dashboard_link:"",
    salary_requested:"", salary_listed:"", salary_type:"Yearly",
    contacts:"", notes:"", job_description:"",
    cover_letter:false, has_outreach:false, outreach_notes:"",
  });
  const [form, setForm] = useState(emptyForm());
  const sf = (k,v) => setForm(f=>({...f,[k]:v}));

const STATUS_TO_TL = {
    "Applied":"pending", "Interviewing":"pending", "Offer":"pending",
    "Accepted":"rejected", "Not Selected":"rejected", "No Answer":"rejected",
    "Rejected":"rejected", "Withdrawn":"rejected", "Ghosted":"ghosted",
  };
  const TL_TO_STATUS = { "pending":"Interviewing", "ghosted":"Ghosted", "rejected":"Rejected" };

  const setStatus = s => {
    sf("status", s);
    if (timelineId) {
      const newTl = STATUS_TO_TL[s] || "pending";
      setTlStatus(newTl);
      if (newTl === "rejected") { if (!closedDate) setClosedDate(localToday()); }
      else setClosedDate("");
    }
    if (s === "Offer" && !offerDate) { setOfferDate(localToday()); setShowOffer(true); }
  };
  // outcome: "pending" | "Ghosted" | "Rejected" | "Withdrawn" | "Accepted"
  const setTlStatusSync = outcome => {
    if (outcome === "Ghosted") {
      setTlStatus("ghosted"); setClosedDate("");
      sf("status", "Ghosted");
    } else if (outcome === "pending") {
      setTlStatus("pending"); setClosedDate("");
      if (!["Offer","Accepted"].includes(form.status)) sf("status", "Interviewing");
    } else {
      setTlStatus("rejected");
      if (!closedDate) setClosedDate(localToday());
      sf("status", outcome);
    }
  };

  // Timeline state
  const [stages, setStages]     = useState([]);
  const [timelineId, setTlId]   = useState(null);
  // Timeline close/reopen/ghosted state — stored on the timeline entry
  const [tlStatus, setTlStatus]     = useState("pending"); // "pending" | "rejected" | "ghosted"
  const [closedDate, setClosedDate] = useState("");
  const [offerDate, setOfferDate]   = useState("");
  const [offerNotes, setOfferNotes] = useState("");
  const [showOffer, setShowOffer]   = useState(false);

  useEffect(()=>{
    if (isNew) return;
    setBusy(true);
    api("application","GET",null,{id:appId}).then(d=>{
      const a = d.application;
      setForm({
        date_applied:a.date_applied||"", company:a.company||"",
        via_recruiting_firm:!!a.via_recruiting_firm, recruiting_firm:a.recruiting_firm||"",
        job_title:a.job_title||"", location_type:a.location_type||"Remote",
        hybrid_location:a.hybrid_location||"", days_onsite:a.days_onsite||"",
        source:a.source||"", applied_through:a.applied_through||"",
        resume_version:a.resume_version||"", rating:a.rating??'',
        status:a.status||"Applied", job_id:a.job_id||"",
        job_link:a.job_link||"", dashboard_link:a.dashboard_link||"",
        salary_requested:a.salary_requested||"", salary_listed:a.salary_listed||"",
        salary_type:a.salary_type||"Yearly", contacts:a.contacts||"",
        notes:a.notes||"", job_description:a.job_description||"",
        cover_letter:!!a.cover_letter, has_outreach:!!a.has_outreach, outreach_notes:a.outreach_notes||"",
      });
      if (d.timeline) {
        const t = d.timeline;
        setTlId(t.id);
        // Determine timeline status
        if (t.date_closed) { setTlStatus("rejected"); setClosedDate(t.date_closed); }
        else if (!t.pending) { setTlStatus("ghosted"); }
        else                 { setTlStatus("pending"); }
        if (t.offer_date)  { setOfferDate(t.offer_date); setShowOffer(true); }
        if (t.offer_notes) setOfferNotes(t.offer_notes);
        setStages([
          { _type:"screening",
            date_recruiter:t.date_recruiter||"", recruiter_name:t.recruiter_name||"",
            date_screening:t.date_screening||"", screener_name:t.screener_name||"",
            screening_type:t.screening_type||"" },
          ...(d.rounds||[]).map(r=>({
            _type:"round", id:r.id, round_order:r.round_order,
            interview_date:r.interview_date||"", interview_type:r.interview_type||"",
            interviewer:r.interviewer||"", notes:r.notes||"",
            is_final_round:!!r.is_final_round,
          }))
        ]);
      }
      setBusy(false);
    }).catch(()=>setBusy(false));
  },[appId]);

  useEffect(()=>{
    const h=e=>{ if(e.key==="Escape"&&!delConf) onClose(); };
    window.addEventListener("keydown",h); return()=>window.removeEventListener("keydown",h);
  },[delConf]);

  const save = async () => {
    setSaving(true);
    try {
      const body = {...form, via_recruiting_firm:form.via_recruiting_firm?1:0, cover_letter:form.cover_letter?1:0, has_outreach:form.has_outreach?1:0};
      if (isNew) {
        const res = await api("application_add","POST",body);
        onSaved&&onSaved(res.id);
      } else {
        await api("application_update","POST",body,{id:appId});
        // Save timeline if it exists
        if (timelineId && stages.length>0 && stages[0]._type==="screening") {
          const s = stages[0];
          const isPending  = tlStatus==="pending";
          const isGhosted  = tlStatus==="ghosted";
          const isRejected = tlStatus==="rejected";
          await api("timeline_update","POST",{
            company:   form.via_recruiting_firm ? form.recruiting_firm : form.company,
            position:  form.job_title,
            rating:    form.rating||null,
            date_applied:   form.date_applied,
            date_recruiter: s.date_recruiter||null,
            recruiter_name: s.recruiter_name||null,
            date_screening: s.date_screening||null,
            screener_name:  s.screener_name||null,
            screening_type: s.screening_type||null,
            pending:        isPending ? 1 : 0,
            date_closed:    isRejected ? (closedDate||null) : null,
            offer_date:     offerDate||null,
            offer_notes:    offerNotes||null,
          },{id:timelineId});
          // Save rounds
          for (const r of stages.filter(x=>x._type==="round")) {
            if (r.interview_date) {
              await api("round_save","POST",{
                timeline_id:    timelineId,
                round_order:    r.round_order,
                interview_date: r.interview_date,
                interview_type: r.interview_type||null,
                interviewer:    r.interviewer||null,
                notes:          r.notes||null,
                is_final_round: r.is_final_round||false,
              });
            }
          }
        }
        onSaved&&onSaved(appId);
      }
    } catch(e) { alert("Save failed: "+e.message); }
    setSaving(false);
  };

  const doDelete = async () => {
    await api("application_delete","POST",null,{id:appId});
    onDeleted&&onDeleted();
  };

  const addRound = () => {
    const maxOrder = Math.max(0,...stages.filter(s=>s._type==="round").map(s=>s.round_order));
    setStages(s=>[...s,{_type:"round",round_order:maxOrder+1,interview_date:"",interview_type:"",interviewer:"",notes:"",is_final_round:false}]);
  };
  const updateStage = (i,k,v) => setStages(s=>{ const n=[...s]; n[i]={...n[i],[k]:v}; return n; });
  const hasTl = !!timelineId;

  if (busy) return (
    <>
      <div className="modal-backdrop" onClick={onClose} style={{ position:"fixed",inset:0,background:"rgba(0,0,0,0.6)",zIndex:500 }} />
      <div className="app-modal modal-fixed-height" style={{ display:"flex",alignItems:"center",justifyContent:"center" }}>
        <span style={{ color:"var(--text-muted)",fontSize:12,letterSpacing:2 }}>LOADING…</span>
      </div>
    </>
  );

  return (
    <>
      <div className="modal-backdrop" onClick={onClose} style={{ position:"fixed",inset:0,background:"rgba(0,0,0,0.6)",zIndex:500 }} />
      <div className="app-modal modal-fixed-height">

        {/* Header — fixed, never scrolls */}
        <div className="modal-header">
          <div>
            <h2 style={{ fontSize:16,fontWeight:700,color:"var(--text-primary)",marginBottom:4 }}>
              {isNew ? "Add Application" : (editing ? "Edit Application" : (form.company||"Application"))}
            </h2>
            {!isNew && !editing && <StatusBadge status={form.status} />}
          </div>
          <div style={{ display:"flex",gap:8,alignItems:"center" }}>
            {isAuth && !isNew && !editing && <button className="btn-secondary" style={{ fontSize:11,padding:"5px 10px",letterSpacing:0.5 }} onClick={()=>setEditing(true)}>Edit</button>}
            {isAuth && !isNew && <button className="btn-danger" style={{ fontSize:11,padding:"5px 10px",letterSpacing:0.5 }} onClick={()=>setDelConf(true)}>Delete</button>}
            <button onClick={onClose} style={{ background:"none",border:"none",color:"var(--text-muted)",fontSize:20,cursor:"pointer",lineHeight:1,padding:"0 4px" }}>✕</button>
          </div>
        </div>

        {/* Status picker — fixed, above tabs, edit mode only */}
        {editing && !isNew && (
          <div style={{ padding:"0 28px 14px", borderBottom:"1px solid var(--border)" }}>
            <div style={{ display:"flex", flexWrap:"wrap", gap:7, alignItems:"center" }}>
              {[form.status, ...(STATUS_TRANSITIONS[form.status]||[])].map(s => {
                const cfg = STATUS_CONFIG[s];
                const active = form.status === s;
                return (
                  <button key={s} onClick={()=>{ if(!active) setStatus(s); }}
                    className={`badge ${cfg.cls}`}
                    style={{ cursor:active?"default":"pointer", fontFamily:"inherit", opacity:active?1:0.55, boxShadow:active?"0 0 0 2px currentColor":"none", transform:active?"scale(1.05)":"scale(1)", transition:"all 0.12s" }}>
                    {cfg.label}
                  </button>
                );
              })}
            </div>
            {form.status==="Interviewing" && !hasTl && (
              <div style={{ fontSize:10,color:"#34d399",marginTop:8 }}>→ A timeline entry will be created automatically on save</div>
            )}
          </div>
        )}

        {/* Tabs — fixed */}
        <div className="modal-tabs">
          <button className={`modal-tab-btn ${tab==="info"?"active":""}`} onClick={()=>setTab("info")}>Application Info</button>
          <button className={`modal-tab-btn ${tab==="interview"?"active":""}`} onClick={()=>setTab("interview")}>Interview Info</button>
        </div>

        {/* Scrollable content area */}
        <div className="modal-body">

          {/* ── Application Info Tab ──────────────────────────────────────── */}
          {tab==="info" && (
            <div>
              {(editing||isNew) && (
                <label className="form-checkbox-label">
                  <input type="checkbox" checked={form.via_recruiting_firm} onChange={e=>sf("via_recruiting_firm",e.target.checked)} />
                  Applied through a recruiting firm
                </label>
              )}
              {form.via_recruiting_firm && (editing||isNew) && (
                <FormField label="Recruiting Firm *">
                  <input className="form-input" value={form.recruiting_firm} onChange={e=>sf("recruiting_firm",e.target.value)} />
                </FormField>
              )}

              <div className="form-row">
                <FormField label={form.via_recruiting_firm?"Company (optional)":"Company *"}>
                  {editing||isNew
                    ? <input className="form-input" value={form.company} onChange={e=>sf("company",e.target.value)} />
                    : <span style={{ fontSize:13,color:"var(--text-primary)",fontWeight:600 }}>{form.company||"—"}{form.via_recruiting_firm&&form.recruiting_firm&&<span style={{ fontSize:11,color:"var(--text-muted)",marginLeft:6 }}>via {form.recruiting_firm}</span>}</span>}
                </FormField>
                <FormField label="Job Title *">
                  {editing||isNew
                    ? <input className="form-input" value={form.job_title} onChange={e=>sf("job_title",e.target.value)} />
                    : <span style={{ fontSize:13,color:"var(--text-active)" }}>{form.job_title}</span>}
                </FormField>
              </div>

              <div className="form-row">
                <FormField label="Date Applied *">
                  {editing||isNew
                    ? <input className="form-input" type="date" value={form.date_applied} onChange={e=>sf("date_applied",e.target.value)} />
                    : <span style={{ fontSize:13,color:"var(--text-secondary)" }}>{form.date_applied}</span>}
                </FormField>
                <FormField label="Rating (0–100) *">
                  {editing||isNew
                    ? <input className="form-input" type="number" min={0} max={100} value={form.rating} onChange={e=>sf("rating",e.target.value)} />
                    : (form.rating !== '' && form.rating !== null && form.rating !== undefined
                        ? <span style={{ display:"inline-block",fontSize:13,fontWeight:700,color:rc(form.rating),background:rb(form.rating),padding:"2px 10px",borderRadius:4 }}>{form.rating}</span>
                        : <span style={{ fontSize:13,color:"var(--text-secondary)" }}>—</span>)}
                </FormField>
              </div>

              <div className="form-row">
                <FormField label="Source *">
                  {editing||isNew ? (
                    <div>
                      <select className="form-select"
                        value={SOURCES.includes(form.source) ? form.source : (form.source ? "__custom_src__" : "")}
                        onChange={e=>{
                          if (e.target.value === "__custom_src__") { sf("source",""); setForm(f=>({...f,_customSrc:true})); }
                          else { sf("source", e.target.value); setForm(f=>({...f,_customSrc:false})); }
                        }}>
                        <option value="">Select…</option>
                        {SOURCES.map(s=><option key={s} value={s}>{s}</option>)}
                        <option value="__custom_src__">Other (type below)…</option>
                      </select>
                      {(form._customSrc || (!SOURCES.includes(form.source) && form.source !== "")) && (
                        <input className="form-input" style={{ marginTop:6 }} value={form.source} onChange={e=>sf("source",e.target.value)} placeholder="Enter source name" autoFocus />
                      )}
                    </div>
                  ) : form.source ? (() => {
                      const sc = SOURCE_COLORS[form.source];
                      return <span className="source-pill" style={sc?{background:sc.bg,color:sc.color,borderColor:sc.border}:{}}>{form.source}</span>;
                    })() : <span style={{ fontSize:13,color:"var(--text-secondary)" }}>—</span>}
                </FormField>
                <FormField label="Applied Through *">
                  {editing||isNew ? (
                    <div>
                      <select className="form-select"
                        value={APPLIED_THROUGH.includes(form.applied_through) ? form.applied_through : (form.applied_through ? "__custom__" : "")}
                        onChange={e=>{
                          if (e.target.value === "__custom__") { sf("applied_through",""); setForm(f=>({...f,_customAts:true})); }
                          else { sf("applied_through", e.target.value); setForm(f=>({...f,_customAts:false})); }
                        }}>
                        <option value="">Select…</option>
                        {APPLIED_THROUGH.map(a=><option key={a} value={a}>{a}</option>)}
                        <option value="__custom__">Other (type below)…</option>
                      </select>
                      {(form._customAts || (!APPLIED_THROUGH.includes(form.applied_through) && form.applied_through !== "")) && (
                        <input className="form-input" style={{ marginTop:6 }} value={form.applied_through} onChange={e=>sf("applied_through",e.target.value)} placeholder="Enter ATS or platform name" autoFocus />
                      )}
                    </div>
                  ) : <span style={{ fontSize:13,color:"var(--text-secondary)" }}>{form.applied_through||"—"}</span>}
                </FormField>
              </div>

              <FormField label="Location *">
                {editing||isNew ? (
                  <div>
                    <div className="form-radio-group" style={{ marginBottom:8 }}>
                      {["Remote","Hybrid"].map(v=>(
                        <label key={v} className="form-radio-label">
                          <input type="radio" checked={form.location_type===v} onChange={()=>sf("location_type",v)} /> {v}
                        </label>
                      ))}
                    </div>
                    {form.location_type==="Hybrid" && (
                      <div className="form-row">
                        <input className="form-input" placeholder="Location" value={form.hybrid_location} onChange={e=>sf("hybrid_location",e.target.value)} />
                        <input className="form-input" placeholder="Days onsite (optional)" value={form.days_onsite} onChange={e=>sf("days_onsite",e.target.value)} />
                      </div>
                    )}
                  </div>
                ) : (
                  <span style={{ fontSize:13,color:"var(--text-secondary)" }}>
                    {form.location_type}{form.hybrid_location?` — ${form.hybrid_location}`:""}
                    {form.days_onsite?` (${form.days_onsite} days)`:""}
                  </span>
                )}
              </FormField>


              {/* Compensation */}
              <div className="modal-section">
                <div className="modal-section-title">Compensation</div>
                <div className="form-row-3">
                  <FormField label="Salary Requested">
                    {editing||isNew ? <input className="form-input" value={form.salary_requested} onChange={e=>sf("salary_requested",e.target.value)} placeholder="e.g. 130000" /> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{form.salary_requested||"—"}</span>}
                  </FormField>
                  <FormField label="Salary Listed">
                    {editing||isNew ? <input className="form-input" value={form.salary_listed} onChange={e=>sf("salary_listed",e.target.value)} placeholder="e.g. 120–140K" /> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{form.salary_listed||"—"}</span>}
                  </FormField>
                  <FormField label="Type">
                    {editing||isNew
                      ? <select className="form-select" value={form.salary_type} onChange={e=>sf("salary_type",e.target.value)}><option>Yearly</option><option>Hourly</option></select>
                      : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{form.salary_type}</span>}
                  </FormField>
                </div>
              </div>

              {/* Cover letter & Outreach */}
              {(editing||isNew) && (
                <div style={{ marginBottom:12 }}>
                  <label className="form-checkbox-label">
                    <input type="checkbox" checked={form.cover_letter} onChange={e=>sf("cover_letter",e.target.checked)} />
                    Cover letter included
                  </label>
                  <label className="form-checkbox-label" style={{ marginTop:6 }}>
                    <input type="checkbox" checked={form.has_outreach} onChange={e=>{ sf("has_outreach",e.target.checked); if(!e.target.checked) sf("outreach_notes",""); }} />
                    Outreach performed
                  </label>
                  {form.has_outreach && (
                    <input className="form-input" style={{ marginTop:6 }} value={form.outreach_notes} onChange={e=>sf("outreach_notes",e.target.value)} placeholder="Name(s) and channel — e.g. Jane Smith via LinkedIn" />
                  )}
                </div>
              )}
              {!editing && !isNew && (form.cover_letter || form.has_outreach) && (
                <div style={{ marginBottom:12,fontSize:12,color:"var(--text-secondary)" }}>
                  {form.cover_letter && <div>✓ Cover letter included</div>}
                  {form.has_outreach && <div>✓ Outreach{form.outreach_notes ? ` — ${form.outreach_notes}` : ""}</div>}
                </div>
              )}

              {/* Details */}
              <div className="modal-section">
                <div className="modal-section-title">Details</div>
                <div className="form-row">
                  <FormField label="Resume Version">
                    {editing||isNew ? (
                      <div>
                        <select className="form-select"
                          value={RESUME_VERSIONS.includes(form.resume_version) ? form.resume_version : (form.resume_version ? "__custom_res__" : "")}
                          onChange={e=>{
                            if (e.target.value === "__custom_res__") { sf("resume_version",""); setForm(f=>({...f,_customRes:true})); }
                            else { sf("resume_version", e.target.value); setForm(f=>({...f,_customRes:false})); }
                          }}>
                          <option value="">—</option>
                          {RESUME_VERSIONS.map(r=><option key={r} value={r}>{r}</option>)}
                          <option value="__custom_res__">Other (type below)…</option>
                        </select>
                        {(form._customRes || (!RESUME_VERSIONS.includes(form.resume_version) && form.resume_version !== "")) && (
                          <input className="form-input" style={{ marginTop:6 }} value={form.resume_version} onChange={e=>sf("resume_version",e.target.value)} placeholder="e.g. TPM-Acme, SPO-v2…" autoFocus />
                        )}
                      </div>
                    ) : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{form.resume_version||"—"}</span>}
                  </FormField>
                  <FormField label="Job ID">
                    {editing||isNew ? <input className="form-input" value={form.job_id} onChange={e=>sf("job_id",e.target.value)} /> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{form.job_id||"—"}</span>}
                  </FormField>
                </div>
                <div className="form-row">
                  <FormField label="Job Link">
                    {editing||isNew
                      ? <input className="form-input" type="url" value={form.job_link} onChange={e=>sf("job_link",e.target.value)} />
                      : form.job_link ? <a href={form.job_link?.match(/^https?:\/\//) ? form.job_link : "#"} target="_blank" rel="noreferrer" style={{ fontSize:12,color:"#60a5fa" }}>Open ↗</a> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>—</span>}
                  </FormField>
                  <FormField label="Dashboard Link">
                    {editing||isNew
                      ? <input className="form-input" type="url" value={form.dashboard_link} onChange={e=>sf("dashboard_link",e.target.value)} />
                      : form.dashboard_link ? <a href={form.dashboard_link?.match(/^https?:\/\//) ? form.dashboard_link : "#"} target="_blank" rel="noreferrer" style={{ fontSize:12,color:"#60a5fa" }}>Open ↗</a> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>—</span>}
                  </FormField>
                </div>
                {isAuth && (
                  <FormField label="Contacts">
                    {editing||isNew ? <input className="form-input" value={form.contacts} onChange={e=>sf("contacts",e.target.value)} placeholder="Names and roles" /> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{form.contacts||"—"}</span>}
                  </FormField>
                )}
                {isAuth && (
                  <FormField label="Notes">
                    {editing||isNew ? <textarea className="form-textarea" value={form.notes} onChange={e=>sf("notes",e.target.value)} /> : <div style={{ fontSize:12,color:"var(--text-secondary)",lineHeight:1.6,whiteSpace:"pre-wrap" }}>{form.notes||"—"}</div>}
                  </FormField>
                )}
                {/* Job Description — collapsed by default, toggle to show */}
                {isAuth && (
                  <div>
                    <div style={{ display:"flex", gap:8, alignItems:"center", marginBottom:8 }}>
                      <button className="btn-ghost" style={{ fontSize:11 }} onClick={()=>setShowJD(v=>!v)}>
                        {showJD ? "▲ Hide Job Description" : "▼ Show Job Description"}
                      </button>
                      {form.job_description && (
                        <button className="btn-ghost" style={{ fontSize:11 }} onClick={()=>{
                          navigator.clipboard.writeText(form.job_description)
                            .then(()=>{ setCopiedJD(true); setTimeout(()=>setCopiedJD(false), 2000); })
                            .catch(()=>{});
                        }}>
                          {copiedJD ? "✓ Copied!" : "⎘ Copy JD"}
                        </button>
                      )}
                    </div>
                    {showJD && (editing||isNew
                      ? <textarea className="form-textarea" style={{ minHeight:160 }} value={form.job_description} onChange={e=>sf("job_description",e.target.value)} placeholder="Paste full job description here…" />
                      : <div style={{ fontSize:12,color:"var(--text-secondary)",lineHeight:1.7,whiteSpace:"pre-wrap",background:"var(--pill-bg)",padding:"10px 12px",borderRadius:6,maxHeight:320,overflowY:"auto" }}>{form.job_description||"No job description saved."}</div>
                    )}
                  </div>
                )}
              </div>

            </div>
          )}

          {/* ── Interview Info Tab ────────────────────────────────────────── */}
          {tab==="interview" && (
            <div>
              {!hasTl && (
                <div style={{ padding:"20px 0",textAlign:"center" }}>
                  <div style={{ fontSize:12,color:"var(--text-muted)",marginBottom:8 }}>No interview information yet.</div>
                  {isAuth && <div style={{ fontSize:11,color:"var(--text-dim)" }}>Set status to Interviewing in Application Info to create a timeline entry.</div>}
                </div>
              )}

              {hasTl && (
                <>
                  {/* ── Stages ───────────────────────────────────────────── */}
                  {stages.map((s,i)=>(
                    <div key={i} className="stage-row">
                      <div className="stage-label">{s._type==="screening" ? "Screening" : `Round ${s.round_order}`}</div>
                      {s._type==="screening" ? (
                        <div>
                          <div className="form-row">
                            <FormField label="Recruiter Contact Date">
                              {editing ? <input className="form-input" type="date" value={s.date_recruiter} onChange={e=>updateStage(i,"date_recruiter",e.target.value)} /> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{s.date_recruiter||"—"}</span>}
                            </FormField>
                            {isAuth && (
                              <FormField label="Recruiter Name">
                                {editing ? <input className="form-input" value={s.recruiter_name} onChange={e=>updateStage(i,"recruiter_name",e.target.value)} /> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{s.recruiter_name||"—"}</span>}
                              </FormField>
                            )}
                          </div>
                          <div className="form-row">
                            <FormField label="Screening Date">
                              {editing ? <input className="form-input" type="date" value={s.date_screening} onChange={e=>updateStage(i,"date_screening",e.target.value)} /> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{s.date_screening||"—"}</span>}
                            </FormField>
                            <FormField label="Screening Type">
                              {editing
                                ? <select className="form-select" value={s.screening_type} onChange={e=>updateStage(i,"screening_type",e.target.value)}>
                                    <option value="">—</option>
                                    {INTERVIEW_TYPES.map(t=><option key={t} value={t}>{t}</option>)}
                                  </select>
                                : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{s.screening_type||"—"}</span>}
                            </FormField>
                            {isAuth && (
                              <FormField label="Screener Name">
                                {editing ? <input className="form-input" value={s.screener_name} onChange={e=>updateStage(i,"screener_name",e.target.value)} /> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{s.screener_name||"—"}</span>}
                              </FormField>
                            )}
                          </div>
                        </div>
                      ) : (
                        <div>
                          <div className="form-row">
                            <FormField label="Date">
                              {editing ? <input className="form-input" type="date" value={s.interview_date} onChange={e=>updateStage(i,"interview_date",e.target.value)} /> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{s.interview_date||"—"}</span>}
                            </FormField>
                            <FormField label="Type">
                              {editing
                                ? <select className="form-select" value={s.interview_type} onChange={e=>updateStage(i,"interview_type",e.target.value)}>
                                    <option value="">—</option>
                                    {INTERVIEW_TYPES.map(t=><option key={t} value={t}>{t}</option>)}
                                  </select>
                                : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{s.interview_type||"—"}</span>}
                            </FormField>
                          </div>
                          {isAuth && (
                            <FormField label="Interviewer(s)">
                              {editing ? <input className="form-input" value={s.interviewer} onChange={e=>updateStage(i,"interviewer",e.target.value)} placeholder="Names and titles" /> : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{s.interviewer||"—"}</span>}
                            </FormField>
                          )}
                          {isAuth && (
                            <FormField label="Notes">
                              {editing ? <textarea className="form-textarea" style={{ minHeight:60 }} value={s.notes} onChange={e=>updateStage(i,"notes",e.target.value)} /> : <div style={{ fontSize:12,color:"var(--text-secondary)",lineHeight:1.6 }}>{s.notes||"—"}</div>}
                            </FormField>
                          )}
                          {/* Final round checkbox — only on last round */}
                          {i === stages.length - 1 && (
                            <label className="form-checkbox-label" style={{ marginTop:6 }}>
                              <input type="checkbox"
                                checked={!!s.is_final_round}
                                onChange={e=>updateStage(i,"is_final_round",e.target.checked)}
                                disabled={!editing}
                              />
                              <span style={{ color: s.is_final_round ? "#fbbf24" : "var(--text-muted)", fontWeight: s.is_final_round ? 600 : 400 }}>
                                ★ Final Round
                              </span>
                            </label>
                          )}
                        </div>
                      )}
                    </div>
                  ))}

                  {isAuth && editing && <button className="btn-ghost" onClick={addRound}>+ Add Round</button>}

                  {/* ── Offer card ────────────────────────────────────────── */}
                  {isAuth && (showOffer || offerDate) && (
                    <div className="modal-section">
                      <div className="modal-section-title">Offer</div>
                      <FormField label="Offer Date">
                        {editing
                          ? <input className="form-input" type="date" value={offerDate} onChange={e=>{ setOfferDate(e.target.value); if(e.target.value) sf("status","Offer"); }} />
                          : <span style={{ fontSize:12,color:"var(--text-secondary)" }}>{offerDate||"—"}</span>}
                      </FormField>
                      <FormField label="Offer Notes">
                        {editing
                          ? <textarea className="form-textarea" value={offerNotes} onChange={e=>setOfferNotes(e.target.value)} placeholder="Compensation details, conditions…" />
                          : <div style={{ fontSize:12,color:"var(--text-secondary)",lineHeight:1.6,whiteSpace:"pre-wrap" }}>{offerNotes||"—"}</div>}
                      </FormField>
                    </div>
                  )}
                  {isAuth && editing && !showOffer && !offerDate && (
                    <button className="btn-ghost" style={{ marginTop:4 }} onClick={()=>{ setShowOffer(true); setOfferDate(localToday()); sf("status","Offer"); }}>+ Add Offer</button>
                  )}

                  {/* ── Process status ────────────────────────────────────── */}
                  {isAuth && (
                    <div className="modal-section">
                      <div className="modal-section-title">Process Status</div>
                      {(()=>{
                        const isClosed = tlStatus !== "pending";
                        const outcomes = [["Rejected","✕ Rejected","#f87171"],["Ghosted","◌ Ghosted","#a78bfa"],["Withdrawn","— Withdrawn","#94a3b8"],["Accepted","✓ Accepted","#4ade80"]];
                        return (<>
                          <div style={{ display:"flex",gap:8,marginBottom:isClosed?8:0 }}>
                            {[["pending","● Active","#3b82f6"],[null,"✕ Closed","#f87171"]].map(([k,l,c])=>{
                              const active = k==="pending" ? !isClosed : isClosed;
                              return (
                                <button key={l}
                                  onClick={()=>{ if(isAuth&&editing){ if(k==="pending") setTlStatusSync("pending"); else if(!isClosed) setTlStatusSync("Rejected"); } }}
                                  style={{ padding:"6px 14px",borderRadius:6,fontSize:11,cursor:editing?"pointer":"default",fontFamily:"inherit",
                                    border:`1px solid ${active?c:"var(--border)"}`,
                                    background:active?`${c}20`:"transparent",
                                    color:active?c:"var(--text-muted)",
                                    transition:"all 0.12s" }}>
                                  {l}
                                </button>
                              );
                            })}
                          </div>
                          {isClosed && (
                            <div style={{ display:"flex",gap:6,flexWrap:"wrap",marginBottom:10 }}>
                              {outcomes.map(([outcome,l,c])=>{
                                const active = outcome==="Ghosted" ? tlStatus==="ghosted" : (tlStatus==="rejected" && form.status===outcome);
                                return (
                                  <button key={outcome}
                                    onClick={()=>{ if(isAuth&&editing) setTlStatusSync(outcome); }}
                                    style={{ padding:"4px 10px",borderRadius:5,fontSize:11,cursor:editing?"pointer":"default",fontFamily:"inherit",
                                      border:`1px solid ${active?c:"var(--border)"}`,
                                      background:active?`${c}20`:"transparent",
                                      color:active?c:"var(--text-muted)",
                                      transition:"all 0.12s" }}>
                                    {l}
                                  </button>
                                );
                              })}
                            </div>
                          )}
                        </>);
                      })()}
                      {tlStatus==="rejected" && editing && (
                        <FormField label="Closed Date">
                          <input className="form-input" type="date" value={closedDate} onChange={e=>setClosedDate(e.target.value)} />
                        </FormField>
                      )}
                      {tlStatus==="rejected" && !editing && closedDate && (
                        <div style={{ fontSize:12,color:"var(--text-secondary)",marginTop:4 }}>Closed: {closedDate}</div>
                      )}
                      {tlStatus==="ghosted" && (
                        <div style={{ fontSize:11,color:"var(--text-muted)",marginTop:4 }}>
                          Ghosted dot will appear 14 days after last activity on timeline.
                        </div>
                      )}
                    </div>
                  )}

                </>
              )}
            </div>
          )}
        </div>{/* end modal-body */}

        {/* Sticky footer — save/cancel, visible in edit mode regardless of active tab */}
        {(editing || isNew) && (
          <div className="modal-footer">
            <button className="btn-primary" style={{ flex:1, padding:"10px 0", fontSize:13 }} onClick={save} disabled={saving}>
              {saving ? "Saving…" : (isNew ? "Add Application" : "Save Changes")}
            </button>
            {!isNew && <button className="btn-secondary" onClick={()=>setEditing(false)}>Cancel</button>}
          </div>
        )}
      </div>

      {delConf && <DeleteConfirm label="application" onConfirm={doDelete} onCancel={()=>setDelConf(false)} />}
    </>
  );
}

// ── TimelineModal — kept for direct timeline add (no linked application) ──────
function TimelineModal({ entryId, isNew, onClose, onSaved, onDeleted }) {
  const [form, setForm]     = useState({ date_recruiter:"", date_screening:"", pending:true, date_closed:"" });
  const [rounds, setRounds] = useState([{interview_date:"",interview_type:"",interviewer:"",notes:""}]);
  const [saving, setSaving] = useState(false);
  const [delConf, setDelConf] = useState(false);
  const sf = (k,v) => setForm(f=>({...f,[k]:v}));

  useEffect(()=>{
    if (isNew) { setForm(f=>({...f,date_applied:localToday()})); return; }
    api("timeline_entry","GET",null,{id:entryId}).then(e=>{
      setForm({ date_recruiter:e.date_recruiter||"",
        date_screening:e.date_screening||"", pending:!!e.pending, date_closed:e.date_closed||"" });
      setRounds((e.rounds||[]).map(r=>({id:r.id,round_order:r.round_order,interview_date:r.interview_date||"",interview_type:r.interview_type||"",interviewer:r.interviewer||"",notes:r.notes||""})));
    });
  },[entryId,isNew]);

  useEffect(()=>{
    const h=e=>{if(e.key==="Escape"&&!delConf)onClose();};
    window.addEventListener("keydown",h); return()=>window.removeEventListener("keydown",h);
  },[delConf]);

  const save = async () => {
    setSaving(true);
    try {
      const body = { pending:form.pending?1:0, date_closed:form.date_closed||null, date_recruiter:form.date_recruiter||null, date_screening:form.date_screening||null };
      let tid = entryId;
      if (isNew) { const r = await api("timeline_add","POST",body); tid=r.id; }
      else await api("timeline_update","POST",body,{id:entryId});
      for (let i=0;i<rounds.length;i++) {
        const r=rounds[i];
        if (r.interview_date) {
          await api("round_save","POST",{ timeline_id:tid, round_order:i+1, interview_date:r.interview_date, interview_type:r.interview_type||null, interviewer:r.interviewer||null, notes:r.notes||null });
        }
      }
      onSaved&&onSaved(tid);
    } catch(e) { alert("Save failed: "+e.message); }
    setSaving(false);
  };

  const doDelete = async () => {
    await api("timeline_delete","POST",null,{id:entryId});
    onDeleted&&onDeleted();
  };

  const ur = (i,k,v) => setRounds(r=>{ const n=[...r]; n[i]={...n[i],[k]:v}; return n; });

  return (
    <>
      <div className="modal-backdrop" onClick={onClose} style={{ position:"fixed",inset:0,background:"rgba(0,0,0,0.6)",zIndex:500 }} />
      <div className="app-modal">
        <div className="modal-header" style={{ flexShrink:0 }}>
          <h2 style={{ fontSize:16,fontWeight:700,color:"var(--text-primary)" }}>{isNew?"Add Timeline Entry":"Edit Timeline Entry"}</h2>
          <div style={{ display:"flex",gap:8 }}>
            {!isNew && <button className="btn-danger" style={{ fontSize:11,padding:"5px 10px",letterSpacing:0.5 }} onClick={()=>setDelConf(true)}>Delete</button>}
            <button onClick={onClose} style={{ background:"none",border:"none",color:"var(--text-muted)",fontSize:20,cursor:"pointer",lineHeight:1,padding:"0 4px" }}>✕</button>
          </div>
        </div>
        <div className="modal-body">
          <div style={{ fontSize:11,color:"var(--text-muted)",marginBottom:12 }}>Company, position, rating and date are managed in the Application Info tab.</div>
          <div className="form-row">
            <FormField label="Recruiter Contact"><input className="form-input" type="date" value={form.date_recruiter} onChange={e=>sf("date_recruiter",e.target.value)} /></FormField>
            <FormField label="Screening Date"><input className="form-input" type="date" value={form.date_screening} onChange={e=>sf("date_screening",e.target.value)} /></FormField>
          </div>
          <div className="modal-section">
            <div className="modal-section-title">Status</div>
            <div style={{ display:"flex",gap:10,marginBottom:12 }}>
              {[["pending","Active"],["closed","Closed"]].map(([k,l])=>(
                <button key={k} className={`pill ${(k==="pending"?form.pending:!form.pending)?"active":""}`} onClick={()=>sf("pending",k==="pending")}>{l}</button>
              ))}
            </div>
            {!form.pending && <FormField label="Closed Date"><input className="form-input" type="date" value={form.date_closed} onChange={e=>sf("date_closed",e.target.value)} /></FormField>}
          </div>
          <div className="modal-section">
            <div className="modal-section-title">Interview Rounds</div>
            {rounds.map((r,i)=>(
              <div key={i} className="stage-row">
                <div className="stage-label">Round {i+1}</div>
                <div className="form-row">
                  <FormField label="Date"><input className="form-input" type="date" value={r.interview_date} onChange={e=>ur(i,"interview_date",e.target.value)} /></FormField>
                  <FormField label="Type">
                    <select className="form-select" value={r.interview_type} onChange={e=>ur(i,"interview_type",e.target.value)}>
                      <option value="">—</option>
                      {INTERVIEW_TYPES.map(t=><option key={t} value={t}>{t}</option>)}
                    </select>
                  </FormField>
                </div>
                <FormField label="Interviewer(s)"><input className="form-input" value={r.interviewer} onChange={e=>ur(i,"interviewer",e.target.value)} /></FormField>
              </div>
            ))}
            <button className="btn-ghost" onClick={()=>setRounds(r=>[...r,{interview_date:"",interview_type:"",interviewer:"",notes:""}])}>+ Add Round</button>
          </div>
        </div>
        <div className="modal-footer">
          <button className="btn-primary" style={{ flex:1,padding:"10px 0",fontSize:13 }} onClick={save} disabled={saving}>{saving?"Saving…":(isNew?"Add Entry":"Save Changes")}</button>
          <button className="btn-secondary" onClick={onClose}>Cancel</button>
        </div>
      </div>
      {delConf && <DeleteConfirm label="timeline entry" onConfirm={doDelete} onCancel={()=>setDelConf(false)} />}
    </>
  );
}