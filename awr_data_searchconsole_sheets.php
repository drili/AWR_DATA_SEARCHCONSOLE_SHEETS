<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AWR DATA SEARCHCONSOLE SHEETS</title>
</head>
<body>
    <?php
        // --- Require files
        // - Composer
        require __DIR__ . '/vendor/autoload.php';

        // - DB connection
        require_once("conn.php");

        // --- Array lists to run
        $array_projects = array(
            // array("byravn.dk", "1sQeCk41INNKRnHrsfmSLbAnpS0ebXH7dM91rJ5TOsT0", "Sheet1"),
            // array("byravn.se", "1BS_2uOZKsupi1Ji2tVyPNFbCdiGBKENol83tIfikjdU", "Sheet1"),
            // array("byravn.no", "1QoJmFeJbk5hZW5ORryc0GEFU2yZeyr3H41OvdwDKj0Q", "Sheet1"),
            // array("vestermarkribe.dk", "1FQdmZAJFbb0T1hgKdJIgblag91y3BVJZYPg0Hrm3xZY", "Sheet1"),
            // array("eurodan-huse.dk", "1RnHO3VCmtBMGm8fVV7Z33rFLW7fviBl7INe5EHTF3xs", "Sheet1"),
            array("www.northernhunting.com", "141HynoGIPANEagNALeqjQkAZrjxLwEKcacq6_f-r3Rc", "Sheet1"),
        );

        foreach ($array_projects as $key) {
            // - Variables
            $project_name = $key[0];
            $project_name_sanitized = preg_replace("/[\W_]+/u", '', $project_name);
            $project_sheet_url = $key[1];
            $project_sheet_name = $key[2];

            try {
                // --- Configure the Google Client
                $client = new \Google_Client();
                $client->setApplicationName('Google Sheets API');
                $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
                $client->setAccessType('offline');
                $path = 'credentials.json';
                $client->setAuthConfig($path);

                // - Init the Sheets Service
                $service = new \Google_Service_Sheets($client);
                
                // - Get the spreadsheet
                $spreadsheet_id = $project_sheet_url;
                $spreadsheet = $service->spreadsheets->get($spreadsheet_id);

                // - Fetch all rows
                $range = $project_sheet_name;
                $response = $service->spreadsheets_values->get($spreadsheet_id, $range);
                $rows = $response->getValues();
                
                $headers = array_shift($rows);

                // - Transform into assoc array
                $array = [];
                foreach ($rows as $row) {
                    $array[] = array_combine($headers, $row);
                }
                $array_ga_data = $array;
    
                $table_suffix = $project_name_sanitized;
                if (mysqli_query($con, "DESCRIBE `awr_data_searchconsole_sheets_".$table_suffix."`")) {
                    // - Table exists, empty table
                    mysqli_query($con, "TRUNCATE TABLE `awr_data_searchconsole_sheets_".$table_suffix."`");
                    echo "<script>console.log('SQL table already exists (awr_data_searchconsole_sheets_".$table_suffix."). - table emptied')</script>";
                } else {
                    $sql_create_table = "CREATE TABLE `awr_data_searchconsole_sheets_".$table_suffix."` (
                        unique_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        keyword VARCHAR(255) NOT NULL,
                        clicks INT(11) NOT NULL,
                        impressions INT(11) NOT NULL,
                        ctr INT(11) NOT NULL,
                        average_position INT(11) NOT NULL,
                        project_client VARCHAR(255) NOT NULL
                    )";

                    if ($con->query($sql_create_table) === TRUE) {
                        echo "<script>console.log('SQL table created successfully (awr_data_searchconsole_sheets_".$table_suffix.").')</script>";
                    } else {
                        echo "ErrorResponse: Error creating table: " . $con->error;
                    }
                }
            } catch (\Throwable $th) {
                throw $th;
            } finally {
                if (!empty($array_ga_data)) {
                    $table = "awr_data_searchconsole_sheets_".$project_name_sanitized;
                    
                    foreach ($array_ga_data as $key) {
                        $sql_insert = "INSERT INTO ".$table." (
                            keyword, 
                            clicks, 
                            impressions,
                            ctr,
                            average_position,
                            project_client) 
                        VALUES (
                            '".mysqli_real_escape_string($con, $key["Search query"])."', 
                            '".mysqli_real_escape_string($con, $key["Clicks"])."',
                            '".mysqli_real_escape_string($con, $key["Impressions"])."',
                            '".mysqli_real_escape_string($con, $key["CTR"])."',
                            '".mysqli_real_escape_string($con, $key["Average position"])."',
                            '".mysqli_real_escape_string($con, $project_name_sanitized)."'
                        )";

                        if ($con->query($sql_insert) === TRUE) {
                                        
                        } else {
                            echo "Error: " . $sql_insert . "<br>" . $con->error;
                        }
                    }
                } 
            }
        }
    ?>
</body>
</html>