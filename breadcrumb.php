<?php
// Breadcrumb
function get_breadcrumb_class($class = null, $theme_location) {
    $classes = array();

    if ($class !== null) {
        if (!is_array($class)) {
            $class = preg_split('#\s+#', $class);
        }
        $classes = array_map('esc_attr', $class);
    }

    $classes = apply_filters('breadcrumb_class', $classes, $class, $theme_location);
    return array_unique($classes);
}

function breadcrumb_class($class = null, $theme_location) {
    echo 'class="' . implode(' ', get_breadcrumb_class($class, $theme_location)) . '"';
}

function get_the_breadcrumb() {
    global $post;
    $item_template = '
        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
            <a class="breadcrumb-link" itemprop="item" href="%s"><span itemprop="name">%s</span></a>
            <meta itemprop="position" content="%s">
        </li>
    ';
    $active_template = '<li class="breadcrumb-item active">%s</li>';
    $output = sprintf(
        '<nav role="navigation"><ol class="%s" itemscope itemtype="http://schema.org/BreadcrumbList">'
        . $item_template,
        implode(' ', get_breadcrumb_class(array('breadcrumb', 'breadcrumb-rsaquo', 'breadcrumb-dark', 'bg-transparent', 'mb-0', 'border-0', 'px-0', 'navbar-breadcrumb'), 'top')),
        esc_url(home_url('/')),
        _('Home'),
        1
    );

    if (is_front_page()) {
        return '';
    } elseif (is_home()) {
        $posts_page = get_posts_page();
        if (!$posts_page) {
            return '';
        } else {
            $output .= sprintf(
                $active_template,
                esc_attr($posts_page->post_title)
            );
        }
    } else {
        $position = 2;

        $posts_page = get_posts_page();
        if ($posts_page && !is_page()) {
            $position++;

            $output .= sprintf(
                $item_template,
                get_permalink($posts_page->ID),
                esc_attr($posts_page->post_title),
                2
            );
        }

        if (is_single()) {
            global $post;
            $categories = wp_get_object_terms($post->ID, 'category', array('orderby' => 'parent'));
            if ($categories) {
                if (count($categories) > 1) {
                    foreach ($categories as $i => $category) {
                        $output .= sprintf(
                            $item_template,
                            esc_url(get_category_link($category->term_id)),
                            esc_attr($category->name),
                            $position
                        );
                        $position++;
                    }
                } else {
                    $output .= sprintf(
                        $item_template,
                        esc_url(get_category_link($categories[0]->term_id)),
                        esc_attr($categories[0]->name),
                        $position
                    );
                }
            }
        } elseif (is_page()) {
            $output .= sprintf($active_template, get_the_title());
        } elseif (is_category()) {
            $output .= sprintf($active_template, single_cat_title('', false));
        } elseif (is_tag()) {
            $output .= sprintf($active_template, single_tag_title('', false));
        } elseif (is_date()) {
            $year = get_query_var('year');
            if (is_year()) {
                $output .= sprintf($active_template, $year);
            } elseif (is_month()) {
                $output .= sprintf(
                    $item_template,
                    get_year_link($year),
                    $year,
                    $position
                );
                $output .= sprintf($active_template, get_query_var('monthnum'));
            } elseif (is_day()) {
                $month = get_query_var('monthnum');
                $output .= sprintf(
                    $item_template,
                    get_year_link($year),
                    $year,
                    $position
                );
                $position++;
                $output .= sprintf(
                    $item_template,
                    get_month_link($year, $month),
                    $month,
                    $position
                );
                $output .= sprintf($active_template, get_query_var('day'));
            }
        } elseif (is_author()) {
            $full_name = get_the_author_full_name();
            if ($full_name) {
                $output .= sprintf($active_template, $full_name);
            }
        } elseif (is_search()) {
            $output .= sprintf($active_template, _('Search'));
        }
    }

    $output .= '</ol></nav>';

    return $output;
}

function the_breadcrumb() {
    echo get_the_breadcrumb();
}
