import { BrowserRouter, Routes, Route, NavLink, Navigate } from "react-router-dom";
import ReportItem from "./pages/ReportItem";
import SearchByImage from "./pages/SearchByImage";
import HotspotMap from "./pages/HotspotMap";
import ClaimItem from "./pages/ClaimItem";

function App() {
  return (
    <BrowserRouter>
      <div className="app">
        <header className="app-header">
          <div className="brand">
            <span className="brand-icon">🔍</span>
            <div>
              <h1>Campus Lost & Found</h1>
              <p>AI image matching • Hotspot mapping • Secure claims</p>
            </div>
          </div>

          <nav className="app-nav">
            <NavLink to="/report" className={({ isActive }) => (isActive ? "active" : "")}>
              Report Item
            </NavLink>
            <NavLink to="/search" className={({ isActive }) => (isActive ? "active" : "")}>
              Search by Image
            </NavLink>
            <NavLink to="/hotspots" className={({ isActive }) => (isActive ? "active" : "")}>
              Loss Hotspots
            </NavLink>
            <NavLink to="/claim" className={({ isActive }) => (isActive ? "active" : "")}>
              Claim Item
            </NavLink>
          </nav>
        </header>

        <main className="app-main">
          <Routes>
            <Route path="/" element={<Navigate to="/report" replace />} />
            <Route path="/report" element={<ReportItem />} />
            <Route path="/search" element={<SearchByImage />} />
            <Route path="/hotspots" element={<HotspotMap />} />
            <Route path="/claim" element={<ClaimItem />} />
            <Route
              path="*"
              element={
                <div className="not-found">
                  <h2>Page not found</h2>
                  <p>The page you are looking for does not exist.</p>
                </div>
              }
            />
          </Routes>
        </main>

        <footer className="app-footer">
          <p>Campus Lost & Found System • Secure claim verification enabled</p>
        </footer>
      </div>

      <style>{`
        * {
          box-sizing: border-box;
        }

        body {
          margin: 0;
          font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
          background: #f5f7fb;
          color: #101828;
        }

        .app {
          min-height: 100vh;
          display: flex;
          flex-direction: column;
        }

        .app-header {
          background: #ffffff;
          border-bottom: 1px solid #e4e7ec;
          padding: 1rem 1.5rem;
          display: flex;
          flex-wrap: wrap;
          align-items: center;
          justify-content: space-between;
          gap: 1rem;
        }

        .brand {
          display: flex;
          align-items: center;
          gap: 0.75rem;
        }

        .brand-icon {
          font-size: 1.75rem;
        }

        .brand h1 {
          margin: 0;
          font-size: 1.25rem;
        }

        .brand p {
          margin: 0.15rem 0 0;
          font-size: 0.85rem;
          color: #667085;
        }

        .app-nav {
          display: flex;
          flex-wrap: wrap;
          gap: 0.5rem;
        }

        .app-nav a {
          text-decoration: none;
          color: #344054;
          padding: 0.6rem 0.9rem;
          border-radius: 999px;
          font-size: 0.95rem;
          font-weight: 600;
          transition: 0.2s ease;
        }

        .app-nav a:hover {
          background: #eef2ff;
          color: #3730a3;
        }

        .app-nav a.active {
          background: #2563eb;
          color: #ffffff;
        }

        .app-main {
          flex: 1;
          width: 100%;
        }

        .app-footer {
          background: #ffffff;
          border-top: 1px solid #e4e7ec;
          padding: 1rem 1.5rem;
          text-align: center;
          color: #667085;
          font-size: 0.9rem;
        }

        .not-found {
          max-width: 640px;
          margin: 4rem auto;
          text-align: center;
          background: #ffffff;
          padding: 2rem;
          border-radius: 16px;
          box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 768px) {
          .app-header {
            flex-direction: column;
            align-items: flex-start;
          }

          .app-nav {
            width: 100%;
          }

          .app-nav a {
            flex: 1;
            text-align: center;
          }
        }
      `}</style>
    </BrowserRouter>
  );
}

export default App;