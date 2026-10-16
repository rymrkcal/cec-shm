// Spreadsheet-style select, copy, and paste for ATG and 3L input cells.
(function () {
    var form = document.querySelector('form.atg-form, form.l3-form');
    if (!form) {
        return;
    }

    var table = form.querySelector('table.atg');
    if (!table) {
        return;
    }

    var items = readCells(table);
    var anchor = null;
    var end = null;
    var dragging = false;
    var editing = false;

    // Row and column of each input, including cells covered by colspan or rowspan.
    function readCells(current) {
        var list = [];
        var taken = [];
        var rows = current.rows;

        for (var r = 0; r < rows.length; r++) {
            var col = 0;
            var cells = rows[r].cells;
            for (var i = 0; i < cells.length; i++) {
                while (taken[r] && taken[r][col]) {
                    col = col + 1;
                }

                var td = cells[i];
                var across = td.colSpan || 1;
                var down = td.rowSpan || 1;
                if (td.classList.contains('input')) {
                    var input = td.querySelector('input[type="text"], input[type="number"], select');
                    if (input) {
                        list.push({ row: r, col: col, td: td, input: input });
                    }
                }

                for (var rr = 0; rr < down; rr++) {
                    if (!taken[r + rr]) {
                        taken[r + rr] = [];
                    }
                    for (var cc = 0; cc < across; cc++) {
                        taken[r + rr][col + cc] = true;
                    }
                }
                col = col + across;
            }
        }

        return list;
    }

    function itemFrom(target, allowButton) {
        if (!target || !target.closest) {
            return null;
        }
        if (!allowButton && target.closest('button, a')) {
            return null;
        }

        var td = target.closest('td.input');
        if (!td || !table.contains(td)) {
            return null;
        }

        for (var i = 0; i < items.length; i++) {
            if (items[i].td === td) {
                return items[i];
            }
        }
        return null;
    }

    function at(row, col) {
        for (var i = 0; i < items.length; i++) {
            if (items[i].row === row && items[i].col === col) {
                return items[i];
            }
        }
        return null;
    }

    function area() {
        return {
            r1: Math.min(anchor.row, end.row),
            r2: Math.max(anchor.row, end.row),
            c1: Math.min(anchor.col, end.col),
            c2: Math.max(anchor.col, end.col)
        };
    }

    function paint() {
        var box = anchor ? area() : null;
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var on = box && item.row >= box.r1 && item.row <= box.r2 && item.col >= box.c1 && item.col <= box.c2;
            if (on) {
                item.td.classList.add('sheet-on');
            } else {
                item.td.classList.remove('sheet-on');
            }
            if (item === anchor) {
                item.td.classList.add('sheet-active');
            } else {
                item.td.classList.remove('sheet-active');
            }
        }
    }

    function pick(item, selectText) {
        anchor = item;
        end = item;
        editing = false;
        paint();
        item.input.focus();
        if (selectText && item.input.select) {
            item.input.select();
        }
    }

    function nearest(item, rowStep, colStep) {
        var best = null;
        var bestGap = 0;
        for (var i = 0; i < items.length; i++) {
            var other = items[i];
            var gap = 0;
            if (rowStep !== 0) {
                if (other.col !== item.col) {
                    continue;
                }
                gap = (other.row - item.row) * rowStep;
            } else {
                if (other.row !== item.row) {
                    continue;
                }
                gap = (other.col - item.col) * colStep;
            }
            if (gap > 0 && (!best || gap < bestGap)) {
                best = other;
                bestGap = gap;
            }
        }
        return best;
    }

    function step(item, direction) {
        var index = -1;
        for (var i = 0; i < items.length; i++) {
            if (items[i] === item) {
                index = i;
            }
        }
        var next = index + direction;
        if (next < 0 || next >= items.length) {
            return null;
        }
        return items[next];
    }

    table.addEventListener('mousedown', function (event) {
        var item = itemFrom(event.target, false);
        if (!item) {
            return;
        }

        if (event.shiftKey && anchor) {
            event.preventDefault();
            end = item;
            editing = false;
            paint();
            anchor.input.focus();
            return;
        }

        // A second click on the selected cell places the text cursor.
        if (item === anchor && anchor === end && item.input.tagName !== 'SELECT') {
            editing = true;
            dragging = false;
            return;
        }

        dragging = true;
        table.classList.add('sheet-drag');
        if (item.input.tagName !== 'SELECT') {
            event.preventDefault();
        }
        pick(item, true);
    });

    document.addEventListener('mousemove', function (event) {
        if (!dragging) {
            return;
        }
        var item = itemFrom(event.target, true);
        if (!item || item === end) {
            return;
        }
        end = item;
        editing = false;
        paint();
        var selection = window.getSelection();
        if (selection) {
            selection.removeAllRanges();
        }
    });

    document.addEventListener('mouseup', function () {
        dragging = false;
        table.classList.remove('sheet-drag');
    });

    form.addEventListener('keydown', function (event) {
        if (!anchor || !table.contains(document.activeElement)) {
            return;
        }

        var key = event.key;
        if (editing && key.indexOf('Arrow') === 0) {
            return;
        }
        if (document.activeElement.tagName === 'SELECT' && key.indexOf('Arrow') === 0) {
            return;
        }

        var next = null;
        if (key === 'Tab') {
            next = step(anchor, event.shiftKey ? -1 : 1);
        } else if (key === 'Enter') {
            next = nearest(anchor, event.shiftKey ? -1 : 1, 0);
        } else if (key === 'ArrowRight') {
            next = nearest(anchor, 0, 1);
        } else if (key === 'ArrowLeft') {
            next = nearest(anchor, 0, -1);
        } else if (key === 'ArrowDown') {
            next = nearest(anchor, 1, 0);
        } else if (key === 'ArrowUp') {
            next = nearest(anchor, -1, 0);
        } else {
            return;
        }

        event.preventDefault();
        if (next) {
            pick(next, true);
        }
    });

    function selectedItems() {
        if (!anchor || !end) {
            return [];
        }
        var box = area();
        var list = [];
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            if (item.row >= box.r1 && item.row <= box.r2 && item.col >= box.c1 && item.col <= box.c2) {
                list.push(item);
            }
        }
        return list;
    }

    function selectionAllowed(input) {
        return input && input.tagName === 'INPUT' && input.type !== 'number';
    }

    function copyingPartOfOneCell() {
        var el = document.activeElement;
        if (!selectionAllowed(el) || el.selectionStart === null) {
            return false;
        }
        if (el.selectionStart === el.selectionEnd) {
            return false;
        }
        if (el.selectionStart === 0 && el.selectionEnd === el.value.length) {
            return false;
        }
        return true;
    }

    function copyText() {
        var box = area();
        var lines = [];
        for (var r = box.r1; r <= box.r2; r++) {
            var parts = [];
            for (var c = box.c1; c <= box.c2; c++) {
                var item = at(r, c);
                parts.push(item ? item.input.value : '');
            }
            lines.push(parts.join('\t'));
        }
        return lines.join('\n');
    }

    document.addEventListener('copy', function (event) {
        if (!anchor || !table.contains(document.activeElement) || copyingPartOfOneCell()) {
            return;
        }
        if (!selectedItems().length || !event.clipboardData) {
            return;
        }
        event.preventDefault();
        event.clipboardData.setData('text/plain', copyText());
    });

    function cleanCell(value) {
        value = String(value).trim();
        if (value.length >= 2 && value.charAt(0) === '"' && value.charAt(value.length - 1) === '"') {
            value = value.substring(1, value.length - 1).replace(/""/g, '"');
        }
        return value;
    }

    function gridFrom(text) {
        text = String(text).replace(/\r\n/g, '\n').replace(/\r/g, '\n');
        if (text.charAt(text.length - 1) === '\n') {
            text = text.substring(0, text.length - 1);
        }
        if (text === '') {
            return [];
        }
        var lines = text.split('\n');
        var grid = [];
        for (var r = 0; r < lines.length; r++) {
            grid.push(lines[r].split('\t'));
        }
        return grid;
    }

    function changed(input) {
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function put(item, value) {
        var input = item.input;
        if (input.tagName === 'SELECT') {
            var matched = false;
            for (var i = 0; i < input.options.length; i++) {
                var option = input.options[i];
                if (option.value === value || option.text === value) {
                    input.value = option.value;
                    matched = true;
                    break;
                }
            }
            if (!matched) {
                return;
            }
        } else {
            if (input.type === 'number') {
                value = String(value).replace(/,/g, '').trim();
                if (value !== '' && !isFinite(Number(value))) {
                    return;
                }
            }
            input.value = value;
        }
        changed(input);
    }

    // One value replaces the selected text. A block of values fills rows and columns.
    function pasteGrid(start, grid) {
        if (grid.length === 1 && grid[0].length === 1) {
            var only = cleanCell(grid[0][0]);
            var input = start.input;
            if (input.tagName === 'SELECT' || !selectionAllowed(input) || input.selectionStart === null) {
                put(start, only);
            } else {
                var from = input.selectionStart;
                var to = input.selectionEnd;
                input.value = input.value.substring(0, from) + only + input.value.substring(to);
                var pos = from + only.length;
                input.setSelectionRange(pos, pos);
                changed(input);
            }
            anchor = start;
            end = start;
            paint();
            return;
        }

        var last = start;
        for (var r = 0; r < grid.length; r++) {
            for (var c = 0; c < grid[r].length; c++) {
                var cell = at(start.row + r, start.col + c);
                if (!cell) {
                    continue;
                }
                put(cell, cleanCell(grid[r][c]));
                last = cell;
            }
        }
        anchor = start;
        end = last;
        editing = false;
        paint();
        start.input.focus();
    }

    form.addEventListener('paste', function (event) {
        var item = itemFrom(event.target, false);
        if (!item || !event.clipboardData) {
            return;
        }

        var text = event.clipboardData.getData('text/plain');
        if (!text) {
            text = event.clipboardData.getData('text');
        }
        var grid = gridFrom(text);
        if (!grid.length) {
            return;
        }

        event.preventDefault();
        pasteGrid(anchor || item, grid);
    });
})();

