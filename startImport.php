<?php
ignore_user_abort(true);
set_time_limit(60);
error_log("Big Data: Starting the import");
$module->cronbigdata();

echo json_encode(array(
        'status' =>'success'
    )
);
?>