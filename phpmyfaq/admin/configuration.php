<?php
/**
 * The main configuration frontend
 *
 * PHP 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['editconfig']) {
    // actions defined by url: user_action=
    $userAction = PMF_Filter::filterInput(INPUT_GET, 'config_action', FILTER_SANITIZE_STRING, 'listConfig');

    // Save the configuration
    if ('saveConfig' === $userAction) {

        $checks = array(
            'filter' => FILTER_SANITIZE_STRING,
            'flags'  => FILTER_REQUIRE_ARRAY
        );
        $editData        = PMF_Filter::filterInputArray(INPUT_POST, array('edit' => $checks));
        $message         = '';
        $userAction      = 'listConfig';
        $oldConfigValues = $faqConfig->config;

        /* XXX the cache concept is designed to be able to activate only one cache engine per time
               so if there are more cache services implemented, respect it here*/
        if (isset($editData['edit']['cache.varnishEnable']) && 'true' == $editData['edit']['cache.varnishEnable']) {
            if (!extension_loaded('varnish')) {
                throw new Exception('Varnish extension is not loaded');
            }
        }

        // Set the new values
        $forbiddenValues = array('{', '}', '$');
        $newConfigValues = array();
        foreach ($editData['edit'] as $key => $value) {
            $newConfigValues[$key] = str_replace($forbiddenValues, '', $value);
        }

        foreach ($oldConfigValues as $key => $value) {
            if (isset($newConfigValues[$key])) {
                continue;
            } else {
                if ($oldConfigValues[$key] == 'true') {
                    $newConfigValues[$key] = 'false';
                } else {
                    $newConfigValues[$key] = $oldConfigValues[$key];
                }
            }
        }

        $faqConfig->update($newConfigValues);
    }
    // Lists the current configuration
    if ('listConfig' === $userAction) {
        $message    = '';
        $userAction = 'listConfig';
?>
        <header>
            <h2><i class="icon-wrench"></i> <?php echo $PMF_LANG['ad_config_edit']; ?></h2>
        </header>

        <div id="user_message"><?php echo $message; ?></div>
        <form class="form-horizontal" id="config_list" name="config_list"
              action="?action=config&amp;config_action=saveConfig" method="post">

            <p>
                <button class="btn btn-inverse toggleConfig" data-toggle="Main">
                    <i class="icon-home icon-white"></i>
                    <?php echo $PMF_LANG['mainControlCenter']; ?>
                </button>
            </p>
            <div id="configMain" class="hide"></div>

            <p>
                <button class="btn btn-inverse toggleConfig" data-toggle="Records">
                    <i class="icon-th-list icon-white"></i>
                    <?php echo $PMF_LANG['recordsControlCenter']; ?>
                </button>
            </p>
            <div id="configRecords" class="hide"></div>

            <p>
                <button class="btn btn-inverse toggleConfig" data-toggle="Search">
                    <i class="icon-search icon-white"></i>
                    <?php echo $PMF_LANG['searchControlCenter']; ?>
                </button>
            </p>
            <div id="configSearch" class="hide"></div>

            <p>
                <button class="btn btn-inverse toggleConfig" data-toggle="Security">
                    <i class="icon-warning-sign icon-white"></i>
                    <?php echo $PMF_LANG['securityControlCenter']; ?>
                </button>
            </p>
            <div id="configSecurity" class="hide"></div>

            <p>
                <button class="btn btn-inverse toggleConfig"  data-toggle="Spam">
                    <i class="icon-thumbs-down icon-white"></i>
                    <?php echo $PMF_LANG['spamControlCenter']; ?>
                </button>
            </p>
            <div id="configSpam" class="hide"></div>

            <p>
                <button class="btn btn-inverse toggleConfig" data-toggle="SocialNetworks">
                    <i class="icon-retweet icon-white"></i>
                    <?php echo $PMF_LANG['socialNetworksControlCenter']; ?>
                </button>
            </p>
            <div id="configSocialNetworks" class="hide"></div>

            <!--
            <p>
                <a class="btn btn-inverse" onclick="javascript:toggleConfig('Cache');">
                    <?php echo $PMF_LANG['cacheControlCenter']; ?>
                </a>
            </p>
            <div id="configCache" class="hide"></div>
            -->

            <p>
                <button class="btn btn-primary" type="submit">
                    <?php echo $PMF_LANG['ad_config_save']; ?>
                </button>
                <button class="btn btn-warning" type="reset">
                    <?php echo $PMF_LANG['ad_config_reset']; ?>
                </button>
            </p>
        </form>

        <script type="text/javascript">
            toggleConfig = function (e) {
                e.preventDefault();
                var configContainer = $("#config" + $(this).data('toggle'));

                if ("hide" === configContainer.attr("class")) {
                    $.get("index.php", {
                        action: "ajax",
                        ajax: "config_list",
                        conf: $(this).data('toggle').toLowerCase()
                    }, function (data) {
                        configContainer.empty().append(data);
                    });
                    configContainer.fadeIn("slow").removeAttr("class");
                } else {
                    configContainer.fadeOut("slow").attr("class", "hide");
                }
            }
            $('button.toggleConfig').on('click', toggleConfig);
        </script>
<?php
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
