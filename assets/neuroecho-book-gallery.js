(function() {
	'use strict';

	var defaultPrefs = {
		theme: 'paper',
		fontSize: '18',
		measure: '70'
	};

	function getJson(key, fallback) {
		try {
			return JSON.parse(window.localStorage.getItem(key)) || fallback;
		} catch (error) {
			return fallback;
		}
	}

	function setJson(key, value) {
		try {
			window.localStorage.setItem(key, JSON.stringify(value));
		} catch (error) {
			return;
		}
	}

	function slugify(text) {
		return text
			.toLowerCase()
			.trim()
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '');
	}

	function initCatalog() {
		document.querySelectorAll('[data-ne-book-gallery]').forEach(function(gallery) {
			var viewButtons = gallery.querySelectorAll('[data-ne-catalog-view]');
			var savedView = getJson('neuroechoCatalogView', 'grid');

			function applyView(view) {
				gallery.setAttribute('data-ne-catalog-layout', view);
				setJson('neuroechoCatalogView', view);

				viewButtons.forEach(function(button) {
					button.setAttribute('aria-pressed', button.getAttribute('data-ne-catalog-view') === view ? 'true' : 'false');
				});
			}

			applyView(savedView === 'list' ? 'list' : 'grid');

			viewButtons.forEach(function(button) {
				button.addEventListener('click', function() {
					applyView(button.getAttribute('data-ne-catalog-view') || 'grid');
				});
			});
		});
	}

	function initReader() {
		var reader = document.querySelector('[data-ne-reader]');

		if (!reader) {
			return;
		}

		var bookId = reader.getAttribute('data-ne-reader-book-id') || 'global';
		var content = reader.querySelector('[data-ne-reader-content]');
		var progress = reader.querySelector('[data-ne-reading-progress]');
		var themeButtons = reader.querySelectorAll('[data-ne-theme-value]');
		var fontSizeInput = reader.querySelector('[data-ne-font-size]');
		var measureInput = reader.querySelector('[data-ne-measure]');
		var toc = reader.querySelector('[data-ne-toc]');
		var resetButton = reader.querySelector('[data-ne-reset-reader]');
		var memoryPanel = reader.querySelector('[data-ne-reader-memory]');
		var memoryText = reader.querySelector('[data-ne-reader-memory-text]');
		var resumeButton = reader.querySelector('[data-ne-resume-reading]');
		var clearPositionButton = reader.querySelector('[data-ne-clear-position]');
		var bookmarkButton = reader.querySelector('[data-ne-bookmark-toggle]');
		var bookmarkList = reader.querySelector('[data-ne-bookmarks]');
		var annotationInput = reader.querySelector('[data-ne-annotation-input]');
		var annotationButton = reader.querySelector('[data-ne-save-annotation]');
		var annotationList = reader.querySelector('[data-ne-annotations]');
		var prefsKey = 'neuroechoReaderPrefs';
		var positionKey = 'neuroechoReaderPosition:' + bookId;
		var bookmarkKey = 'neuroechoReaderBookmarks:' + bookId;
		var annotationKey = 'neuroechoReaderAnnotations:' + bookId;

		function getPrefs() {
			return getJson(prefsKey, {});
		}

		function savePrefs(nextPrefs) {
			setJson(prefsKey, nextPrefs);
		}

		function applyPrefs(prefs) {
			var theme = prefs.theme || defaultPrefs.theme;
			var fontSize = prefs.fontSize || defaultPrefs.fontSize;
			var measure = prefs.measure || defaultPrefs.measure;

			if (['paper', 'night', 'focus'].indexOf(theme) === -1) {
				theme = defaultPrefs.theme;
			}

			reader.setAttribute('data-ne-theme', theme);
			document.body.setAttribute('data-ne-reader-theme', theme);
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

		function getReadRatio() {
			if (!content) {
				return 0;
			}

			var rect = content.getBoundingClientRect();
			var viewport = window.innerHeight || document.documentElement.clientHeight;
			var total = Math.max(1, rect.height - viewport * 0.5);
			var read = Math.min(total, Math.max(0, viewport * 0.22 - rect.top));

			return read / total;
		}

		function updateProgress() {
			if (!progress) {
				return;
			}

			progress.style.transform = 'scaleX(' + getReadRatio().toFixed(4) + ')';
		}

		function savePosition() {
			var ratio = getReadRatio();

			if (ratio > 0.02) {
				setJson(positionKey, {
					ratio: ratio,
					updated: Date.now()
				});
			}
		}

		function restorePosition(position) {
			if (!content || !position || !position.ratio) {
				return;
			}

			var top = content.offsetTop + content.offsetHeight * Math.min(0.98, Math.max(0, position.ratio));
			window.scrollTo({
				top: Math.max(0, top - 120),
				behavior: 'smooth'
			});
		}

		function showMemoryPanel() {
			var position = getJson(positionKey, null);

			if (!memoryPanel || !position || !position.ratio || position.ratio < 0.04) {
				return;
			}

			if (memoryText) {
				memoryText.textContent = Math.round(position.ratio * 100) + '% read';
			}

			memoryPanel.hidden = false;
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

		function getCurrentSectionLabel() {
			var active = toc ? toc.querySelector('[aria-current="location"]') : null;

			if (active) {
				return active.textContent;
			}

			return document.title || 'Reading position';
		}

		function jumpToRatio(ratio) {
			if (!content) {
				return;
			}

			window.scrollTo({
				top: Math.max(0, content.offsetTop + content.offsetHeight * ratio - 120),
				behavior: 'smooth'
			});
		}

		function renderSavedList(container, items, emptyText, onDelete) {
			if (!container) {
				return;
			}

			container.innerHTML = '';

			if (!items.length) {
				var empty = document.createElement('p');
				empty.textContent = emptyText;
				container.appendChild(empty);
				return;
			}

			items.forEach(function(item, index) {
				var row = document.createElement('div');
				var jump = document.createElement('button');
				var remove = document.createElement('button');

				row.className = 'ne-reader-saved-item';
				jump.type = 'button';
				jump.textContent = item.label || item.text || 'Saved item';
				jump.addEventListener('click', function() {
					jumpToRatio(item.ratio || 0);
				});
				remove.type = 'button';
				remove.textContent = 'Remove';
				remove.addEventListener('click', function() {
					onDelete(index);
				});

				row.appendChild(jump);
				row.appendChild(remove);
				container.appendChild(row);
			});
		}

		function renderBookmarks() {
			var bookmarks = getJson(bookmarkKey, []);

			renderSavedList(bookmarkList, bookmarks, 'No bookmarks saved.', function(index) {
				bookmarks.splice(index, 1);
				setJson(bookmarkKey, bookmarks);
				renderBookmarks();
			});
		}

		function renderAnnotations() {
			var annotations = getJson(annotationKey, []);

			renderSavedList(annotationList, annotations, 'No notes saved.', function(index) {
				annotations.splice(index, 1);
				setJson(annotationKey, annotations);
				renderAnnotations();
			});
		}

		var prefs = getPrefs();
		applyPrefs(prefs);
		buildToc();
		updateProgress();
		updateTocState();
		showMemoryPanel();
		renderBookmarks();
		renderAnnotations();

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

		if (resumeButton) {
			resumeButton.addEventListener('click', function() {
				restorePosition(getJson(positionKey, null));
			});
		}

		if (clearPositionButton) {
			clearPositionButton.addEventListener('click', function() {
				window.localStorage.removeItem(positionKey);

				if (memoryPanel) {
					memoryPanel.hidden = true;
				}
			});
		}

		if (bookmarkButton) {
			bookmarkButton.addEventListener('click', function() {
				var bookmarks = getJson(bookmarkKey, []);
				bookmarks.unshift({
					label: getCurrentSectionLabel(),
					ratio: getReadRatio(),
					created: Date.now()
				});
				setJson(bookmarkKey, bookmarks.slice(0, 20));
				renderBookmarks();
			});
		}

		if (annotationButton && annotationInput) {
			annotationButton.addEventListener('click', function() {
				var text = annotationInput.value.trim();

				if (!text) {
					return;
				}

				var annotations = getJson(annotationKey, []);
				annotations.unshift({
					label: getCurrentSectionLabel(),
					text: text,
					ratio: getReadRatio(),
					created: Date.now()
				});
				setJson(annotationKey, annotations.slice(0, 30));
				annotationInput.value = '';
				renderAnnotations();
			});
		}

		window.addEventListener('scroll', function() {
			updateProgress();
			updateTocState();
			savePosition();
		}, { passive: true });
		window.addEventListener('resize', function() {
			updateProgress();
			updateTocState();
		});
		window.addEventListener('beforeunload', savePosition);
	}

	initCatalog();
	initReader();
})();
