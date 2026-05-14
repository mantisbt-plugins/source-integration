document.addEventListener('DOMContentLoaded', function() {
	document.querySelectorAll('.bbdc-test-connection').forEach(function(btn) {
		btn.addEventListener('click', function() {
			btn.disabled = true;
			fetch(btn.dataset.url)
				.then(function(r) { return r.json(); })
				.then(function(d) { alert(d.message); })
				.catch(function() { alert('Request failed'); })
				.finally(function() { btn.disabled = false; });
		});
	});
});
