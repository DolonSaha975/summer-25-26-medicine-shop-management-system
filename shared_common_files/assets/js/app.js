// assets/js/app.js — small utilities shared by the search boxes and forms.
// Kept deliberately simple: no build step, just plain functions on window.

// HTML-escape a value before dropping it into innerHTML (the AJAX search
// results are rendered client-side, so this matters).
function escapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Basic "required field" check used by a few forms for a quick client-side
// validation pass before the real (server-side) validation in PHP runs.
function validateRequired(form) {
    var ok = true;
    form.querySelectorAll('[required]').forEach(function (field) {
        if (!field.value || !field.value.trim()) {
            ok = false;
            field.classList.add('field-error');
        } else {
            field.classList.remove('field-error');
        }
    });
    return ok;
}

// Wires up a search box to an AJAX endpoint: types into #inputId, waits a
// short pause, fetches JSON from index.php?page=ajax&type=<type>&q=<term>,
// and calls render(rows) with the result. Used by every "live search" table.
function ajaxTable(inputId, type, render, countId) {
    var input   = document.getElementById(inputId);
    var counter = countId ? document.getElementById(countId) : null;
    if (!input) return;
    var timer;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            var q = input.value.trim();
            fetch('index.php?page=ajax&type=' + type + '&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (rows) {
                    render(rows);
                    if (counter) counter.textContent = rows.length + (q ? ' results' : ' total');
                })
                .catch(function (err) { console.error('Search failed:', err); });
        }, 250);
    });
}
