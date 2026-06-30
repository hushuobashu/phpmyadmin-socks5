(function() {
    'use strict';

    var basePath = '/sqlite/';

    function loadTree() {
        var el = document.getElementById('sqlite-tree');
        if (!el) return;

        fetch(basePath + 'ajax/tree.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) {
                    el.innerHTML = '<span class="text-danger">' + escapeHtml(data.error) + '</span>';
                    return;
                }
                renderTree(el, data);
            })
            .catch(function() {
                el.innerHTML = '<span class="text-danger">Failed to load</span>';
            });
    }

    function renderTree(container, databases) {
        var params = new URLSearchParams(window.location.search);
        var currentDb = params.get('db') || '';
        var currentTable = params.get('table') || '';

        var html = '<ul>';
        databases.forEach(function(db) {
            var expanded = db.path === currentDb;
            html += '<li>';
            html += '<a class="db-name" href="' + basePath + 'pages/tables.php?db=' + encodeURIComponent(db.path) + '">';
            html += (expanded ? '&#9662; ' : '&#9656; ') + escapeHtml(db.name);
            html += '</a>';
            if (expanded && db.tables.length > 0) {
                html += '<ul>';
                db.tables.forEach(function(tbl) {
                    var active = tbl === currentTable ? ' active' : '';
                    html += '<li><a class="tbl-name' + active + '" href="' + basePath + 'pages/browse.php?db=' + encodeURIComponent(db.path) + '&table=' + encodeURIComponent(tbl) + '">';
                    html += escapeHtml(tbl);
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadTree);
    } else {
        loadTree();
    }

    window.sqliteLoadTree = loadTree;
})();
