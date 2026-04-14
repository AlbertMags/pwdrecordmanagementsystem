<?php
include("db_connect.php");

// 1. Gender data (FIXED: Filtered by status = 'Accepted')
$query = "SELECT gender, COUNT(*) AS count 
          FROM pwd 
          WHERE status = 'Official' 
          GROUP BY gender";
$result = $conn->query($query);
$gender_data = [];
while ($row = $result->fetch_assoc()) {
    $gender_data[] = $row;
}

// 2. Disability Type Data (FIXED: Filtered by status = 'Accepted')
$disabilityQuery = "SELECT d.disability_name, COUNT(*) AS count 
                    FROM pwd p 
                    JOIN disability_type d ON p.disability_type = d.id 
                    WHERE p.status = 'Official' 
                    GROUP BY p.disability_type";
$disabilityResult = $conn->query($disabilityQuery);
$disability_data = [];
while ($row = $disabilityResult->fetch_assoc()) {
    $disability_data[] = $row;
}

// 3. Get PWD count per barangay (FIXED: Filtered by status = 'Accepted')
$barangayQuery = "
    SELECT barangay.brgy_name AS barangay, COUNT(*) AS count 
    FROM pwd 
    JOIN barangay ON pwd.barangay_id = barangay.id 
    WHERE pwd.status = 'Official'
    GROUP BY barangay.id 
    ORDER BY barangay.brgy_name ASC
";
$barangayResult = $conn->query($barangayQuery);
$barangay_data = [];
while ($row = $barangayResult->fetch_assoc()) {
    $barangay_data[] = $row;
}

// 4. Age Distribution Data (FIXED: Filtered by status = 'Accepted')
$age_groups = [
    "0–17" => 0,
    "18–30" => 0,
    "31–45" => 0,
    "46–60" => 0,
    "61+" => 0,
];

$query = "SELECT birth_date FROM pwd WHERE status = 'Official'";
$result = $conn->query($query);

$current_year = date('Y');

while ($row = $result->fetch_assoc()) {
    $age = $current_year - date('Y', strtotime($row['birth_date']));

    if ($age <= 17) {
        $age_groups["0–17"]++;
    } elseif ($age <= 30) {
        $age_groups["18–30"]++;
    } elseif ($age <= 45) {
        $age_groups["31–45"]++;
    } elseif ($age <= 60) {
        $age_groups["46–60"]++;
    } else {
        $age_groups["61+"]++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Graphical Report</title>
  <link rel="stylesheet" href="style.css" /> 
</head>
<body>
<div class="charts-row">
  <div class="chart-container">
    <h3>Gender Distribution</h3>
    <canvas id="genderChart"></canvas>
  </div>


  <div class="chart-container">
    <h3>Disability Type Distribution </h3>
    <canvas id="disabilityChart"></canvas>
  </div>
</div>

<div class="charts-row">
  <div class="chart-container">
    <h3>Barangay Distribution </h3>
    <canvas id="barangayChart"></canvas>
  </div>

  <div class="chart-container">
    <h3>Age Distribution </h3>
    <canvas id="ageChart"></canvas>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // PHP to JS
  const genderLabels = <?php echo json_encode(array_column($gender_data, 'gender')); ?>;
  const genderCounts = <?php echo json_encode(array_column($gender_data, 'count')); ?>;

  const disabilityLabels = <?php echo json_encode(array_column($disability_data, 'disability_name')); ?>;
  const disabilityCounts = <?php echo json_encode(array_column($disability_data, 'count')); ?>;

  // Gender Chart
  new Chart(document.getElementById('genderChart').getContext('2d'), {
    type: 'pie',
    data: {
      labels: genderLabels,
      datasets: [{
        data: genderCounts,
        backgroundColor: ['#36A2EB', '#FF6384'],
        borderColor: '#fff',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom' },
        title: {
          display: true,
          text: 'PWD Gender Distribution'
        }
      }
    }
  });

  // Disability Chart (FIXED: Changed to Horizontal Bar)
  new Chart(document.getElementById('disabilityChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: disabilityLabels,
      datasets: [{
        label: 'Accepted PWDs per Disability Type',
        data: disabilityCounts,
        backgroundColor: '#f45c0a',
        borderRadius: 5
      }]
    },
    options: {
      responsive: true,
      indexAxis: 'y', // <--- KEY FIX: Makes the chart horizontal
      plugins: {
        title: {
          display: true,
          text: 'Disability Type Distribution'
        },
        legend: {
          display: false
        }
      },
      scales: {
        x: { // The count axis is now X
          beginAtZero: true,
          ticks: {
            precision: 0
          }
        },
        y: { // The label axis is now Y
            grid: {
                display: false
            }
        }
      }
    }
  });


  // Barangay Data from PHP
const barangayLabels = <?php echo json_encode(array_column($barangay_data, 'barangay')); ?>;
const barangayCounts = <?php echo json_encode(array_column($barangay_data, 'count')); ?>;

const ctxBarangay = document.getElementById('barangayChart').getContext('2d');
const barangayChart = new Chart(ctxBarangay, {
    type: 'bar', 
    data: {
        labels: barangayLabels,
        datasets: [{
            label: 'PWDs per Barangay',
            data: barangayCounts,
            backgroundColor: '#17a2b8'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'PWDs per Barangay'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});


const ageLabels = <?php echo json_encode(array_keys($age_groups)); ?>;
const ageCounts = <?php echo json_encode(array_values($age_groups)); ?>;

const ctxAge = document.getElementById('ageChart').getContext('2d');
new Chart(ctxAge, {
    type: 'bar',
    data: {
        labels: ageLabels,
        datasets: [{
            label: 'PWDs by Age Group',
            data: ageCounts,
            backgroundColor: '#05f545'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: 'PWD Age Group Distribution'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

</script>

</body>
</html>