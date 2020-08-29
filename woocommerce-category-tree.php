<?php
/**
 * Plugin Name: WooCommerce Disable Category
 * Description: WooCommerce Category tree to disable specific category.
 * Version: 1.0.0
 * Author: Vicky P
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if (!is_plugin_active('woocommerce/woocommerce.php')) {
    return;
}

class VickyCatTree {

    public function __construct() {
        add_action('admin_menu', array($this, 'vicky_add_pages'), 12);
        add_action('wp_ajax_vicky_update_category', array($this, 'vicky_update_category'));
        add_filter('get_terms', array($this, 'hide_category_from_frontend'), 10, 3);
    }

    public function vicky_add_pages() {
        $page_hook = add_submenu_page(
                'edit.php?post_type=product', __('Disable Category', 'category-tree'), __('Disable Category', 'category-tree'), 'manage_options', 'category-tree', array($this, 'vicky_settings_page')
        );
    }

    public function hide_category_from_frontend($terms, $taxonomies, $args) {
        $disabled = get_option('disabled_cats');
        if (!empty($disabled)) {
            $new_terms = array();

            if (in_array('product_cat', $taxonomies) && !is_admin()) {
                foreach ($terms as $key => $term) {
                    if (!in_array($term->term_id, $disabled)) {
                        $new_terms[] = $term;
                    }
                }
                $terms = $new_terms;
            }
        }
        return $terms;
    }

    public function vicky_update_category() {
        $vals = $_POST['vals'];
        update_option('disabled_cats', $vals);

        $op = array();
        $disabled = get_option('disabled_cats');
        foreach ($disabled as $disabled) {
            $t = get_term_by('id', $disabled, 'product_cat');
            $op[] = $t->name;
        }
        echo json_encode(array('success' => true, 'result' => $op));
        die();
    }

    public function vicky_settings_page() {
        $disabled = get_option('disabled_cats');
        $op = array();
        foreach ($disabled as $disabled_c) {
            $t = get_term_by('id', $disabled_c, 'product_cat');
            $op[] = $t->name;
        }
        ?>
        <div class="vicky_ct_wrapper">
            <h1>Product Category</h2><br>
                <button class="button" id="exp_all">Expand All</button>
                <div id="cat_tree">
                    <ul>
                        <?php
                        $args = array(
                            'descendants_and_self' => 0,
                            'selected_cats' => $disabled,
                            'popular_cats' => false,
                            'walker' => null,
                            'taxonomy' => 'product_cat',
                            'checked_ontop' => true
                        );
                        wp_terms_checklist(0, $args);
                        ?>
                    </ul>
                </div>
                <div class="updt_msg">
                    Updated Successfully.
                </div>
                <div class="fixed_disabled">
                    <h5>Disabled Category</h5>
                    <ul id="fxdsbl">
                        <?php
                        foreach ($op as $disabled_c) {
                            echo '<li>' . $disabled_c . '</li>';
                        }
                        ?>
                    </ul>
                </div>
        </div>
        <script>
            jQuery(document).ready(function () {
                jQuery('#cat_tree li').each(function (index) {
                    if (jQuery(this).find('ul').length > 0) {
                        jQuery(this).addClass('prn');
                        jQuery(this).prepend("<span class='expnd'>+</span>");
                    }
                });
                jQuery('.expnd').click(function () {
                    jQuery(this).parent().find('ul:first').slideToggle();
                    jQuery(this).parent().toggleClass('expanded');
                    jQuery(this).text(jQuery(this).text() == "+" ? "-" : "+");
                });
                
                jQuery('#exp_all').click(function () {
                    jQuery("div#cat_tree ul.children").show();
                    jQuery('#cat_tree li.prn').addClass("expanded");
                });

                jQuery("#cat_tree input[type=checkbox]").change(function () {
                    var checkValues = jQuery('#cat_tree input[type=checkbox]:checked').map(function () {
                        return jQuery(this).val();
                    }).get();

                    var data = {
                        action: 'vicky_update_category',
                        vals: checkValues,
                    }

                    jQuery.ajax({
                        type: 'post',
                        url: ajaxurl,
                        data: data,
                        dataType: 'JSON',
                        success: function (response) {
                            dsb = '';
                            for (var i = 0, l = response.result.length; i < l; ++i) {
                                dsb += "<li>" + response.result[i] + "</li>";
                            }
                            jQuery('#fxdsbl').html(dsb);
                            jQuery('#cat_tree').removeClass('loading');
                            jQuery(".updt_msg").show().delay( 4000 ).hide(0);
                        },
                        beforeSend: function () {
                            jQuery('#cat_tree').addClass('loading');

                        },
                        error: function (err) {
                            console.log(err);
                        }
                    });

                    return false;
                });
            });
        </script>
        <style>
            div#cat_tree {
                display: block;
                padding-left: 20px;
            }
            div#cat_tree ul{
                list-style: none;
                margin: 0 0px 0 15px;
                position:relative;
            }
            div#cat_tree ul:after {
                content: "";
                position: absolute;
                top: 0;
                z-index: -1;
                bottom: 0;
                left: -9px;
                width: 1px;
                border-left: 1px dotted #aaa;
            }
            div#cat_tree li {
                list-style: none;
                margin: 4px 0;
            }
            div#cat_tree ul.children {
                display: none;
            }
            div#cat_tree li.prn {
                position: relative;
            }
            div#cat_tree span.expnd {
                position: absolute;
                left: -15px;
                font-size: 0;
                font-weight: bold;
                line-height: 1;
                cursor: pointer;
                width: 14px;
                height: 14px;
                top: 6px;
                background-size: 12px 12px !important;
                background-color: #F1F1F1;
                background-image: url(<?php echo plugin_dir_url(__FILE__) . 'collapse.png'; ?>);
            }
            div#cat_tree li.expanded > span.expnd{
                background-image: url(<?php echo plugin_dir_url(__FILE__) . 'expanded.png'; ?>);
            }
            .fixed_disabled {
                position: fixed;
                right: 10px;
                top: 100px;
                z-index: 111;
                max-width: 337px;
                background: #fff;
                padding: 20px;
                max-height: 300px;
                overflow-y: auto;
                box-shadow: 0 0 6px #bbb;
            }
            .vicky_ct_wrapper{
                position: relative;
            }
            .fixed_disabled li {
                border-bottom: 1px solid #eee;
                margin: 4px 0;
                padding: 4px 0;
            }
            .fixed_disabled h5{
                text-align: center;
            }
            .updt_msg {
                position: fixed;
                top: 56px;
                right: 10px;
                width: 340px;
                padding: 7px 20px;
                background: rgba(60, 199, 60, 0.3);
                border: 1px solid rgb(60, 199, 60);
                box-sizing: border-box;
                display: none;
            }
            #cat_tree input[type=checkbox]:checked:before {
                content: "\02A2F" !important;
                margin: 5px 0 0px -4px;
                color: #ff5252;
                font-size: 17px;
            }
            button#exp_all {
                margin-bottom: 10px;
                margin-left: 35px;
            }
            div#cat_tree.loading {
                pointer-events: none;
                opacity: 0.5;
                cursor: progress;
            }
        </style>
        <?php
    }

}

global $vicky_cat_tree;

$vicky_cat_tree = new VickyCatTree();
