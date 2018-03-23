<?php require_once( 'mysql_connect.php' ); ?>

<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartist/0.11.0/chartist.min.js" integrity="sha256-UzffRueYhyZDw8Cj39UCnnggvBfa1fPcDQ0auvCbvCc=" crossorigin="anonymous"></script>
<script src="chartist-plugin-tooltip.js"></script>
<script src="chartist-plugin-accessibility.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartist-plugin-legend/0.6.2/chartist-plugin-legend.min.js"></script>
<script src="chartist-plugin-axistitle.min.js"></script>
<link rel="stylesheet" href="style.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartist-plugin-legend/0.6.0/chartist-plugin-legend.min.js" integrity="sha256-pq1MeMEHeQ1gkHPgyWVm7hTnVBCj67OHZvUFLTlbtmM= sha384-BEZNT6JBK402FMVqptUsO9g5c26peq6Zn/EZvXm3qKEXlClpGaF//41Daps3V/nX sha512-v4wed7LFapSM1ssko2OirKZjqzmM3eRpaqAZ24ypT3Sg29BiS54J832lhtZYY5jpE7HUmFFIRjVGzHx2HtHtAA=="
        crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chartist/0.11.0/chartist.min.css" integrity="sha256-Te9+aTaL9j0U5PzLhtAHt+SXlgIT8KT9VkyOZn68hak=" crossorigin="anonymous"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/4.10.0/d3.min.js"></script>

<?php

$res = $dbh->query('
SELECT c.level2_name, t.raw
  FROM collections_cpy c USE INDEX (collections_cpy_collection_id_index)
  JOIN member_of_collection m
    ON c.collection_id = m.collection_id
  JOIN citations t
    ON m.citation_id = t.citation_id
  JOIN  (
          SELECT c.collection_id, count(*)
            FROM collections_cpy c USE INDEX (collections_cpy_collection_id_index)
            JOIN member_of_collection m
              ON c.collection_id = m.collection_id
            JOIN citations t
              ON t.citation_id = m.citation_id
          WHERE length(trim(t.raw)) > 0
                AND RIGHT(c.collection_name, length(c.collection_name) - 4) IN (SELECT substring_index(c.collection_name,\'SEP \',-1) FROM collections_cpy USE INDEX (collections_cpy_collection_id_index))
                AND left(t.raw, 3) != \'–––\'
                AND left(t.raw, 4) != \'----\'
                AND left(t.raw, 2) != \'* \'
                AND c.level2_name IS NOT NULL 
          GROUP BY c.collection_id
          ORDER BY count(*) DESC
          LIMIT 100
        ) cc
    ON m.collection_id = cc.collection_id');

$res->execute();

$analyse = $res->fetchAll(PDO::FETCH_ASSOC);


$tmp = [];

foreach ($analyse as $arg)
{
    $first1                       = preg_split('/[,.(12]/', trim($arg['raw']));
    $second1                      = preg_split('/and|\s/', trim($first1[1]));
    $tmp[ $arg['level2_name'] ][] = $second1[0];
}


$f      = 0;
$m      = 0;
$mf     = 0;
$male   = [];
$female = [];
$others = [];

foreach ($tmp as $key => $value)
{
    foreach ($value as $name)
    {
        $statement = $dbh->prepare('SELECT gender FROM cats_names WHERE names = :n LIMIT 1');
        $statement->execute([ 'n' => trim($name) ]);
        $user = $statement->fetch();

        if ($user[0] == "F")
            $f++;
        else if ($user[0] == "M")
            $m++;
        else
            $mf++;
    }
    array_push($male, $m);
    array_push($female, $f);
    array_push($others, $mf);
    $f  = 0;
    $m  = 0;
    $mf = 0;
}

?>
<!-- chart div -->
<div class="ct-chart"></div>

<script>
    var chart = new Chartist.Bar('.ct-chart',
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

    chart.on('draw', function (data) {
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

<?php

$cats = array(); 
$cats = array_keys($tmp); ?>
<div style="margin:20px 0 0 0">
    <h4>View Publications by Women by Category</h4>
Select Level 2 Category:
<select title="" id="level2">
    <option value="select" selected>Select Level 2 Category</option>
    <?php
    foreach($cats as $value)
    { ?>
        <option value="<?php echo $value; ?>"><?php echo $value; ?></option>
    <?php }
    ?>
</select>
</div>

<div id="hide_level1" style="display: none">
    Select Level 1 Category
    <select id="level1" title="">
    </select>
</div>

<div id="resultDiv" style="margin: 20px 0 0 0"></div>


<script>
    //Detect change in dropdown selection for level 2 categories, then
    //Get the level 1 categories and append them inside a dropdown
    $(function () {
        $("#level2").change(function () {
            var sel = $("#level1");
            $("#resultDiv").empty();
            $("#hide_level1").css('display', 'none');
            var level2Value = this.value;
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: 'http://sepgen.cogs.indiana.edu/test/ajax.php',
                data: { level2Click: "yes", level2Value: level2Value },
                success: function (result) {
                    $("#hide_level1").css('display', 'block');
                    sel.empty();
                    sel.append('<option value="select" selected>Select Level 1 Category</option>');
                    for (var i = 0; i < result.length; i++) {
                        sel.append('<option value="' + result[i].level1_name + '">' + result[i].level1_name + '</option>');
                    }
                }
            })
        });
        
        // Get publications for appropriate level 1 and level 2 combimations.
        $("#level1").change(function () {
            var level1Value = this.value;
            var resultDiv =  $("#resultDiv");
            $.ajax({
                type: 'POST',
                dataType: 'html',
                url: 'http://sepgen.cogs.indiana.edu/test/ajax.php',
                data: { level1Click: "yes", level1Value: level1Value },
                success: function (result) {
                    resultDiv.html(result);
                }
            })
        })
    });
</script>
