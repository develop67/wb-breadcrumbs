<?php
// Full name
function get_the_author_full_name($userID = null) {
    $full_name = '';

    $first_name = get_the_author_meta('first_name', $userID);
    if ($first_name) {
        $full_name .= $first_name;
    }

    $last_name = get_the_author_meta('last_name', $userID);
    if ($last_name) {
        if ($first_name) {
            $full_name .= ' ';
        }

        $full_name .= $last_name;
    }

    return $full_name;
}

function the_author_full_name() {
    echo get_the_author_full_name();
}
