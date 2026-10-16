document.querySelectorAll('.sidenav .nav-block').forEach(function (block) {
    var key = 'nav-block-' + block.getAttribute('data-block-id');
    var currentSensor = new URLSearchParams(window.location.search).get('sensor_id');
    var hasCurrentSensor = false;

    if (currentSensor) {
        block.querySelectorAll('.sensor-link').forEach(function (link) {
            if (new URLSearchParams(link.search).get('sensor_id') === currentSensor) {
                hasCurrentSensor = true;
            }
        });
    }

    if (!hasCurrentSensor && localStorage.getItem(key) === '1') {
        block.classList.add('collapsed');
    }

    block.querySelector('.block-toggle').addEventListener('click', function () {
        var collapsed = block.classList.toggle('collapsed');
        localStorage.setItem(key, collapsed ? '1' : '0');
    });
});
