<?php
//make a connection
include"../connect.php";
//check if connected
    //if success then run our query
    if($conn){
        //get our list of maching numbers
        $getList = 'SELECT DISTINCT [Lots Issued].[machine number] AS mach FROM [Lots Issued] ORDER BY [machine number] ASC;';
        //run query
        if($stmt = sqlsrv_query($conn, $getList)){
             //if success echo out the results
            while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){

                echo "<option>" . $row['mach'] . "</option>";
            }                                            
        }else{
             //if error send back message to contact administrator with error number and specific message for IT department
            echo "error-list";
        }
        
    }else{
        //if error then send back error and produce message to user about connection
        echo "error-db";
    }
//close db
 sqlsrv_close($conn);
?>
