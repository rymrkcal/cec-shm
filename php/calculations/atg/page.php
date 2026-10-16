<?php
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/parameter.php';
require_once __DIR__ . '/measured.php';
require_once __DIR__ . '/theoretical.php';
require_once __DIR__ . '/output.php';
require_once __DIR__ . '/../../icons.php';
require_once __DIR__ . '/../view.php';
// ATG calculator screen.

function atg_td($value, $decimals = null, $id = '') {
    $id_attr = '';
    if ($id !== '') {
        $id_attr = ' id="' . htmlspecialchars($id) . '"';
    }
    echo '<td class="formula"' . $id_attr . '>' . htmlspecialchars(atg_text($value, $decimals)) . '</td>';
}

function atg_add($label) {
    if (calc_view_only()) {
        return;
    }
    echo '<button class="add-icon" type="submit" name="add_row" value="1" aria-label="' . htmlspecialchars($label) . '">+</button>';
}

function atg_trash($row_no, $message) {
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
$params = atg_load_params($conn, (int) $selected_sensor['sensor_id']);                                        //loads the saved rows before draws the tables
$draft_params = atg_draft_section((int) $project['project_id'], (int) $selected_sensor['sensor_id'], 'parameter');
if (!calc_view_only() && is_array($draft_params)) {
    $params = array_merge(atg_default_params(), $draft_params);
}
$readings = atg_load_readings($conn, (int) $selected_sensor['sensor_id']);                                    //loads readings
$draft_readings = atg_draft_section((int) $project['project_id'], (int) $selected_sensor['sensor_id'], 'details');
if (!calc_view_only() && is_array($draft_readings)) {
    $readings = atg_pad_rows($draft_readings, 'reading');
}
$history = atg_load_history($conn, (int) $selected_sensor['sensor_id']);                                     //load history
$draft_history = atg_draft_section((int) $project['project_id'], (int) $selected_sensor['sensor_id'], 'modulus');
if (!calc_view_only() && is_array($draft_history)) {
    $history = atg_pad_rows($draft_history, 'history');
}
$parameter = atg_parameter($params, $history); //MUST CALCULATE IN THIS ORDER
$rows = atg_measured($readings, $parameter);
$rows = atg_shrinkage($rows, $params, $parameter);
$rows = atg_creep($rows, $params, $history, $parameter);
$rows = atg_output($rows);
$calc = $parameter;
$calc['rows'] = $rows;
$modulus = array('rows' => atg_modulus($params, $history, $parameter));
calc_view_begin();
?>
<h1>
  <span style="font-weight: normal; font-size: 1rem;">PROJECT:</span>
  <strong><?php echo htmlspecialchars($project['project_name']); ?></strong><br>
  <span style="font-weight: normal; font-size: 1rem;">Sensor Model:</span>
  <strong style="font-size: 1rem;">DL-12LGS33</strong>
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
    <form id="atg-form" class="atg-form" method="post" action="project.php?id=<?php echo (int) $project['project_id']; ?>&sensor_id=<?php echo (int) $selected_sensor['sensor_id']; ?>&tab=details">
        <input type="hidden" name="atg_form" value="details">
        <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
        <input type="hidden" name="sensor_id" value="<?php echo (int) $selected_sensor['sensor_id']; ?>">
        <table class="atg">
            <tr>
                <th rowspan="2">Sensor measurement</th>
                <th rowspan="2">Date</th>
                <th colspan="2">Bottom</th>
                <th colspan="2">Top</th>
                <?php if (!calc_view_only()) { ?>
                    <th rowspan="2"><?php atg_add('Add a row'); ?></th>
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
                    <td class="input"><input type="text" name="reading_date_<?php echo (int) $reading['row_no']; ?>" value="<?php echo htmlspecialchars(atg_date_text($reading['reading_date'])); ?>"></td>
                    <td class="input"><input type="text" name="bottom_strain_<?php echo (int) $reading['row_no']; ?>" value="<?php echo htmlspecialchars($reading['bottom_strain']); ?>"></td>
                    <td class="input"><input type="text" name="bottom_temp_<?php echo (int) $reading['row_no']; ?>" value="<?php echo htmlspecialchars($reading['bottom_temp']); ?>"></td>
                    <td class="input"><input type="text" name="top_strain_<?php echo (int) $reading['row_no']; ?>" value="<?php echo htmlspecialchars($reading['top_strain']); ?>"></td>
                    <td class="input"><input type="text" name="top_temp_<?php echo (int) $reading['row_no']; ?>" value="<?php echo htmlspecialchars($reading['top_temp']); ?>"></td>
                    <?php if (!calc_view_only()) { ?>
                        <td class="trash-cell"><?php atg_trash((int) $reading['row_no'], 'Delete this row?'); ?></td>
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
                <?php atg_td(atg_show($row['used'], $row['avg_strain']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['avg_temp']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['d_strain']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['d_temp']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['temp_c']), 1); ?>
                <?php
                if ((int) $row['row_no'] === 1) {
                    echo '<td class="formula"></td>';
                } else {
                    atg_td(atg_show($row['used'], $row['measured']), 3);
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
                <td class="formula"><?php echo htmlspecialchars(atg_date_text($row['reading']['reading_date'])); ?></td>
                <?php
                if ((int) $row['row_no'] === 1) {
                    atg_td(0, 3);
                    atg_td(0, 3);
                    atg_td(0, 3);
                    atg_td(0, 3);
                    atg_td(0, 3);
                } else {
                    atg_td(atg_show($row['used'], $row['out_measured']), 3);
                    atg_td(atg_show($row['used'], $row['out_shrink']), 3);
                    atg_td(atg_show($row['used'], $row['out_creep']), 3);
                    atg_td(atg_show($row['used'], $row['out_strain']), 3);
                    atg_td(atg_show($row['used'], $row['out_total']), 3);
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
                <?php atg_td(atg_show($row['used'], $row['eps_cs0']), 5); ?>
                <?php atg_td(atg_show($row['used'], $row['eps_s'])); ?>
                <?php atg_td(atg_show($row['used'], $row['beta_srh'])); ?>
                <?php atg_td(atg_show($row['used'], $row['beta_rh']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['beta_s']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['eps_cs']), 3); ?>
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
                <?php atg_td(atg_show($row['used'], $row['phi']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['phi0']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['phi_rh']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['beta_fcm']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['beta_t0']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['beta_c']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['beta_h']), 3); ?>
                <?php atg_td(atg_show($row['used'], $row['creep']), 3); ?>
            </tr>
        <?php } ?>
    </table>

<?php } elseif ($tab === 'theoretical') { ?>
    <form id="atg-form" class="atg-form" method="post" action="project.php?id=<?php echo (int) $project['project_id']; ?>&sensor_id=<?php echo (int) $selected_sensor['sensor_id']; ?>&tab=theoretical&part=modulus">
        <input type="hidden" name="atg_form" value="modulus">
        <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
        <input type="hidden" name="sensor_id" value="<?php echo (int) $selected_sensor['sensor_id']; ?>">
        <table class="atg">
            <tr>
                <th rowspan="2">SN</th>
                <th rowspan="2">Date of stress history <?php atg_add('Add a stress date'); ?></th>
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
                        <input type="text" name="stress_date_<?php echo (int) $row['row_no']; ?>" value="<?php echo htmlspecialchars(atg_date_text($row['reading']['stress_date'])); ?>">
                        <?php atg_trash((int) $row['row_no'], 'Delete this stress date?'); ?>
                    </td>
                    <?php atg_td($row['age']); ?>
                    <?php atg_td($row['total_load'], 2); ?>
                    <?php atg_td($row['stress_inc'], 2); ?>
                    <?php atg_td($row['ec_mod']); ?>
                    <?php atg_td(atg_show($row['used'], $row['eci_t']), 5); ?>
                    <?php atg_td($row['eci']); ?>
                    <?php atg_td(atg_show($row['used'], $row['beta_e']), 3); ?>
                    <?php atg_td(atg_show($row['used'], $row['beta_cc']), 3); ?>
                </tr>
            <?php } ?>
        </table>
    </form>

<?php } else { ?>
    <form id="atg-form" class="atg-form" method="post" action="project.php?id=<?php echo (int) $project['project_id']; ?>&sensor_id=<?php echo (int) $selected_sensor['sensor_id']; ?>&tab=parameter">
        <input type="hidden" name="atg_form" value="parameter">
        <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
        <input type="hidden" name="sensor_id" value="<?php echo (int) $selected_sensor['sensor_id']; ?>">
        <table class="atg atg-parameter">
            <tr>
                <th>Parameter</th>
                <th></th>
                <th>Input</th>
                <th>Unit</th>
                <th class="section" colspan="5"></th>
            </tr>
            <tr>
                <td>Concrete  grade</td>
                <td class="eq">=</td>
                
                <td class="input">
                    <select name="concrete_grade">
                        <option value="40"<?php echo $params['concrete_grade'] === '40' ? ' selected' : ''; ?>>40</option>
                        <option value="50"<?php echo $params['concrete_grade'] === '50' ? ' selected' : ''; ?>>50</option>
                        <option value="60"<?php echo $params['concrete_grade'] === '60' ? ' selected' : ''; ?>>60</option>
                    </select>
                </td>
                <td>kN/mm2</td>
                <td></td>
                <td class="section" colspan="4">SENSOR PARAMETERS</td>
            </tr>
            <tr>
                <td>fcm</td>
                <td class="eq">=</td>
                <td class="formula">fck + &Delta;f</td>
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
                <?php atg_td($calc['fck'], null, 'atg-fck'); ?>
                <td></td>
                <td></td>
                <td>Ks</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="ks" value="<?php echo htmlspecialchars($params['ks']); ?>"></td>
                <td>nm/u&epsilon;</td>
                
            </tr>
            <tr>
                <td>&Delta;f</td>
                <td class="eq">=</td>
                <td class="formula">8</td>
                <td></td>
                <td></td>
                <td>Ktt</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="ktt" value="<?php echo htmlspecialchars($params['ktt']); ?>"></td>
                <td>nm/&deg;C</td>
            </tr>
            <tr>
                <td>fcm</td>
                <td class="eq">=</td>
                <?php atg_td($calc['fcm'], null, 'atg-fcm'); ?>
                <td></td>
                <td></td>
                <td>Kts</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="kts" value="<?php echo htmlspecialchars($params['kts']); ?>"></td>
                <td>nm/&deg;C</td>
            </tr>
            <tr>
                <td>fcm0</td>
                <td class="eq">=</td>
                <td class="formula">10</td>
                <td></td>
                <td></td>
                <td>Kst</td>
                <td class="eq">=</td>
                <?php atg_td($calc['kst_ratio']); ?>
                <td>nm/&deg;C</td>
            </tr>
            <tr>
                <td>&beta;sc</td>
                <td class="eq">=</td>
                <td class="formula">5</td>
                <td></td>
                <td></td>
                <td rowspan="2">Temp-FBG</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="temp_fbg_1" value="<?php echo htmlspecialchars($params['temp_fbg_1']); ?>"></td>
                <td></td>
            </tr>
            <tr>
                <td>&pi;</td>
                <td class="eq">=</td>
                <td class="formula">3.1415926</td>
                <td></td>
               <td></td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="temp_fbg_2" value="<?php echo htmlspecialchars($params['temp_fbg_2']); ?>"></td>
                <td></td>
            </tr>
            <tr>
                <td>Es</td>
                <td class="eq">=</td>
                <td class="formula">200</td>
                <td>kN/mm2</td>
                <td></td>
                <td class="section" colspan="4">Days</td>
            </tr>
            <tr>
                <td>Ec</td>
                <td class="eq">=</td>
                <?php atg_td($calc['ec_table'], null, 'atg-ec'); ?>
                <td>kN/mm2</td>
                <td></td>
                <td>t0</td>
                <td class="eq">=</td>
                <?php atg_td($calc['creep_t0']);?>
                <td>days</td>
            </tr>
            <tr>
                <td>SFC</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="sfc" value="<?php echo htmlspecialchars($params['sfc']); ?>"></td>
                <td>Mpa</td>
                <td></td>
                <td>ts</td>
                <td class="eq">=</td>
                <td class="formula">0</td>
                <td>days</td> 
            </tr>
            <tr>
                <td>Eco</td>
                <td class="eq">=</td>
                <td class="formula">21500</td>
                <td>Mpa</td>
                <td></td>
                <td>t1</td>
                <td class="eq">=</td>
                <td class="formula">1</td>
                <td>days</td>
            </tr>
            <tr>
                <td>Eequ</td>
                <td class="eq">=</td>
                <td class="formula" id="eequ"><?php echo htmlspecialchars(atg_text($calc['eequ'])); ?></td>
                <td>kN/mm2</td>
                <td></td>
                <td class="section" colspan="4">Notional size of member</td>
            </tr>
            <tr>
                <td>S</td>
                <td class="eq">=</td>
                <td class="formula">0.25</td>
                <td>Mpa</td>
                <td></td>
                <td>h</td>
                <td class="eq">=</td>
                <?php atg_td($calc['h']); ?>
                <td>mm</td> 
            </tr>
            <tr>
                <td class="section" colspan="4">Time of Pouring</td>
                <td></td>
                <td>h0</td>
                <td class="eq">=</td>
                <td class="formula">100</td>
                <td>mm</td>
            </tr>
            <tr>
                <td>t0</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="pour_date" value="<?php echo htmlspecialchars($params['pour_date']); ?>"></td>
                <td>days</td>
                <td></td>
                <td class="section" colspan="4">Strength</td>
            </tr>
            <tr>
                <td class="section" colspan="4">Relative Humidity</td>
                <td></td>
                <td>Strength</td>
                <td class="eq">=</td>
                <td class="formula" id="strength"><?php echo htmlspecialchars(atg_text($calc['strength'])); ?></td>
                <td>kN</td>
            </tr>
            <tr>
                <td>RH</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="rh" value="<?php echo htmlspecialchars($params['rh']); ?>"></td>
                <td>%</td>
                <td></td>
                <td class="section" colspan="4">Eq area of conc</td>
            </tr>
            <tr>
                <td>RH0</td>
                <td class="eq">=</td>
                <td class="formula">100</td>
                <td>%</td>
                <td></td>
                <td>Eq area of conc</td>
                <td class="eq">=</td>
                <td class="formula" id="eq-area"><?php echo htmlspecialchars(atg_text($calc['eq_area'])); ?></td>
                <td>mm2</td> 
            </tr>
            <tr>
                <td class="section" colspan="4">Area of Concrete</td>
                <td></td>
                <td class="section" colspan="4">Stress history</td>
            </tr>
            <tr>
                <td>Ac</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="ac" value="<?php echo htmlspecialchars($params['ac']); ?>"></td>
                <td>mm2</td>
                <td></td>
                <td>BASE</td>
                <td class="eq">=</td>
                <td class="formula"><?php echo htmlspecialchars(atg_date_text((string) ($calc['base_date'] ?? ''))); ?></td>
                <?php atg_td($calc['base_stress']); ?>
            </tr>
            <tr>
                <td class="section" colspan="4">Area of Reinforcement</td>
                <td></td>
                <td>MID</td>
                <td class="eq">=</td>
                <td class="formula"><?php echo htmlspecialchars(atg_date_text((string) ($calc['mid_date'] ?? ''))); ?></td>
                <?php atg_td($calc['mid_stress']); ?>
            </tr>
            <tr>
                <td>Ar</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="ar" value="<?php echo htmlspecialchars($params['ar']); ?>"></td>
                <td>mm2</td>
                <td></td>
                <td>ROOF</td>
                <td class="eq">=</td>
                <td class="formula"><?php echo htmlspecialchars(atg_date_text((string) ($calc['roof_date'] ?? ''))); ?></td>
                <?php atg_td($calc['roof_stress']); ?>
            </tr>
            <tr>
                <td class="section" colspan="4">Perimeter of Concrete</td>
                <td></td>
                <td class="section" colspan="4">Floor Level</td>
            </tr>
            <tr>
                <td>u</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="u" value="<?php echo htmlspecialchars($params['u']); ?>"></td>
                <td>mm</td>
                <td></td>
                <td>BASE</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="base_floor" value="<?php echo htmlspecialchars($params['base_floor']); ?>"></td>
                <td></td>
            </tr>
            <tr>
                <td class="section" colspan="4">Total Load</td>
                <td></td>
                <td>MID</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="mid_floor" value="<?php echo htmlspecialchars($params['mid_floor']); ?>"></td>
                <td></td>
            </tr>
            <tr>
                <td>Load</td>
                <td class="eq">=</td>
                <td class="input"><input type="text" name="load_value" value="<?php echo htmlspecialchars($params['load_value']); ?>"></td>
                <td>Mpa</td>
                <td></td>
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
<script src="../js/sheet.js?v=1"></script>
<script src="../js/atg.js?v=2"></script>
<?php } ?>
