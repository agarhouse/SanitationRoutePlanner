<?php
session_start();
require_once 'auth/user.php';
require_login();
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$avatar_path = file_exists("profile_pics/{$user_id}.jpg") ? "profile_pics/{$user_id}.jpg" : (file_exists("profile_pics/{$user_id}.png") ? "profile_pics/{$user_id}.png" : 'https://api.dicebear.com/7.x/identicon/svg?seed=' . urlencode($username));

// Get workspace id from ?ws= param
$ws_id = isset($_GET['ws']) ? intval($_GET['ws']) : null;
$truck_info = null;
$workspace_name = '';
if ($ws_id) {
    $db = get_db();
    $stmt = $db->prepare('SELECT truck_id, name FROM workspaces WHERE id = ? AND user_id = ?');
    $stmt->execute([$ws_id, $user_id]);
    $ws = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ws) {
        $workspace_name = $ws['name'];
        if ($ws['truck_id']) {
            $stmt2 = $db->prepare('SELECT name, driver_name, driver_start_time, yard FROM trucks WHERE id = ? AND user_id = ?');
            $stmt2->execute([$ws['truck_id'], $user_id]);
            $truck_info = $stmt2->fetch(PDO::FETCH_ASSOC);
        }
    }
}
// Get day from query param, default to Monday
$selectedDay = isset($_GET['day']) ? $_GET['day'] : 'Monday';
$file = isset($_GET['file']) ? basename($_GET['file']) : '';
$filepath = "uploads/" . $file;
$stops = [];
$error = '';
if ($file && file_exists($filepath)) {
    $json = file_get_contents($filepath);
    $data = json_decode($json, true);
    if (is_array($data)) {
        $stops = $data;
    } else {
        $error = 'Invalid JSON format.';
    }
} else if ($file) {
    $error = 'File not found.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Workspace</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; }
        .app-bar { position: sticky; top: 0; z-index: 1050; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .workspace-main { display: flex; flex-direction: row; height: calc(100vh - 80px); }
        .kanban-area { flex: 1; display: flex; flex-direction: column; padding: 1.5rem 1rem 1rem 1rem; }
        .kanban-columns { display: flex; gap: 1.5rem; flex: 1; overflow-x: auto; }
        .kanban-column { min-width: 320px; background: #f4f6fb; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.03); padding: 1rem; display: flex; flex-direction: column; }
        .kanban-column-header { font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .kanban-stops { flex: 1; min-height: 80px; }
        .kanban-stop-card { cursor: grab; margin-bottom: 1rem; border-radius: 0.75rem; box-shadow: 0 1px 4px rgba(0,0,0,0.04); transition: box-shadow 0.2s; }
        .kanban-stop-card.selected, .kanban-stop-card:focus { box-shadow: 0 0 0 3px #0d6efd33; }
        .day-tabs { margin-bottom: 1.5rem; }
        .sidebar-right { width: 340px; background: #fff; border-left: 1px solid #eee; padding: 1.5rem 1rem 1rem 1rem; overflow-y: auto; }
        @media (max-width: 991px) {
            .sidebar-right { display: none; }
        }
    </style>
</head>
<body>
<!-- App Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white app-bar">
  <div class="container-fluid align-items-center">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
      <i class="bi bi-trash3-fill me-2"></i> Garbage Route Planner
    </a>
    <?php if ($workspace_name): ?>
      <span class="fw-bold ms-3">Workspace: <?php echo htmlspecialchars($workspace_name); ?></span>
    <?php endif; ?>
    <?php if ($truck_info): ?>
      <span class="badge bg-info text-dark ms-3">
        <i class="bi bi-truck me-1"></i> <?php echo htmlspecialchars($truck_info['name']); ?>
        <span class="ms-2"><i class="bi bi-person"></i> <?php echo htmlspecialchars($truck_info['driver_name'] ?: '-'); ?></span>
        <span class="ms-2"><i class="bi bi-clock"></i> <?php echo htmlspecialchars($truck_info['driver_start_time'] ?: '-'); ?></span>
        <span class="ms-2"><i class="bi bi-geo"></i> <?php echo htmlspecialchars($truck_info['yard'] ?: '-'); ?></span>
      </span>
    <?php endif; ?>
    <button class="btn btn-outline-success ms-3" data-bs-toggle="modal" data-bs-target="#uploadDayModal"><i class="bi bi-upload"></i> Upload Routes</button>
    <button class="btn btn-primary ms-2" id="saveWorkspaceBtn"><i class="bi bi-cloud-arrow-up"></i> Save Workspace</button>
    <button class="btn btn-outline-secondary ms-2" id="exportBtn"><i class="bi bi-download"></i> Export</button>
    <button class="btn btn-outline-danger ms-2" id="clearWorkspaceBtn"><i class="bi bi-x-circle"></i> Clear</button>
    <a href="trucks.php" class="btn btn-secondary ms-2"><i class="bi bi-truck"></i> My Trucks</a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <div class="dropdown">
        <button class="btn btn-light dropdown-toggle d-flex align-items-center" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
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
<!-- Upload Modal -->
<div class="modal fade" id="uploadDayModal" tabindex="-1" aria-labelledby="uploadDayModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="upload_day.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="uploadDayModalLabel">Upload Routes for a Day</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="daySelect" class="form-label">Select Day</label>
            <select class="form-select" id="daySelect" name="day" required>
              <?php foreach (["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"] as $day): ?>
                <option value="<?php echo $day; ?>" <?php if ($selectedDay === $day) echo 'selected'; ?>><?php echo $day; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="routeFileDay" class="form-label">Select JSON File</label>
            <input class="form-control" type="file" id="routeFileDay" name="routeFile" accept="application/json" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="workspace-main">
  <div class="kanban-area">
    <ul class="nav nav-tabs day-tabs" id="dayTabs">
      <?php foreach (["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"] as $day): ?>
        <li class="nav-item"><a class="nav-link<?php if ($selectedDay === $day) echo ' active'; ?>" data-day="<?php echo $day; ?>" href="#"><?php echo substr($day,0,3); ?></a></li>
      <?php endforeach; ?>
    </ul>
    <div class="kanban-columns" id="kanbanColumns">
      <!-- Kanban columns for Unassigned and each truck will be rendered here by JS -->
    </div>
  </div>
  <aside class="sidebar-right d-none d-lg-block" id="stopDetailsPanel">
    <div id="stopDetailsPlaceholder" class="text-muted text-center mt-5">
      <span class="fs-4">Select a stop to view details</span>
    </div>
    <div id="stopDetailsContent" style="display:none;"></div>
  </aside>
</div>
<div id="toast-container" style="position:fixed;top:1rem;right:1rem;z-index:2000;"></div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
window.INITIAL_STOPS = <?php echo json_encode($stops); ?>;
window.INITIAL_DAY = <?php echo json_encode($selectedDay); ?>;
</script>
<script src="assets/js/app.js"></script>
</body>
</html> 