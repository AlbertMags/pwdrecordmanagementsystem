<?php
include("../db_connect.php");
$query = "SELECT * FROM services ORDER BY title ASC";
$result = $conn->query($query);
?>

<section id="services" class="gray-bg-section">
    <div class="section-header">
        <h2 style="color: #2c3e50; font-size: 2.5rem;">
            <i class="fas fa-hand-holding-heart" style="color: #2ecc71;"></i> Available Services
        </h2>
    </div>
    
    <div class="services-grid" id="services-list">
        <?php
        if ($result && $result->num_rows > 0) {
            $count = 0;
            while($row = $result->fetch_assoc()) {
                $count++;
                $extraClass = ($count > 8) ? 'hidden-service' : '';
                $displayStyle = ($count > 8) ? 'display: none;' : 'display: block;';
                ?>

                <div class="service-card <?php echo $extraClass; ?>" 
                     onclick="openServiceModal('<?php echo addslashes(htmlspecialchars($row['title'])); ?>', '<?php echo addslashes(htmlspecialchars($row['description'])); ?>', '<?php echo $row['category']; ?>', '<?php echo $row['provider']; ?>', '<?php echo $row['location']; ?>', '<?php echo $row['schedule']; ?>', '<?php echo $row['contact']; ?>')"
                     style="<?php echo $displayStyle; ?> background: white; padding: 30px; border-radius: 15px; border-bottom: 4px solid #2ecc71; transition: all 0.3s ease; cursor: pointer;">
                    
                     <h3 style="color: #2c3e50;"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p style="color: #666; font-size: 0.85rem; margin: 10px 0;"><?php echo substr(htmlspecialchars($row['description']), 0, 80); ?>...</p>
                    <span style="color: #2ecc71; font-size: 0.8rem; font-weight: bold;">View Details <i class="fas fa-arrow-right"></i></span>
                </div>

                <?php
            }
        }
        ?>
    </div>

    <?php if ($result->num_rows > 8): ?>
    <div style="text-align: center; margin-top: 50px;">
         <button id="service-toggle-btn" onclick="toggleServices(event)" class="btn-green" style="padding: 12px 40px; cursor: pointer; border-radius: 30px; border: none; font-weight: 600;">
            See All Services
         </button>
    </div>
    <?php endif; ?>
</section>

<div id="serviceModal" class="custom-modal">
    <div class="modal-content" style="border-top: 5px solid #2ecc71;">
        <span class="close-modal" onclick="closeServiceModal()">&times;</span>
        <span id="s-modal-cat" style="background: #e8f8f0; color: #27ae60; padding: 4px 10px; border-radius: 15px; font-size: 0.8rem;"></span>
        <h2 id="s-modal-title" style="margin: 15px 0;"></h2>
        <p id="s-modal-desc" style="color: #555; line-height: 1.6;"></p>
        
        <div style="background: #6f6c6c; padding: 20px; border-radius: 10px; margin-top: 20px;">
            <p><strong><i class="fas fa-building"></i> Provider:</strong> <span id="s-modal-prov"></span></p>
            <p><strong><i class="fas fa-map-marker-alt"></i> Location:</strong> <span id="s-modal-loc"></span></p>
            <p><strong><i class="fas fa-clock"></i> Schedule:</strong> <span id="s-modal-sched"></span></p>
            <p><strong><i class="fas fa-phone"></i> Contact:</strong> <span id="s-modal-phone"></span></p>
        </div>
    </div>
</div>

<script>
function openServiceModal(title, desc, cat, prov, loc, sched, phone) {
    document.getElementById('s-modal-title').innerText = title;
    document.getElementById('s-modal-desc').innerText = desc;
    document.getElementById('s-modal-cat').innerText = cat;
    document.getElementById('s-modal-prov').innerText = prov;
    document.getElementById('s-modal-loc').innerText = loc;
    document.getElementById('s-modal-sched').innerText = sched;
    document.getElementById('s-modal-phone').innerText = phone;
    document.getElementById('serviceModal').style.display = "block";
}
function closeServiceModal() { document.getElementById('serviceModal').style.display = "none"; }

function toggleServices(e) {
    e.stopPropagation();
    const hiddenCards = document.querySelectorAll('.hidden-service');
    const btn = document.getElementById('service-toggle-btn');
    if (btn.innerHTML.includes('See All')) {
        hiddenCards.forEach(card => { card.style.display = 'block'; });
        btn.innerHTML = 'Hide Services <i class="fas fa-minus"></i>';
    } else {
        hiddenCards.forEach(card => { card.style.display = 'none'; });
        btn.innerHTML = 'See All Services <i class="fas fa-plus"></i>';
        document.getElementById('services').scrollIntoView({ behavior: 'smooth' });
    }
}
</script>