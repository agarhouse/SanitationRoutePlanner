// --- State ---
const DAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
let trucks = [];
let truckDetails = {}; // { truckName: {driver, start, yard} }
let assignments = {}; // { day: { truck: [stops], Unassigned: [stops] } }
let selectedDay = window.INITIAL_DAY || DAYS[0];
let selectedStop = null;

// --- DOM Elements ---
const kanbanColumns = document.getElementById('kanbanColumns');
const dayTabs = document.getElementById('dayTabs');
const exportBtn = document.getElementById('exportBtn');
const stopDetailsPanel = document.getElementById('stopDetailsPanel');
const stopDetailsPlaceholder = document.getElementById('stopDetailsPlaceholder');
const stopDetailsContent = document.getElementById('stopDetailsContent');
const saveBtn = document.getElementById('saveWorkspaceBtn');
const clearBtn = document.getElementById('clearWorkspaceBtn');

// --- LocalStorage Helpers ---
const STORAGE_KEY = 'route_workspace_assignments_v1';
const TRUCKS_KEY = 'route_workspace_trucks_v1';

function saveWorkspaceLocal() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(assignments));
    localStorage.setItem(TRUCKS_KEY, JSON.stringify(trucks));
}

function loadWorkspaceLocal() {
    const saved = localStorage.getItem(STORAGE_KEY);
    const savedTrucks = localStorage.getItem(TRUCKS_KEY);
    if (saved) {
        try { assignments = JSON.parse(saved); } catch (e) {}
    }
    if (savedTrucks) {
        try { trucks = JSON.parse(savedTrucks); } catch (e) {}
    }
}

function clearWorkspace() {
    localStorage.removeItem(STORAGE_KEY);
    localStorage.removeItem(TRUCKS_KEY);
    location.reload();
}

// --- Init State ---
function initAssignments() {
    loadWorkspaceLocal();
    if (!assignments || typeof assignments !== 'object' || Array.isArray(assignments)) assignments = {};
    DAYS.forEach(day => { if (!assignments[day]) assignments[day] = { Unassigned: [] }; });
    if (!Array.isArray(trucks)) trucks = [];
    // Use PHP-passed stops for selected day's unassigned (merge if already present)
    if (window.INITIAL_STOPS && window.INITIAL_STOPS.length) {
        const stopsToAdd = window.INITIAL_STOPS.map(s => ({...s}));
        const existing = assignments[selectedDay].Unassigned.map(s => JSON.stringify(s));
        stopsToAdd.forEach(stop => {
            if (!existing.includes(JSON.stringify(stop))) {
                assignments[selectedDay].Unassigned.push(stop);
            }
        });
        window.INITIAL_STOPS = [];
        saveWorkspaceLocal();
    }
}
initAssignments();

// --- Render Functions ---
function renderKanban() {
    kanbanColumns.innerHTML = '';
    // Unassigned column
    kanbanColumns.appendChild(createKanbanColumn('Unassigned', assignments[selectedDay].Unassigned));
    // Truck columns
    trucks.forEach(truck => {
        if (!assignments[selectedDay][truck]) assignments[selectedDay][truck] = [];
        kanbanColumns.appendChild(createKanbanColumn(truck, assignments[selectedDay][truck], truckDetails[truck]));
    });
}

function createKanbanColumn(truck, stops, details) {
    const col = document.createElement('div');
    col.className = 'kanban-column';
    col.dataset.truck = truck;
    let header = '';
    if (truck === 'Unassigned') {
        header = `<i class='bi bi-inbox'></i> Unassigned`;
    } else {
        header = `<span class='badge bg-info me-2'>${truck[0] || '?'}</span> ${truck}`;
        if (details) {
            header += `<br><span class='small text-muted'>`;
            if (details.driver_name) header += `<i class='bi bi-person'></i> ${details.driver_name} `;
            if (details.driver_start_time) header += `<i class='bi bi-clock'></i> ${details.driver_start_time} `;
            if (details.yard) header += `<i class='bi bi-geo'></i> ${details.yard}`;
            header += `</span>`;
        }
    }
    col.innerHTML = `<div class="kanban-column-header">${header}</div>`;
    const stopsDiv = document.createElement('div');
    stopsDiv.className = 'kanban-stops droppable';
    stopsDiv.dataset.truck = truck;
    stops.forEach(stop => {
        const stopDiv = createStopCard(stop);
        stopsDiv.appendChild(stopDiv);
    });
    makeDroppable(stopsDiv, truck);
    col.appendChild(stopsDiv);
    return col;
}

function createStopCard(stop) {
    const div = document.createElement('div');
    div.className = 'card kanban-stop-card';
    div.tabIndex = 0;
    div.draggable = true;
    div.dataset.stop = JSON.stringify(stop);
    div.innerHTML = `<div class="card-body p-2">
        <strong>${stop['Site'] || 'Unknown'}</strong><br>
        <small>${stop['Street Address 2'] || ''}, ${stop['Street Address 3'] || ''}</small>
    </div>`;
    makeDraggable(div);
    div.onclick = () => showStopDetails(stop, div);
    return div;
}

function showStopDetails(stop, div) {
    selectedStop = stop;
    document.querySelectorAll('.kanban-stop-card.selected').forEach(el => el.classList.remove('selected'));
    if (div) div.classList.add('selected');
    stopDetailsPlaceholder.style.display = 'none';
    stopDetailsContent.style.display = '';
    stopDetailsContent.innerHTML = `
      <h5>${stop['Site'] || 'Unknown'}</h5>
      <div class="mb-2"><span class="fw-bold">Address:</span> ${stop['Street Address'] || ''} ${stop['Street Address 2'] || ''}, ${stop['Street Address 3'] || ''}, ${stop['State'] || ''} ${stop['Zip Code'] || ''}</div>
      <div class="mb-2"><span class="fw-bold">Container:</span> ${stop['Container Type'] || ''}</div>
      <div class="mb-2"><span class="fw-bold">Material:</span> ${stop['Material Profile'] || ''}</div>
      <div class="mb-2"><span class="fw-bold">Pickup Interval:</span> ${stop['Pickup Interval'] || ''}</div>
      <div class="mb-2"><span class="fw-bold">Route No:</span> ${stop['Route No'] || ''}</div>
      <div class="mb-2"><span class="fw-bold">Position:</span> ${stop['Position'] || ''}</div>
    `;
}

function clearStopDetails() {
    selectedStop = null;
    stopDetailsPlaceholder.style.display = '';
    stopDetailsContent.style.display = 'none';
    document.querySelectorAll('.kanban-stop-card.selected').forEach(el => el.classList.remove('selected'));
}

// --- Drag and Drop ---
function makeDraggable(el) {
    el.addEventListener('dragstart', e => {
        e.dataTransfer.setData('text/plain', el.dataset.stop);
        setTimeout(() => el.classList.add('invisible'), 0);
    });
    el.addEventListener('dragend', e => {
        el.classList.remove('invisible');
    });
}

function makeDroppable(el, truckName) {
    el.addEventListener('dragover', e => {
        e.preventDefault();
        el.classList.add('bg-light');
    });
    el.addEventListener('dragleave', e => {
        el.classList.remove('bg-light');
    });
    el.addEventListener('drop', e => {
        e.preventDefault();
        el.classList.remove('bg-light');
        const stop = JSON.parse(e.dataTransfer.getData('text/plain'));
        for (const t in assignments[selectedDay]) {
            assignments[selectedDay][t] = assignments[selectedDay][t].filter(s => JSON.stringify(s) !== JSON.stringify(stop));
        }
        if (!assignments[selectedDay][truckName]) assignments[selectedDay][truckName] = [];
        assignments[selectedDay][truckName].push(stop);
        clearStopDetails();
        saveWorkspaceLocal();
        renderKanban();
    });
}

// --- Day Tabs ---
Array.from(dayTabs.querySelectorAll('.nav-link')).forEach(tab => {
    tab.addEventListener('click', e => {
        e.preventDefault();
        Array.from(dayTabs.querySelectorAll('.nav-link')).forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        selectedDay = tab.dataset.day;
        clearStopDetails();
        renderKanban();
    });
    if (tab.dataset.day === selectedDay) {
        tab.classList.add('active');
    } else {
        tab.classList.remove('active');
    }
});

// --- Export ---
if (exportBtn) {
    exportBtn.addEventListener('click', () => {
        const exportData = {};
        DAYS.forEach(day => {
            exportData[day] = {};
            Object.keys(assignments[day]).forEach(truck => {
                exportData[day][truck] = assignments[day][truck];
            });
        });
        const blob = new Blob([JSON.stringify(exportData, null, 2)], {type: 'application/json'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'routes_export.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        showToast('Exported as routes_export.json', 'success');
    });
}

// --- Save Workspace (Cloud) ---
if (saveBtn) {
    saveBtn.addEventListener('click', async function() {
        let wsId = null;
        let wsName = null;
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('ws')) wsId = urlParams.get('ws');
        if (wsId && localStorage.getItem('route_workspace_name_'+wsId)) wsName = localStorage.getItem('route_workspace_name_'+wsId);
        let name = wsName;
        if (!wsId) {
            name = prompt('Enter workspace name:', 'Untitled');
            if (!name) return;
        }
        showToast('Saving workspace...','info');
        try {
            const res = await fetch('save_workspace.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ assignments, trucks, name, id: wsId })
            });
            const data = await res.json();
            if (data.success) {
                wsId = data.id;
                wsName = name;
                if (wsId && wsName) localStorage.setItem('route_workspace_name_'+wsId, wsName);
                showToast('Workspace saved!','success');
                if (!urlParams.has('ws')) {
                    urlParams.set('ws', wsId);
                    window.history.replaceState({}, '', window.location.pathname + '?' + urlParams.toString());
                }
            } else {
                showToast('Save failed: ' + (data.error || 'Unknown error'),'danger');
            }
        } catch (e) {
            showToast('Save failed: ' + e.message,'danger');
        }
    });
}

// --- Clear Workspace ---
if (clearBtn) {
    clearBtn.addEventListener('click', function() {
        if (confirm('Clear all assignments and trucks?')) clearWorkspace();
    });
}

function showToast(msg, type) {
    const toastDiv = document.createElement('div');
    toastDiv.className = `alert alert-${type || 'info'} fade show`;
    toastDiv.style.minWidth = '200px';
    toastDiv.innerHTML = msg;
    document.getElementById('toast-container').appendChild(toastDiv);
    setTimeout(() => {
        toastDiv.classList.remove('show');
        toastDiv.classList.add('hide');
        setTimeout(() => toastDiv.remove(), 500);
    }, 2500);
}

// --- Initial Render ---
renderKanban();
clearStopDetails(); 