<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php
if ($data = get_transient('waypay_admin_notice')) {
    ?>
    <div class="updated <?php echo $data[1] ?> is-dismissible">
        <p><?php echo $data[0] ?></p>
    </div>
    <?php
    delete_transient('waypay_admin_notice');
}
