<?php
function get_posts_page() {
    $page = null;

    if (get_option('show_on_front') == 'page') {
        $page_id = get_option('page_for_posts');
        if ($page_id) {
            $page = get_page($page_id);
        }
    }

    return $page;
}
