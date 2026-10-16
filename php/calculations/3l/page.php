<?php
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/parameter.php';
require_once __DIR__ . '/measured.php';
require_once __DIR__ . '/theoretical.php';
require_once __DIR__ . '/output.php';
require_once __DIR__ . '/../../icons.php';
require_once __DIR__ . '/../view.php';
// 3L calculator screen.

function l3_td($value, $decimals = null) {
    echo '<td class="formula">' . htmlspecialchars(l3_text($value, $decimals)) . '</td>';
}

function l3_add($label) {
    if (calc_view_only()) {
        return;
    }
    echo '<button class="add-icon" type="submit" name="add_row" value="1" aria-label="' . htmlspecialchars($label) . '">+</button>';
}

function l3_trash($row_no, $message) {
    if (calc_view_only()) {
        return;
    }
    echo '<button class="trash" type="submit" name="delete_row" value="' . (int) $row_no . '" onclick="return confirm(' . htmlspecialchars(json_encode($message), ENT_QUOTES) . ');">';
    echo icon_trash();
    echo '</button>';
}

$tab = $_GET['tab'] ?? 'parameter';
$part = $_GET['part'] ?? 'modulus';
if ($tab !== 'parameter' && $tab !== 'details' && $tab !== 'measured' && $tab !== 'output' && $tab !== 'theoretical') {
    $tab = 'parameter';
}
if ($part !== 'modulus' && $part !== 'shrinkage' && $part !== 'creep') {
    $part = 'modulus';
}

$link = 'project.php?id=' . (int) $project['project_id'] . '&sensor_id=' . (int) $selected_sensor['sensor_id'];
$params = l3_load_params($conn, (int) $selected_sensor['sensor_id']);                                        //loads parameter
$draft_params = l3_draft_section((int) $project['project_id'], (int) $selected_sensor['sensor_id'], 'parameter');
if (!calc_view_only() && is_array($draft_params)) {               
    $params = array_merge(l3_default_params(), $draft_params);
}
$readings = l3_load_readings($conn, (int) $selected_sensor['sensor_id']);                                   //loads readings
$draft_readings = l3_draft_section((int) $project['project_id'], (int) $selected_sensor['sensor_id'], 'details');
if (!calc_view_only() && is_array($draft_readings)) {
    $readings = l3_pad_rows($draft_readings, 'reading');
}
$history = l3_load_history($conn, (int) $selected_sensor['sensor_id']);                                    //loads history
$draft_history = l3_draft_section((int) $project['project_id'], (int) $selected_sensor['sensor_id'], 'modulus');
if (!calc_view_only() && is_array($draft_history)) {
    $history = l3_pad_rows($draft_history, 'history');
}
$parameter = l3_parameter($params, $history); //after the saved rows laoded, it will run this formulas
$rows = l3_measured($readings, $parameter);
$rows = l3_shrinkage($rows, $params, $parameter);
$rows = l3_creep($rows, $params, $history, $parameter);
$rows = l3_output($rows);
$calc = $parameter;
$calc['rows'] = $rows;
$modulus = array('rows' => l3_modulus($params, $history, $parameter));
calc_view_begin();
?>
<h1>
  <span style="font-weight: normal; font-size: 1rem;">PROJECT:</span>
  <strong><?php echo htmlspecialchars($project['project_name']); ?></strong><br>
  <span style="font-weight: normal; font-size: 1rem;">Sensor Model:</span>
  <strong style="font-size: 1rem;">CE-126LGS</strong>
</h1>
<p>Sensor: <?php echo htmlspecialchars($selected_sensor['sensor_number']); ?></p>
<?php if (calc_view_only()) { ?>
<?php } ?>

<div class="tabs">
    <a href="<?php echo $link; ?>&tab=parameter"<?php if ($tab === 'parameter') { echo ' class="active"'; } ?>>Parameter</a> 
    <a href="<?php echo $link; ?>&tab=details"<?php if ($tab === 'details') { echo ' class="active"'; } ?>>Details</a>
    <a href="<?php echo $link; ?>&tab=measured"<?php if ($tab === 'measured') { echo ' class="active"'; } ?>>Measured strain calculation</a>
    <a href="<?php echo $link; ?>&tab=output"<?php if ($tab === 'output') { echo ' class="active"'; } ?>>Output</a>
    <a href="<?php echo $link; ?>&tab=theoretical&part=modulus"<?php if ($tab === 'theoretical') { echo ' class="active"'; } ?>>Theoretical strain calculation</a>
</div>

<?php if ($tab === 'theoretical') { ?>
    <div class="tabs subtabs">
        <a href="<?php echo $link; ?>&tab=theoretical&part=modulus"<?php if ($part === 'modulus') { echo ' class="active"'; } ?>>Modulus</a>
        <a href="<?php echo $link; ?>&tab=theoretical&part=shrinkage"<?php if ($part === 'shrinkage') { echo ' class="active"'; } ?>>Notional shrinkage</a>
        <a href="<?php echo $link; ?>&tab=theoretical&part=creep"<?php if ($part === 'creep') { echo ' class="active"'; } ?>>Creep</a>
    </div>
<?php } ?>

<?php if ($tab === 'details') { ?>
    <form id="l3-form" class="l3-form" method="post" action="project.php?id=<?php echo (int) $project['project_id']; ?>&sensor_id=<?php echo (int) $selected_sensor['sensor_id']; ?>&tab=details">
        <input type="hidden" name="l3_form" value="details">
        <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
        <input type="hidden" name="sensor_id" value="<?php echo (int) $selected_sensor['sensor_id']; ?>">
        <table class="atg">
            <tr>
                <th rowspan="2">Sensor measurement</th>
                <th rowspan="2">Date</th>
                <th colspan="2">Bottom</th>
                <th colspan="2">Top</th>
                <?php if (!calc_view_only()) { ?>
                    <th rowspan="2"><?php l3_add('Add a row'); ?></th>
                <?php } ?>
            </tr>
            <tr>
                <th>Strain</th>
                <th>Temp</th>
                <th>Strain</th>
                <th>Temp</th>
            </tr>
            <?php foreach ($readings as $reading) { ?>
                <tr>
                    <td class="input"><input type="text" name="item_name_<?php echo (int) $reading['row_no']; ?>" value="<?php echo htmlspecialchars($reading['item_name']); ?>"></td>
                    <td class="input"><input type="text" name="reading_date_<?php echo (int) $reading['row_no']; ?>" value="<?php echo htmlspecialchars(l3_date_text($reading['reading_date'])); ?>"></td>
                    <td class="input"><input type="number" step="any" inputmode="decimal" name="bottom_strain_<?php echo (int) $reading['row_no']; ?>" value="<?php $n = str_replace(',', '', trim((string) $reading['bottom_strain'])); echo htmlspecialchars(is_numeric($n) ? $n : ''); ?>"></td>
                    <td class="input"><input type="number" step="any" inputmode="decimal" name="bottom_temp_<?php echo (int) $reading['row_no']; ?>" value="<?php $n = str_replace(',', '', trim((string) $reading['bottom_temp'])); echo htmlspecialchars(is_numeric($n) ? $n : ''); ?>"></td>
                    <td class="input"><input type="number" step="any" inputmode="decimal" name="top_strain_<?php echo (int) $reading['row_no']; ?>" value="<?php $n = str_replace(',', '', trim((string) $reading['top_strain'])); echo htmlspecialchars(is_numeric($n) ? $n : ''); ?>"></td>
                    <td class="input"><input type="number" step="any" inputmode="decimal" name="top_temp_<?php echo (int) $reading['row_no']; ?>" value="<?php $n = str_replace(',', '', trim((string) $reading['top_temp'])); echo htmlspecialchars(is_numeric($n) ? $n : ''); ?>"></td>
                    <?php if (!calc_view_only()) { ?>
                        <td class="trash-cell"><?php l3_trash((int) $reading['row_no'], 'Delete this row?'); ?></td>
                    <?php } ?>
                </tr>
            <?php } ?>
        </table>
    </form>

<?php } elseif ($tab === 'measured') { ?>
    <table class="atg">
        <tr>
            <th rowspan="2">Sensor measurement</th>
            <th colspan="2">Average measured strain</th>
            <th colspan="2">Changes in measured strain</th>
            <th rowspan="2">&deg;C</th>
            <th rowspan="2">Measured strain value</th>
        </tr>
        <tr>
            <th>Strain</th>
            <th>Temp</th>
            <th>Strain</th>
            <th>Temp</th>
        </tr>
        <?php foreach ($calc['rows'] as $row) { ?>
            <tr>
                <td class="formula"><?php echo htmlspecialchars($row['reading']['item_name']); ?></td>
                <?php l3_td(l3_show($row['used'], $row['avg_strain']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['avg_temp']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['d_strain']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['d_temp']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['temp_c']), 1); ?>
                <?php
                if ((int) $row['row_no'] === 1) {
                    echo '<td class="formula"></td>';
                } else {
                    l3_td(l3_show($row['used'], $row['measured']), 3);
                }
                ?>
            </tr>
        <?php } ?>
    </table>

<?php } elseif ($tab === 'output') { ?>
    <table class="atg">
        <tr>
            <th rowspan="2">Item</th>
            <th rowspan="2">Date</th>
            <th rowspan="2">Measured</th>
            <th colspan="3">Theoretical strain</th>
            <th rowspan="2">Total TS</th>
        </tr>
        <tr>
            <th>&epsilon; shrinkage</th>
            <th>&epsilon; creep</th>
            <th>&epsilon; strain</th>
        </tr>
        <?php foreach ($calc['rows'] as $row) { ?>
            <tr>
                <td class="formula"><?php echo htmlspecialchars($row['reading']['item_name']); ?></td>
                <td class="formula"><?php echo htmlspecialchars(l3_date_text($row['reading']['reading_date'])); ?></td>
                <?php
                if ((int) $row['row_no'] === 1) {
                    l3_td(0, 3);
                    l3_td(0, 3);
                    l3_td(0, 3);
                    l3_td(0, 3);
                    l3_td(0, 3);
                } else {
                    l3_td(l3_show($row['used'], $row['out_measured']), 3);
                    l3_td(l3_show($row['used'], $row['out_shrink']), 3);
                    l3_td(l3_show($row['used'], $row['out_creep']), 3);
                    l3_td(l3_show($row['used'], $row['out_strain']), 3);
                    l3_td(l3_show($row['used'], $row['out_total']), 3);
                }
                ?>
            </tr>
        <?php } ?>
    </table>

<?php } elseif ($tab === 'theoretical' && $part === 'shrinkage') { ?>
    <table class="atg">
        <tr>
            <th>SN</th>
            <th>&epsilon;cs0</th>
            <th>&epsilon;s (fcm)</th>
            <th>&beta;sRH</th>
            <th>&beta;RH</th>
            <th>&beta;s (t-ts)</th>
            <th>&epsilon;cs (t, ts)</th>
        </tr>
        <?php foreach ($calc['rows'] as $row) { ?>
            <tr>
                <td class="formula"><?php echo (int) $row['row_no']; ?></td>
                <?php l3_td(l3_show($row['used'], $row['eps_cs0']), 5); ?>
                <?php l3_td(l3_show($row['used'], $row['eps_s'])); ?>
                <?php l3_td(l3_show($row['used'], $row['beta_srh'])); ?>
                <?php l3_td(l3_show($row['used'], $row['beta_rh']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['beta_s']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['eps_cs']), 3); ?>
            </tr>
        <?php } ?>
    </table>

<?php } elseif ($tab === 'theoretical' && $part === 'creep') { ?>
    <table class="atg">
        <tr>
            <th>SN</th>
            <th>&phi; (t, t0)</th>
            <th>&phi;0</th>
            <th>&phi;RH</th>
            <th>&beta; (fcm)</th>
            <th>&beta; (t0)</th>
            <th>&beta;c (t-t0)</th>
            <th>&beta;H</th>
            <th>Total</th>
        </tr>
        <?php foreach ($calc['rows'] as $row) { ?>
            <tr>
                <td class="formula"><?php echo (int) $row['row_no']; ?></td>
                <?php l3_td(l3_show($row['used'], $row['phi']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['phi0']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['phi_rh']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['beta_fcm']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['beta_t0']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['beta_c']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['beta_h']), 3); ?>
                <?php l3_td(l3_show($row['used'], $row['creep']), 3); ?>
            </tr>
        <?php } ?>
    </table>

<?php } elseif ($tab === 'theoretical') { ?>
    <form id="l3-form" class="l3-form" method="post" action="project.php?id=<?php echo (int) $project['project_id']; ?>&sensor_id=<?php echo (int) $selected_sensor['sensor_id']; ?>&tab=theoretical&part=modulus">
        <input type="hidden" name="l3_form" value="modulus">
        <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
        <input type="hidden" name="sensor_id" value="<?php echo (int) $selected_sensor['sensor_id']; ?>">
        <table class="atg">
            <tr>
                <th rowspan="2">SN</th>
                <th rowspan="2">Date of stress history <?php l3_add('Add a stress date'); ?></th>
                <th rowspan="2">Age</th>
                <th rowspan="2">Total load</th>
                <th rowspan="2">Stress inc</th>
                <th style="text-align: center" colspan="5">Modulus of elasticity</th>
            </tr>
            <tr>
                <th>Ec</th>
                <th>Eci (t)</th>
                <th>Eci</th>
                <th>&beta;E (t)</th>
                <th>&beta;cc (t)</th>
            </tr>
            <?php foreach ($modulus['rows'] as $row) { ?>
                <tr>
                    <td class="formula"><?php echo (int) $row['row_no']; ?></td>
                    <td class="input<?php echo calc_view_only() ? '' : ' with-trash'; ?>">
                        <input type="text" name="stress_date_<?php echo (int) $row['row_no']; ?>" value="<?php echo htmlspecialchars(l3_date_text($row['reading']['stress_date'])); ?>">
                        <?php l3_trash((int) $row['row_no'], 'Delete this stress date?'); ?>
                    </td>
                    <?php l3_td($row['age']); ?>
                    <?php l3_td($row['total_load'], 2); ?>
                    <?php l3_td($row['stress_inc'], 2); ?>
                    <?php l3_td($row['ec_mod']); ?>
                    <?php l3_td(l3_show($row['used'], $row['eci_t']), 5); ?>
                    <?php l3_td($row['eci']); ?>
                    <?php l3_td(l3_show($row['used'], $row['beta_e']), 3); ?>
                    <?php l3_td(l3_show($row['used'], $row['beta_cc']), 3); ?>
                </tr>
            <?php } ?>
        </table>
    </form>

<?php } else { ?>
    <form id="l3-form" class="l3-form" method="post" action="project.php?id=<?php echo (int) $project['project_id']; ?>&sensor_id=<?php echo (int) $selected_sensor['sensor_id']; ?>&tab=parameter">
        <input type="hidden" name="l3_form" value="parameter">
        <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
        <input type="hidden" name="sensor_id" value="<?php echo (int) $selected_sensor['sensor_id']; ?>">
        <table class="atg atg-parameter">
            <tr>
                <th>Parameter</th>
                <th></th>
                <th>Input</th>
                <th>Unit</th>
                <th></th>
                <th class="section" colspan="4">SENSOR PARAMETERS</th>
            </tr>
            <tr>
                <td>Concrete grade</td>
                <td class="eq">=</td>
                <td class="input">
                    <select name="concrete_grade">
                        <option value="40"<?php echo $params['concrete_grade'] === '40' ? ' selected' : ''; ?>>40</option>
                        <option value="50"<?php echo $params['concrete_grade'] === '50' ? ' selected' : ''; ?>>50</option>
                        <option value="60"<?php echo $params['concrete_grade'] === '60' ? ' selected' : ''; ?>>60</option>
                    </select>
                </td>
                <td></td>
                <td></td>
                <td>SERIAL NO</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="serial_no" value="<?php echo htmlspecialchars($params['serial_no']); ?>"></td>
                <td></td>
            </tr>
            <tr>
                <td>fck</td>
                <td class="eq">=</td>
                <td class="formula" id="l3-fck"><?php echo htmlspecialchars(l3_text($calc['fck'])); ?></td>
                <td></td>
                <td></td>
                <td>CS</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="cs" value="<?php echo htmlspecialchars($params['cs']); ?>"></td>
                <td>u&epsilon;/pm</td>
            </tr>
            <tr>
                <td>fcm</td>
                <td class="eq">=</td>
                <td class="formula">fck + &Delta;f</td>
                <td></td>
                <td></td>
                <td>CT</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="ct" value="<?php echo htmlspecialchars($params['ct']); ?>"></td>
                <td>&deg;C/pm</td>
            </tr>
            <tr>
                <td>&Delta;f</td>
                <td class="eq">=</td>
                <td class="formula">8</td>
                <td></td>
                <td></td>
                <td>CTE<sub>A</sub></td>
                <td class="eq">=</td>
                <td class="formula">14.1</td>
                <td>um/m-&deg;C</td>
            </tr>
            <tr>
                <td>fcm</td>
                <td class="eq">=</td>
                <td class="formula" id="l3-fcm"><?php echo htmlspecialchars(l3_text($calc['fcm'])); ?></td>
                <td></td>
                <td></td>
                <td>CTE<sub>B</sub></td>
                <td class="eq">=</td>
                <td class="formula">17.3</td>
                <td>um/m-&deg;C</td>
            </tr>
            <tr>
                <td>fcm0</td>
                <td class="eq">=</td>
                <td class="formula">10</td>
                <td></td>
                <td></td>
                <td>CTE<sub>C</sub></td>
                <td class="eq">=</td>
                <td class="formula">16.4</td>
                <td>um/m-&deg;C</td>
            </tr>
            <tr>
                <td>&beta;sc</td>
                <td class="eq">=</td>
                <td class="formula">5</td>
                <td></td>
                <td></td>
                <td>L<sub>1</sub></td>
                <td class="eq">=</td>
                <td class="formula">0.006</td>
                <td>m</td>
            </tr>
            <tr>
                <td>&pi;</td>
                <td class="eq">=</td>
                <td class="formula">3.1415926</td>
                <td></td>
                <td></td>
                <td>L<sub>2</sub></td>
                <td class="eq">=</td>
                <td class="formula">0.03</td>
                <td>m</td>
            </tr>
            <tr>
                <td>Es</td>
                <td class="eq">=</td>
                <td class="formula">200</td>
                <td>kN/mm2</td>
                <td></td>
                <td>CC</td>
                <td class="eq">=</td>
                <td class="formula">1000</td>
                <td></td>
            </tr>
            <tr>
                <td>Ec</td>
                <td class="eq">=</td>
                <td class="formula" id="l3-ec"><?php echo htmlspecialchars(l3_text($calc['ec_table'])); ?></td>
                <td>kN/mm2</td>
                <td></td>
                <td>T wavelength</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="t_wave" value="<?php echo htmlspecialchars($params['t_wave']); ?>"></td>
                <td>nm</td>
            </tr>
            <tr>
                <td>SFC</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="sfc" value="<?php echo htmlspecialchars($params['sfc']); ?>"></td>
                <td>Mpa</td>
                <td></td>
                <td>Temperature</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="temperature" value="<?php echo htmlspecialchars($params['temperature']); ?>"></td>
                <td>&deg;C</td>
            </tr>
            <tr>
                <td>Eco</td>
                <td class="eq">=</td>
                <td class="formula">21500</td>
                <td>Mpa</td>
                <td></td>
                <td>Temp</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="temp_offset" value="<?php echo htmlspecialchars($params['temp_offset']); ?>"></td>
                <td></td>
            </tr>
            <tr>
                <td>Eequ</td>
                <td class="eq">=</td>
                <td class="formula" id="l3-eequ"><?php echo htmlspecialchars(l3_text($calc['eequ'])); ?></td>
                <td>kN/mm2</td>
                <td></td>
                <td class="section" colspan="4">Days</td>
            </tr>
            <tr>
                <td>S</td>
                <td class="eq">=</td>
                <td class="formula">0.25</td>
                <td>Mpa</td>
                <td></td>
                <td>t0</td>
                <td class="eq">=</td>
                <td class="formula">28</td>
                <td>days</td>
            </tr>
            <tr>
                <td class="section" colspan="4">Time of Pouring</td>
                <td></td>
                <td>ts</td>
                <td class="eq">=</td>
                <td class="formula">0</td>
                <td>days</td>
            </tr>
            <tr>
                <td>t0</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="pour_date" value="<?php echo htmlspecialchars($params['pour_date']); ?>"></td>
                <td>days</td>
                <td></td>
                <td>t1</td>
                <td class="eq">=</td>
                <td class="formula">1</td>
                <td>days</td>
            </tr>
            <tr>
                <td class="section" colspan="4">Relative Humidity</td>
                <td></td>
                <td class="section" colspan="4">Notional size of member</td>
            </tr>
            <tr>
                <td>RH</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="rh" value="<?php echo htmlspecialchars($params['rh']); ?>"></td>
                <td>%</td>
                <td></td>
                <td>h</td>
                <td class="eq">=</td>
                <td class="formula" id="l3-h"><?php echo htmlspecialchars(l3_text($calc['h'])); ?></td>
                <td>mm</td>
            </tr>
            <tr>
                <td>RH0</td>
                <td class="eq">=</td>
                <td class="formula">100</td>
                <td>%</td>
                <td></td>
                <td>h0</td>
                <td class="eq">=</td>
                <td class="formula">100</td>
                <td>mm</td>
            </tr>
            <tr>
                <td class="section" colspan="4">Area of Concrete</td>
                <td></td>
                <td class="section" colspan="4">Strength</td>
            </tr>
            <tr>
                <td>Ac</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="ac" value="<?php echo htmlspecialchars($params['ac']); ?>"></td>
                <td>mm2</td>
                <td></td>
                <td>Strength</td>
                <td class="eq">=</td>
                <td class="formula" id="l3-strength"><?php echo htmlspecialchars(l3_text($calc['strength'])); ?></td>
                <td>kN</td>
            </tr>
            <tr>
                <td class="section" colspan="4">Area of Reinforcement</td>
                <td></td>
                <td class="section" colspan="4">Eq area of conc</td>
            </tr>
            <tr>
                <td>Ar</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="ar" value="<?php echo htmlspecialchars($params['ar']); ?>"></td>
                <td>mm2</td>
                <td></td>
                <td>Eq area of conc</td>
                <td class="eq">=</td>
                <td class="formula" id="l3-eq-area"><?php echo htmlspecialchars(l3_text($calc['eq_area'])); ?></td>
                <td>mm2</td>
            </tr>
            <tr>
                <td class="section" colspan="4">Perimeter of Concrete</td>
                <td></td>
                <td class="section" colspan="4">Stress history</td>
            </tr>
            <tr>
                <td>u</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="u" value="<?php echo htmlspecialchars($params['u']); ?>"></td>
                <td>mm</td>
                <td></td>
                <td>BASE</td>
                <td class="eq">=</td>
                <td class="formula"><?php echo htmlspecialchars(l3_date_text((string) ($calc['base_date'] ?? ''))); ?></td>
                <?php l3_td($calc['base_stress']); ?>
            </tr>
            <tr>
                <td class="section" colspan="4">Total Load</td>
                <td></td>
                <td>MID</td>
                <td class="eq">=</td>
                <td class="formula"><?php echo htmlspecialchars(l3_date_text((string) ($calc['mid_date'] ?? ''))); ?></td>
                <?php l3_td($calc['mid_stress']); ?>
            </tr>
            <tr>
                <td>Load</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="load_value" value="<?php echo htmlspecialchars($params['load_value']); ?>"></td>
                <td>Mpa</td>
                <td></td>
                <td>ROOF</td>
                <td class="eq">=</td>
                <td class="formula"><?php echo htmlspecialchars(l3_date_text((string) ($calc['roof_date'] ?? ''))); ?></td>
                <?php l3_td($calc['roof_stress']); ?>
            </tr>
            <tr>
                <td colspan="5"></td>
                <td class="section" colspan="4">Floor Level</td>
            </tr>
            <tr>
                <td colspan="5"></td>
                <td>BASE</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="base_floor" value="<?php echo htmlspecialchars($params['base_floor']); ?>"></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="5"></td>
                <td>MID</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="mid_floor" value="<?php echo htmlspecialchars($params['mid_floor']); ?>"></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="5"></td>
                <td>ROOF</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="roof_floor" value="<?php echo htmlspecialchars($params['roof_floor']); ?>"></td>
                <td></td>
            </tr>
        </table>
    </form>
<?php } ?>

<?php
calc_view_end();
if (!calc_view_only()) {
?>
<script src="../js/sheet.js?v=2"></script> 
<script src="../js/3l.js?v=2"></script>
<?php } ?>
