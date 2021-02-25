<?php

// Conf Redmine
$redmine['pass']='wEqB,*L).s=ytAuK';
$redmine['user']='redmine-ro';
$redmine['server']='10.7.161.59';
$redmine['database']='redmine';


// Connection Redmine;
$mysqli = new mysqli($redmine['server'], $redmine['user'], $redmine['pass'], $redmine['database']);
if ($mysqli->connect_errno) {
    printf("Echec de la connexion : %s\n", $mysqli->connect_error);
    exit();
}
$mysqli->set_charset("utf8");


// List all sprints
function getCustomSprints(&$mysqli) {

  $query = <<<EOT
select possible_values from custom_fields
where `custom_fields`.id=20
EOT;

  $result = $mysqli->query($query);
  $row = $result->fetch_assoc();
  $sprints = explode("\n- ", $row['possible_values']);
  return $sprints;
}

// Get bucket complexities for a given day
function getDayBuckets($sprint, $first_day, $filter = null) {
  if (!is_null($filter)) {
    $filter_us = "join custom_values c3 on (c3.customized_id = t2.issue_id  and c3.custom_field_id = 24 AND c3.value = '$filter')";
  } else {
    $filter_us = "";
  }
  $query = <<<EOT
  select sum(complexity) total,
  status_id from (
  SELECT
        (select c1.value from custom_values c1  where  c1.customized_id = journalized_id and c1.custom_field_id = 28) as complexity,
        fulllist.journalized_id as issue_id,
        status_id,
        old_status_id,
        issue_date, created_on,
        num
  FROM
   (SELECT issue_id,
           status_id,
           old_status_id,
           created_on,
           @row_number:=CASE
             WHEN @issue_no = journalized_id THEN
               @row_number - 1
             ELSE
               0
             END AS num,
            @issue_no:=journalized_id AS journalized_id,
            @issuedate:=date_format(created_on, '%Y-%m-%d') AS issue_date

   from
     (SELECT
           @issue_no AS issue_id,
           jd.value AS status_id,
           jd.old_value AS old_status_id,
           j.created_on,
           @row_number:=CASE
             WHEN @issue_no = j.journalized_id
             AND @issuedate = date_format(j.created_on, '%Y-%m-%d') THEN
               @row_number - 1
             ELSE
               0
             END AS num,
            @issue_no:=j.journalized_id AS journalized_id,
            @issuedate:=date_format(j.created_on, '%Y-%m-%d') AS issue_date
    FROM journals j,
         journal_details jd,
         issues i,
         custom_values c2
    WHERE j.journalized_type='Issue'
      AND jd.prop_key='status_id'
      AND jd.journal_id=j.id
      AND EXISTS
        (SELECT 1
         FROM journal_details jd2
         WHERE jd2.prop_key='status_id'
           AND jd2.journal_id=j.id )
      AND c2.custom_field_id = 20
      AND c2.customized_id = i.id
      AND c2.value like "$sprint%"
      AND i.id = j.journalized_id
       and j.created_on < '$first_day 23:59:59'
    ORDER BY j.journalized_id, date_format(j.created_on, '%Y-%m-%d %H:%i:%s') DESC
    ) t2 $filter_us
    union
    (
     SELECT i.id as issue_id,
            i.status_id as status_id,
            i.status_id as old_status_id,
           i.created_on,
           '0' as num,
           i.id as journalized_id,
           '$first_day' AS issue_date
    FROM
         issues i,
         custom_values c2
    WHERE not EXISTS
        (SELECT 1
         FROM journals j, journal_details jd2
         WHERE jd2.prop_key='status_id'
           AND jd2.journal_id=j.id
           AND i.id = j.journalized_id)
      AND c2.custom_field_id = 20
      AND c2.customized_id = i.id
      AND c2.value like "$sprint%"

    )
    )
    as fulllist
  WHERE
    num = 0
  -- us qui n''ont pas de journalization en status value =1 et comme date la date de début de sprint

  ORDER BY status_id
  ) T2
  group by status_id
EOT;
  if (isset($_GET['debug'])) {
    echo  "<!-- getDayBuckets('$sprint', '$first_day', '$filter') \n\n $query \n -->\n\n\n";
  }
  return $query;
}

// Parse sprint to get dates
function getSprintDates($sprint_str) {
  $dates    = explode("-", $sprint_str);
  $currYear = date('Y');
  $prevYear = $currYear-1;
  $ydebut   = (substr($dates[1],2,2)>'11')? $prevYear : $currYear ;
  $yfin     = ($ydebut==$prevYear )?$prevYear:$currYear;
  $debut    = substr($dates[1],2,2).'-'.substr($dates[1],0,2);
  $fin      = substr($dates[2],2,2).'-'.substr($dates[2],0,2);
  $beginstr = $ydebut.'-'.$debut.' 23:59:59';
  $begin    = strtotime( $ydebut.'-'.$debut );
  $end      = strtotime( $yfin.'-'.$fin   );

  return [
            "begin" => $begin,
            "end"   => $end
          ];
}

// Get the total complexity of the sprint
function getSprintComplexityQuery($sprint, $filter = null) {
  if (!is_null($filter)) {
    $filter_us = "where exists (select 1 from custom_values c3 where c3.customized_id = T2.issue_id  and c3.custom_field_id = 24 AND c3.value = '$filter')";
  } else {
    $filter_us = "";
  }

  $query = <<<EOT
  SELECT
        (select c1.value from custom_values c1  where  c1.customized_id = journalized_id and c1.custom_field_id = 28) as complexity,
        fulllist.journalized_id as issue_id,
        status_id,
        old_status_id,
        issue_date
  FROM
     (
       select issue_id, journalized_id, status_id, old_status_id, created_on, issue_date  from
       (
     select issue_id, journalized_id, status_id, old_status_id, created_on, issue_date from
     (SELECT i.id AS issue_id,
              i.id AS journalized_id,
             jd.value AS status_id,
             jd.old_value AS old_status_id,
             j.created_on,
             date_format(j.created_on, '%Y-%m-%d') as issue_date
    FROM journals j,
         journal_details jd,
         issues i,
         custom_values c2
    WHERE j.journalized_type='Issue'
      AND jd.prop_key='status_id'
      AND jd.journal_id=j.id
      AND EXISTS
        (SELECT 1
         FROM journal_details jd2
         WHERE jd2.prop_key='status_id'
           AND jd2.journal_id=j.id )
      AND c2.custom_field_id = 20
      AND c2.customized_id = i.id
      AND c2.value like "$sprint%"
      AND i.id = j.journalized_id
    ORDER BY date_format(j.created_on, '%Y-%m-%d') ASC, j.journalized_id ASC, j.created_on DESC
    ) as T2
      $filter_us
    ) as t3
    UNION
  (SELECT i.id as issue_id,
          i.id as journalized_id,
         '1' as status_id,
         '1' as old_status_id,
         i.created_on,
         date_format(i.created_on, '%Y-%m-%d') as issue_date
         FROM
       issues i,
       custom_values c2
  WHERE not EXISTS
      (SELECT 1
       FROM journals j, journal_details jd2
       WHERE jd2.prop_key='status_id'
         AND jd2.journal_id=j.id
         AND i.id = j.journalized_id)
    AND c2.custom_field_id = 20
    AND c2.customized_id = i.id
    AND c2.value like "$sprint%"
  )
  )
    as fulllist
  ORDER BY issue_date;

EOT;

  if (isset($_GET['debug'])) {
    echo  "<!-- getSprintComplexityQuery('$sprint', '$filter') \n\n $query \n -->\n\n\n";
  }
  return $query;
}

// Intiate all series
function hydrateSeries($mysqli, $sprint, $filter = null) {
	$query  = getSprintComplexityQuery($sprint, $filter);
  $result = $mysqli->query($query);

  while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
  	$labels[$row['issue_date']] = date('w', strtotime($row['issue_date']));
  	$stories[$row['issue_id']] = $row['complexity'];
    $status[$row['issue_date']]['entries'][$row['status_id']] = ($status[$row['issue_date']][$row['status_id']] ?? 0) + $row['complexity'];
    $status[$row['issue_date']]['exits'][$row['old_status_id']] = ($status[$row['issue_date']][$row['old_status_id']] ?? 0) + $row['complexity'];
  }

	return ['labels'=>$labels, 'stories'=>$stories, 'status'=>$status];
}

function findLastSprintInList($sprint_list, $project) {
  foreach ($sprint_list as $sprint_name) {
    if (strpos($sprint_name, $project) !== false) {
      $dates = getSprintDates($sprint_name);
      if ($dates['begin'] <= time() && strtotime('-1 day') <= $dates['end']) {
        return $sprint_name;
      }
    }
  }
  return false;
}

function rotatePrj() {
  switch ($_GET['p']) {
    case 'FOV':
    case 'F':
      $meta = '<meta http-equiv="Refresh" content="120;">';
      break;
    default:
      $meta = '';
      break;
  }

  return $meta;
}

// Setting sprint
$sprint_list = getCustomSprints($mysqli);

if (isset($_GET['s'])) {
  $sprint = $_GET['s'];
} elseif (isset($_GET['p'])) {
  $sprint = findLastSprintInList($sprint_list, $_GET['p']);
}
$sprint = $sprint ?? "FOV-1802-0103-S29";

// Specific FOV case (filter only US)
if (strpos($sprint, "FOV") !== false) {
  $filter = "Dette/Tâche technique";
} else {
  $filter = null;
}

// Fetching data
$data = hydrateSeries($mysqli, $sprint, $filter);

// Map Redmine status ids to agile buckets
$buckets = new stdClass;
$buckets->{"TODO"}   = [1, 36, 37];
$buckets->{"WIP"}    = [38, 39, 43, 44, 45, 46];
$buckets->{"TEST"}   = [40];
$buckets->{"DONE"}   = [42, 5];
$buckets->{"CUMULATIVE"} = array_merge($buckets->{"TODO"},$buckets->{"WIP"},$buckets->{"TEST"},$buckets->{"DONE"});
$burndown = [];




// WOUP-WOUP
// Iterate throught days to get values

$s = getSprintDates($sprint);
$j = array_count_values($data['labels']);
$j_ouvres = 0;
$ccl = [];

for ( $i = $s["begin"]; $i <= $s["end"] + ((date('w', $s["end"])==5)? 86400 : 0); $i = $i + 86400 ) {
  $datestr = date( 'Y-m-d', $i);

  if (date('w', $i) != 6  && date('w', $i) != 0) {
    if ($i <= time()) {
      // Fill buckets
      $query  = getDayBuckets($sprint, $datestr, $filter);
      $result = $mysqli->query($query);
      $rows = [];
      while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $rows[] = $row;

        foreach ($buckets as $bucket_name => $bucket_ids)  {

          if (in_array((int)$row['status_id'], $bucket_ids)) {
            if (isset($graph[$bucket_name][$j_ouvres])) {
              $graph[$bucket_name][$j_ouvres] += (int)$row['total'];
            } else {
              $graph[$bucket_name][$j_ouvres] = (int)$row['total'];
            }
          }

        }

      }
        foreach ($buckets as $bucket_name => $bucket_ids)  {
          if (empty($graph[$bucket_name][$j_ouvres])) $graph[$bucket_name][$j_ouvres] = 0;
        }
        $burndown[] = array_sum($data['stories']) - end($graph["DONE"]);
      }

    // Labels
    $labels[] = date('d/m/Y', $i);
    $j_ouvres++;
  }

}

// Modèle
for ($i = 0; $i < $j_ouvres; $i++) {
  $line[] = array_sum($data['stories']) - $i * (array_sum($data['stories']) / ($j_ouvres - 1));
}


$sel_ = "<form method='GET'>\n";
if (isset($_GET['p'])) $sel_ .= "<input type='hidden' name='p' value='" . $_GET['p'] . "'>\n";
$sel_ .= "<select name='s' onchange='this.form.submit()'>\n";
foreach ($sprint_list as $sel_sprint) {
  $sel_sprint = trim($sel_sprint);
  if (isset($_GET['p']) && (strpos($sel_sprint, $_GET['p']) === false)) continue;

  $sel_attr = "";
  if ($sel_sprint == $sprint) $sel_attr = "selected";
  $sel_ .= "<option value='$sel_sprint' $sel_attr>$sel_sprint</option>\n";
}
$sel_ .= "</select>\n</form>\n";

?>

<!doctype html>
<html>
	<head>
    <!-- je vais vomir -->
    <?php echo rotatePrj() . "\n" ?>
    <!-- ... -->
		<title>CUMULATIVE FLOW CHART</title>
    <script src="../Chart.js?<?php echo date("l"); ?>"></script>
<style>
  * {
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    overflow: hidden;
  }
  body {
    font: 12px arial, sans-serif;
  }
  form, form select {
    display: inline;
    padding-right: 10px;
    padding: 0.25em;
    border: 0;
    border-radius: 0;
    background-color: #fff !important;
  }
  #legend {
    display:block;
    float:left;
    border:2px solid ghostwhite;
    clear:both;
    width:100%;
  }
  ul.line-legend{
    display:inline;
  }
  ul.line-legend li{
    display:inline;
    padding-right:10px;
    float:right;
  }
  ul.line-legend li span {
    width: 20px;
    display:inline-block;
    margin-right:10px;
  }
  ul.line-legend {
    list-style-type: none;
    padding-left:5px;
    padding-right:5px;
  }
  #chart_title, #chart_title * {
    text-align:center;
    font-size: 32px;
  }

  pony {
    display: block;
    height: 300px;
    width: 300px;
    position: absolute;
    bottom: 0;
    right: calc(100vw + 10px);
    animation-name: walk;
    animation-timing-function: linear;
    animation-duration: 37s;
    animation-delay: 2s;
    animation-iteration-count: infinite;
    background-image: url('pony3.gif');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: bottom;
  }

  @keyframes walk {
    from {right: calc(0vw - 310px);}
    to {right: calc(100vw + 10px);}
  }
</style>
	</head>
	<body>
		<div id="chart_title">
		  Cumulative Flow Chart <?php echo $sel_ ?>
		</div><br><br>
		<div style="width:100%; max-width:1900px; float:left;">
			<canvas id="canvas" width="100%"></canvas>
		</div>
	<script>
		var rose = 'rgba(217, 75, 151, 1)';
		var rouge = 'rgba(234, 62, 55, 1)';
		var orange = 'rgba(246, 143, 31, 1)';
		var jaune = 'rgba(246, 213, 9, 1)';
		var vert = 'rgba(168, 203, 56, 1)';
		var bleu  = 'rgba(32, 154, 207, 1)';
		var violet  = 'rgba(170, 82, 160, 1)';
		var noir  = 'rgba(65, 65, 66, 1)';
    var options = {
      type: 'line',
      data: {
        labels: <?php echo json_encode($labels) ?>,
        datasets: [
          {
    				label: 'TODO',
    				data: <?php echo json_encode(array_values($graph["TODO"])) ?>,
            yAxisID: 'stacked_axis',
            pointRadius : 0,
    				borderWidth: 1,
            borderColor: jaune,
            backgroundColor: jaune,
            lineTension: 0
    			},
          {
    	      label: 'WIP',
    	      data: <?php echo json_encode(array_values($graph["WIP"])) ?>,
            borderColor: rose,
            backgroundColor: rose,
            yAxisID: 'stacked_axis',
            pointRadius : 0,
            borderWidth: 1,
            lineTension: 0
        	},
          {
    	      label: 'TEST',
    	      data: <?php echo json_encode(array_values($graph["TEST"])) ?>,
            borderColor: vert,
            backgroundColor: vert,
            yAxisID: 'stacked_axis',
            pointRadius : 0,
            borderWidth: 1,
            lineTension: 0
        	},
          {
    	      label: 'DONE',
    	      data: <?php echo json_encode(array_values($graph["DONE"])) ?>,
            borderColor: bleu,
            backgroundColor: bleu,
            yAxisID: 'stacked_axis',
            pointRadius : 0,
            borderWidth: 1,
            lineTension: 0
        	}
    		]
      },
      options: {
        animation: false,
        elements: {
          line: {
            lineTension: 0,
            fill: true,
          }
        },
        plugins: {
            filler: {
                propagate: true
            }
        },
        legend: {
            display: true,
            labels: {
                fontSize: 20,
                filter: function(item, chart) {
                    // Logic to remove a particular legend item goes here
                    return !item.text.includes('Ideal burndown');
                }
            }
        },
        layout: {
          padding: {
            top: 5
          }
        },
        scales: {
          yAxes:
            [{
              id: "normal_axis",
              ticks: {
                min: 0,
                max: <?php echo max($graph["CUMULATIVE"]) ?>,
                fontSize: 18
              },
              stacked: false
            }, {
              id: "stacked_axis",
              ticks: {
                min: 0,
                max: <?php echo max($graph["CUMULATIVE"]) ?>,
                fontSize: 18
              },
              stacked: true,
              display: false
            }],
          xAxes:
            [{
              ticks: {
                fontSize: 18,
                autoSkip: false,
                maxRotation: 50,
                minRotation: 50
              }
            }]
        }
      }
    }

    var ctx = document.getElementById('canvas').getContext('2d');
    new Chart(ctx, options);
	</script>

	</body>
</html>
