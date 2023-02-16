<?php
include_once('top.php');
include_once('ga4api.php');

// start preparing GAnalytics data
$ga = new GAnalytics4();

$from = $_GET['startdate'] ? $_GET['startdate'] : date('Y-m-d', strtotime('-1 month'));
$to = $_GET['enddate'] ? $_GET['enddate'] : date('Y-m-d');
$response = $ga->run($_GET['report'], $from, $to, $_GET['u']);  // response of one report for each page, or multiple reports in 'overview' page

// for single report
$response_html = '';
$response_array = '';

// for overview page
$response_array_cur = '';
$response_array_cun = '';
$response_array_visit = '';
if (isset($_GET['report']) && ($_GET['report']!='') && ($_GET['report']!='overview-recently')){
	$report_name = $_GET['report'];
	$report_title = $ga->getAllReportAttributes()[$report_name]['title'];
	$response_html = $response['html'];
	$response_array = $response['array'];
}else{
	$report_name = 'overview-recently';
	$report_title = 'Recently visit';
    $response_array = $response['recently'];
    $response_array_cur = $response['currently'];
    $response_array_cun = $response['country'];
    $response_array_visit = $response['top-visit'];
}




?>
<!DOCTYPE HTML>
<html>
<head>
<?php include_once('../meta.php'); ?>
 <script src="<?=CONFIG_TOOLKIT_PATH.'chart-4.1.2/chart.umd.js'?>"></script>
 <script type="text/javascript" src="jquery.table2excel.js"></script>

</head>
<body id="analytics">
	<div id="container">
    	<?php include_once('../header.php'); ?>        
		<?php include_once('../nav.php'); ?>
        <?php include_once('../toolbar.php'); ?>
        <?php include_once('aside.php'); ?>
        <div id="content" class="view">
            <form id="filter">
            	<?=$Lang->_('Range : ')?>
    			<?=$Lang->_('from')?> <input type="text" name="startdate" class="date-picker" value="<?=$from?>" />
    			<?=$Lang->_('to')?> <input type="text" name="enddate" class="date-picker" value="<?=$to?>" />&nbsp;
				<?=$Lang->_('Page')?> : <input type="text" name="u" value="<?=$_GET['u']?>" placeholder="Ex: /page/1/" />
				<input type="hidden" name="report" value="<?=$_GET['report']?>" />
    			<button type="submit" href="#" class="button" id="view-button"><?=$Lang->_('View')?></button>
                <button type="button" href="#" class="button" id="export-button"><?=$Lang->_('Export')?></button>
            </form>

			<h2><?=($report_name=='overview-recently')? '': $Lang->_($report_title)?></h2>
			<fieldset class="chart">
				<legend><?=($report_name=='overview-recently')? $Lang->_('Recently visit') : $Lang->_('View As Chart')?></legend>
				 <div class="chart-wrapper">
					 <div class="chart-inner"><canvas id="myChart"></canvas></div>
				 </div>
			</fieldset>
			<fieldset class="table">
				<legend><?=$Lang->_('View As Table')?></legend>
				<div id="ga-box">
					<table id="toExcel" data-title="<?=$report_name?>">
                        <?=$response_html?>
					</table>
				</div>
			</fieldset>
			<div id="chart-group" class="<?=($report_name=='overview-recently')? 'show':'' ?>">
				<div class="inner column-3">
					<fieldset id="chart-online" class="col-item">
						<legend><?=$Lang->_('Currently visit')?></legend>
						<div>&nbsp;</div>
						<div class="count"><?= isset($response_array_cur['metrics'][0][0])? ($response_array_cur['metrics'][0][0]): 0 ?></div>
						<div class="note"><?= isset($response_array_cur['dimensions'][0][0])? ($response_array_cur['dimensions'][0][0]).' '.$Lang->_('minutes ago'): '' ?></div>
					</fieldset>
					<fieldset id="chart-country" class="col-item">
						<legend><?=$Lang->_('Country')?></legend>
						<ul class="inline-list">
							<?php
                            $di = array_slice($response_array_cun['dimensions'][0],0,6);
                            $me = array_slice($response_array_cun['metrics'][0],0,6);
							foreach($di as $k=>$d){
								$html="<li>";
								$html.= "<div class='col'>" .$d. "</div>";
								$html.= "<div class='col'>" .$me[$k]. "</div>";
								$html.="</li>";
                                echo $html;
							}
							?>
						</ul>
					</fieldset>
					<fieldset id="chart-page" class="col-item">
						<legend><?=$Lang->_('Top visit page')?></legend>
						<ul class="inline-list">
                            <?php
                            $di = array_slice($response_array_visit['dimensions'][1],0,6);
                            $me = array_slice($response_array_visit['metrics'][0],0,6);
                            foreach($di as $k=>$d){
                                $html="<li>";
                                $html.= "<div class='col'>" .$d. "</div>";
                                $html.= "<div class='col'>" .$me[$k]. "</div>";
                                $html.="</li>";
                                echo $html;
                            }
                            ?>
						</ul>
					</fieldset>
				</div>
			</div>
        </div>
        <?php include_once('../footer.php'); ?>
    </div>
</body>
</html>




<script type="text/javascript">
$(document).ready(function(){


    /** Generating Charts **/
    var data_array = <?= json_encode($response_array) ?>;  // assign php array to js array
	var dimensions = data_array['dimensions'];
	var metrics = data_array['metrics'];
	var report_name = "<?php echo $report_name; ?>"; // assign php variable to js array

	var remap = (value, x1, y1, x2, y2) => (value - x1) * (y2 - x2) / (y1 - x1) + x2;  // remap function

    var ctx = $('#myChart');
	switch (report_name) {

		case 'overview-recently': {
		    $('fieldset.table').hide();
            var charttt = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dimensions[0],
                    datasets: [
                        {
                            label: 'Counts',
                            data: metrics[0]
                        }
                    ]
                },
                options: {
                    // responsive:true,
                    maintainAspectRatio:true,
                    plugins:{
                        legend: {
                            position: 'bottom',
                        },

                    }
				},
                plugins: []
            });
            // charttt.canvas.parentNode.style.height = '150px';
            // charttt.canvas.parentNode.style.width = '300px';
            break;
        }
        case 'currently-visit':{
            $('fieldset.chart').hide();
		}
		case 'recently-visit': {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dimensions[0],
                    datasets: [
                        {
                            label: 'Counts',
                            data: metrics[0]
                        }
                    ]
                },
                options: {
                    responsive:true,
                    maintainAspectRatio:true,
                    plugins:{
                        legend: {
                            position: 'bottom',
                        },

                    }
				},
                plugins: []
            });
            break;
        }
		case 'visiting-time': {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dimensions[0],
                    datasets: [
                        {
                            label: 'Counts',
                            data: metrics[0]
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {beginAtZero: true,

						}
                    },
					plugins:{
                        legend: {
                            position: 'bottom',
                        },

					}

                },
                plugins: []
            });
            break;
        }
		case 'top-visit-page': {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dimensions[0].slice(0, 10),
                    datasets: [
                        {
                            label: 'Counts',
                            data: metrics[0].slice(0, 10)
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',  // horizontal chart
                    scales: {
                        y: {beginAtZero: true}
                    },
                    responsive: true,
                    plugins: {
                        /*title: {
                            display: false,
                            text: 'Top 10 of Visiting Page',
							padding:{
                                top:0,
								bottom:15
							}
                        },*/
						legend:{
                            position:'bottom'
						}

                    }

                },
                plugins: []
            });
            break;
        }
		case 'landing-page': {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dimensions[0].slice(0, 10),
                    datasets: [
                        {
                            label: 'Counts',
                            data: metrics[0].slice(0, 10)
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',  // horizontal chart
                    scales: {
                        y: {beginAtZero: true}
                    },
                    responsive: true,
                    plugins: {
                        /*title: {
                            display: false,
                            text: 'Top 10 of Landing Page',
                            padding:{
                                top:0,
                                bottom:15
                            }
                        },*/
                        legend:{
                            position:'bottom'
                        }
                    }

                },
                plugins: []
            });
            break;
        }
		case 'guest': {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dimensions[0],
                    datasets: [
                        {
                            label: 'Total Users',
                            data: metrics[0]
                        },
                        {
                            label: 'New Users',
                            data: metrics[1]
                        }
                    ]
                },
                options: {
                    plugins:{
                        legend: {
                            position: 'bottom',
                        },

                    }
				},
                plugins: []
            });
            break;
        }
		case 'source-link': {

            // remap array number to a new range to make beautiful chart
            var in_max = Math.max.apply(Math,metrics[2]);
            var in_min = Math.min.apply(Math,metrics[2]);
            var out_max = Math.max.apply(Math,metrics[0]);
            var out_min = Math.min.apply(Math,metrics[0]);
            var newArr = metrics[2].map( (val)=> remap(val, in_min,in_max,out_min,out_max) );

            new Chart(ctx, {
                data: {
                    labels: dimensions[0].slice(0, 10),
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Session Count',
                            data: metrics[0]
                        },
                        {
                            type: 'line',
                            label: 'Avg Session Duration',
                            data: newArr
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',  // horizontal chart
                    scales: {
                        y: {beginAtZero: true}
                    },
                    responsive: true,
                    plugins: {
                        /*title: {
                            display: false,
                            text: 'Top 10 Of Source URL'
                        },*/
                        legend:{
                            display:true,
                            position: 'bottom',
                        }

                    }

                },
                plugins: []
            });
            break;
        }
		case 'country': {

		    // remap array number to a new range to make beautiful chart
            var in_max = Math.max.apply(Math,metrics[2]);
            var in_min = Math.min.apply(Math,metrics[2]);
            var out_max = Math.max.apply(Math,metrics[0]);
            var out_min = Math.min.apply(Math,metrics[0]);
			var newArr = metrics[2].map( (val)=> remap(val, in_min,in_max,out_min,out_max) );
            new Chart(ctx, {

                data: {
                    labels: dimensions[0],
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Session Count',
                            data: metrics[0]
                        },
                        {
                            type: 'line',
                            label: 'Avg Session Duration',
                            data: newArr
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',  // horizontal chart
                    scales: {
                        y: {beginAtZero: false}
                    },
                    responsive: true,
                    plugins: {
                        legend:{

                            position: 'bottom',
                        },
                        /*title: {
                            display: true,
                            text: 'Top 10 Of Visiting Country',

                        }*/
                    }

                },
                plugins: []
            });

            break;
        }
		case 'language': {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: dimensions[0],
                    datasets: [{
                        label: 'counts',
                        data: metrics[0],
                        // backgroundColor: undefined,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Languages of Visitor Usage'
                        }
                    }
                }
            });

            break;
        }
		case 'browser': {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: dimensions[0],
                    datasets: [{
                        label: 'counts',
                        data: metrics[0],
                        // backgroundColor: colors,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend:{
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Browsers of Visitor Usage'
                        }
					}
                }

            });
            break;
        }
		case 'operating-system': {
		    //aggregate browser name and count
		    $di_name = {};
			$(dimensions[0]).each(function (idx, val) {
                $di_name[val] = (val in $di_name)? $di_name[val]+metrics[0][idx] : metrics[0][idx] ;
            });

			new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys($di_name),
                    datasets: [{
                        label: 'counts',
                        data: Object.values($di_name),
                        // backgroundColor: colors,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend:{

                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'OS of Visitor Usage'
                        }
					}
                }

            });
            break;
        }
		case 'screen-resolution': {
            // $('fieldset.chart').hide();
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dimensions[0].slice(0, 10),
                    datasets: [
                        {
                            label: 'Counts',
                            data: metrics[0].slice(0, 10)
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',  // horizontal chart
                    scales: {
                        y: {beginAtZero: true}
                    },
                    responsive: true,
                    plugins: {
                        /*title: {
                            display: false,
                            text: 'Top 10 of Visiting Page',
							padding:{
                                top:0,
								bottom:15
							}
                        },*/
                        legend:{
                            position:'bottom'
                        }

                    }

                },
                plugins: []
            });
            break;
        }


	}

	//RWD
	/** when click #export-button, output excel **/
    $('#filter #export-button').click(function(){
        const date = new Date().toJSON().slice(0, 10);
        $("#toExcel").table2excel({
            // exclude CSS class
            exclude:".noExl",
            sheetName:report_name,  // 改成report name
            filename:date+'_'+report_name,//do not include extension   改成日期_title or report name
            fileext:".xls", // file extension
            // preserveColors:true,
            // exclude_img:true,
            // exclude_links:true,
            // exclude_inputs:true
        });
    });




});
</script>