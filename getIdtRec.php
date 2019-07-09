
<?php
include"../connect.php";
include"../functions.php";
if($conn){
    $idtnum = $_POST['idtnum'];
     $dataToSendBack = array();
    //GET OUR IDT_REC DATA
    $sql = "SELECT idt_rec_id AS idt_rec,
                mcode AS mcode,
                usr_id AS empnum,
                qty AS qty,
                usr_shift AS empshift,
                machine AS machine, 
                SUBSTRING(CONVERT(VARCHAR,created_date),0,12) AS cdate,
                SUBSTRING(CONVERT(VARCHAR,created_date),12,24) AS timed
            FROM idt_rec 
            WHERE idt_id = '". $idtnum ."' 
            ORDER BY sort_seq ASC;";
    
    
     //RUN OUR QUERY
    $res = sqlsrv_query($conn, $sql);
    
//CHECK IF IT RAN
    if($res){
        while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){

            array_push($dataToSendBack, array("idt_rec" => $row['idt_rec'], 
                                              "mcode" => $row['mcode'],
                                              "empnum" => $row['empnum'],
                                              "qty" => $row['qty'],
                                              "empshift" => $row['empshift'],
                                              "machine" => $row['machine'],
                                              "cdate" => $row['cdate'],
                                              "timed" => $row['timed']
                                             ));
        }

        echo json_encode($dataToSendBack);
        
    }else{
        echo "error-idtrec";
        exit;
    }
    
    
}else{
    echo "error-db";
    exit;
}

sqlsrv_close($conn);
?>
