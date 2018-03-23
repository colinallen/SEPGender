<?php require_once( 'mysql_connect.php' );

ob_start();

//Get all level 1 categories for specific level 2 category
if(isset($_POST["level2Click"]))
{
    $res = $dbh->prepare('SELECT  c.level1_name FROM collections_cpy c WHERE c.level2_name = :level2 GROUP BY c.level1_name');
    $res->bindParam(':level2', $_POST["level2Value"]);
    $res->execute();
    $level1_names = $res->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($level1_names);
}

//Get all publications for a level 1 category
if(isset($_POST["level1Click"]))
{
    $res = $dbh->prepare('
SELECT t.raw, c.level1_name
  FROM collections_cpy c USE INDEX (collections_cpy_collection_id_index)
  JOIN member_of_collection m
    ON c.collection_id = m.collection_id
  JOIN citations t
    ON m.citation_id = t.citation_id
  JOIN  (
          SELECT c.collection_id, count(*)
          FROM   collections_cpy c USE INDEX (collections_cpy_collection_id_index)
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
    ON m.collection_id = cc.collection_id WHERE c.level1_name = :level1');
    $res->bindParam(":level1",$_POST["level1Value"]);
    $res->execute();
    $analyse = $res->fetchAll(PDO::FETCH_ASSOC);

    $female = [];

    foreach ($analyse as $arg)
    {
        $first   = preg_split('/[,.(12]/', trim($arg['raw'])); 
        $second  = preg_split('/and|\s/', trim($first[1]));
        $statement = $dbh->prepare('SELECT gender, names FROM cats_names WHERE names = :n LIMIT 1');
        $statement->execute([ 'n' => trim($second[0]) ]);
        $user = $statement->fetch();

        if ($user["gender"] == "F")
        {
            array_push($female, $arg["raw"]);
        }
    }
    if(count($female) > 0)
    {
        $res = "";
        $i = 1;
        foreach($female as $fem)
        {
            $res .= $i.".  ".$fem . "<br>";
            $i++;
        }
        echo $res;
    }
    else{
        echo "No Data";
    }
}
