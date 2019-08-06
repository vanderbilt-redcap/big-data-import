<?php
ignore_user_abort(true);
set_time_limit(0);

$module->cronbigdata();

echo json_encode(array(
        'status' =>'success'
    )
);
?>