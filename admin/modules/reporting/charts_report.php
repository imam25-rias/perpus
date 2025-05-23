<?php
/*
 * Chart
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* Chart/Plot Report section */

// key to authenticate
define('INDEX_AUTH', '1');

if (!defined('SB')) {
    // main system configuration
    require '../../../sysconfig.inc.php';
    // start the session
    require SB.'admin/default/session.inc.php';
}

// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');

require SB.'admin/default/session_check.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
// PHPLOT Library
if (file_exists(LIB.'phplot'.DIRECTORY_SEPARATOR.'phplot.php')) {
    require LIB.'phplot'.DIRECTORY_SEPARATOR.'phplot.php';
} else {
    die();
}

// privileges checking
$can_read = utility::havePrivilege('reporting', 'r');
$can_write = utility::havePrivilege('reporting', 'w');

if (!$can_read) { die(); }

/**
 * Function to generate random color
 * Taken from http://www.jonasjohn.de/snippets/php/random-color.htm
 * Licensed in Public Domain
 */
function generateRandomColors()
{
    // @mt_srand((double)microtime()*10000);
    $_c = '';
    while(strlen($_c)<6){ $_c .= sprintf("%02X", mt_rand(0, 255)); }
    return $_c;
}

if($sysconf['chart']['mode'] == 'plot') {
    // create PHPLot object
    $plot = new PHPlot(770, 515);
    $plot_data = [];
    $data_colors = [];    
} else {
    $plot_data = [];    
    $data_label = [];
}

// default chart
$chart = 'total_title_gmd';
$chart_title = __('Total Titles By Medium/GMD');

if (isset($_GET['chart'])) {
    $chart = trim($_GET['chart']);
}


/**
 * Defines data here
 */
switch ($chart) {
    case 'total_title_colltype':
        $chart_title = __('Total Items By Collection Type');
        $stat_query = $dbs->query('SELECT coll_type_name, COUNT(item_id) AS total_items
            FROM `item` AS i
            INNER JOIN mst_coll_type AS ct ON i.coll_type_id = ct.coll_type_id
            GROUP BY i.coll_type_id
            HAVING total_items >0
            ORDER BY COUNT(item_id) DESC');
        // set plot data and colors
        while ($data = $stat_query->fetch_row()) {
            if($sysconf['chart']['mode'] == 'plot') {
                $plot_data[] = array($data[0], $data[1]);
                $data_colors[] = '#'.generateRandomColors();
            } else {
                $data_label[] = "'".preg_replace('/[^A-Za-z0-9]/', '', $data[0]).' ('.$data[1].")'";
                $plot_data[] = $data[1];
            }

        }
        break;
    case 'total_member_by_type':
        $chart_title = __('Total Members By Membership Type');
        // total number of active member by membership type
        $report_q = $dbs->query('SELECT member_type_name, COUNT(member_id) FROM mst_member_type AS mt
            LEFT JOIN member AS m ON mt.member_type_id=m.member_type_id
            WHERE TO_DAYS(expire_date)>TO_DAYS(\''.date('Y-m-d').'\')
            GROUP BY m.member_type_id ORDER BY COUNT(member_id) DESC');
        while ($data = $report_q->fetch_row()) {
            if($sysconf['chart']['mode'] == 'plot') {
                $plot_data[] = array($data[0], $data[1]);
                $data_colors[] = '#'.generateRandomColors();
            } else {
                $data_label[] = "'".str_replace("'",'',$data[0]).' ('.$data[1].")'";
                $plot_data[] = $data[1];
            }
        }
        break;
    case 'total_loan_gmd':
        $chart_title = __('Total Loan By GMD/Medium');
        $report_q = $dbs->query('SELECT gmd_name, COUNT(loan_id) FROM loan AS l
            INNER JOIN item AS i ON l.item_code=i.item_code
            INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
            INNER JOIN mst_gmd AS gmd ON b.gmd_id=gmd.gmd_id
            GROUP BY b.gmd_id ORDER BY COUNT(loan_id) DESC');
        $report_d = '';
        while ($data = $report_q->fetch_row()) {
            if($sysconf['chart']['mode'] == 'plot') {
                $plot_data[] = array($data[0], $data[1]);
                $data_colors[] = '#'.generateRandomColors();
            } else {
                $data_label[] = "'".preg_replace('/[^A-Za-z0-9]/', '', $data[0]).' ('.$data[1].")'";
                $plot_data[] = $data[1];
            }
        }
        break;
    case 'total_loan_colltype':
        $chart_title = __('Total Loan By Collection Type');
        $report_q = $dbs->query('SELECT coll_type_name, COUNT(loan_id) FROM loan AS l
            INNER JOIN item AS i ON l.item_code=i.item_code
            INNER JOIN mst_coll_type AS ct ON i.coll_type_id=ct.coll_type_id
            GROUP BY i.coll_type_id ORDER BY COUNT(loan_id) DESC');
        while ($data = $report_q->fetch_row()) {
            if($sysconf['chart']['mode'] == 'plot') {
                $plot_data[] = array($data[0], $data[1]);
                $data_colors[] = '#'.generateRandomColors();
            } else {
                $data_label[] = "'".preg_replace('/[^A-Za-z0-9]/', '', $data[0]).' ('.$data[1].")'";
                $plot_data[] = $data[1];
            }
        }
        break;
    default:
        $stat_query = $dbs->query('SELECT gmd_name, COUNT(biblio_id) AS total_titles
            FROM `biblio` AS b
            INNER JOIN mst_gmd AS gmd ON b.gmd_id = gmd.gmd_id
            GROUP BY b.gmd_id HAVING total_titles>0 ORDER BY COUNT(biblio_id) DESC');
        // set plot data and colors
        while ($data = $stat_query->fetch_row()) {
            if($sysconf['chart']['mode'] == 'plot') {
                $plot_data[] = array($data[0], $data[1]);
                $data_colors[] = '#'.generateRandomColors();
            } else {
                $data_label[] = "'".preg_replace('/[^A-Za-z0-9]/', '', $data[0]).' ('.$data[1].")'";
                $plot_data[] = $data[1];
            }
        }
        // dd($data_label, $plot_data);
        break;
}
/**
 * Charts data definition end
 */

// Create plot
if($sysconf['chart']['mode'] == 'plot') {
    if ($plot_data && $chart) {
        // set plot titles
        $plot->SetTitle($chart_title);

        // set data
        $plot->SetDataValues($plot_data);

        // set plot colors
        $plot->SetDataColors($data_colors);

        // set plot shading
        $plot->SetShading(20);

        // set plot type to pie
        $plot->SetPlotType('pie');
        $plot->SetDataType('text-data-single');

        // set legend
        foreach ($plot_data as $row) {
          $plot->SetLegend(implode(': ', $row));
        }

        //Draw it
        $plot->DrawGraph();
    }
} else {
    echo '<script src="'.JWB.'chartjs/chart-2.9.3.min.js"></script>
    <a class="print_btn" onclick="window.print()" href="#">Print</a>
    <div id="canvas-holder" style="text-align:center; width:70%; margin-left:auto; margin-right:auto;">
        <canvas id="chart-area" width="200" height="200" />
    </div>
    <script>

        var setTitle = "'.$chart_title.'";
        var setData = ['.implode(",",$plot_data).'];
        var setLabel = ['.implode(",",$data_label).'];
        var ctx = document.getElementById("chart-area").getContext("2d");
        window.chartColors = {
            red: "rgb(255, 99, 132)",
            orange: "rgb(255, 159, 64)",
            yellow: "rgb(255, 205, 86)",
            green: "rgb(75, 192, 192)",
            blue: "rgb(54, 162, 235)",
            ocean: "rgb(35, 91, 160)",
            leaf: "rgb(103, 148, 54)",
            tree: "rgb(233, 175, 163)",
            purple: "rgb(153, 102, 255)",
            pink: "rgb(153, 102, 255)",
            cyan: "rgb(112, 188, 193)",
            brown: "rgb(255, 147, 102)",
            lime: "rgb(165, 190, 0)",
            lime: "rgb(165, 190, 0)",
            grey: "rgb(201, 203, 207)"
        };
        var config = {
			type: "doughnut",
			data: {
				datasets: [{
					data: setData,
					backgroundColor: [
						window.chartColors.red,
						window.chartColors.orange,
						window.chartColors.yellow,
						window.chartColors.green,
						window.chartColors.blue,
						window.chartColors.ocean,
						window.chartColors.leaf,
						window.chartColors.tree,
						window.chartColors.purple,
						window.chartColors.pink,
						window.chartColors.cyan,
						window.chartColors.brown,
						window.chartColors.lime,
						window.chartColors.grey,
					],
					label: "Dataset 1"
				}],
				labels: setLabel
			},
			options: {
				responsive: true,
				title: {
                    display: true,
                    text: setTitle
				},
				legend: {
					position: "bottom",
				},
				animation: {
					animateScale: true,
					animateRotate: true
				},
                tooltips: false,
            }
		};

		window.onload = function() {
			var ctx = document.getElementById("chart-area").getContext("2d");
			window.myDoughnut = new Chart(ctx, config);
		};
    </script>';
}
exit();
