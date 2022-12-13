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
            array("www.fji.dk", "1-WhMxlGkAoOAQFytRokjyT7gKZGtTU9zHOZ981KCWYg", "Sheet1")
        );

        foreach ($array_projects as $key) {
            // - Variables
            $project_name = $key[0];
            $project_name_sanitized = preg_replace("/[\W_]+/u", '', $project_name);
            $project_sheet_url = $key[1];
            $project_sheet_name = $key[2];

            try {
                function configureGoogleClient($project_sheet_url, $project_sheet_name) {
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
    
                    return $array;
                }
                $array_ga_data = configureGoogleClient($project_sheet_url, $project_sheet_name);
    
                function databaseTableHandler($project_name_sanitized, $con) {
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
                }
                databaseTableHandler($project_name_sanitized, $con);
            } catch (\Throwable $th) {
                throw $th;
            } finally {
                echo "<pre>";
                var_dump($array_ga_data);
                echo "</pre>";
                function handleGAData($array_ga_data, $project_name_sanitized, $con) {
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
                                '".$key["Search query"]."', 
                                '".$key["Clicks"]."',
                                '".$key["Impressions"]."',
                                '".$key["CTR"]."',
                                '".$key["Average position"]."',
                                '".$project_name_sanitized."'
                            )";

                            if ($con->query($sql_insert) === TRUE) {
                                            
                            } else {
                                echo "Error: " . $sql_insert . "<br>" . $con->error;
                            }
                        }
                    }   
                }
                handleGAData($array_ga_data, $project_name_sanitized, $con);
            }
        }
    ?>
</body>
</html>