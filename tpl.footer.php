<hr />

<details>
	<summary>Queries (<?= count($db->queries) ?>)</summary>
	<pre><?= html(print_r($db->queries, 1)) ?></pre>
</details>

<script>
Turbolinks.Location.prototype.isHTML = () => true;
Turbolinks.undoNextScroll = function() {
	const scrollToPosition = Turbolinks.ScrollManager.prototype.scrollToPosition;
	Turbolinks.ScrollManager.prototype.scrollToPosition = () => 1;
	const onload = function(e) {
		Turbolinks.ScrollManager.prototype.scrollToPosition = scrollToPosition;
		document.removeEventListener('turbolinks:load', onload);
	};
	document.addEventListener('turbolinks:load', onload);
};
document.querySelectorAll('form[data-turbolink]').forEach(form => {
	const buttons = form.querySelectorAll('button:not([type="button"])');
	var submitButton;
	buttons.forEach(btn => btn.addEventListener('click', function(e) {
		submitButton = this;
	}));
	form.addEventListener('submit', function(e) {
		e.preventDefault();

		const data = new FormData(this);
		data.append((submitButton || buttons[0]).name, (submitButton || buttons[0]).value);

		const xhr = new XMLHttpRequest();
		xhr.open(this.method, this.action, true);
		xhr.setRequestHeader('X-Turbolinks', 1);
		xhr.send(data);
		xhr.onload = function(e) {
			// Turbolinks.undoNextScroll();
			Turbolinks.visit(this.responseText);
		};
	});
});
</script>

</body>

</html>
