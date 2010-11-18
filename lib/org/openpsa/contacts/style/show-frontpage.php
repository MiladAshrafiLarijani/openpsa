<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="wide">
    <?php
    midcom::dynamic_load($node[MIDCOM_NAV_RELATIVEURL] . "buddylist");
    ?>
</div>
