<?php
include("../db_connect.php");
$brgy_id = $_SESSION['barangay_id'];

// 1. Gender Data (Filtered by Barangay and Accepted Status)
$genderQuery = "SELECT gender, COUNT(*) AS count FROM pwd WHERE status = 'Official' AND barangay_id = '$brgy_id' GROUP BY gender";
$genderResult = $conn->query($genderQuery);
$gender_data = [];
while ($row = $genderResult->fetch_assoc()) { $gender_data[] = $row; }

// 2. Disability Type Data (Horizontal Bar Logic)
$disabilityQuery = "SELECT d.disability_name, COUNT(*) AS count 
                    FROM pwd p 
                    JOIN disability_type d ON p.disability_type = d.id 
                    WHERE p.status = 'Official' AND p.barangay_id = '$brgy_id'
                    GROUP BY p.disability_type";
$disabilityResult = $conn->query($disabilityQuery);
$disability_data = [];
while ($row = $disabilityResult->fetch_assoc()) { $disability_data[] = $row; }

// 3. Age Distribution Data (Matching your grouping logic)
$age_groups = ["0–17" => 0, "18–30" => 0, "31–45" => 0, "46–60" => 0, "61+" => 0];
$ageQuery = "SELECT birth_date FROM pwd WHERE status = 'Official' AND barangay_id = '$brgy_id'";
$ageResult = $conn->query($ageQuery);
$current_year = date('Y');

while ($row = $ageResult->fetch_assoc()) {
    $age = $current_year - date('Y', strtotime($row['birth_date']));
    if ($age <= 17) $age_groups["0–17"]++;
    elseif ($age <= 30) $age_groups["18–30"]++;
    elseif ($age <= 45) $age_groups["31–45"]++;
    elseif ($age <= 60) $age_groups["46–60"]++;
    else $age_groups["61+"]++;
}
?>

<div class="barangay-header">
    <h2><i class="fas fa-chart-bar"></i> Barangay Graphical Report</h2>
    <p>Statistics for Accepted PWDs in your Barangay</p>
</div>

<div class="charts-row">
  <div class="chart-container">
    <h3>Gender Distribution</h3>
    <canvas id="genderChart"></canvas>
  </div>

  <div class="chart-container">
    <h3>Disability Type Distribution</h3>
    <canvas id="disabilityChart"></canvas>
  </div>
</div>

<div class="charts-row">
  <div class="chart-container" style="width: 100%; max-width: 600px; margin: auto;">
    <h3>Age Distribution</h3>
    <canvas id="ageChart"></canvas>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  // GENDER CHART
  new Chart(document.getElementById('genderChart').getContext('2d'), {
    type: 'pie',
    data: {
      labels: <?php echo json_encode(array_column($gender_data, 'gender')); ?>,
      datasets: [{
        data: <?php echo json_encode(array_column($gender_data, 'count')); ?>,
        backgroundColor: ['#36A2EB', '#FF6384'],
        borderColor: '#fff',
        borderWidth: 1
      }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  });

  // DISABILITY CHART (Horizontal Bar)
  new Chart(document.getElementById('disabilityChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_column($disability_data, 'disability_name')); ?>,
      datasets: [{
        label: 'PWD Count',
        data: <?php echo json_encode(array_column($disability_data, 'count')); ?>,
        backgroundColor: '#28a745',
        borderRadius: 5
      }]
    },
    options: {
      responsive: true,
      indexAxis: 'y', // Horizontal
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });

  // AGE CHART
  new Chart(document.getElementById('ageChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_keys($age_groups)); ?>,
      datasets: [{
        label: 'PWD Count',
        data: <?php echo json_encode(array_values($age_groups)); ?>,
        backgroundColor: '#4CAF50'
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });
</script>