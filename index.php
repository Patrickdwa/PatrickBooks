<?php
// Load Backend Logic
require_once 'conn.php';
require_once 'LibraryController.php';

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Session Management
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Initialize Application
try {
    // $mongoManager comes from conn.php
    $app = new LibraryController($pdo, $mongoManager ?? null);
    $app->handleRequest();
    $stats = $app->getCounts();
} catch (Exception $e) {
    die("Critical Application Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Manager Pro v5</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>

<div id="loadingOverlay">
    <div class="text-center">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
        <div class="mt-2 fw-bold text-primary">Processing...</div>
    </div>
</div>

<nav class="navbar navbar-dark mb-4">
    <div class="container">
        <span class="navbar-brand">
            <i class="fa-solid fa-book-open-reader me-2"></i>Library<span class="fw-light opacity-75">Manager</span>
        </span>
        <div class="text-white small d-none d-md-block">
            <i class="fa fa-server me-1"></i> <?= DB_HOST ?> | <i class="fa fa-clock me-1"></i> <?= date('H:i') ?>
        </div>
    </div>
</nav>

<div class="container fade-in">
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card blue">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-title">Total Books</div>
                        <h3 class="stat-val"><?= number_format($stats['books']) ?></h3>
                    </div>
                    <i class="fa fa-book fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card green">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-title">Members</div>
                        <h3 class="stat-val"><?= number_format($stats['members']) ?></h3>
                    </div>
                    <i class="fa fa-users fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card yellow">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-title">Active Loans</div>
                        <h3 class="stat-val"><?= number_format($stats['active_loans']) ?></h3>
                    </div>
                    <i class="fa fa-handshake fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-title">System Logs</div>
                        <h3 class="stat-val"><?= number_format($stats['logs']) ?></h3>
                    </div>
                    <i class="fa fa-list-check fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card main-card mb-5">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#books"><i class="fa fa-book me-2"></i>Books</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#members"><i class="fa fa-users me-2"></i>Members</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#loans"><i class="fa fa-exchange-alt me-2"></i>Loans</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#logs"><i class="fa fa-database me-2"></i>Audit Logs</button></li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content">
                
                <div class="tab-pane fade show active" id="books">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-primary fw-bold m-0">Book Inventory</h5>
                        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddBook"><i class="fa fa-plus me-2"></i>Add Book</button>
                    </div>
                    <table class="table table-hover datatable w-100">
                        <thead><tr><th>ID</th><th>Title</th><th>Author</th><th>Pub/Year</th><th>Genre</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($app->getBooks() as $r): ?>
                            <tr>
                                <td class="text-muted" data-order="<?= $r['book_id'] ?>">
                                    #<?= $r['book_id'] ?>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($r['title']) ?></td>
                                <td><?= htmlspecialchars($r['author']) ?></td>
                                <td><?= htmlspecialchars($r['publisher']) ?> <span class="badge bg-light text-dark border"><?= $r['year_published'] ?></span></td>
                                <td><span class="badge bg-info bg-opacity-10 text-info"><?= htmlspecialchars($r['genre']) ?></span></td>
                                <td class="text-end">
                                    <button class="btn btn-outline-primary btn-action edit-book-btn"
                                            data-id="<?= $r['book_id'] ?>"
                                            data-title="<?= htmlspecialchars($r['title']) ?>"
                                            data-author="<?= htmlspecialchars($r['author']) ?>"
                                            data-publisher="<?= htmlspecialchars($r['publisher']) ?>"
                                            data-year="<?= $r['year_published'] ?>"
                                            data-genre="<?= htmlspecialchars($r['genre']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalEditBook" title="Edit"><i class="fa fa-pen"></i></button>
                                    
                                    <form method="POST" class="d-inline form-delete" onsubmit="return confirm('Delete this book permanently?');">
                                        <input type="hidden" name="csrf_token" value="<?= $app->getCsrfToken() ?>">
                                        <input type="hidden" name="action" value="delete_book">
                                        <input type="hidden" name="id" value="<?= $r['book_id'] ?>">
                                        <button class="btn btn-outline-danger btn-action" title="Delete"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="members">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-success fw-bold m-0">Member Directory</h5>
                        <button class="btn btn-success rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddMember"><i class="fa fa-user-plus me-2"></i>Add Member</button>
                    </div>
                    <table class="table table-hover datatable w-100">
                        <thead><tr><th>ID</th><th>Name</th><th>Contact Info</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach($app->getMembers() as $r): ?>
                            <tr>
                                <td class="text-muted" data-order="<?= $r['member_id'] ?>">
                                    M-<?= $r['member_id'] ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($r['name']) ?></div>
                                    <small class="text-muted"><i class="fa fa-map-marker-alt me-1"></i><?= htmlspecialchars(substr($r['address'], 0, 30)) ?>...</small>
                                </td>
                                <td>
                                    <div class="small"><i class="fa fa-envelope me-1 text-muted"></i><?= htmlspecialchars($r['email']) ?></div>
                                    <div class="small"><i class="fa fa-phone me-1 text-muted"></i><?= htmlspecialchars($r['phone']) ?></div>
                                </td>
                                <td>
                                    <button class="btn btn-outline-success btn-action edit-member-btn"
                                            data-id="<?= $r['member_id'] ?>"
                                            data-name="<?= htmlspecialchars($r['name']) ?>"
                                            data-email="<?= htmlspecialchars($r['email']) ?>"
                                            data-phone="<?= htmlspecialchars($r['phone']) ?>"
                                            data-address="<?= htmlspecialchars($r['address']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalEditMember"><i class="fa fa-pen"></i></button>
                                    <form method="POST" class="d-inline form-delete" onsubmit="return confirm('Delete this member?');">
                                        <input type="hidden" name="csrf_token" value="<?= $app->getCsrfToken() ?>">
                                        <input type="hidden" name="action" value="delete_member">
                                        <input type="hidden" name="id" value="<?= $r['member_id'] ?>">
                                        <button class="btn btn-outline-danger btn-action"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="loans">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-warning fw-bold m-0">Circulation</h5>
                        <button class="btn btn-warning text-white rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddLoan"><i class="fa fa-handshake me-2"></i>New Loan</button>
                    </div>
                    <table class="table table-hover datatable w-100">
                        <thead><tr><th>Status</th><th>Loan Details</th><th>Dates</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach($app->getLoans() as $r): 
                                $isOverdue = (strtotime($r['return_date']) < time()); 
                            ?>
                            <tr>
                                <td><?= $isOverdue ? '<span class="badge bg-danger">Overdue</span>' : '<span class="badge bg-success">Active</span>' ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($r['title']) ?></div>
                                    <small class="text-muted">Borrower: <?= htmlspecialchars($r['name']) ?></small>
                                </td>
                                <td>
                                    <small class="d-block text-muted">Out: <?= $r['loan_date'] ?></small>
                                    <small class="d-block fw-bold <?= $isOverdue ? 'text-danger' : 'text-success' ?>">Due: <?= $r['return_date'] ?></small>
                                </td>
                                <td>
                                    <button class="btn btn-outline-warning btn-action edit-loan-btn"
                                            data-id="<?= $r['loan_id'] ?>"
                                            data-loandate="<?= $r['loan_date'] ?>"
                                            data-returndate="<?= $r['return_date'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalEditLoan"><i class="fa fa-calendar-alt"></i></button>
                                    <form method="POST" class="d-inline form-delete" onsubmit="return confirm('Mark as returned (delete record)?');">
                                        <input type="hidden" name="csrf_token" value="<?= $app->getCsrfToken() ?>">
                                        <input type="hidden" name="action" value="delete_loan">
                                        <input type="hidden" name="id" value="<?= $r['loan_id'] ?>">
                                        <button class="btn btn-outline-secondary btn-action"><i class="fa fa-check"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="logs">
                    <h5 class="text-info fw-bold mb-4">Audit Trail</h5>
                    <table class="table table-sm table-hover datatable w-100 font-monospace small">
                        <thead><tr><th>Timestamp</th><th>Action</th><th>Description</th><th>IP Addr</th></tr></thead>
                        <tbody>
                            <?php foreach($app->getLogs() as $doc): ?>
                            <tr>
                                <td class="text-muted"><?= $doc->timestamp->toDateTime()->format('Y-m-d H:i:s') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($doc->action) ?></span></td>
                                <td><?= htmlspecialchars($doc->description) ?></td>
                                <td class="text-muted"><?= $doc->user_ip ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <?php if (isset($_SESSION['toast'])): ?>
    <div id="liveToast" class="toast align-items-center text-white bg-<?= $_SESSION['toast']['type'] ?> border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa fa-info-circle me-2"></i> <?= htmlspecialchars($_SESSION['toast']['msg']) ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    <?php unset($_SESSION['toast']); endif; ?>
</div>

<div class="modal fade" id="modalAddBook" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $app->getCsrfToken() ?>"><input type="hidden" name="action" value="add_book">
    <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fa fa-book me-2"></i>Add Book</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="form-floating mb-3"><input type="text" name="title" class="form-control" placeholder="T" required><label>Book Title</label></div>
        <div class="row g-2 mb-3">
            <div class="col"><div class="form-floating"><input type="text" name="author" class="form-control" placeholder="A" required><label>Author</label></div></div>
            <div class="col"><div class="form-floating"><input type="text" name="publisher" class="form-control" placeholder="P" required><label>Publisher</label></div></div>
        </div>
        <div class="row g-2">
            <div class="col"><div class="form-floating"><input type="number" name="year_published" class="form-control" placeholder="Y" required><label>Year</label></div></div>
            <div class="col"><div class="form-floating"><input type="text" name="genre" class="form-control" placeholder="G" required><label>Genre</label></div></div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Book</button></div>
</form></div></div>

<?php include 'modals_partial.php'; /* Suggestion: Move huge modal blocks to a partial file */ ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="script.js"></script>

<div class="modal fade" id="modalEditBook" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $app->getCsrfToken() ?>"><input type="hidden" name="action" value="edit_book">
    <input type="hidden" name="book_id" id="edit_book_id">
    <div class="modal-header bg-primary text-white"><h5 class="modal-title">Edit Book</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label>Title</label><input type="text" name="title" id="edit_book_title" class="form-control" required></div>
        <div class="mb-2"><label>Author</label><input type="text" name="author" id="edit_book_author" class="form-control" required></div>
        <div class="mb-2"><label>Publisher</label><input type="text" name="publisher" id="edit_book_publisher" class="form-control" required></div>
        <div class="row"><div class="col"><label>Year</label><input type="number" name="year_published" id="edit_book_year" class="form-control" required></div><div class="col"><label>Genre</label><input type="text" name="genre" id="edit_book_genre" class="form-control" required></div></div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-primary w-100">Update Book</button></div>
</form></div></div>

<div class="modal fade" id="modalAddMember" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $app->getCsrfToken() ?>"><input type="hidden" name="action" value="add_member">
    <div class="modal-header bg-success text-white"><h5 class="modal-title">Add Member</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label>Name</label><input type="text" name="name" class="form-control" required></div>
        <div class="mb-2"><label>Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="mb-2"><label>Phone</label><input type="text" name="phone" class="form-control" required></div>
        <div class="mb-2"><label>Address</label><textarea name="address" class="form-control" required></textarea></div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-success w-100">Save</button></div>
</form></div></div>

<div class="modal fade" id="modalEditMember" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $app->getCsrfToken() ?>"><input type="hidden" name="action" value="edit_member">
    <input type="hidden" name="member_id" id="edit_member_id">
    <div class="modal-header bg-success text-white"><h5 class="modal-title">Edit Member</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label>Name</label><input type="text" name="name" id="edit_member_name" class="form-control" required></div>
        <div class="mb-2"><label>Email</label><input type="email" name="email" id="edit_member_email" class="form-control" required></div>
        <div class="mb-2"><label>Phone</label><input type="text" name="phone" id="edit_member_phone" class="form-control" required></div>
        <div class="mb-2"><label>Address</label><textarea name="address" id="edit_member_address" class="form-control" required></textarea></div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-success w-100">Update Member</button></div>
</form></div></div>

<div class="modal fade" id="modalAddLoan" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $app->getCsrfToken() ?>"><input type="hidden" name="action" value="add_loan">
    <div class="modal-header bg-warning"><h5 class="modal-title">New Loan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-2"><label>Book</label><select name="book_id" class="form-select" required><?php foreach($app->getBooks() as $b) echo "<option value='{$b['book_id']}'>".htmlspecialchars($b['title'])."</option>"; ?></select></div>
        <div class="mb-2"><label>Member</label><select name="member_id" class="form-select" required><?php foreach($app->getMembers() as $m) echo "<option value='{$m['member_id']}'>".htmlspecialchars($m['name'])."</option>"; ?></select></div>
        <div class="row"><div class="col"><label>Loan Date</label><input type="date" name="loan_date" value="<?= date('Y-m-d') ?>" class="form-control" required></div><div class="col"><label>Return Date</label><input type="date" name="return_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" class="form-control" required></div></div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Confirm</button></div>
</form></div></div>

<div class="modal fade" id="modalEditLoan" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $app->getCsrfToken() ?>"><input type="hidden" name="action" value="edit_loan">
    <input type="hidden" name="loan_id" id="edit_loan_id">
    <div class="modal-header bg-warning"><h5 class="modal-title">Edit Loan Dates</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="alert alert-info small mb-3">To change books/members, delete and re-create.</div>
        <div class="row">
            <div class="col"><label>Loan Date</label><input type="date" name="loan_date" id="edit_loan_date" class="form-control" required></div>
            <div class="col"><label>Return Date</label><input type="date" name="return_date" id="edit_loan_return" class="form-control" required></div>
        </div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-warning w-100">Update Loan</button></div>
</form></div></div>

</body>
</html>