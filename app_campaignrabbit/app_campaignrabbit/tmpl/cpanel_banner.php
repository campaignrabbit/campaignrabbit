<?php
/**
 * --------------------------------------------------------------------------------
 * APP - Campaign Rabbit
 * --------------------------------------------------------------------------------
 * @package     Joomla  3.x
 * @subpackage  J2 Store
 * @author      Alagesan, J2Store <support@j2store.org>
 * @copyright   Copyright (c) 2018 J2Store . All rights reserved.
 * @license     GNU/GPL license: v3 or later
 * @link        http://j2store.org
 * --------------------------------------------------------------------------------
 *
 * */
defined('_JEXEC') or die('Restricted access');

if (!$vars->show_campaign_message) {
    ?>
    <style>
        @media (max-width: 960px) {
            .campaginrabbit-banner-message .campaginrabbit-content {
                display: block
            }

            .campaginrabbit-banner-message .campaginrabbit-content .btn-box-style {
                display: inline-block;
                margin-bottom: 10px
            }

            .campaginrabbit-banner-message .campaginrabbit-content .campaginrabbit-content-action {
                display: inline-block
            }
        }
    </style>
    <div class="campaginrabbit-banner-message updated campaginrabbit-banner-message--success"
         style="display:none;background:#fff;border-left:4px solid #385FF7;border-radius:0;box-shadow:0 7px 14px 0 rgba(59,65,94,.1),0 3px 6px 0 rgba(0,0,0,.07);
         margin-bottom:15px;padding:15px!important;position:relative">
    </div>

    <script type="text/javascript">
        function removeCampaignBanner() {
            (function ($) {
                $('.campaginrabbit-banner-message').hide();
                $.ajax({
                    url: '<?php echo $vars->app_url?>&appTask=removeBanner',
                    type: 'get',
                    dataType: 'json',
                    success: function (json) {
                    }

                });
            })(j2store.jQuery);
        }
        (function ($) {
            $(document).on('ready', function () {
                $.ajax({
                    url: 'http://cdn.j2store.net/campaign.json',
                    type: 'get',
                    dataType: 'json',
                    success: function (json) {
                        console.log(json);
                        if (json['success'] && json['content_html']) {
                            $('.campaginrabbit-banner-message').html(json['content_html']);
                            $('#campaginrabbit-content-action-button').attr('href','<?php echo $vars->app_url;?>');
                            $('#campaginrabbit_image_logo').attr('src','<?php echo $vars->logo_url;?>');
                            $('.campaginrabbit-banner-message').show();
                        }
                    }

                });
            });
        })(j2store.jQuery)
    </script>
    <?php
}
