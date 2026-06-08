(function() {
	'use strict';

	var reader = document.querySelector('[data-ne-reader]');

	if (!reader) {
		return;
	}

	var content = reader.querySelector('[data-ne-reader-content]');
	var progress = reader.querySelector('[data-ne-reading-progress]');
	var themeButtons = reader.querySelectorAll('[data-ne-theme-value]');
	var fontSizeInput = reader.querySelector('[data-ne-font-size]');
	var measureInput = reader.querySelector('[data-ne-measure]');
	var toc = reader.querySelector('[data-ne-toc]');
	var resetButton = reader.querySelector('[data-ne-reset-reader]');
	var storageKey = 'neuroechoReaderPrefs';
	var defaultPrefs = {
		theme: 'paper',
		fontSize: '18',
		measure: '70'
	};

	function getPrefs() {
		try {
			return JSON.parse(window.localStorage.getItem(storageKey)) || {};
		} catch (error) {
			return {};
		}
	}

	function savePrefs(nextPrefs) {
		try {
			window.localStorage.setItem(storageKey, JSON.stringify(nextPrefs));
		} catch (error) {
			return;
		}
	}

	function applyPrefs(prefs) {
		var theme = prefs.theme || defaultPrefs.theme;
		var fontSize = prefs.fontSize || defaultPrefs.fontSize;
		var measure = prefs.measure || defaultPrefs.measure;

		reader.setAttribute('data-ne-theme', theme);
		reader.style.setProperty('--ne-reader-size', fontSize + 'px');
		reader.style.setProperty('--ne-reader-measure', measure + 'ch');

		if (fontSizeInput) {
			fontSizeInput.value = fontSize;
		}

		if (measureInput) {
			measureInput.value = measure;
		}

		themeButtons.forEach(function(button) {
			button.setAttribute('aria-pressed', button.getAttribute('data-ne-theme-value') === theme ? 'true' : 'false');
		});
	}

	function updateProgress() {
		if (!content || !progress) {
			return;
		}

		var rect = content.getBoundingClientRect();
		var viewport = window.innerHeight || document.documentElement.clientHeight;
		var total = Math.max(1, rect.height - viewport * 0.5);
		var read = Math.min(total, Math.max(0, viewport * 0.22 - rect.top));
		var ratio = read / total;

		progress.style.transform = 'scaleX(' + ratio.toFixed(4) + ')';
	}

	function updateTocState() {
		if (!toc) {
			return;
		}

		var links = toc.querySelectorAll('[data-ne-toc-link]');

		if (!links.length) {
			return;
		}

		var activeLink = links[0];

		links.forEach(function(link) {
			var target = document.getElementById(link.getAttribute('href').slice(1));

			if (target && target.getBoundingClientRect().top <= 120) {
				activeLink = link;
			}
		});

		links.forEach(function(link) {
			link.setAttribute('aria-current', link === activeLink ? 'location' : 'false');
		});
	}

	function slugify(text) {
		return text
			.toLowerCase()
			.trim()
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '');
	}

	function buildToc() {
		if (!content || !toc) {
			return;
		}

		var headings = content.querySelectorAll('h2, h3');

		if (!headings.length) {
			return;
		}

		headings.forEach(function(heading, index) {
			if (!heading.id) {
				heading.id = slugify(heading.textContent) || 'section-' + (index + 1);
			}

			var link = document.createElement('a');
			link.href = '#' + heading.id;
			link.textContent = heading.textContent;
			link.setAttribute('data-ne-toc-link', '');

			if (heading.tagName.toLowerCase() === 'h3') {
				link.className = 'is-nested';
			}

			toc.appendChild(link);
		});
	}

	var prefs = getPrefs();
	applyPrefs(prefs);
	buildToc();
	updateProgress();
	updateTocState();

	themeButtons.forEach(function(button) {
		button.addEventListener('click', function() {
			prefs = getPrefs();
			prefs.theme = button.getAttribute('data-ne-theme-value') || 'paper';
			savePrefs(prefs);
			applyPrefs(prefs);
		});
	});

	if (fontSizeInput) {
		fontSizeInput.addEventListener('input', function() {
			prefs = getPrefs();
			prefs.fontSize = fontSizeInput.value;
			savePrefs(prefs);
			applyPrefs(prefs);
		});
	}

	if (measureInput) {
		measureInput.addEventListener('input', function() {
			prefs = getPrefs();
			prefs.measure = measureInput.value;
			savePrefs(prefs);
			applyPrefs(prefs);
		});
	}

	if (resetButton) {
		resetButton.addEventListener('click', function() {
			prefs = {};
			savePrefs(prefs);
			applyPrefs(defaultPrefs);
		});
	}

	window.addEventListener('scroll', function() {
		updateProgress();
		updateTocState();
	}, { passive: true });
	window.addEventListener('resize', function() {
		updateProgress();
		updateTocState();
	});
})();
