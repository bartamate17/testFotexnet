<?php
//API - TV CHANNEL
$rtlKlub = "tvchannel-5";
$channelTV2 = "tvchannel-3";
$channelViasat3 = "tvchannel-21";
$channelDuna = "tvchannel-6";
$channelDunaWorld = "tvchannel-103";


//DATABASE -> TABLE átnevezés (functions)

?>

<!Doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/x-icon" href="./img/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>PORT.hu - Műsorlista</title>
</head>

<body>
    <header>
        <h1>PORT.HU - Online műsorújság</h1>
        <form method="POST">
            <select name="optionChannel">
                <option value="RTL Klub">RTL Klub</option>
                <option value="TV2">TV2</option>
                <option value="Viasat 3">Viasat 3</option>
                <option value="Duna TV">Duna TV</option>
                <option value="Duna World">Duna World</option>
            </select>
            <input type="date" name="dateExact">
            <input type="submit" value="Betöltés">
        </form>
    </header>

    <main>
        <?php

        function MySqlFunct($host, $user, $pass, $dbname, $chnlName, $progStart, $progTitle, $shortDesc, $ageLimit, $dateUser, $channelUrl)
        {
            //MYSQLI CONNECT
            $conn = mysqli_connect($host, $user, $pass, $dbname);
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


            loadFromTable($host, $user, $pass, $dbname, $dateUser, $channelUrl);
        };

        function channelDateFunction($dateUser, $channelUrl)
        {
            require_once "./database.php";

            if (checkFromDatabase($localhost, $user, $password, $dbname, $dateUser, $channelUrl) == 1) {

                loadFromTable($localhost, $user, $password, $dbname, $dateUser, $channelUrl);
            } else {

                deleteTable($localhost, $user, $password, $dbname, $dateUser, $channelUrl);

                $json_url = "https://port.hu/tvapi?channel_id=" . $channelUrl . "&date=" . $dateUser . "";
                $json = file_get_contents($json_url);
                $data = json_decode($json, TRUE);

                //COUNT PROGRAMLENGTH
                $count = 0;
                $programLength = $data["channels"][0]["programs"];

                foreach ($programLength as $key) {
                    $count += 1;
                }

                //DATA REFERENCE
                for ($i = 0; $i < $count; $i++) {

                    $channelName = $data["channels"][0]["name"];
                    $channelProgramStart = $data["channels"][0]["programs"][$i]["start_datetime"];
                    //dateTime objektumbol állítunk mysql fogyasztható formátumot
                    $dateTimeProgramStart = new DateTime($channelProgramStart);
                    $mysqlDateAsstring = date_format($dateTimeProgramStart, "Y-m-d H:i:s");

                    $channelProgramTitle = $data["channels"][0]["programs"][$i]["title"];
                    $channelProgramShortDescription = $data["channels"][0]["programs"][$i]["short_description"];
                    $channelProgramShortAgeLimit = $data["channels"][0]["programs"][$i]["restriction"]["age_limit"];

                    MySqlFunct(
                        $localhost,
                        $user,
                        $password,
                        $dbname,
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

            loadFromTable($localhost, $user, $password, $dbname, $dateUser, $channelUrl);
        };

        function loadFromTable($host, $user, $pass, $dbname, $userDate, $userChannelUrl)
        {
            $con = mysqli_connect($host, $user, $pass, $dbname);
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

            $result = mysqli_query($con, $sql);

            while ($row = mysqli_fetch_assoc($result)) {
        ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php print($row["title"]); ?></h5>
                        <p class="card-text"><?php print($row["description"]); ?></p>
                        <button disabled>
                            <?php
                            $explodedBeginSpace = explode(" ", $row["begin"]);
                            $explodedBegin = explode(":", $explodedBeginSpace[1]);
                            print($explodedBegin[0] . ":" . $explodedBegin[1]);
                            ?>
                        </button>
                        <button disabled><?php print($row["ageLimit"]); ?></button>
                    </div>
                </div>

                <?php
            }

            mysqli_free_result($result);

            mysqli_close($con);
        };


        function checkFromDatabase($host, $user, $pass, $dbname, $userDate, $userChannel)
        {
            $con = mysqli_connect($host, $user, $pass, $dbname);
            if (mysqli_connect_errno()) {
                echo "Hiba a  MySQL csatlakozás során: " . mysqli_connect_error();
                exit();
            }

            $sql = "SELECT dateUser, channelUrl FROM programs";

            $result = mysqli_query($con, $sql);

            while ($row = mysqli_fetch_assoc($result)) {
                $dateTimeProgramStartUser = new DateTime($userDate);
                $mysqlDateAsstringUser = date_format($dateTimeProgramStartUser, "Y-m-d H:i:s");

                if ($mysqlDateAsstringUser == $row["dateUser"] && $userChannel == $row["channelUrl"]) {
                    return true;
                } else {
                    return false;
                }
            }
            mysqli_free_result($result);

            mysqli_close($con);
        };

        function deleteTable($host, $user, $pass, $dbname, $userDate, $urlChannel)
        {
            $conn = mysqli_connect($host, $user, $pass, $dbname);
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


        if (isset($_POST["dateExact"])) {

            $userDate = $_POST["dateExact"];

            $json_url = "https://port.hu/tvapi/init";
            $json = file_get_contents($json_url);
            $data = json_decode($json, TRUE);

            switch ($_POST["optionChannel"]) {

                case "RTL Klub":

                ?>
                    <div class="card">
                        <div class="card-header">
                            <img src="<?php print($data["channels"][0]["logo"]) ?>" alt="<?php print($data["channels"][0]["name"]) ?>">
                        </div>
                    </div>
                <?php
                    channelDateFunction($userDate, $rtlKlub);
                    break;

                case "TV2":

                ?>
                    <div class="card">
                        <div class="card-header">
                            <img src="<?php print($data["channels"][1]["logo"]) ?>" alt="<?php print($data["channels"][1]["name"]) ?>">
                        </div>
                    </div>
                <?php
                    channelDateFunction($userDate, $channelTV2);
                    break;
                case "Viasat 3":
                ?>
                    <div class="card">
                        <div class="card-header">
                            <img src="<?php print($data["channels"][2]["logo"]) ?>" alt="<?php print($data["channels"][2]["name"]) ?>">
                        </div>
                    </div>
                <?php
                    channelDateFunction($userDate, $channelViasat3);
                    break;
                case "Duna TV":
                ?>
                    <div class="card">
                        <div class="card-header">
                            <img src="<?php print($data["channels"][3]["logo"]) ?>" alt="<?php print($data["channels"][3]["name"]) ?>">
                        </div>
                    </div>
                <?php
                    channelDateFunction($userDate, $channelDuna);
                    break;
                case "Duna World":
                ?>
                    <div class="card">
                        <div class="card-header">
                            <img src="<?php print($data["channels"][4]["logo"]) ?>" alt="<?php print($data["channels"][4]["name"]) ?>">
                        </div>
                    </div>
        <?php
                    channelDateFunction($userDate, $channelDunaWorld);
                    break;
                default:
                    print("Hiba a betöltés során!");
            }
        };

        ?>
    </main>
    <footer></footer>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>

</html>






<?php
//PORT.HU API
$channel = "https://port.hu/tvapi/init";
$rtlKlub = "https://port.hu/tvapi?channel_id=tvchannel-5&date=2022-04-09";
$tv2 = "https://port.hu/tvapi?channel_id=tvchannel-3&date=2022-04-09";
$viasat3 = "https://port.hu/tvapi?channel_id=tvchannel-21&date=2022-04-09";
$duna = "https://port.hu/tvapi?channel_id=tvchannel-6&date=2022-04-09";
$dunaWorld = "https://port.hu/tvapi?channel_id=tvchannel-103&date=2022-04-09";


/*
    //FRONTEND
    $json_url = "https://port.hu/tvapi/init";
    $json = file_get_contents($json_url);
    $data = json_decode($json, TRUE);
    for ($i = 0; $i <= 4; $i++) {
    ?>
        <img src="<?php if (isset($data)) {
                        print_r($data["channels"][$i]["logo"]);
                    } ?>" alt="" srcset="">

    <?php
    }
    */

/*
    //FIND OFFSET PARAMETER FUNCTION
    function getCalendarDate(string $date)
    {
        $posDate = strpos($date, "=", strpos($date, "=") + 1) + 1;
        $returnDate = substr($date, $posDate);

        return $returnDate;
    };
    */
?>