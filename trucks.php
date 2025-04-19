<?php
session_start();
require_once 'auth/user.php';
require_login();
$db = get_db();
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$avatar_path = file_exists("profile_pics/{$user_id}.jpg") ? "profile_pics/{$user_id}.jpg" : (file_exists("profile_pics/{$user_id}.png") ? "profile_pics/{$user_id}.png" : 'https://api.dicebear.com/7.x/identicon/svg?seed=' . urlencode($username));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Trucks</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">Garbage Route Planner</a>
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
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-bold">My Trucks</span>
          <button class="btn btn-success btn-sm" id="addTruckBtn">Add Truck</button>
        </div>
        <div class="card-body">
          <div id="trucksList"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Truck Modal -->
<div class="modal fade" id="truckModal" tabindex="-1" aria-labelledby="truckModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="truckForm">
        <div class="modal-header">
          <h5 class="modal-title" id="truckModalLabel">Add Truck</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="truckId">
          <div class="mb-3">
            <label class="form-label">Truck Name</label>
            <input type="text" class="form-control" id="truckName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Driver Name</label>
            <input type="text" class="form-control" id="driverName">
          </div>
          <div class="mb-3">
            <label class="form-label">Driver Start Time</label>
            <input type="time" class="form-control" id="driverStartTime">
          </div>
          <div class="mb-3">
            <label class="form-label">Yard</label>
            <input type="text" class="form-control" id="yard">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
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

function renderTrucks() {
  fetch('trucks_api.php').then(r=>r.json()).then(list => {
    const el = document.getElementById('trucksList');
    if (!list.length) {
      el.innerHTML = '<div class="text-muted">No trucks yet.</div>';
      return;
    }
    el.innerHTML = '';
    list.forEach(truck => {
      const row = document.createElement('div');
      row.className = 'd-flex align-items-center justify-content-between border rounded p-2 mb-2';
      row.innerHTML = `
        <div>
          <strong>${truck.name.replace(/</g,'&lt;')}</strong>
          <span class='text-muted small ms-2'>Driver: ${truck.driver_name || '-'}</span>
          <span class='text-muted small ms-2'>Start: ${truck.driver_start_time || '-'}</span>
          <span class='text-muted small ms-2'>Yard: ${truck.yard || '-'}</span>
        </div>
        <div class='btn-group'>
          <button class='btn btn-outline-secondary btn-sm' title='Edit'>Edit</button>
          <button class='btn btn-outline-danger btn-sm' title='Delete'>Delete</button>
        </div>
      `;
      // Edit
      row.querySelector('.btn-outline-secondary').onclick = () => openTruckModal(truck);
      // Delete
      row.querySelector('.btn-outline-danger').onclick = async () => {
        if(confirm('Delete this truck?')) {
          const res = await fetch('trucks_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: truck.id })
          });
          const data = await res.json();
          if(data.success) {
            showToast('Truck deleted!','success');
            renderTrucks();
          } else {
            showToast('Delete failed: ' + (data.error || 'Unknown error'),'danger');
          }
        }
      };
      el.appendChild(row);
    });
  });
}
renderTrucks();

document.getElementById('addTruckBtn').onclick = () => openTruckModal();

function openTruckModal(truck) {
  document.getElementById('truckId').value = truck ? truck.id : '';
  document.getElementById('truckName').value = truck ? truck.name : '';
  document.getElementById('driverName').value = truck ? truck.driver_name : '';
  document.getElementById('driverStartTime').value = truck ? truck.driver_start_time : '';
  document.getElementById('yard').value = truck ? truck.yard : '';
  document.getElementById('truckModalLabel').textContent = truck ? 'Edit Truck' : 'Add Truck';
  var modal = new bootstrap.Modal(document.getElementById('truckModal'));
  modal.show();
}

document.getElementById('truckForm').onsubmit = async function(e) {
  e.preventDefault();
  const id = document.getElementById('truckId').value;
  const name = document.getElementById('truckName').value.trim();
  const driver_name = document.getElementById('driverName').value.trim();
  const driver_start_time = document.getElementById('driverStartTime').value;
  const yard = document.getElementById('yard').value.trim();
  if(!name) return showToast('Truck name required','danger');
  const res = await fetch('trucks_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: id ? 'edit' : 'add',
      id, name, driver_name, driver_start_time, yard
    })
  });
  const data = await res.json();
  if(data.success) {
    showToast('Truck saved!','success');
    renderTrucks();
    bootstrap.Modal.getInstance(document.getElementById('truckModal')).hide();
  } else {
    showToast('Save failed: ' + (data.error || 'Unknown error'),'danger');
  }
};
</script>
</body>
</html> 