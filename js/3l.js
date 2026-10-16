(function () {
    var form = document.querySelector('form.l3-form');
    if (!form) {
        return;
    }

    function field(name) {
        return form.querySelector('[name="' + name + '"]');
    }

    function num(name) {
        var el = field(name);
        if (!el) {
            return null;
        }
        var value = String(el.value).replace(/,/g, '').trim();
        if (value === '' || isNaN(Number(value))) {
            return null;
        }
        return Number(value);
    }

    function gradeParts(grade) {
        if (grade === '40') {
            return { fck: 32, ec: 28 };
        }
        if (grade === '50') {
            return { fck: 40, ec: 30 };
        }
        if (grade === '60') {
            return { fck: 50, ec: 32 };
        }
        return null;
    }

    function shown(value) {
        if (value === null || !isFinite(value)) {
            return '';
        }
        var rounded = Math.round(value * 1000000) / 1000000;
        if (Math.abs(rounded) < 0.0000005) {
            return '0';
        }
        return rounded.toFixed(6).replace(/\.?0+$/, '');
    }

    function showSection() {
        var fckCell = document.getElementById('l3-fck');
        var fcmCell = document.getElementById('l3-fcm');
        var ecCell = document.getElementById('l3-ec');
        var eequCell = document.getElementById('l3-eequ');
        var strengthCell = document.getElementById('l3-strength');
        var areaCell = document.getElementById('l3-eq-area');
        var hCell = document.getElementById('l3-h');
        var gradeInput = field('concrete_grade');
        if (!eequCell || !strengthCell || !areaCell || !gradeInput) {
            return;
        }
        var grade = gradeParts(String(gradeInput.value).trim());
        var ac = num('ac');
        var ar = num('ar');
        var sfc = num('sfc');
        var u = num('u');
        var es = 200;
        var eequ = null;
        var strength = null;
        var area = null;
        var h = null;
        if (grade && ac !== null && ar !== null && sfc !== null && sfc !== 0 && (ac + ar) !== 0) {
            eequ = ((grade.fck * ac / sfc) + (es * ar)) / (ac + ar);
        }
        if (grade && ac !== null && ar !== null && grade.ec !== 0) {
            strength = grade.ec * ac + es * ar;
            area = strength / grade.ec;
        }
        if (ac !== null && u !== null && u !== 0) {
            h = 2 * ac / u;
        }
        if (fckCell) {
            fckCell.textContent = grade ? shown(grade.fck) : '';
        }
        if (fcmCell) {
            fcmCell.textContent = grade ? shown(grade.fck + 8) : '';
        }
        if (ecCell) {
            ecCell.textContent = grade ? shown(grade.ec) : '';
        }
        eequCell.textContent = shown(eequ);
        strengthCell.textContent = shown(strength);
        areaCell.textContent = shown(area);
        if (hCell) {
            hCell.textContent = shown(h);
        }
    }

    showSection();

    form.addEventListener('input', showSection);
    form.addEventListener('change', showSection);

    function pageName(href) {
        try {
            var url = new URL(href, window.location.href);
            return url.pathname.substring(url.pathname.lastIndexOf('/') + 1) + url.search;
        } catch (error) {
            return href;
        }
    }

    function remember(href) {
        var data = new FormData(form);
        data.append('draft_only', '1');
        data.append('next', pageName(href));
        fetch(form.action, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            redirect: 'manual'
        }).then(function () {
            window.location = href;
        }).catch(function () {
            window.location = href;
        });
    }

    var links = document.querySelectorAll('.tabs a, .sidenav a, a.back');
    for (var k = 0; k < links.length; k++) {
        links[k].addEventListener('click', function (event) {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                return;
            }
            event.preventDefault();
            remember(this.href);
        });
    }

    var saveForm = document.querySelector('form.save-project-form');
    if (saveForm) {
        saveForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var flag = document.createElement('input');
            flag.type = 'hidden';
            flag.name = 'save_project';
            flag.value = '1';
            form.appendChild(flag);
            var next = document.createElement('input');
            next.type = 'hidden';
            next.name = 'next';
            next.value = pageName(window.location.href);
            form.appendChild(next);
            form.submit();
        });
    }
})();

