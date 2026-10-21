import React from 'react';
import { createRoot } from 'react-dom/client';

function ControlCenterTabs() {
    const [active, setActive] = React.useState(() => new URLSearchParams(window.location.search).get('tab') === 'events' ? 'events' : 'matches');

    React.useEffect(() => {
        const panes = document.querySelectorAll('[data-cc-pane]');
        panes.forEach((pane) => { pane.hidden = pane.dataset.ccPane !== active; });
        document.querySelectorAll('[data-cc-tab]').forEach((tab) => {
            const selected = tab.dataset.ccTab === active;
            tab.classList.toggle('active', selected);
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        });
        const url = new URL(window.location.href);
        url.searchParams.set('tab', active);
        window.history.replaceState({}, '', url);
    }, [active]);

    return <div className="cc-tabs" role="tablist" aria-label="Fungsi kontrol global">
        <button type="button" className={`cc-tab ${active === 'matches' ? 'active' : ''}`} role="tab" aria-selected={active === 'matches'} onClick={() => setActive('matches')}>
            <i className="fa-solid fa-people-group" /> Open Match
        </button>
        <button type="button" className={`cc-tab ${active === 'events' ? 'active' : ''}`} role="tab" aria-selected={active === 'events'} onClick={() => setActive('events')}>
            <i className="fa-solid fa-calendar-days" /> Event Resmi
        </button>
    </div>;
}

const mount = document.getElementById('control-center-tabs-root');
if (mount) createRoot(mount).render(<ControlCenterTabs />);
