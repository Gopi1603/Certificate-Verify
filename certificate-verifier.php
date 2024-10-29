<?php
/*
Plugin Name: Certificate Verifier
Description: A plugin to create and verify certificate IDs with details.
Version: 1.0
Author: Gopi Chakradhar
*/

// Step 1: Created a Database Table
register_activation_hook(__FILE__, 'cv_create_table');

function cv_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'certificates';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        certificate_id varchar(100) NOT NULL,
        type varchar(50) NOT NULL,
        duration varchar(50) NOT NULL,
        verified tinyint(1) NOT NULL,
        signed_by varchar(100) NOT NULL,
        signature blob,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Step 2: Created a Admin Page for Input(optional even user can enter it)
add_action('admin_menu', 'cv_add_admin_menu');

function cv_add_admin_menu() {
    add_menu_page('Certificate Verifier', 'Certificate Verifier', 'manage_options', 'certificate_verifier', 'cv_admin_page');
}

function cv_admin_page() {
    if (isset($_POST['submit'])) {
        cv_save_certificate();
    }

    ?>
    <h1>Certificate Verifier</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="certificate_id" placeholder="Certificate ID" required>
        <select name="type" required>
            <option value="Certification">Certification</option>
            <option value="Training Program">Training Program</option>
            <option value="Workshop">Workshop</option>
            <option value="Graduate Program">Graduate Program</option>
            <option value="Summer Internship">Summer Internship</option>
        </select>
        <input type="text" name="duration" placeholder="Duration" required>
        <select name="verified" required>
            <option value="1">Verified</option>
            <option value="0">Not Verified</option>
        </select>
        <input type="text" name="signed_by" placeholder="Digitally Signed By" required>
        <input type="file" name="signature" accept="image/*" required>
        <input type="submit" name="submit" value="Add Certificate">
    </form>
    <?php
}

// Step 3: Saved the Certificate Details in db
function cv_save_certificate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'certificates';

    $certificate_id = sanitize_text_field($_POST['certificate_id']);
    $type = sanitize_text_field($_POST['type']);
    $duration = sanitize_text_field($_POST['duration']);
    $verified = intval($_POST['verified']);
    $signed_by = sanitize_text_field($_POST['signed_by']);
    $signature = file_get_contents($_FILES['signature']['tmp_name']);

    $wpdb->insert($table_name, [
        'certificate_id' => $certificate_id,
        'type' => $type,
        'duration' => $duration,
        'verified' => $verified,
        'signed_by' => $signed_by,
        'signature' => $signature
    ]);
}

// Step 4: Created Frontend Verification Form for data entry details
add_shortcode('create_certificate', 'cv_creation_form');

function cv_creation_form() {
    ob_start();
    if (isset($_POST['submit'])) {
        cv_save_certificate();
        echo "<p>Certificate added successfully!</p>";
    }
    ?>
    <h2>Create Your Certificate</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="certificate_id" placeholder="Certificate ID" required>
        <select name="type" required>
            <option value="Certification">Certification</option>
            <option value="Training Program">Training Program</option>
            <option value="Workshop">Workshop</option>
            <option value="Graduate Program">Graduate Program</option>
            <option value="Summer Internship">Summer Internship</option>
        </select>
        <input type="text" name="duration" placeholder="Duration" required>
        <select name="verified" required>
            <option value="1">Verified</option>
            <option value="0">Not Verified</option>
        </select>
        <input type="text" name="signed_by" placeholder="Digitally Signed By" required>
        <input type="file" name="signature" accept="image/*" required>
        <input type="submit" name="submit" value="Add Certificate">
    </form>
    <?php
    return ob_get_clean();
}

add_shortcode('verify_certificate', 'cv_verification_form');

function cv_verification_form() {
    ob_start();
    ?>
    <h2>Verify Your Certificate</h2>
    <form method="POST">
        <input type="text" name="verify_certificate_id" placeholder="Enter Certificate ID" required>
        <input type="submit" name="verify" value="Verify">
    </form>
    <?php

    if (isset($_POST['verify'])) {
        cv_verify_certificate($_POST['verify_certificate_id']);
    }

    return ob_get_clean();
}

// Step 5: Function to Verify Certificate
function cv_verify_certificate($certificate_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'certificates';
    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE certificate_id = %s", $certificate_id));

    if ($result) {
        echo "<h3>Certificate Details:</h3>";
        echo "ID: {$result->certificate_id}<br>";
        echo "Type: {$result->type}<br>";
        echo "Duration: {$result->duration}<br>";
        echo "Verified: " . ($result->verified ? "Yes" : "No") . "<br>";
        echo "Signed By: {$result->signed_by}<br>";
        if ($result->signature) {
            echo "<img src='data:image/png;base64," . base64_encode($result->signature) . "' alt='Signature' style='max-width:200px;'><br>";
        }
    } else {
        echo "Certificate ID not found.";
    }
}


//M.Gopi Chakradhar