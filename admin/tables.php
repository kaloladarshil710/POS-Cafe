<?php
include("../config/db.php");
include("layout/header.php");

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
$message = $error = "";

// ADD TABLE
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_table'])) {
    if (!hash_equals($csrf,$_POST['csrf']??'')) die('CSRF check failed');
    $table_number = trim($_POST['table_number']??'');
    $seats        = safe_int($_POST['seats']??0);
    $active       = in_array($_POST['active']??'',['yes','no'])?$_POST['active']:'yes';
    if (empty($table_number)||$seats<=0) { $error="Table name and seats required!"; }
    else {
        $qr_token = gen_token(16);
$stmt = mysqli_prepare($conn,"INSERT INTO restaurant_tables (table_number,seats,qr_token,status,active) VALUES (?,?,?,'free',?)");
        mysqli_stmt_bind_param($stmt,"siss",$table_number,$qr_token,$seats,$active);
        if (mysqli_stmt_execute($stmt)) { header("Location: tables.php?msg=added"); exit(); }
        else { $error = "Failed: ".mysqli_error($conn); }
    }
}

// FREE TABLE (admin override)
if (isset($_GET['free'],$_GET['csrf']) && hash_equals($csrf,$_GET['csrf'])) {
    $fid = safe_int($_GET['free']);
    $s = mysqli_prepare($conn,"UPDATE restaurant_tables SET status='free',occupied_since=NULL WHERE id=?");
    mysqli_stmt_bind_param($s,"i",$fid); mysqli_stmt_execute($s);
    header("Location: tables.php?msg=freed"); exit();
}

// DELETE
if (isset($_GET['delete'],$_GET['csrf']) && hash_equals($csrf,$_GET['csrf'])) {
    $did = safe_int($_GET['delete']);
    $s = mysqli_prepare($conn,"DELETE FROM restaurant_tables WHERE id=?");
    mysqli_stmt_bind_param($s,"i",$did); mysqli_stmt_execute($s);
    header("Location: tables.php?msg=deleted"); exit();
}

$msgs = ['added'=>'✅ Table added!','freed'=>'✅ Table freed.','deleted'=>'✅ Table deleted.'];
if (isset($_GET['msg'],$msgs[$_GET['msg']])) $message = $msgs[$_GET['msg']];

$per_page = in_array(safe_int($_GET['per_page']??10),[10,25,50,100])?safe_int($_GET['per_page']??10):10;
$sort = safe_sort_col($_GET['sort']??'id',['id','table_number','seats','status','active','created_at'],'id');
$dir  = safe_dir($_GET['dir']??'ASC');
$search = trim($_GET['q']??'');
$stat_f = in_array($_GET['stat_f']??'',['free','occupied',''])?($_GET['stat_f']??''):'';
$active_f = in_array($_GET['active_f']??'',['yes','no',''])?($_GET['active_f']??''):'';

$where_parts = ["1=1"];
if ($search!=='')   $where_parts[] = "rt.table_number LIKE '%".mysqli_real_escape_string($conn,$search)."%'";
if ($stat_f!=='')   $where_parts[] = "rt.status='".mysqli_real_escape_string($conn,$stat_f)."'";
if ($active_f!=='') $where_parts[] = "rt.active='".mysqli_real_escape_string($conn,$active_f)."'";
$where = implode(" AND ",$where_parts);

$total = (int)mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM restaurant_tables rt WHERE $where"))[0];
$page   = max(1,safe_int($_GET['page']??1)); $offset = ($page-1)*$per_page;
$tables = mysqli_query($conn,"SELECT rt.*,COALESCE((SELECT SUM(o.total_amount) FROM orders o WHERE o.table_id=rt.id AND o.status!='paid'),0) AS running_total FROM restaurant_tables rt WHERE $where ORDER BY $sort $dir LIMIT $per_page OFFSET $offset");

// Base URL for customer QR
$base_url = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME']));
?>

<div class="panel">
  <h6 class="fw-bold mb-3">Add Restaurant Table</h6>
  <?php if($message): ?><div class="msg-success"><?php echo h($message); ?></div><?php endif; ?>
  <?php if($error):   ?><div class="msg-error"><?php echo h($error); ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label fw-semibold">Table Name/Number *</label><input type="text" name="table_number" class="form-control" required maxlength="20" placeholder="e.g. Table 1 / VIP-1"></div>
      <div class="col-md-2"><label class="form-label fw-semibold">Seats *</label><input type="number" name="seats" min="1" max="50" class="form-control" required placeholder="4"></div>
      <div class="col-md-2"><label class="form-label fw-semibold">Active</label><select name="active" class="form-select"><option value="yes">Active</option><option value="no">Inactive</option></select></div>
      <div class="col-md-2 d-flex align-items-end"><button type="submit" name="add_table" class="btn text-white fw-bold w-100" style="background:#F97316;border-radius:10px;">+ Add Table</button></div>
    </div>
  </form>
</div>

<div class="panel">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2"><h6 class="fw-bold mb-0">Tables</h6><span class="badge" style="background:rgba(249,115,22,0.1);color:#F97316;"><?php echo $total; ?></span></div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <?php echo per_page_selector($per_page); ?>
      <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="text" name="q" value="<?php echo h($search); ?>" class="form-control form-control-sm" style="width:130px;" placeholder="🔍 Search…">
        <select name="stat_f" class="form-select form-select-sm" style="width:110px;" onchange="this.form.submit()">
          <option value="">All Status</option>
          <option value="free" <?php echo $stat_f==='free'?'selected':''; ?>>Free</option>
          <option value="occupied" <?php echo $stat_f==='occupied'?'selected':''; ?>>Occupied</option>
        </select>
        <select name="active_f" class="form-select form-select-sm" style="width:100px;" onchange="this.form.submit()">
          <option value="">All</option>
          <option value="yes" <?php echo $active_f==='yes'?'selected':''; ?>>Active</option>
          <option value="no"  <?php echo $active_f==='no'?'selected':''; ?>>Inactive</option>
        </select>
        <input type="hidden" name="per_page" value="<?php echo $per_page; ?>">
        <button class="btn btn-sm text-white" style="background:#F97316;border-radius:8px;">Filter</button>
        <?php if($search||$stat_f||$active_f): ?><a href="tables.php" class="btn btn-sm btn-secondary">✕</a><?php endif; ?>
      </form>
    </div>
  </div>
<div class="table-responsive">
    <table class="table table-hover modern-table">
      <thead><tr>
        <?php echo sort_th('id','#',$sort,$dir); ?>
        <?php echo sort_th('table_number','Table',$sort,$dir); ?>
        <?php echo sort_th('seats','Seats',$sort,$dir); ?>
        <?php echo sort_th('status','Status',$sort,$dir); ?>
        <th>Pending Bill</th>
        <?php echo sort_th('active','Active',$sort,$dir); ?>
        <th>QR Code</th>
        <th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(mysqli_num_rows($tables)===0): ?>
        <tr><td colspan="8" class="text-center py-5 text-muted">
          <i class="bi bi-table fs-1 d-block mb-3 opacity-50"></i>
          <div>No tables found matching your filters</div>
          <small class="opacity-75">Try adjusting search or status filters above</small>
        </td></tr>
      <?php else: while($row=mysqli_fetch_assoc($tables)): $occ=$row['status']==='occupied'; 
        $status_emoji = $occ ? '🔴' : '🟢';
        $status_class = $occ ? 'bg-gradient-danger text-white' : 'bg-gradient-success text-white';
      ?>
        <tr class="table-row-hover" data-table-id="<?php echo $row['id']; ?>">
          <td class="fw-semibold text-primary"><?php echo $row['id']; ?></td>
          <td class="fw-bold fs-5">
            <i class="bi bi-table me-2"></i><?php echo h($row['table_number']); ?>
          </td>
          <td>
            <span class="badge bg-light text-dark px-3 py-2">
              <i class="bi bi-people"></i> <?php echo $row['seats']; ?>
            </span>
          </td>
          <td>
            <span class="badge <?php echo $status_class; ?> px-3 py-2 fw-semibold fs-6">
              <?php echo $status_emoji; ?> <?php echo ucfirst($row['status']); ?>
            </span>
          </td>
          <td>
            <?php if($row['running_total']>0): ?>
              <span class="fw-bold text-primary fs-6 d-flex align-items-center gap-1" style="text-decoration:none;" onclick="openBill(<?php echo $row['id']; ?>)">
                <i class="bi bi-receipt"></i> ₹<?php echo number_format($row['running_total'], 0); ?>
              </span>
            <?php else: ?>
              <span class="text-muted fst-italic">— No pending</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge <?php echo $row['active']==='yes' ? 'badge-active fs-6 px-3 py-2' : 'badge-inactive fs-6 px-3 py-2'; ?>">
              <?php echo $row['active']==='yes' ? '✅ Active' : '⏸️ Inactive'; ?>
            </span>
          </td>
          <td class="text-nowrap">
            <?php if($row['qr_token']): ?>
              <div class="btn-group btn-group-sm" role="group">
                <a href="<?php echo $base_url; ?>/customer/scan.php?table=<?php echo $row['id']; ?>" 
                   target="_blank" class="btn btn-outline-primary" title="Customer QR">
                  <i class="bi bi-qr-code-scan"></i>
                </a>
                <button class="btn btn-outline-secondary" onclick="copyQR('<?php echo $base_url; ?>/customer/scan.php?table=<?php echo $row['id']; ?>')" title="Copy Link">
                  <i class="bi bi-copy"></i>
                </button>
              </div>
            <?php else: ?>
              <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="action-group gap-1">
              <?php if($occ && $row['running_total']>0): ?>
                <a href="../pos/table_bill.php?table_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                  <i class="bi bi-credit-card"></i> Pay
                </a>
              <?php endif; ?>
              <?php if($occ): ?>
                <a href="tables.php?free=<?php echo $row['id']; ?>&csrf=<?php echo $csrf; ?>" 
                   class="btn btn-sm btn-warning" onclick="return confirm('Force-free this table?')" title="Force Free">
                  <i class="bi bi-unlock"></i>
                </a>
              <?php endif; ?>
              <a href="tables.php?delete=<?php echo $row['id']; ?>&csrf=<?php echo $csrf; ?>" 
                 class="btn btn-sm btn-danger" onclick="return confirm('Delete table?')" title="Delete">
                <i class="bi bi-trash"></i>
              </a>
            </div>
          </td>
        </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>

  <style>
  .modern-table tbody tr {transition: all 0.2s ease;}
  .modern-table tbody tr:hover {background: linear-gradient(90deg, rgba(249,115,22,0.05) 0%, transparent 100%); transform: scale(1.01); box-shadow: 0 4px 12px rgba(0,0,0,0.08);}
  .table-row-hover {cursor:pointer;}
  .action-group .btn {min-width:36px; height:36px; padding:6px;}
  .bg-gradient-success {background: linear-gradient(135deg, #10B981 0%, #059669 100%) !important;}
  .bg-gradient-danger {background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%) !important;}
  @media (max-width:768px) {.table-responsive {font-size:0.875rem;}}
  </style>

  <script>
  function openBill(tableId) {
    window.open('../pos/table_bill.php?table_id=' + tableId, '_blank');
  }
  function copyQR(url) {
    navigator.clipboard.writeText(url).then(() => {
      const btn = event.target;
      const orig = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-check-lg"></i>';
      btn.classList.add('btn-success');
      setTimeout(() => {
        btn.innerHTML = orig;
        btn.classList.remove('btn-success');
      }, 1500);
    });
  }
  </script>
  <?php echo pagination_html($total,$per_page,$page); ?>
</div>

<?php include("layout/footer.php"); ?>