<h1><?php echo htmlspecialchars($title) ?></h1>
<?php
	if (!empty($items) && is_array($items)) {
?>
<ul>
<?php
	foreach($items as $item) {
?>
	<li><?php echo htmlspecialchars($item)  ?></li>
<?php
	}
?>
</ul>
<?php
	}
?>