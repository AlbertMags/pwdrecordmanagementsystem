<?php
// Since this is loaded via AJAX or included in index.php, 
// $conn and session are already available.
?>

<style>
    .doh-form-wrapper { 
        background: #fff; 
        padding: 40px; 
        color: #000; 
        font-family: Arial, sans-serif; 
        border: 1px solid #000;
        margin: 10px;
        max-width: 1000px;
    }
    
    .doh-header-container {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        border-bottom: 2px solid #000;
        padding-bottom: 15px;
    }

    .doh-logo {
        width: 100px;
        height: auto;
        margin-right: 30px;
    }

    .header-text {
        flex: 1;
        text-align: center;
        line-height: 1.2;
    }

    .header-text p { margin: 1px 0; font-size: 14px; }

    .form-title { 
        font-weight: bold; 
        font-size: 18px; 
        text-align: center;
        margin: 15px 0; 
        text-transform: uppercase;
    }

    .assessment-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }

    .column-group {
        display: block; 
        text-align: left;
    }

    .column-title { 
        font-weight: bold; 
        text-decoration: underline; 
        font-style: italic; 
        font-size: 16px;
        margin-bottom: 10px; 
        margin-top: 15px;
        display: block; 
    }

    /* Strict Left Alignment Fix */
    .check-item { 
        display: block; 
        margin-bottom: 5px;
        font-size: 14px;
        cursor: pointer;
        line-height: 1.5;
    }

    .check-item input[type="checkbox"] { 
        float: left;
        width: 18px;
        height: 18px; 
        margin-right: 12px; 
        margin-top: 2px;
    }

    .check-item::after {
        content: "";
        display: table;
        clear: both;
    }

    .line-input {
        border: none;
        border-bottom: 1px solid #000;
        outline: none;
        padding: 2px 5px;
        font-size: 15px;
        background: transparent;
    }

    textarea { 
        width: 100%; 
        border: 1px solid #000; 
        height: 100px; 
        font-size: 15px; 
        padding: 10px; 
        margin-top: 5px;
        outline: none;
        resize: none;
    }

    .bottom-section {
        border-top: 1px solid #000;
        margin-top: 25px;
        padding-top: 20px;
    }

    .submit-btn {
        width: 100%; 
        padding: 15px; 
        background: #1a5c20; 
        color: white; 
        font-weight: bold; 
        font-size: 18px; 
        border: none; 
        margin-top: 20px; 
        cursor: pointer;
        border-radius: 4px;
    }
</style>

<div class="doh-form-wrapper">
    <div class="doh-header-container">
        <img src="../uploads/doh.png" class="doh-logo" alt="DOH Logo">
        <div class="header-text">
            <p>DEPARTMENT OF HEALTH</p>
            <p><strong>Philippine Registry For Persons with Disabilities Version 4.0</strong></p>
            <p>Application Form</p>
        </div>
    </div>

    <div class="form-title">FUNCTIONAL ASSESSMENT</div>
    
    <p style="text-align: left; font-size: 15px;">Applicant: <strong id="applicant_name_display">---</strong></p>

    <form method="POST" action="process_assessment.php">
        <input type="hidden" name="pwd_id" id="pwd_id_input">

        <div class="assessment-grid">
            <div class="column-group">
                <span class="column-title">Musculoskeletal, Orthopedic, Mobility</span>
                <?php 
                $musculo = [
                    "001 Weak, paralyzed left leg", "002 Weak, paralyzed right leg", 
                    "003 Underdeveloped left leg", "004 Underdeveloped right leg", 
                    "005 Underdeveloped both legs", "006 Missing left leg", 
                    "007 Missing right leg", "008 Missing both legs", 
                    "009 Missing left foot", "010 Missing right foot", 
                    "011 Missing both feet", "012 Missing, paralyzed left arm", 
                    "013 Missing, paralyzed right arm", "014 Missing, paralyzed both arm", 
                    "015 Underdeveloped left arm", "016 Underdeveloped right arm", 
                    "017 Underdeveloped both arm", "018 Missing left arm", 
                    "019 Missing right arm", "020 Missing both arms", 
                    "021 Missing left hand", "022 Missing right hand", 
                    "023 Missing both hands", "024 Polio"
                ];
                foreach($musculo as $item) {
                    echo "<label class='check-item'><input type='checkbox' name='musculo[]' value='$item'> $item</label>";
                }
                ?>
                
                <span class="column-title">Motor disability</span>
                <label class="check-item"><input type="checkbox" name="motor[]" value="Cerebral Palsy"> 001 Cerebral Palsy</label>
                <label class="check-item"><input type="checkbox" name="motor[]" value="Stroke"> 002 Stroke</label>
                <label class="check-item"><input type="checkbox" name="motor[]" value="Arthritis"> 003 Severe Debilitating Arthritis</label>
                <label class="check-item"><input type="checkbox" name="motor[]" value="Epilepsy"> 004 Epilepsy</label>
            </div>

            <div class="column-group">
                <span class="column-title">Visual Impairment</span>
                <label class="check-item"><input type="checkbox" name="visual[]" value="Total visual left"> 001 Total visual impairment, left</label>
                <label class="check-item"><input type="checkbox" name="visual[]" value="Total visual right"> 002 Total visual impairment, right</label>
                <label class="check-item"><input type="checkbox" name="visual[]" value="Total visual both"> 003 Total visual impairment, both</label>

                <span class="column-title">Hearing Impairment</span>
                <label class="check-item"><input type="checkbox" name="hearing[]" value="Total hearing both"> 003 Total hearing, both</label>
                <label class="check-item"><input type="checkbox" name="hearing[]" value="Partial hearing both"> 006 Partial hearing, both</label>

                <span class="column-title">Speech, Language, Communication</span>
                <label class="check-item"><input type="checkbox" name="speech[]" value="Total speech impairment"> 001 Total speech impairment</label>
                <label class="check-item"><input type="checkbox" name="speech[]" value="Partial unclear"> 002 Partial (Unclear speech)</label>
                <label class="check-item"><input type="checkbox" name="speech[]" value="Partial irrelevant"> 003 Partial (Irrelevant words)</label>

                <span class="column-title">Mental Impairment</span>
                <label class="check-item"><input type="checkbox" name="mental[]" value="Mentally Ill"> 001 Mentally Ill</label>
                <label class="check-item"><input type="checkbox" name="mental[]" value="Mentally Retarded"> 002 Mentally Retarded</label>
                <label class="check-item"><input type="checkbox" name="mental[]" value="Autistic"> 003 Autistic</label>

                <span class="column-title">Deformities</span>
                <label class="check-item"><input type="checkbox" name="deformities[]" value="Hunchback"> 001 Hunchback</label>
                <label class="check-item"><input type="checkbox" name="deformities[]" value="Cleft palate"> 002 Cleft palate</label>
           
                <span class="column-title">Etiology</span>
                <label class="check-item"><input type="checkbox" name="etiology[]" value="Inborn"> 001 Inborn</label>
                <label class="check-item">
                    <input type="checkbox" name="etiology[]" value="Acquired"> 002 Acquired by: 
                    <input type="text" name="acquired_details" class="line-input" style="width: 140px; margin-left: 5px;">
                </label>

                <span class="column-title" style="margin-top: 25px;">Assistive Device Needed</span>
                <label class="check-item"><input type="checkbox" name="devices[]" value="Wheelchair"> 001 Wheelchair</label>
                <label class="check-item"><input type="checkbox" name="devices[]" value="Quad cane"> 003 Quad cane</label>
                <label class="check-item">
                    Others: <input type="text" name="other_device" class="line-input" style="width: 180px;">
                </label>
            </div>
        </div>

        <div class="bottom-section">
            <strong>MEDICAL CERTIFICATE / RECOMMENDATION:</strong>
            <p style="margin: 5px 0; font-size: 13px;">FINAL DIAGNOSIS AND IMPRESSION:</p>
            <textarea name="medical_remarks" required></textarea>
            
            <div style="margin-top:25px;">
                <div style="margin-bottom: 15px;">
                    <strong>Examining Physician:</strong><br>
                    <input type="text" name="physician_name" class="line-input" style="width: 400px; font-weight: bold; text-transform: uppercase;" placeholder="ENTER FULL NAME" required>
                </div>
                <div>
                    PRC License No.: <input type="text" name="physician_license" class="line-input" style="width: 200px;" required>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    PTR No.: <input type="text" name="physician_ptr" class="line-input" style="width: 200px;" required>
                </div>
            </div>
        </div>

        <button type="submit" name="btn_submit_assessment" class="submit-btn">SUBMIT ASSESSMENT</button>
    </form>
</div>