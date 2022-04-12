<?php
//API - TV CHANNELS
$rtlKlub = "tvchannel-5";
$channelTV2 = "tvchannel-3";
$channelViasat3 = "tvchannel-21";
$channelDuna = "tvchannel-6";
$channelDunaWorld = "tvchannel-103";

$dbArray = array("host" => "localhost", "user" => "root", "password" => "", "dbname" => "portprogramme");

//FUNCTIONS BEGIN --->

//DOWNLOADED TABLE LOAD AS OPTION IN SELECT
function loadDownloadedTableDate($dbArray)
{
    $conn = mysqli_connect($dbArray["host"], $dbArray['user'], $dbArray['password'], $dbArray['dbname']);
    if (mysqli_connect_errno()) {
        echo "Hiba a  MySQL csatlakozás során: " . mysqli_connect_error();
        exit();
    }

    $sql = "SELECT dateLoad FROM  programsdownload";

    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
?>
        <option value="<?php print($row["dateLoad"]) ?>"><?php print($row["dateLoad"]) ?></option>
    <?php
    }
    mysqli_free_result($result);

    mysqli_close($conn);
};

//CHECK FROM DATABASE IF CLIENT DATE EXIST
function checkIfDateExist($dbArray, $userDate)
{
    $conn = mysqli_connect($dbArray["host"], $dbArray['user'], $dbArray['password'], $dbArray['dbname']);
    if (mysqli_connect_errno()) {
        echo "Hiba a  MySQL csatlakozás során: " . mysqli_connect_error();
        exit();
    }

    $sql = "SELECT IF(EXISTS(SELECT * FROM programsdownload WHERE dateLoad = '" . $userDate . "'),1,0) AS result";

    $result = mysqli_query($conn, $sql);

    $row = mysqli_fetch_assoc($result);

    mysqli_free_result($result);

    mysqli_close($conn);

    if ($row["result"] == 0) {

        return true;
    } else {

        return false;
    }
};

//INSERT CLIENT DATE INTO DATABASE
function downloadDateClient($dbArray, $date)
{
    $conn = mysqli_connect($dbArray["host"], $dbArray['user'], $dbArray['password'], $dbArray['dbname']);
    if (mysqli_connect_errno()) {
        echo "Hiba a MySQL csatlakozás során: " . mysqli_connect_error();
        exit();
    }

    $sql = "INSERT INTO programsdownload(dateLoad) VALUES('" . $date . "')";

    if (mysqli_query($conn, $sql)) {
        return true;
    } else {
        echo "Hiba: " . $sql . "<br>" . mysqli_error($conn);
    }
    mysqli_close($conn);
};

//INSERT PORT.HU API INFORMATIONS INTO DATABASE
function insertProgramToMySqlFunct($dbArray, $chnlName, $progStart, $progTitle, $shortDesc, $ageLimit, $dateUser, $channelUrl)
{
    //MYSQLI CONNECT
    $conn = mysqli_connect($dbArray["host"], $dbArray['user'], $dbArray['password'], $dbArray['dbname']);
    if (mysqli_connect_errno()) {
        echo "Hiba a MySQL csatlakozás során: " . mysqli_connect_error();
        exit();
    }

    $sql = "INSERT INTO
    programs (
    channelName,
    channelProgramStart,
    channelProgramTitle,
    channelProgramShortDescription,
    channelProgramShortAgeLimit,
    dateUser,
    channelUrl
    )
    VALUES
    (
    '" . str_replace("'", "\\'", $chnlName) . "',
    '" . str_replace("'", "\\'", $progStart) . "',
    '" . str_replace("'", "\\'", $progTitle) . "',
    '" . str_replace("'", "\\'", $shortDesc) . "',
    '" . str_replace("'", "\\'", $ageLimit) . "',
    '" . str_replace("'", "\\'", $dateUser) . "',
    '" . str_replace("'", "\\'", $channelUrl) . "'
    )";

    if (mysqli_query($conn, $sql)) {
        return true;
    } else {
        echo "Hiba: " . $sql . "<br>" . mysqli_error($conn);
    }
    mysqli_close($conn);

    loadTableCardInMain($dbArray, $dateUser, $channelUrl);
};

//PORT.HU INFORMATION SELECTION FROM API
function jsonDataCollect($dbArray, $dateUser, $channelUrl)
{
    //DELETE IN CASE OF PROGRAM CHANGE
    deleteTableByDateAndChannel($dbArray, $dateUser, $channelUrl);

    $json_url = "https://port.hu/tvapi?channel_id=" . $channelUrl . "&date=" . $dateUser . "";
    $json = file_get_contents($json_url);
    $data = json_decode($json, TRUE);

    //IF NO PROGRAM RELEASED YET
    if ($data["channels"] == []) {
    ?>
        <div class="errorDiv">Nincs elérhető műsor ebben az időpontban.</div>
    <?php
    } else {
        //COUNT PROGRAM LENGTH
        $count = 0;
        $programLength = $data["channels"][0]["programs"];

        foreach ($programLength as $key) {
            $count += 1;
        }

        //JSON DATA COLLECT
        for ($i = 0; $i < $count; $i++) {

            $channelName = $data["channels"][0]["name"];
            $channelProgramStart = $data["channels"][0]["programs"][$i]["start_datetime"];

            //FORMAT DATETIME TO ACCEPTABLE MYSQL DATA
            $dateTimeProgramStart = new DateTime($channelProgramStart);
            $mysqlDateAsstring = date_format($dateTimeProgramStart, "Y-m-d H:i:s");

            $channelProgramTitle = $data["channels"][0]["programs"][$i]["title"];
            $channelProgramShortDescription = $data["channels"][0]["programs"][$i]["short_description"];
            $channelProgramShortAgeLimit = $data["channels"][0]["programs"][$i]["restriction"]["age_limit"];

            insertProgramToMySqlFunct(
                $dbArray,
                $channelName,
                $mysqlDateAsstring,
                $channelProgramTitle,
                $channelProgramShortDescription,
                $channelProgramShortAgeLimit,
                $dateUser,
                $channelUrl
            );
        }
    }
    loadTableCardInMain($dbArray, $dateUser, $channelUrl);
};


//INSERT CHANNEL SCREENING INFORAMTION IN HTML
function loadTableCardInMain($dbArray, $userDate, $userChannelUrl)
{
    $ageLimitArray = array("0" => "./img/0.png", "6" => "./img/6.png", "12" => "./img/12.png", "16" => "./img/16.png", "18" => "./img/18.png");

    $conn = mysqli_connect($dbArray["host"], $dbArray['user'], $dbArray['password'], $dbArray['dbname']);
    if (mysqli_connect_errno()) {
        echo "Hiba a  MySQL csatlakozás során: " . mysqli_connect_error();
        exit();
    }

    $sql = "SELECT
        channelName AS channel,
        channelProgramStart AS begin,
        channelProgramTitle AS title,
        channelProgramShortDescription as description,
        channelProgramShortAgeLimit AS ageLimit
        FROM
        programs
        WHERE dateUser = '" . $userDate . "' AND channelUrl = '" . $userChannelUrl . "'";

    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
    ?>
        <div class="hoverEvent card">
            <div class="grid-container card-body">
                <h5 class="seriesName card-title"><?php print($row["title"]); ?></h5>
                <p class="seriesDesc card-text"><?php print($row["description"]); ?></p>
                <button disabled>
                    <b>
                        <?php
                        $explodedBeginSpace = explode(" ", $row["begin"]);
                        $explodedBegin = explode(":", $explodedBeginSpace[1]);
                        print($explodedBegin[0] . ":" . $explodedBegin[1]);
                        ?>
                    </b>
                </button>
                <button disabled><img class="ageLimitClass" src="<?php print($ageLimitArray[$row["ageLimit"]]); ?>" alt="<?php print($row["ageLimit"] . " - korhatár besorolás"); ?>"></button>
            </div>
        </div>
<?php
    }
    mysqli_free_result($result);

    mysqli_close($conn);
};

//IN CASE OF PROGRAM CHANGE, DELETE BEFORE INSERTING THE ACTUAL LIST
function deleteTableByDateAndChannel($dbArray, $userDate, $urlChannel)
{
    $conn = mysqli_connect($dbArray["host"], $dbArray['user'], $dbArray['password'], $dbArray['dbname']);
    if (mysqli_connect_errno()) {
        echo "Hiba a MySQL csatlakozás során: " . mysqli_connect_error();
        exit();
    }

    $sql = "DELETE FROM programs WHERE dateUser = '" . $userDate . "' AND channelUrl = '" . $urlChannel . "'";
    if (mysqli_query($conn, $sql)) {
        return true;
    } else {
        echo "Hiba a törlés során: " . $sql . "<br>" . mysqli_error($conn);
    }
    mysqli_close($conn);
};
//<--- FUNCTIONS END

?>

<!Doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/x-icon" href="./img/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="./style.css">
    <title>PORT.hu - Műsorlista</title>
</head>

<body>
    <header>
        <div class="container-fluid" id="headerStyle">
            <a href="https://port.hu/">
                <img src="./img/logo.png" class="img-fluid" alt="Port.hu Logó">
                <h1>Online műsorújság</h1>
            </a>
        </div>

        <nav>
            <hr>
            <form method="POST">
                <div class="labelDiv col-md-12">
                    <div class="labelDiv form-group text-center">
                        <label for="optionCalendar">Időpont:</label><br>
                        <input id="optionCalendar" type="date" name="dateDownload" min="<?php echo date("Y-m-d"); ?>" value="<?php echo date("Y-m-d"); ?>" required>
                    </div>
                    <div class="form-group text-center">
                        <input id="optionButton" type="submit" value="Letöltés">
                    </div>
                </div>
            </form>
            <form method="POST">
                <div class="labelDiv col-md-12">
                    <label for="optionChannel">Csatorna:</label><br>
                    <select class="optionChannel form-control text-center" name="optionChannel" required>
                        <option value="RTL Klub">RTL Klub</option>
                        <option value="TV2">TV2</option>
                        <option value="Viasat 3">Viasat 3</option>
                        <option value="Duna TV">Duna TV</option>
                        <option value="Duna World">Duna World</option>
                    </select>
                    <div class="labelDiv form-group text-center">
                        <label for="optionCalendar">Letöltött Időpontok:</label><br>
                        <select class="optionChannel form-control text-center" name="optionChannelDate" required>
                            <?php

                            loadDownloadedTableDate($dbArray);

                            if (isset($_POST['dateDownload'])) {

                                $rowCount = checkIfDateExist($dbArray, $_POST['dateDownload']);

                                if ($rowCount == 'true') {

                                    downloadDateClient($dbArray, $_POST['dateDownload']);
                                    header("Location: http://localhost/feladat_Fotex/index.php");
                                } else {
                                    echo "<script>alert('Sikertelen mentés!')</script>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group text-center">
                        <input id="optionButton" type="submit" value="Betöltés">
                    </div>
                </div>
            </form>
        </nav>
        <hr>
    </header>

    <main>
        <?php

        //INSERT CHANNEL INFORMATION IN HTML
        if (isset($_POST["optionChannelDate"]) && $_POST["optionChannel"]) {

            $userDate = $_POST["optionChannelDate"];
            $userDateDot = str_replace("-", ".", $userDate);

            $json_url = "https://port.hu/tvapi/init";
            $json = file_get_contents($json_url);
            $data = json_decode($json, TRUE);

            if ($userDate == "") {
        ?>
                <div class="errorDiv">Nem választott dátumot!</div>
                <?php
            } else {

                switch ($_POST["optionChannel"]) {

                    case "RTL Klub":
                ?>
                        <div class="card">
                            <div class="grid-containerHeader card-header">
                                <img class="logoChannel" src="<?php print($data["channels"][0]["logo"]) ?>" alt="<?php print($data["channels"][0]["name"]) ?>">
                                <p class="nameChannel"><?php print($userDateDot) ?></p>
                                <p class="nameChannel">Kezdés</p>
                                <p class="nameChannel">Korhatár</p>
                            </div>
                        </div>
                    <?php
                        jsonDataCollect($dbArray, $userDate, $rtlKlub);
                        break;

                    case "TV2":
                    ?>
                        <div class="card">
                            <div class="grid-containerHeader card-header">
                                <img class="logoChannel" src="<?php print($data["channels"][1]["logo"]) ?>" alt="<?php print($data["channels"][1]["name"]) ?>">
                                <p class="nameChannel"><?php print($userDateDot) ?></p>
                                <p class="nameChannel">Kezdés</p>
                                <p class="nameChannel">Korhatár</p>
                            </div>
                        </div>
                    <?php
                        jsonDataCollect($dbArray, $userDate, $channelTV2);
                        break;

                    case "Viasat 3":
                    ?>
                        <div class="card">
                            <div class="grid-containerHeader card-header">
                                <img class="logoChannel" src="<?php print($data["channels"][2]["logo"]) ?>" alt="<?php print($data["channels"][2]["name"]) ?>">
                                <p class="nameChannel"><?php print($userDateDot) ?></p>
                                <p class="nameChannel">Kezdés</p>
                                <p class="nameChannel">Korhatár</p>
                            </div>
                        </div>
                    <?php
                        jsonDataCollect($dbArray, $userDate, $channelViasat3);
                        break;

                    case "Duna TV":
                    ?>
                        <div class="card">
                            <div class="grid-containerHeader card-header">
                                <img class="logoChannel" src="<?php print($data["channels"][3]["logo"]) ?>" alt="<?php print($data["channels"][3]["name"]) ?>">
                                <p class="nameChannel"><?php print($userDateDot) ?></p>
                                <p class="nameChannel">Kezdés</p>
                                <p class="nameChannel">Korhatár</p>
                            </div>
                        </div>
                    <?php
                        jsonDataCollect($dbArray, $userDate, $channelDuna);
                        break;

                    case "Duna World":
                    ?>
                        <div class="card">
                            <div class="grid-containerHeader card-header">
                                <img class="logoChannel" src="<?php print($data["channels"][4]["logo"]) ?>" alt="<?php print($data["channels"][4]["name"]) ?>">
                                <p class="nameChannel"><?php print($userDateDot) ?></p>
                                <p class="nameChannel">Kezdés</p>
                                <p class="nameChannel">Korhatár</p>
                            </div>
                        </div>
                    <?php
                        jsonDataCollect($dbArray, $userDate, $channelDunaWorld);
                        break;

                    default:
                    ?>
                        <div class="errorDiv">Ismeretlen keresés!</div>
            <?php
                }
            };
            ?>
    </main>
    <footer>
        <div class="card text-center">
            <div class="card-footer text-muted">
                Az oldallal és annak tartalmával kapcsolatos minden jog fenntartva! - Port.hu
            </div>
        </div>
    </footer>
<?php
        };
?>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>

</html>