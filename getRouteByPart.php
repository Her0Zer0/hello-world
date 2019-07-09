<?php
/*
*
*   FILE: GET ROUTE BY PART
*   AUTHOR: ROBERT SMITH
*   LAST MODIFIED: 11/21/2017
*   FUNCTIONALITY: 
*               1). GET CONNECTED
*               2). STORE VARIABLES
*               3). RUN QUERY TO RETURN BACK ROUTING PER PART
*               4). IF SUCCESS ECHO OUT DATA BACK TO JS FILE
*               5). ELSE RETURN ERROR FOR LIST
*               6). CHECK IF WE RETURN ANY ROWS IF NOT THEN SHOW ERROR FOR ROUTINGS NEEDED. 
*                   
*/
//GET CONNECTED
include"../connect.php";
//CHECK CONNECTION
if($conn){//CONNECTED
    //STORE VARIABLES RECIEVED 
    $part = $_POST['part'];
    //STORE SQL     
    $sql = "SELECT	 routing.sd_mach_code AS mcode,
			         routing.sd_part_number AS prtnum,		
			         p_codes.[CODE-DESCRIPTION] AS p_desc,
			         routing.sd_sequence AS process_sequence 
	           FROM [Route] AS routing INNER JOIN [PROCESS CODES] AS p_codes ON routing.sd_mach_code = p_codes.[CODE] 		 
		              WHERE routing.sd_part_number = '" . $part . "'
		              ORDER BY routing.sd_sequence ASC;";
        //SEND OUT THE QUERY
        $res = sqlsrv_query($conn, $sql);
           //CHECK TO SEE IF QUERY IS SUCCESSFUL 
            
            if($res){//SUCCESS
                //CHECK ROWS AFFECTED
                    $rows_affected = sqlsrv_rows_affected($res);
                //IF NOT EQUAL TO ZERO THEN WE HAVE ROWS ELSE SHOW ERROR
                if($rows_affected != 0){
                    //WHILE WE HAVE ROWS ECHO THEM OUT FOR RETURN
                    while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
                        echo "<option>" . $row['mcode'] . " " . $row['p_desc'] . " " . $row['process_sequence'] . "</option>";
                    }
                }else{
                    echo "no route";   
                }               
            } else {//QUERY FAILED
                //SHOW ERROR GETTING LIST
                echo "error-list";
            }    
} else {//DIDN'T CONNECT
    echo "error-db";
}

sqlsrv_close($conn);
?>