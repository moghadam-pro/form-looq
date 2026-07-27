(function () {
	'use strict';

	function focusFirstError() {
		document.querySelectorAll('.fmpf-form-wrap[data-fmpf-has-errors="1"]').forEach(function (wrapper) {
			var target = wrapper.querySelector('[aria-invalid="true"]');
			if (!target) {
				target = wrapper.querySelector('.fmpf-error-summary');
			}
			if (target && typeof target.focus === 'function') {
				target.focus({ preventScroll: true });
				target.scrollIntoView({ block: 'center', behavior: 'smooth' });
			}
		});
	}

	function cleanStatusParameters() {
		if (!window.history || !window.URL) {
			return;
		}

		var url = new URL(window.location.href);
		if (!url.searchParams.has('fmpf_status') && !url.searchParams.has('fmpf_state')) {
			return;
		}

		url.searchParams.delete('fmpf_status');
		url.searchParams.delete('fmpf_state');
		window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			focusFirstError();
			cleanStatusParameters();
		});
	} else {
		focusFirstError();
		cleanStatusParameters();
	}
}());
