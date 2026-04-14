<?php
// Connection already exists from index.php
$n_query = "SELECT * FROM news ORDER BY event_date DESC";
$n_result = $conn->query($n_query);
?>

<section id="news" class="white-bg-section">
    <div class="section-header">
        <h2 style="color: #2c3e50; font-size: 2.5rem;">
            <i class="fas fa-newspaper" style="color: #e67e22;"></i> News & Activities
        </h2>
    </div>
    
    <div class="services-grid" id="news-list">
        <?php
        if ($n_result && $n_result->num_rows > 0) {
            $count = 0;
            while($row = $n_result->fetch_assoc()) {
                $count++;
                $extraClass = ($count > 8) ? 'hidden-news' : '';
                $displayStyle = ($count > 8) ? 'display: none;' : 'display: block;';
                $imgSrc = !empty($row['image']) ? "../uploads/news/".$row['image'] : "";
                ?>

                <div class="service-card <?php echo $extraClass; ?>" 
                     onclick="openNewsModal('<?php echo addslashes(htmlspecialchars($row['title'])); ?>', '<?php echo addslashes(htmlspecialchars($row['content'])); ?>', '<?php echo $imgSrc; ?>', '<?php echo date('M d, Y', strtotime($row['event_date'])); ?>')"
                     style="<?php echo $displayStyle; ?> background: white; border-radius: 15px; border-bottom: 4px solid #e67e22; transition: 0.3s; cursor: pointer; overflow: hidden;">
                    
                    <?php if($imgSrc): ?>
                        <div style="height: 180px; background-image: url('<?php echo $imgSrc; ?>'); background-size: cover; background-position: center;"></div>
                    <?php else: ?>
                        <div style="height: 180px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #ccc;">
                            <i class="fas fa-image fa-3x"></i>
                        </div>
                    <?php endif; ?>

                    <div style="padding: 25px; text-align: left;">
                        <span style="color: #e67e22; font-size: 0.8rem; font-weight: bold;"><?php echo date('M d, Y', strtotime($row['event_date'])); ?></span>
                        <h3 style="color: #2c3e50; margin: 10px 0;"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p style="color: #666; font-size: 0.85rem;"><?php echo substr(htmlspecialchars($row['content']), 0, 90); ?>...</p>
                        <span style="color: #e67e22; font-size: 0.8rem; font-weight: bold; display: block; margin-top: 15px;">Read Story <i class="fas fa-arrow-right"></i></span>
                    </div>
                </div>

                <?php
            }
        }
        ?>
    </div>

    <?php if ($n_result->num_rows > 8): ?>
    <div style="text-align: center; margin-top: 50px;">
         <button id="news-toggle-btn" onclick="toggleNews(event)" class="btn-green" style="background: #e67e22; padding: 12px 40px; border-radius: 30px; border: none; font-weight: 600; cursor: pointer;">
            See All Activities 
         </button>
    </div>
    <?php endif; ?>
</section>

<div id="newsModal" class="custom-modal" style="display:none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); overflow-y: auto;">
    <div style="background: white; margin: 50px auto; padding: 30px; width: 90%; max-width: 700px; border-radius: 15px; position: relative;">
        <span onclick="closeNewsModal()" style="position: absolute; right: 20px; top: 15px; font-size: 2rem; cursor: pointer; color: #333;">&times;</span>
        <span id="n-modal-date" style="color: #e67e22; font-weight: bold;"></span>
        <h2 id="n-modal-title" style="margin: 15px 0; color: #2c3e50;"></h2>
        <div id="n-modal-img" style="margin-bottom: 20px;"></div>
        <p id="n-modal-desc" style="color: #444; line-height: 1.8; white-space: pre-wrap;"></p>
    </div>
</div>

<script>
function openNewsModal(title, desc, img, date) {
    document.getElementById('n-modal-title').innerText = title;
    document.getElementById('n-modal-desc').innerText = desc;
    document.getElementById('n-modal-date').innerText = date;
    const imgCont = document.getElementById('n-modal-img');
    imgCont.innerHTML = img ? `<img src="${img}" style="width: 100%; border-radius: 10px; max-height: 400px; object-fit: cover;">` : "";
    document.getElementById('newsModal').style.display = "block";
    document.body.style.overflow = "hidden";
}

function closeNewsModal() { 
    document.getElementById('newsModal').style.display = "none"; 
    document.body.style.overflow = "auto";
}

function toggleNews(e) {
    const hidden = document.querySelectorAll('.hidden-news');
    const btn = document.getElementById('news-toggle-btn');
    if (btn.innerHTML.includes('See All')) {
        hidden.forEach(el => el.style.display = 'block');
        btn.innerHTML = 'Hide Activities <i class="fas fa-minus"></i>';
    } else {
        hidden.forEach(el => el.style.display = 'none');
        btn.innerHTML = 'See All Activities <i class="fas fa-plus"></i>';
        // Updated to scroll to the correct ID
        document.getElementById('news').scrollIntoView({ behavior: 'smooth' });
    }
}
</script>