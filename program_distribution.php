<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("db_connect.php");

/* Fetch Programs */
$program_query = "SELECT title FROM services WHERE status='Active' ORDER BY created_at DESC";
$program_result = $conn->query($program_query);

/* Fetch Barangays */
$barangay_query = "SELECT * FROM barangay ORDER BY brgy_name ASC";
$barangay_result = $conn->query($barangay_query);

/* GET FILTER VALUES */
$filter_program = isset($_GET['program']) ? $_GET['program'] : '';
$filter_barangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Program Distribution</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>

/* PAGE STYLE */

body, html{
background:#e9ecef;
font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
margin:0;
padding:0;
}

.top-nav{
background:#fff;
display:flex;
align-items:center;
padding:40px;
box-shadow:0 2px 10px rgba(0,0,0,0.1);
position:fixed;
top:0;
left:250px;
width:calc(100% - 250px);
height:70px;
z-index:1000;
}

.top-nav h1{
margin:0;
font-size:22px;
color:#1a3a5f;
}

.nav-sub{
font-size:15px;
color:#666;
margin:0;
}

.dashboard-wrapper{
padding:100px 25px 25px 25px;
}

.content-card{
background:#fff;
border-radius:12px;
padding:40px;
box-shadow:0 4px 20px rgba(0,0,0,0.05);
}

/* BUTTON */

.button-program{
background:#0056b3;
color:white;
border:none;
padding:12px 20px;
border-radius:8px;
cursor:pointer;
font-weight:600;
display:inline-flex;
align-items:center;
gap:10px;
font-size:15px;
}

/* FILTER BAR */

.filter-bar{
display:flex;
align-items:center;
gap:10px;
margin-bottom:20px;
flex-wrap:wrap;
}

.filter-bar select{
padding:8px;
border-radius:6px;
border:1px solid #ccc;
}

/* TABLE */

table{
width:100%;
border-collapse:collapse;
}

thead{
background:#f8f9fa;
}

th,td{
padding:10px;
text-align:left;
}

tbody tr{
border-bottom:1px solid #eee;
}

/* MODAL */

.modal{
display:none;
position:fixed;
z-index:2000;
left:0;
top:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.5);
}

.modal-content{
background:white;
margin:5% auto;
padding:20px;
border-radius:8px;
width:80%;
max-height:85vh;
overflow:auto;
}

.close{
float:right;
font-size:22px;
cursor:pointer;
}

</style>
</head>

<body>

<!-- NAVBAR -->
<header class="top-nav">
<div>
<h1>Program Distribution</h1>
<p class="nav-sub">Manage assistance distribution for PWD beneficiaries.</p>
</div>
</header>

<div class="dashboard-wrapper">

<main class="content-card">

<!-- BUTTON + FILTER -->

<form method="GET">

<div class="filter-bar">

<button type="button" class="button-program" onclick="openDistributionModal()">
<i class="fas fa-plus"></i> New Distribution
</button>

<select name="program" onchange="this.form.submit()">
<option value="">All Programs</option>

<?php
$program_list = $conn->query("SELECT DISTINCT program_name FROM distribution_logs ORDER BY program_name ASC");
while($p = $program_list->fetch_assoc()):
?>

<option value="<?= $p['program_name'] ?>" <?= ($filter_program==$p['program_name'])?'selected':'' ?>>
<?= $p['program_name'] ?>
</option>

<?php endwhile; ?>

</select>

<select name="barangay" onchange="this.form.submit()">

<option value="">All Barangays</option>

<?php while($b=$barangay_result->fetch_assoc()): ?>

<option value="<?= $b['id'] ?>" <?= ($filter_barangay==$b['id'])?'selected':'' ?>>
<?= $b['brgy_name'] ?>
</option>

<?php endwhile; ?>

</select>

</div>

</form>

<!-- DISTRIBUTION HISTORY TABLE -->

<table>

<thead>
<tr>
<th>Date Encoded</th>
<th>Program</th>
<th>Recipient</th>
<th>Barangay</th>
<th>Remarks</th>
</tr>
</thead>

<tbody>

<?php

$log_query = "SELECT l.*, p.first_name, p.last_name, b.brgy_name
FROM distribution_logs l
JOIN pwd p ON l.pwd_id=p.id
JOIN barangay b ON l.barangay_id=b.id
WHERE 1=1";

if($filter_program != ""){
$log_query .= " AND l.program_name='".mysqli_real_escape_string($conn,$filter_program)."'";
}

if($filter_barangay != ""){
$log_query .= " AND b.id='".mysqli_real_escape_string($conn,$filter_barangay)."'";
}

$log_query .= " ORDER BY l.date_encoded DESC";

$log_result = $conn->query($log_query);

if($log_result && $log_result->num_rows>0):

while($log=$log_result->fetch_assoc()):

?>

<tr>

<td><?= date("M d Y h:i A",strtotime($log['date_encoded'])) ?></td>

<td>
<b><?= htmlspecialchars($log['program_name']) ?></b>
</td>

<td>
<?= strtoupper($log['last_name'].", ".$log['first_name']) ?>
</td>

<td>
<?= htmlspecialchars($log['brgy_name']) ?>
</td>

<td>
<?= htmlspecialchars($log['remarks']) ?>
</td>

</tr>

<?php endwhile; else: ?>

<tr>
<td colspan="5" align="center">No distribution history found.</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</main>
</div>

<!-- DISTRIBUTION MODAL -->

<div id="distributionModal" class="modal">

<div class="modal-content">

<span class="close" onclick="closeDistributionModal()">&times;</span>

<h2>New Program Distribution</h2>

<form action="save_bulk_distribution.php" method="POST">

<br>

<label><b>Select Program</b></label>

<select name="program_name" id="programSelect" onchange="toggleOtherInput()" required>

<option value="">-- Choose Program --</option>

<?php
if($program_result && $program_result->num_rows>0):
while($p=$program_result->fetch_assoc()):
?>

<option value="<?= htmlspecialchars($p['title']) ?>">
<?= htmlspecialchars($p['title']) ?>
</option>

<?php endwhile; endif; ?>

<option value="Other">Other</option>
<option value="General Distribution">General Distribution</option>

</select>

<br><br>

<div id="otherInputDiv" style="display:none">

<input type="text" name="other_program_name" placeholder="Enter Program Name">

<br><br>

</div>

<label><b>Remarks</b></label>

<input type="text" name="remarks" style="width:100%; padding:8px;">

<br><br>

<!-- PWD TABLE -->

<table>

<thead>
<tr>
<th width="40"><input type="checkbox" id="selectAll"></th>
<th>PWD Name</th>
<th>Barangay</th>
<th>Disability</th>
</tr>
</thead>

<tbody>

<?php

$pwd_query="SELECT pwd.id,pwd.first_name,pwd.last_name,
barangay.brgy_name,
disability_type.disability_name

FROM pwd
JOIN barangay ON pwd.barangay_id=barangay.id
JOIN disability_type ON pwd.disability_type=disability_type.id

WHERE pwd.status='Official'

ORDER BY barangay.brgy_name,pwd.last_name";

$pwd_result=$conn->query($pwd_query);

if($pwd_result && $pwd_result->num_rows>0):

while($row=$pwd_result->fetch_assoc()):
?>

<tr>

<td>
<input type="checkbox" name="pwd_ids[]" value="<?= $row['id'] ?>" class="pwd-checkbox">
</td>

<td>
<?= strtoupper($row['last_name'].", ".$row['first_name']) ?>
</td>

<td>
<?= $row['brgy_name'] ?>
</td>

<td>
<?= $row['disability_name'] ?>
</td>

</tr>

<?php endwhile; endif; ?>

</tbody>
</table>

<br>

<button type="submit" class="button-program">
<i class="fas fa-save"></i> Save Distribution
</button>

</form>

</div>
</div>

<script>

/* OPEN MODAL */
function openDistributionModal(){
document.getElementById("distributionModal").style.display="block";
}

/* CLOSE MODAL */
function closeDistributionModal(){
document.getElementById("distributionModal").style.display="none";
}

/* PROGRAM OTHER INPUT */

function toggleOtherInput(){

const select=document.getElementById("programSelect");
const otherDiv=document.getElementById("otherInputDiv");

if(select.value==="Other"){
otherDiv.style.display="block";
}else{
otherDiv.style.display="none";
}

}

/* SELECT ALL */

document.addEventListener("change",function(e){

if(e.target.id==="selectAll"){

document.querySelectorAll(".pwd-checkbox").forEach(cb=>{
cb.checked=e.target.checked;
});

}

});

</script>

</body>
</html>