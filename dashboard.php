<?php
session_start();
require_once 'auth/user.php';
require_login();
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$avatar_path = file_exists("profile_pics/{$user_id}.jpg") ? "profile_pics/{$user_id}.jpg" : (file_exists("profile_pics/{$user_id}.png") ? "profile_pics/{$user_id}.png" : 'https://api.dicebear.com/7.x/identicon/svg?seed=' . urlencode($username));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Garbage Route Planner</a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <div class="dropdown">
        <button class="btn btn-dark dropdown-toggle d-flex align-items-center" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar" width="32" height="32" class="rounded-circle me-2">
          <span><?php echo htmlspecialchars($username); ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
          <li><a class="dropdown-item" href="profile.php">Profile</a></li>
          <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<div class="container mt-4">
  <div class="row g-4 justify-content-center">
    <!-- Profile Card -->
    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-body text-center">
          <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar" class="rounded-circle mb-3" style="width:80px;height:80px;object-fit:cover;">
          <h4 class="mb-1"><?php echo htmlspecialchars($username); ?></h4>
          <span class="badge bg-<?php echo $role === 'admin' ? 'danger' : 'info'; ?> text-dark mb-2"><?php echo htmlspecialchars($role); ?></span>
          <div><a href="profile.php" class="btn btn-outline-primary btn-sm mt-2">View Profile</a></div>
        </div>
      </div>
    </div>
    <!-- Quick Links Card -->
    <div class="col-md-8">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="mb-3">Your Workspaces <button id="newWorkspaceBtn" class="btn btn-success btn-sm ms-2">New Workspace</button></h5>
          <div id="workspacesList" class="mb-3"></div>
          <a href="workspace.php" class="btn btn-success w-100 mb-2">New Blank Workspace</a>
          <a href="trucks.php" class="btn btn-secondary w-100 mb-2">My Trucks</a>
          <?php if ($role === 'admin'): ?>
            <a href="admin/index.php" class="btn btn-primary w-100 mb-2">Admin Panel</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <!-- Activity Feed Card -->
    <div class="col-md-8">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="mb-3">Activity Feed</h5>
          <div class="text-muted">(Coming soon: Your recent activity will appear here.)</div>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="toast-container" style="position:fixed;top:1rem;right:1rem;z-index:2000;"></div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
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

function renderWorkspaces() {
  fetch('list_workspaces.php').then(r=>r.json()).then(list => {
    const el = document.getElementById('workspacesList');
    if (!list.length) {
      el.innerHTML = '<div class="text-muted">No saved workspaces yet.</div>';
      return;
    }
    el.innerHTML = '';
    list.forEach(ws => {
      const row = document.createElement('div');
      row.className = 'd-flex align-items-center justify-content-between border rounded p-2 mb-2';
      row.innerHTML = `
        <div>
          <strong>${ws.name.replace(/</g,'&lt;')}</strong>
          <span class='text-muted small ms-2'>${ws.updated_at.replace('T',' ').slice(0,19)}</span>
        </div>
        <div class='btn-group'>
          <button class='btn btn-outline-success btn-sm' title='Load'>Load</button>
          <button class='btn btn-outline-secondary btn-sm' title='Rename'>Rename</button>
          <button class='btn btn-outline-danger btn-sm' title='Delete'>Delete</button>
        </div>
      `;
      // Load
      row.querySelector('.btn-outline-success').onclick = async () => {
        showToast('Loading workspace...','info');
        const res = await fetch('load_workspace.php?id='+ws.id);
        const data = await res.json();
        if(data.success && data.assignments && data.trucks) {
          localStorage.setItem('route_workspace_assignments_v1', JSON.stringify(data.assignments));
          localStorage.setItem('route_workspace_trucks_v1', JSON.stringify(data.trucks));
          showToast('Workspace loaded! Redirecting...','success');
          setTimeout(()=>{ window.location.href = 'workspace.php?ws='+ws.id; }, 1200);
        } else {
          showToast('Load failed: ' + (data.error || 'No data'),'danger');
        }
      };
      // Rename
      row.querySelector('.btn-outline-secondary').onclick = async () => {
        const newName = prompt('Enter new workspace name:', ws.name);
        if (newName && newName !== ws.name) {
          const res = await fetch('rename_workspace.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: ws.id, name: newName })
          });
          const data = await res.json();
          if(data.success) {
            showToast('Workspace renamed!','success');
            renderWorkspaces();
          } else {
            showToast('Rename failed: ' + (data.error || 'Unknown error'),'danger');
          }
        }
      };
      // Delete
      row.querySelector('.btn-outline-danger').onclick = async () => {
        if(confirm('Delete this workspace?')) {
          const res = await fetch('delete_workspace.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: ws.id })
          });
          const data = await res.json();
          if(data.success) {
            showToast('Workspace deleted!','success');
            renderWorkspaces();
          } else {
            showToast('Delete failed: ' + (data.error || 'Unknown error'),'danger');
          }
        }
      };
      el.appendChild(row);
    });
  });
}
renderWorkspaces();

document.getElementById('newWorkspaceBtn').onclick = async function() {
  // Fetch user's trucks
  const trucks = await fetch('trucks_api.php').then(r=>r.json());
  if (!trucks.length) {
    alert('You must add a truck before creating a workspace.');
    window.location.href = 'trucks.php';
    return;
  }
  const name = prompt('Enter workspace name:','Untitled');
  if(!name) return;
  // Show truck selection
  let truckOptions = trucks.map(t => `${t.id}: ${t.name} (Driver: ${t.driver_name||'-'}, Start: ${t.driver_start_time||'-'}, Yard: ${t.yard||'-'})`).join('\n');
  let truckId = prompt('Select truck by entering its ID:\n' + truckOptions, trucks[0].id);
  if (!truckId || !trucks.find(t => t.id == truckId)) return alert('Invalid truck selection.');
  const truck = trucks.find(t => t.id == truckId);
  // Save blank workspace with selected truck
  const res = await fetch('save_workspace.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ assignments: {}, trucks: [truck.name], truck_id: truck.id, name })
  });
  const data = await res.json();
  if(data.success) {
    showToast('Workspace created!','success');
    renderWorkspaces();
  } else {
    showToast('Create failed: ' + (data.error || 'Unknown error'),'danger');
  }
};
</script>
</body>
</html> 