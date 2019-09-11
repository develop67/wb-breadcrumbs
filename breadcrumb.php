<?php
/**
 * Plugin Name:       Breadcrumbs
 * Plugin URI:        https://github.com/tarkhov/wordpress-breadcrumbs
 * Description:       WordPress seo breadcrumbs with schema support.
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            Alexander Tarkhov
 * Author URI:        https://github.com/tarkhov
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       breadcrumbs
 * Domain Path:       /languages
 */

function get_the_breadcrumb($theme_location = null) {
    global $post;

    $item_classes = apply_filters('breadcrumb_item_class', 'breadcrumb-item', $theme_location);
    $link_classes = apply_filters('breadcrumb_link_class', 'breadcrumb-link', $theme_location);

    $item_template = '
        <li class="' . $item_classes . '" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
            <a class="' . $link_classes . '" itemprop="item" href="%s">
                <span itemprop="name">%s</span>
            </a>
            <meta itemprop="position" content="%s">
        </li>
    ';

    $active_template = '<li class="' . $item_classes . ' active">%s</li>';

    $classes = apply_filters('breadcrumb_class', 'breadcrumb breadcrumb-rsaquo', $theme_location);
    $output = sprintf(
        '<ol class="%s" itemscope itemtype="http://schema.org/BreadcrumbList">'
        . $item_template,
        $classes, 
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
            $output .= sprintf($active_template, esc_attr($posts_page->post_title));
        }
    } elseif (is_404()) {
        $output .= sprintf($active_template, 404);
    } else {
        $position = 2;

        $posts_page = get_posts_page();
        if ($posts_page && !is_page()) {
            $output .= sprintf(
                $item_template,
                get_permalink($posts_page->ID),
                esc_attr($posts_page->post_title),
                $position
            );

            $position++;
        }

        if (is_single()) {
            $taxonomy = null;
            if (get_post_type($post) == 'post') {
                $taxonomy = 'post';
            } else {
                $taxonomy_names = get_post_taxonomies();
                if (!empty($taxonomy_names)) {
                    foreach ($taxonomy_names as $taxonomy_name) {
                        $tax = get_taxonomy($taxonomy_name);
                        if ($tax && $tax->hierarchical) {
                            $taxonomy = $tax->name;
                            break;
                        }
                    }
                }
            }

            if ($taxonomy !== null) {
                $categories = wp_get_object_terms($post->ID, $taxonomy, ['orderby' => 'parent']);
                if (!empty($categories)) {
                    if (count($categories) > 1) {
                        foreach ($categories as $category) {
                            $output .= sprintf(
                                $item_template,
                                esc_url(get_category_link($category->term_id)),
                                esc_attr($category->name),
                                $position
                            );
                            $position++;
                        }
                    } else {
                        $category = $categories[0];
                        if ($category->parent) {
                            $parent_ids = get_ancestors($category->term_id, $taxonomy, 'taxonomy');
                            if (!empty($parent_ids)) {
                                $parent_categories = get_categories([
                                    'include' => $parent_ids,
                                ]);
                                if (!empty($parent_categories)) {
                                    foreach ($parent_categories as $parent_category) {
                                        $output .= sprintf(
                                            $item_template,
                                            esc_url(get_category_link($parent_category->term_id)),
                                            esc_attr($parent_category->name),
                                            $position
                                        );
                                        $position++;
                                    }
                                }
                            }
                        }

                        $output .= sprintf(
                            $item_template,
                            esc_url(get_category_link($category->term_id)),
                            esc_attr($category->name),
                            $position
                        );
                        $position++;
                    }
                }
            }
        } elseif (is_page()) {
            if ($post->post_parent) {
                $parent_ids = get_post_ancestors($post->ID);
                if (!empty($parent_ids)) {
                    $parent_pages = get_posts([
                        'numberposts' => -1,
                        'post_type'   => 'page',
                        'include'     => $parent_ids
                    ]);
                    if (!empty($parent_pages)) {
                        foreach ($parent_pages as $parent_page) {
                            $output .= sprintf(
                                $item_template,
                                get_permalink($parent_page->ID),
                                esc_attr($parent_page->post_title),
                                $position
                            );
                            $position++;
                        }
                    }
                    wp_reset_postdata();
                }
            }

            $output .= sprintf($active_template, get_the_title());
        } elseif (is_category()) {
            $category = get_queried_object();
            if ($category && $category->parent) {
                $parent_ids = get_ancestors($category->term_id, 'category', 'taxonomy');
                if (!empty($parent_ids)) {
                    $parent_categories = get_categories([
                        'include' => $parent_ids,
                    ]);
                    if (!empty($parent_categories)) {
                        foreach ($parent_categories as $parent_category) {
                            $output .= sprintf(
                                $item_template,
                                esc_url(get_category_link($parent_category->term_id)),
                                esc_attr($parent_category->name),
                                $position
                            );
                            $position++;
                        }
                    }
                }
            }
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
            $output .= sprintf($active_template, get_the_author());
        } elseif (is_search()) {
            $output .= sprintf($active_template, _('Search'));
        } elseif (is_tax()) {
            $term = get_queried_object();
            if ($term && $term->parent) {
                $parent_ids = get_ancestors($term->term_id, $term->taxonomy, 'taxonomy');
                if (!empty($parent_ids)) {
                    $parent_terms = get_terms([
                        'taxonomy' => $term->taxonomy,
                        'include'  => $parent_ids,
                    ]);
                    if (!empty($parent_terms)) {
                        foreach ($parent_terms as $parent_term) {
                            $output .= sprintf(
                                $item_template,
                                esc_url(get_category_link($parent_term->term_id)),
                                esc_attr($parent_term->name),
                                $position
                            );
                            $position++;
                        }
                    }
                }
            }

            $output .= sprintf($active_template, esc_attr($term->name));
        }
    }

    $output .= '</ol>';

    return apply_filters('breadcrumb_html', $output, $theme_location);
}

function the_breadcrumb() {
    echo get_the_breadcrumb();
}
