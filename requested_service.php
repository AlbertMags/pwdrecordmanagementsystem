<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
include("db_connect.php");

/**
 * ACTION: APPROVE REQUEST
 */
if (isset($_GET['approve_request'])) {
    $req_id = intval($_GET['approve_request']);
    $stmt = $conn->prepare("SELECT * FROM service_requests WHERE id = ?");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if ($request) {
        $update = $conn->prepare("UPDATE service_requests SET status = 'Approved' WHERE id = ?");
        $update->bind_param("i", $req_id);
        $update->execute();

        // Add to available services list
        $title = $request['service_type'];
        $desc = "Approved request: " . $request['remarks'];
        $add_service = $conn->prepare("INSERT INTO services (title, description, status) VALUES (?, ?, 'Active')");
        $add_service->bind_param("ss", $title, $desc);
        $add_service->execute();

        // --- CONSISTENT REDIRECT ---
        $_SESSION['active_tab'] = 'requests'; // Set the tab to stay on
        $_SESSION['success_msg'] = "Request approved successfully!";
        header("Location: support_center"); // Clean URL redirect
        exit();
    }
}

/**
 * ACTION: DECLINE REQUEST
 */
if (isset($_GET['decline_request'])) {
    $req_id = intval($_GET['decline_request']);
    $stmt = $conn->prepare("UPDATE service_requests SET status = 'Declined' WHERE id = ?");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();

    // --- CONSISTENT REDIRECT ---
    $_SESSION['active_tab'] = 'requests';
    $_SESSION['error_msg'] = "Request has been declined.";
    header("Location: support_center"); // Clean URL redirect
    exit();
}
?>

<div class="card shadow-sm border-0 mt-3">
    <div class="card-body">
        <h4 class="mb-4 text-primary"><i class="fas fa-list-ul me-2"></i>Resident Service Applications</h4>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Resident</th>
                        <th>Service Requested</th>
                        <th>Remarks</th>
                        <th>Date Requested</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // FIXED QUERY: Join with 'pwd' table and select first_name/last_name
                    $query = "SELECT r.*, p.first_name, p.last_name 
                              FROM service_requests r 
                              LEFT JOIN pwd p ON r.pwd_id = p.id 
                              ORDER BY r.created_at DESC";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0):
                        while ($row = mysqli_fetch_assoc($result)):
                            $status_class = 'bg-warning';
                            if($row['status'] == 'Approved') $status_class = 'bg-success';
                            if($row['status'] == 'Declined') $status_class = 'bg-danger';
                            
                            $fullname = $row['first_name'] . ' ' . $row['last_name'];
                    ?>
                    <tr>
                        <td><strong><?php echo !empty($row['firstname']) ? htmlspecialchars($fullname) : "ID: ".$row['pwd_id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($row['service_type']); ?></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($row['remarks']); ?></small></td>
                        <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                        <td><span class="badge <?php echo $status_class; ?>"><?php echo $row['status']; ?></span></td>
                        <td class="text-center">
                            <?php if ($row['status'] == 'Pending'): ?>
                                <a href="requested_service.php?approve_request=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this request?')">
                                   <i class="fas fa-check"></i> Approve
                                </a>
                                <a href="requested_service.php?decline_request=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger" onclick="return confirm('Decline this request?')">
                                   <i class="fas fa-times"></i> Decline
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">Processed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">No service requests found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>