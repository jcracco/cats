<?php
require_once __DIR__ . '/bootstrap.php';

$IS_DEMO = (
    isset($_SERVER['HTTP_HOST']) &&
    isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], IS_DEMO_DOMAIN) !== false
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $IS_DEMO ? 'CATS - Demo' : 'CATS'; ?></title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMiAzMiI+CiAgPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiByeD0iNyIgZmlsbD0iIzFlM2E1ZiIvPgogIDxyZWN0IHg9IjciIHk9IjUiIHdpZHRoPSIxOCIgaGVpZ2h0PSIyMiIgcng9IjIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzkzYzVmZCIgc3Ryb2tlLXdpZHRoPSIxLjUiLz4KICA8bGluZSB4MT0iMTIiIHkxPSIxMSIgeDI9IjIyIiB5Mj0iMTEiIHN0cm9rZT0iIzkzYzVmZCIgc3Ryb2tlLXdpZHRoPSIxIiBvcGFjaXR5PSIwLjYiLz4KICA8bGluZSB4MT0iMTIiIHkxPSIxNiIgeDI9IjIyIiB5Mj0iMTYiIHN0cm9rZT0iIzkzYzVmZCIgc3Ryb2tlLXdpZHRoPSIxIiBvcGFjaXR5PSIwLjYiLz4KICA8bGluZSB4MT0iMTIiIHkxPSIyMSIgeDI9IjIyIiB5Mj0iMjEiIHN0cm9rZT0iIzkzYzVmZCIgc3Ryb2tlLXdpZHRoPSIxIiBvcGFjaXR5PSIwLjYiLz4KICA8Y2lyY2xlIGN4PSIxMCIgY3k9IjExIiByPSIyLjIiIGZpbGw9IiNjMDg0ZmMiLz4KICA8Y2lyY2xlIGN4PSIxMCIgY3k9IjE2IiByPSIyLjIiIGZpbGw9IiNmYmJmMjQiLz4KICA8Y2lyY2xlIGN4PSIxMCIgY3k9IjIxIiByPSIyLjIiIGZpbGw9IiNmODcxNzEiLz4KPC9zdmc+" />
  <link rel="stylesheet" href="pipeline.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.production.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.production.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
  <script>window.IS_DEMO = <?php echo $IS_DEMO ? 'true' : 'false'; ?>;</script>
<?php if ($IS_DEMO): ?>
  <script src="mock-api.js"></script>
<?php endif; ?>
<?php if ($IS_DEMO && defined('UMAMI_ENABLED') && UMAMI_ENABLED): ?>
  <script>
    // Allows ?src to be tracked as ?utm_source in umami
		(function() {
		  const url = new URL(window.location.href);
		  const shortSrc = url.searchParams.get('src');
		  
		  if (shortSrc) {
			url.searchParams.set('utm_source', shortSrc);
			url.searchParams.delete('src');
			
			// Updates the address bar dynamically without a page refresh
			window.history.replaceState(null, '', url.pathname + url.search);
		  }
		})();
	</script>
  <script defer src="https://cloud.umami.is/script.js" data-website-id="<?php echo UMAMI_WEBSITE_ID; ?>"></script>
<?php endif; ?>
</head>
<body>

<?php if ($IS_DEMO): ?>
<div class="demo-banner">
    <span>⚠ <strong>Demo version</strong> — all data is fictional. Changes reset when you close the tab.</span>
    <a href="https://github.com/jcracco/" target="_blank" rel="noreferrer" class="demo-banner-link">View on GitHub ↗</a>
</div>
<?php endif; ?>

<div id="root"></div>
<script type="text/babel">
const { useState, useEffect, useRef, useCallback, useMemo } = React;

// ── Icon primitives ───────────────────────────────────────────────────────────
function LucideIcon({ size = 24, children }) {
  return (
    <svg xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24"
         fill="none" stroke="currentColor" strokeWidth="2"
         strokeLinecap="round" strokeLinejoin="round"
         style={{display:'inline-block',verticalAlign:'middle'}}>
      {children}
    </svg>
  );
}
const LogOut   = ({ size = 24 }) => <LucideIcon size={size}><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></LucideIcon>;
const Settings = ({ size = 24 }) => <LucideIcon size={size}><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></LucideIcon>;
const Sun      = ({ size = 24 }) => <LucideIcon size={size}><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></LucideIcon>;
const Moon     = ({ size = 24 }) => <LucideIcon size={size}><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></LucideIcon>;
const FileText = ({ size = 24 }) => <LucideIcon size={size}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 20 8 14 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></LucideIcon>;
const Download  = ({ size = 24 }) => <LucideIcon size={size}><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></LucideIcon>;
const Share2    = ({ size = 24 }) => <LucideIcon size={size}><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></LucideIcon>;
const UsersIcon = ({ size = 24 }) => <LucideIcon size={size}><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></LucideIcon>;

// ── ThemeToggle (fixed bottom-right) ──────────────────────────────────────────
function ThemeToggle({ theme, onToggle }) {
  const Icon = theme === 'dark' ? Sun : Moon;
  return (
    <button onClick={onToggle} title="Toggle theme"
      style={{ position:'fixed', bottom:24, right:24, zIndex:500,
        background:'var(--bg2)', border:'1px solid var(--border)',
        borderRadius:'50%', width:36, height:36, cursor:'pointer',
        display:'flex', alignItems:'center', justifyContent:'center',
        color:'var(--text-muted)', transition:'all 0.15s' }}>
      <Icon size={16} />
    </button>
  );
}

<?php include PRIVATE_PATH . 'pipeline-core.php'; ?>
<?php include PRIVATE_PATH . 'pipeline-modal.php'; ?>

function App() {
  const urlShareParam = new URLSearchParams(window.location.search).get('share');

  // ── Auth ──────────────────────────────────────────────────────────────────
  const [isAuth, setIsAuth]             = useState(false);
  const [authChecked, setChecked]       = useState(false);
  const [showLogin, setShowLogin]       = useState(false);
  const [myShareToken, setMyShareToken] = useState(null);
  const [username, setUsername]         = useState(null);
  const [isAdmin, setIsAdmin]           = useState(false);
  const [shareApps, setShareApps]       = useState(null); // null=loading, false=error, array=ok
  const [shareTab, setShareTab]         = useState('applications');
  const [showUserMenu, setShowUserMenu] = useState(false);
  const userMenuRef = useRef(null);

  const applySession = (d) => {
    setIsAuth(!!d.auth);
    setMyShareToken(d.share_token || null);
    setUsername(d.username || null);
    setIsAdmin(!!d.is_admin);
  };

  useEffect(()=>{
    if (urlShareParam) {
      api("share","GET",null,{token:urlShareParam,slim:1})
        .then(apps=>{ setShareApps(apps); setChecked(true); })
        .catch(()=>{ setShareApps(false); setChecked(true); });
      return;
    }
    if (window.IS_DEMO) {
      api("login","POST",{username:"demo",password:"demo"})
        .then(()=>{ setIsAuth(true); setChecked(true); })
        .catch(()=>{ setIsAuth(true); setChecked(true); });
    } else {
      api("session").then(d=>{ applySession(d); setChecked(true); }).catch(()=>setChecked(true));
    }
  },[]);

  // Close user menu on outside click
  useEffect(()=>{
    if (!showUserMenu) return;
    const handler = (e) => { if (userMenuRef.current && !userMenuRef.current.contains(e.target)) setShowUserMenu(false); };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, [showUserMenu]);

  const handleLogin = () => {
    setIsAuth(true); setShowLogin(false); refresh();
    api("session").then(d => applySession(d)).catch(()=>{});
  };
  const demoHint = window.IS_DEMO ? (
    <div style={{ background:"rgba(251,191,36,0.1)", border:"1px solid rgba(251,191,36,0.3)",
      borderRadius:8, padding:"8px 12px", marginBottom:16, fontSize:11, color:"#fbbf24", textAlign:"center" }}>
      Demo credentials: <strong>demo</strong> / <strong>demo</strong>
    </div>
  ) : null;
  const handleLogout = () => {
    api("logout","POST").then(()=>{ setIsAuth(false); setMyShareToken(null); setUsername(null); setIsAdmin(false); setShowUserMenu(false); refresh(); });
  };

  // ── Theme ─────────────────────────────────────────────────────────────────
  const [theme, setTheme] = useState(()=>localStorage.getItem("pipeline_theme")||"dark");
  useEffect(()=>{
    document.documentElement.setAttribute("data-theme",theme);
    localStorage.setItem("pipeline_theme",theme);
  },[theme]);
  const toggleTheme = () => setTheme(t=>t==="dark"?"light":"dark");

  // ── Tab ───────────────────────────────────────────────────────────────────
  const [tab, setTab] = useState(()=>{
    const h = window.location.hash.replace("#","");
    return h==="timeline" ? "timeline" : "applications";
  });
  useEffect(()=>{ window.location.hash = tab; },[tab]);
  useEffect(()=>{
    const h = ()=>{ const t=window.location.hash.replace("#",""); if(t==="timeline"||t==="applications") setTab(t); };
    window.addEventListener("hashchange",h); return()=>window.removeEventListener("hashchange",h);
  },[]);

  // ── Modals ────────────────────────────────────────────────────────────────
  const [appModal, setAppModal]   = useState(null); // null | {id: N|null}
  const [tlModal, setTlModal]     = useState(null);  // null | {id: N|null, isNew: bool}
  const [refreshKey, setRefresh]  = useState(0);
  const refresh = () => setRefresh(k=>k+1);
  const [showExport, setShowExport] = useState(false);
  const [showShare,  setShowShare]  = useState(false);

  const openApp  = (a) => setAppModal({id: a?.id ?? null});
  const openTl   = (e) => {
    // If timeline entry has a linked application, open AppModal on interview tab
    if (e?.application_id) { setAppModal({id: e.application_id, defaultTab:"interview"}); }
    else setTlModal({id: e?.id ?? null, isNew: !e?.id});
  };

  const onAppSaved   = ()=>{ setAppModal(null); refresh(); };
  const onAppDeleted = ()=>{ setAppModal(null); refresh(); };
  const onTlSaved    = ()=>{ setTlModal(null);  refresh(); };
  const onTlDeleted  = ()=>{ setTlModal(null);  refresh(); };

  // From timeline: clicking link indicator → open application modal
  const onLinkClick = (appId) => setAppModal({id:appId, defaultTab:"interview"});

  if (!authChecked) return (
    <div className="centered muted">LOADING…</div>
  );

  // ── Shared view (public read-only, no login required) ─────────────────────
  if (urlShareParam) {
    if (shareApps === false) return (
      <div className="centered muted">Invalid or expired share link.</div>
    );
    return (
      <div className="page">
        <div className="top-bar">
          <div className="top-bar-left">
            <div className="eyebrow">Candidate Application Tracking System</div>
            <h1>CATS</h1>
          </div>
          <div className="top-bar-right">
            <span style={{ fontSize:11, color:"var(--text-muted)", letterSpacing:1 }}>SHARED VIEW</span>
            <button title={theme==="dark"?"Switch to light mode":"Switch to dark mode"} onClick={toggleTheme}
              style={{ background:"none", border:"none", color:"var(--text-muted)", cursor:"pointer", padding:"5px 6px", display:"flex", alignItems:"center", borderRadius:6, transition:"color 0.12s" }}
              onMouseEnter={e=>e.currentTarget.style.color="var(--text-secondary)"}
              onMouseLeave={e=>e.currentTarget.style.color="var(--text-muted)"}>
              {theme==="dark" ? <Sun size={15} /> : <Moon size={15} />}
            </button>
          </div>
        </div>
        <div className="tabs">
          <button className={`tab-btn${shareTab==="applications"?" active":""}`} onClick={()=>setShareTab("applications")}>Applications</button>
          <button className={`tab-btn${shareTab==="timeline"?" active":""}`} onClick={()=>setShareTab("timeline")}>Timeline</button>
        </div>
        {shareTab==="applications" && <SharedAppsView allApps={shareApps} />}
        {shareTab==="timeline"     && <SharedTimelineView allApps={shareApps} />}
      </div>
    );
  }

  return (
    <div className="page">
      {/* Auth gate — on production, unauthenticated users see only the login prompt */}
      {!window.IS_DEMO && !isAuth && authChecked && (
        <div style={{ display:"flex", flexDirection:"column", alignItems:"center", justifyContent:"center", minHeight:"60vh", gap:16 }}>
          <h1>CATS</h1>
          <div class="eyebrow">Candidate Application Tracker</div>
          <p style={{ fontSize:13, color:"var(--text-muted)" }}>Sign in to access your pipeline.</p>
          <button className="btn-primary" style={{ padding:"10px 28px", fontSize:13 }} onClick={()=>setShowLogin(true)}>Login</button>
        </div>
      )}
      {/* On production, hide everything below until authenticated */}
      {(window.IS_DEMO || isAuth) && (<>

      {/* Top bar */}
      <div className="top-bar">
        <div className="top-bar-left">
          <div className="eyebrow">Candidate Application Tracking System</div>
          <h1>CATS</h1>
        </div>
        <div className="top-bar-right">
          {isAuth && (
            <button className="btn-primary" onClick={()=>{ if(tab==="applications") openApp(null); else openTl(null); }}>
              + Add
            </button>
          )}
          {isAuth && !window.IS_DEMO ? (
            <div className="user-menu-wrap" ref={userMenuRef}>
              <button className={`user-chip${showUserMenu?" open":""}`} onClick={()=>setShowUserMenu(v=>!v)}>
                {username || "me"}
              </button>
              {showUserMenu && (
                <div className="user-menu">
                  {isAdmin && (
                    <a className="user-menu-item" href="admin.php">
                      <UsersIcon size={14} /> User Management
                    </a>
                  )}
                  <button className="user-menu-item" onClick={()=>{ setShowExport(true); setShowUserMenu(false); }}>
                    <Download size={14} /> Export CSV
                  </button>
                  <button className="user-menu-item" onClick={()=>{ setShowShare(true); setShowUserMenu(false); }}>
                    <Share2 size={14} /> Share Pipeline
                  </button>
                  <div className="user-menu-divider" />
                  <button className="user-menu-item" onClick={()=>{ toggleTheme(); setShowUserMenu(false); }}>
                    {theme==="dark" ? <Sun size={14} /> : <Moon size={14} />}
                    {theme==="dark" ? "Light Mode" : "Dark Mode"}
                  </button>
                  <div className="user-menu-divider" />
                  <button className="user-menu-item danger" onClick={handleLogout}>
                    <LogOut size={14} /> Sign Out
                  </button>
                </div>
              )}
            </div>
          ) : window.IS_DEMO ? (<>
            <button title="Export to CSV" className="icon-btn" onClick={()=>setShowExport(true)}><Download size={15} /></button>
            <button title={theme==="dark"?"Light mode":"Dark mode"} className="icon-btn" onClick={toggleTheme}>
              {theme==="dark" ? <Sun size={15} /> : <Moon size={15} />}
            </button>
          </>) : !isAuth ? (
            <button className="btn-link" onClick={()=>setShowLogin(true)}>Login</button>
          ) : null}
        </div>
      </div>

      {/* Tabs */}
      <div className="tabs">
        <button className={`tab-btn ${tab==="applications"?"active":""}`} onClick={()=>setTab("applications")}>
          Applications
        </button>
        <button className={`tab-btn ${tab==="timeline"?"active":""}`} onClick={()=>setTab("timeline")}>
          Timeline
        </button>
      </div>

      {/* Tab content */}
      {tab==="applications" && (
        <ApplicationsTab
          isAuth={isAuth}
          onOpenApp={openApp}
          refreshKey={refreshKey}
          onStatusChange={async (appId, status) => {
            try {
              await api("application_status","POST",{status, today: localToday()},{id:appId});
              refresh();
            } catch(e) { alert("Failed to update status"); }
          }}
        />
      )}
      {tab==="timeline" && (
        <TimelineTab
          isAuth={isAuth}
          onRowClick={isAuth ? openTl : null}
          onLinkClick={onLinkClick}
          refreshKey={refreshKey}
        />
      )}

      </>)}
      {/* Modals */}
      {showLogin   && <LoginModal onSuccess={handleLogin} onClose={()=>setShowLogin(false)} hint={demoHint} />}
      {appModal    && <AppModal appId={appModal.id} isAuth={isAuth} onClose={()=>setAppModal(null)} onSaved={onAppSaved} onDeleted={onAppDeleted} defaultTab={appModal.defaultTab||"info"} />}
      {tlModal     && <TimelineModal entryId={tlModal.id} isNew={tlModal.isNew} onClose={()=>setTlModal(null)} onSaved={onTlSaved} onDeleted={onTlDeleted} />}
      {showExport  && <ExportModal onClose={()=>setShowExport(false)} />}
      {showShare   && <ShareModal initialToken={myShareToken} onClose={()=>setShowShare(false)} onTokenChange={setMyShareToken} />}
    </div>
  );
}

ReactDOM.createRoot(document.getElementById("root")).render(<App />);
</script>
</body>
</html>
