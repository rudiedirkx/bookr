<? if (is_debug_ip()): ?>
	<hr>
	<details>
		<summary>Queries (<?= count($db->queries) ?>)</summary>
		<pre><?= html(print_r($db->queries, 1)) ?></pre>
	</details>
<? endif ?>

</body>

</html>
