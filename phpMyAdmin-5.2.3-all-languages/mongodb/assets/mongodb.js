(function() {
    'use strict';

    var basePath = '/mongodb/';

    function loadTree() {
        var el = document.getElementById('mongo-tree');
        if (!el) return;

        fetch(basePath + 'ajax/tree.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) {
                    el.innerHTML = '<span class="text-danger">' + data.error + '</span>';
                    return;
                }
                renderTree(el, data);
            })
            .catch(function(e) {
                el.innerHTML = '<span class="text-danger">Failed to load</span>';
            });
    }

    function renderTree(container, databases) {
        var params = new URLSearchParams(window.location.search);
        var currentDb = params.get('db') || '';
        var currentCol = params.get('col') || '';

        var html = '<ul>';
        databases.forEach(function(db) {
            var expanded = db.name === currentDb;
            html += '<li>';
            html += '<a class="db-name" href="' + basePath + 'pages/collections.php?db=' + encodeURIComponent(db.name) + '">';
            html += (expanded ? '&#9662; ' : '&#9656; ') + escapeHtml(db.name);
            html += '</a>';
            if (expanded && db.collections.length > 0) {
                html += '<ul>';
                db.collections.forEach(function(col) {
                    var active = col === currentCol ? ' active' : '';
                    html += '<li><a class="col-name' + active + '" href="' + basePath + 'pages/documents.php?db=' + encodeURIComponent(db.name) + '&col=' + encodeURIComponent(col) + '">';
                    html += escapeHtml(col);
                    html += '</a></li>';
                });
                html += '</ul>';
            }
            html += '</li>';
        });
        html += '</ul>';
        container.innerHTML = html;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadTree);
    } else {
        loadTree();
    }

    // Expose globally for AJAX refresh
    window.mongoLoadTree = loadTree;
})();
