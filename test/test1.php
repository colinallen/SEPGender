<?php require_once('mysql_connect.php'); ?>


<script src="https:cdnjs.cloudflare.com/ajax/libs/chartist/0.11.0/chartist.min.js" integrity="sha256-UzffRueYhyZDw8Cj39UCnnggvBfa1fPcDQ0auvCbvCc=" crossorigin="anonymous"></script>
<script src="chartist-plugin-tooltip.js"></script>
<script src="chartist-plugin-accessibility.js"></script>
<script src="https:cdnjs.cloudflare.com/ajax/libs/chartist-plugin-legend/0.6.2/chartist-plugin-legend.min.js"></script>
<script src="chartist-plugin-axistitle.min.js"></script>
<link rel="stylesheet" href="style.css"/>
<script src="https:cdnjs.cloudflare.com/ajax/libs/chartist-plugin-legend/0.6.0/chartist-plugin-legend.min.js" integrity="sha256-pq1MeMEHeQ1gkHPgyWVm7hTnVBCj67OHZvUFLTlbtmM= sha384-BEZNT6JBK402FMVqptUsO9g5c26peq6Zn/EZvXm3qKEXlClpGaF41Daps3V/nX sha512-v4wed7LFapSM1ssko2OirKZjqzmM3eRpaqAZ24ypT3Sg29BiS54J832lhtZYY5jpE7HUmFFIRjVGzHx2HtHtAA==" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https:cdnjs.cloudflare.com/ajax/libs/chartist/0.11.0/chartist.min.css" integrity="sha256-Te9+aTaL9j0U5PzLhtAHt+SXlgIT8KT9VkyOZn68hak=" crossorigin="anonymous"/>
<script src="https:cdnjs.cloudflare.com/ajax/libs/d3/4.10.0/d3.min.js"></script>

<?php

$res = $dbh->query('
SELECT   t.raw,  t.year
FROM collections_cpy c
  JOIN member_of_collection m
    ON c.collection_id = m.collection_id
  JOIN citations_cpy t
    ON m.citation_id = t.citation_id
WHERE t.year > 1900
AND left(t.raw, 3) != \'–––\'
AND left(t.raw, 4) != \'----\'
AND left(t.raw, 2) != \'* \'
AND t.year  < 2017
AND length(t.year) = 4
ORDER BY t.year DESC
LIMIT 25000');

$res->execute();

echo "<pre>";
$analyse = $res->fetchAll(PDO::FETCH_ASSOC);


$tmp = [];
$arrayNew = [];

foreach ($analyse as $arg) {
    $first1 = explode(',', trim($arg['raw']));
    $tmp[$arg['year']][] = $first1[0];
}


$f = 0;
$m = 0;
$mf = 0;
$male = array();
$female = array();
$others = array();
$gender = array();


foreach ($tmp as $key => $value) {
    foreach ($value as $name) {
        $statement = $dbh->prepare('SELECT gender FROM cats_names WHERE names = :n LIMIT 1');
        $statement->execute(['n' => trim($name)]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        if ($user["gender"] == "F")
            $f++;
        else if ($user["gender"] == "M")
            $m++;
        else
            $mf++;
    }
    $gender[$key] = array('Male' => $m, 'Female' => $f, 'Others' => $mf);
    array_push($male, $m);
    array_push($female, $f);
    array_push($others, $mf);
    $f = 0;
    $m = 0;
    $mf = 0;
}

?>
<div class="ct-chart1"></div>
<script>
    var chart = new Chartist.Line('.ct-chart1',
      {
           labels: <?php echo json_encode(array_keys($tmp)) ?>,
            series: [
                {
                    "name": "Male",
                    "data": <?php echo json_encode($male) ?>
                },
                {
                    "name": "Female",
                    "data": <?php echo json_encode($female) ?>
                }
            ]
        },
        {
            plugins: [
                Chartist.plugins.legend({
                    className: 'crazyPink',
                    clickable: true
                }),
                Chartist.plugins.tooltip()
            ],
            fullWidth: true,
            chartPadding: {
                top: 20,
                right: 0,
                bottom: 0,
                left: 10
            },
            width: '100%',
            height: '30%',
            padding: '0 0 40px 0 !important',
            onlyInteger: true,
            seriesBarDistance: 15
        });

    chart.on('draw', function(data) {
        data.element.animate({
            y2: {
                dur: 500,
                from: data.y1,
                to: data.y2,
                easing: Chartist.Svg.Easing.easeOutQuint
            },
            opacity: {
                dur: 500,
                from: 0,
                to: 1,
                easing: Chartist.Svg.Easing.easeOutQuint
            }
        });
    });
</script>
