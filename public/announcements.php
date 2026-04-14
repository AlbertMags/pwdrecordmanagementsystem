<?php
include("../db_connect.php");
$query = "SELECT * FROM announcements ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<section id="announcements" class="white-bg-section">
    <div class="section-header">
        <h2 style="color: #2c3e50; font-size: 2rem;">
            <i class="fas fa-bullhorn" style="color:#3498db;"></i> Latest Announcements
        </h2>
    </div>
    
    <div class="announcement-container" id="announcement-list">
        <?php
        if ($result && $result->num_rows > 0) {
            $count = 0;
            while($row = $result->fetch_assoc()) {
                $count++;
                $extraClass = ($count > 8) ? 'hidden-announcement' : '';
                $displayStyle = ($count > 8) ? 'display: none;' : 'display: block;';
                $imgSrc = !empty($row['image']) ? "../uploads/announcements/".$row['image'] : "";
                ?>
                
                <div class="announcement-box <?php echo $extraClass; ?>" 
                     onclick="openAnnModal('<?php echo addslashes(htmlspecialchars($row['title'])); ?>', '<?php echo addslashes(htmlspecialchars($row['message'])); ?>', '<?php echo $imgSrc; ?>', '<?php echo date('M d, Y', strtotime($row['created_at'])); ?>')"
                     style="<?php echo $displayStyle; ?> transition: all 0.3s ease; cursor: pointer;">
                    
                    <span class="date"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                    <h4><?php echo htmlspecialchars($row['title']); ?></h4>

                    <?php if (!empty($row['image'])): ?>
                        <div class="announcement-image" style="margin: 15px 0;">
                            <img src="<?php echo $imgSrc; ?>" style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px;">
                        </div>
                    <?php endif; ?>

                    <p><?php echo nl2br(htmlspecialchars(substr($row['message'], 0, 150))); ?>...</p>
                    <small style="color: #3498db; font-weight: 600;">Click to read more</small>
                </div>

                <?php
            }
        }
        ?>
    </div>

    <?php if ($result->num_rows > 8): ?>
    <div style="text-align: center; margin-top: 30px;">
         <button id="toggle-btn" onclick="toggleAnnouncements(event)" class="btn-bluue" style="padding: 12px 30px; cursor: pointer;
          border-radius: 30px; border: none; font-weight: 600;">
            See All Announcements 
         </button>
    </div>
    <?php endif; ?>
</section>

<div id="annModal" class="custom-modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeAnnModal()">&times;</span>
        <span id="modal-date" style="color: #7f8c8d; font-size: 0.9rem;"></span>
        <h2 id="modal-title" style="margin: 10px 0; color: #2c3e50;"></h2>
        <div id="modal-img-container"></div>
        <p id="modal-desc" style="line-height: 1.6; color: #444; margin-top: 15px;"></p>
    </div>
</div>

<style>
.custom-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px); }
.modal-content { background-color: white; margin: 5% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 700px; position: relative; max-height: 85vh; overflow-y: auto; }
.close-modal { position: absolute; right: 20px; top: 10px; font-size: 30px; cursor: pointer; color: #333; }
.announcement-box:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); border-color: #3498db; }
</style>

<script>
function openAnnModal(title, desc, img, date) {
    document.getElementById('modal-title').innerText = title;
    document.getElementById('modal-desc').innerHTML = desc.replace(/\n/g, "<br>");
    document.getElementById('modal-date').innerText = date;
    const imgCont = document.getElementById('modal-img-container');
    imgCont.innerHTML = img ? `<img src="${img}" style="width:100%; border-radius:10px; margin-top:10px; max-height:400px; object-fit:contain; background:#f9f9f9;">` : "";
    document.getElementById('annModal').style.display = "block";
}
function closeAnnModal() { document.getElementById('annModal').style.display = "none"; }
window.onclick = function(event) { 
    if (event.target == document.getElementById('annModal')) closeAnnModal(); 
}

function toggleAnnouncements(e) {
    e.stopPropagation();
    const hiddenBoxes = document.querySelectorAll('.hidden-announcement');
    const btn = document.getElementById('toggle-btn');
    if (btn.innerHTML.includes('See All')) {
        hiddenBoxes.forEach(box => { box.style.display = 'block'; box.style.opacity = '1'; });
        btn.innerHTML = 'Hide Announcements <i class="fas fa-minus"></i>';
    } else {
        hiddenBoxes.forEach(box => { box.style.display = 'none'; });
        btn.innerHTML = 'See All Announcements <i class="fas fa-plus"></i>';
        document.getElementById('announcements').scrollIntoView({ behavior: 'smooth' });
    }
}
</script>