<h1><?= htmlspecialchars($title) ?></h1>
<?php
	if (!empty($items) && is_array($items)) {
?>
<ul>
<?php
	foreach($items as $item) {
?>
	<li><?= htmlspecialchars($item)  ?></li>
<?php
	}
?>
</ul>
<?php
	}
?>