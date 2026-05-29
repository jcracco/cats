<?php
require_once __DIR__ . '/bootstrap.php';
require_once PRIVATE_PATH . 'config.php';
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
</head>
<body>
<div id="root"></div>
<script type="text/babel">
const { useState, useEffect, useRef, useCallback } = React;

<?php include PRIVATE_PATH . 'pipeline-core.php'; ?>
<?php include PRIVATE_PATH . 'pipeline-modal.php'; ?>

function App() {
  // ── Auth ──────────────────────────────────────────────────────────────────
  const [isAuth, setIsAuth]       = useState(false);
  const [authChecked, setChecked] = useState(false);
  const [showLogin, setShowLogin] = useState(false);

  useEffect(()=>{
    api("session").then(d=>{ setIsAuth(!!d.auth); setChecked(true); }).catch(()=>setChecked(true));
  },[]);

  const handleLogin = () => { setIsAuth(true); setShowLogin(false); refresh(); };
  const demoHint = window.IS_DEMO ? (
    <div style={{ background:"rgba(251,191,36,0.1)", border:"1px solid rgba(251,191,36,0.3)",
      borderRadius:8, padding:"8px 12px", marginBottom:16, fontSize:11, color:"#fbbf24", textAlign:"center" }}>
      Demo credentials: <strong>demo</strong> / <strong>demo</strong>
    </div>
  ) : null;
  const handleLogout = () => {
    api("logout","POST").then(()=>{ setIsAuth(false); refresh(); });
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

  return (
    <div className="page">
      {/* Demo banner */}
      {window.IS_DEMO && (
        <div style={{ position:"sticky", top:0, zIndex:1000, background:"#854d0e",
          borderBottom:"1px solid #a16207", padding:"8px 20px",
          display:"flex", alignItems:"center", justifyContent:"space-between",
          fontSize:12, color:"#fef9c3" }}>
          <span>⚠ <strong>Demo version</strong> — all data is fictional. Changes reset when you close the tab.</span>
          <a href="https://github.com/jeromecracco" target="_blank" rel="noreferrer"
            style={{ color:"#fef9c3", textDecoration:"underline", textUnderlineOffset:3, opacity:0.8 }}>
            View on GitHub ↗
          </a>
        </div>
      )}
      {/* Top bar */}
      <div className="top-bar">
        <div className="top-bar-left">
          <div className="eyebrow">Candidate Application Tracking System</div>
          <h1>CATS</h1>
        </div>
        <div className="top-bar-right">
          <button className="theme-toggle" onClick={toggleTheme}>
            {theme==="dark"?"☀ Light":"● Dark"}
          </button>
          {isAuth && (
            <button className="btn-primary" onClick={()=>{ if(tab==="applications") openApp(null); else openTl(null); }}>
              + Add
            </button>
          )}
          {isAuth
            ? <button className="btn-link" onClick={handleLogout}>Logout</button>
            : <button className="btn-link" onClick={()=>setShowLogin(true)}>Admin Login</button>
          }
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

      {/* Modals */}
      {showLogin && <LoginModal onSuccess={handleLogin} onClose={()=>setShowLogin(false)} hint={demoHint} />}
      {appModal  && <AppModal appId={appModal.id} isAuth={isAuth} onClose={()=>setAppModal(null)} onSaved={onAppSaved} onDeleted={onAppDeleted} defaultTab={appModal.defaultTab||"info"} />}
      {tlModal   && <TimelineModal entryId={tlModal.id} isNew={tlModal.isNew} onClose={()=>setTlModal(null)} onSaved={onTlSaved} onDeleted={onTlDeleted} />}
    </div>
  );
}

ReactDOM.createRoot(document.getElementById("root")).render(<App />);
</script>
</body>
</html>
