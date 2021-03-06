<?php
/**
 * Shows the list of records ordered by categories
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf(
    '<header><h2><i class="icon-pencil"></i> %s</h2><header>',
    $PMF_LANG['ad_entry_aor']
);

if ($permission['editbt'] || $permission['delbt']) {

    $category = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->transform(0);
    
    // Set the Category for the helper class
    $categoryHelper = new PMF_Helper_Category();
    $categoryHelper->setCategory($category);

    $category->buildTree();
    
    $linkverifier = new PMF_Linkverifier($faqConfig, $user->getLogin());
    if ($linkverifier->isReady()) {
?>
    <script type="text/javascript">
        <!--
        function getImageElement(id, lang)
        {
            return $('#imgurl_' + lang + '_' + id);
        }

        function getSpanElement(id, lang)
        {
            return $('#spanurl_' + lang + '_' + id);
        }

        function getDivElement(id, lang)
        {
            return $('#divurl_' + lang + '_' + id);
        }

        function onDemandVerifyURL(id, lang, target)
        {
            var target = getSpanElement(id, lang);
            var widthPx  = 780;
            var heigthPx = 450;
            var leftPx   = (screen.width  - widthPx)/2;
            var topPx    = (screen.height - heigthPx)/2;
            Fenster = window.open('index.php?action=ajax&ajax=onDemandURL&id=' + id + '&artlang=' + lang, 'onDemandURLVerification', 'toolbar=no, location=no, status=no, menubar=no, width=' + widthPx + ', height=' + heigthPx + ', left=' + leftPx + ', top=' + topPx + ', resizable=yes, scrollbars=yes');
            Fenster.focus();

            verifyEntryURL(id, lang);
        }

        function verifyEntryURL(id, lang)
        {
            //var target = getImageElement(id, lang);
            var target = getSpanElement(id, lang);

            // !!IMPORTANT!! DISABLE ONLOAD. If you do not do this, you will get infinite loop!
            getImageElement(id, lang).onload = "";

            //target.src = "images/url-checking.png";
            getDivElement(id, lang).className = "url-checking";
            target.innerHTML = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-checking']); ?>";

            var url = 'index.php';
            var pars = 'action=ajax&ajax=verifyURL&id=' + id + '&artlang=' + lang;
            var myAjax = new jQuery.ajax({url: url,
                type: 'get',
                data: pars,
                complete: verifyEntryURL_success,
                error: verifyEntryURL_failure});

            function verifyEntryURL_success(XmlRequest)
            {
                //target.src = "images/url-" + XmlRequest.responseText + ".png";
                var allResponses = new Array();
                allResponses['batch1'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-batch1']); ?>";
                allResponses['batch2'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-batch2']); ?>";
                allResponses['batch3'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-batch3']); ?>";
                allResponses['checking'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-checking']); ?>";
                allResponses['disabled'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-disabled']); ?>";
                allResponses['linkbad'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-linkbad']); ?>";
                allResponses['linkok'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-linkok']); ?>";
                allResponses['noaccess'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-noaccess']); ?>";
                allResponses['noajax'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-noajax']); ?>";
                allResponses['nolinks'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-nolinks']); ?>";
                allResponses['noscript'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-noscript']); ?>";
                getDivElement(id, lang).className = "url-" + XmlRequest.responseText;
                if (typeof(allResponses[XmlRequest.responseText]) == "undefined") {
                    getDivElement(id, lang).className = "url-noajax ";
                    target.html(allResponses['noajax']);
                } else {
                    target.html(allResponses[XmlRequest.responseText]);
                }
            }

            function verifyEntryURL_failure(XmlRequest)
            {
                getDivElement(id, lang).className = "url-noaccess";
                target.html("<?php print($PMF_LANG['ad_linkcheck_feedback_url-noaccess']); ?>");
            }

        }
        //-->
    </script>
<?php
    }

    $comment = new PMF_Comment($faqConfig);
    $faq     = new PMF_Faq($faqConfig);
    $date    = new PMF_Date($faqConfig);

    $selectedCategory = PMF_Filter::filterInput(INPUT_GET, 'category', FILTER_VALIDATE_INT, 0);
    $orderBy          = PMF_Filter::filterInput(INPUT_GET, 'orderby', FILTER_SANITIZE_STRING, 1);
    $sortBy           = PMF_Filter::filterInput(INPUT_GET, 'sortby', FILTER_SANITIZE_STRING);
    if (1 !== $orderBy) {
        switch ($orderBy) {
            case 'id':
                $orderBy = 1;
                break;
            case 'title':
                $orderBy = 2;
                break;
            case 'date':
                $orderBy = 3;
                break;
        }
    }
?>
    <form id="recordSelection" name="recordSelection" method="post">
<?php
    $numCommentsByFaq = $comment->getNumberOfComments();
    $numRecordsByCat  = $category->getNumberOfRecordsOfCategory();

    $matrix = $category->getCategoryRecordsMatrix();
    foreach ($matrix as $catkey => $value) {
        $numCommentsByCat[$catkey] = 0;
        foreach ($value as $faqkey => $value) {
            if (isset($numCommentsByFaq[$faqkey])) {
                $numCommentsByCat[$catkey] += $numCommentsByFaq[$faqkey];
            }
        }
    }

    $faq->getAllRecords($orderBy, null, $sortBy);
    foreach ($faq->faqRecords as $record) {
        if (!isset($numActiveByCat[$record['category_id']])) {
            $numActiveByCat[$record['category_id']] = 0;
        }
        $numActiveByCat[$record['category_id']] += $record['active'] == 'yes' ? 1 : 0;
    }

    if (count($faq->faqRecords) > 0) {
        $old     = 0;
        $all_ids = array();

        $visits    = new PMF_Visits($faqConfig);
        $numVisits = array();
        foreach ($visits->getAllData() as $visit) {
            $numVisits[$visit['id']] = $visit['lang'];
        }
        
        foreach ($faq->faqRecords as $record) {
            $catInfo =  '';
            $cid     = $record['category_id'];
            
            if (isset($numRecordsByCat[$cid]) && ($numRecordsByCat[$cid] > 0)) {
                $catInfo .= sprintf(
                    '<span class="label label-info" id="category_%d_item_count">%d %s</span> ',
                    $cid, 
                    $numRecordsByCat[$cid], 
                    $PMF_LANG['msgEntries']
                );
            }
            
            if (isset($numRecordsByCat[$cid]) && $numRecordsByCat[$cid] > $numActiveByCat[$cid]) {
                $catInfo .= sprintf(
                    '<span class="label label-important"><span id="js-active-records-%d">%d</span> %s</span> ',
                    $cid,
                    $numRecordsByCat[$cid] - $numActiveByCat[$cid],
                    $PMF_LANG['ad_record_inactive']
                );
            }
            
            if (isset($numCommentsByCat[$cid]) && ($numCommentsByCat[$cid] > 0)) {
                $catInfo .= sprintf('<span class="label label-inverse">%d %s</span>',
                    $numCommentsByCat[$cid],
                    $PMF_LANG['ad_start_comments']
                );
            }
            $catInfo .= '';
            
            if ($cid != $old) {
                if ($old == 0) {
                    printf('<a name="cat_%d"></a>', $cid);
                } else {
                    print "        </tbody>\n        </table>\n        </div>";
                }
?>
        <p>
            <a class="btn" href="javascript:void(0);"
               onclick="showhideCategory('category_<?php print $cid; ?>');">
                <i class="icon icon-arrow-right"></i>
                <strong><?php print $category->getPath($cid); ?></strong>
                <?php print $catInfo;?>
            </a>
        </p>
        <div id="category_<?php print $cid; ?>" class="categorybox <?php print ($selectedCategory == $cid) ? '' : 'hide'; ?>">
        <table class="table table-striped">
        <thead>
        <tr>
            <th colspan="2" style="width: 24px;">
                <a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=desc">
                    &uarr;
                </a>
                <a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=asc">
                    &darr;
                </a>
            </th>
            <th>
                #
            </th>
            <th style="width: 72px;">
                <input type="checkbox" id="sticky_category_block_<?php print $cid; ?>"
                       onclick="saveStatusForCategory(<?php print $cid; ?>, 'sticky')" />
                &nbsp;<?php print $PMF_LANG['ad_record_sticky'] ?>
            </th>
            <th>
                <a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=desc">
                    &uarr;
                </a>
                <a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=asc">
                    &darr;
                </a>
            </th>
            <th>
                <a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=desc">
                    &uarr;
                </a>
                <a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=asc">
                    &darr;
                </a>
            </th>
            <th colspan="3">
                &nbsp;
            </th>
            <th style="width: 84px;">
                <?php if ($permission['approverec']) { ?>
                <input type="checkbox" id="active_category_block_<?php print $cid; ?>"
                       onclick="saveStatusForCategory(<?php print $cid; ?>, 'active')" />
                &nbsp;<?php print $PMF_LANG['ad_record_active'] ?>
                <?php } ?>
            </th>
        </tr>
        </thead>
        <tbody>
<?php
            }
?>
        <tr class="record_<?php  echo $record['id'] . '_' . $record['lang']; ?>'">
            <td style="width: 24px; text-align: right;">
                <a href="?action=editentry&amp;id=<?php print $record['id']; ?>&amp;lang=<?php print $record['lang']; ?>">
                    <?php print $record['id']; ?>
                </a>
            </td>
            <td style="width: 16px;">
                <?php print $record['lang']; ?>
            </td>
            <td style="width: 24px;">
                <a href="?action=editentry&amp;id=<?php print $record['id']; ?>&amp;lang=<?php print $record['lang']; ?>"
                   title="<?php print $PMF_LANG["ad_user_edit"]; ?> '<?php print str_replace("\"", "´", $record['title']); ?>'">
                    <?php print $record['solution_id']; ?>
                </a>
            </td>
            <td style="width: 56px;">
                <input type="checkbox" lang="<?php print $record['lang'] ?>"
                       onclick="saveStatus(<?php print $cid . ', [' . $record['id'] . ']' ?>, 'sticky');"
                       id="sticky_record_<?php print $cid . '_' . $record['id'] ?>"
                    <?php $record['sticky'] ? print 'checked="checked"' : print '    ' ?> />
            </td>
            <td>
                <a href="?action=editentry&amp;id=<?php print $record['id']; ?>&amp;lang=<?php print $record['lang']; ?>"
                   title="<?php print $PMF_LANG["ad_user_edit"]; ?> '<?php print str_replace("\"", "´", $record['title']); ?>'">
                    <?php print $record['title']; ?>
                </a>
<?php
        if (isset($numCommentsByFaq[$record['id']])) {
            printf(
                '<br/><a class="label label-inverse" href="?action=comments#record_id_%d">%d %s</a>',
                $record['id'],
                $numCommentsByFaq[$record['id']],
                $PMF_LANG['ad_start_comments']
                );
        }
?></td>
            <td style="width: 48px;">
                <?php print $date->format($record['date']); ?>
            </td>
            <td style="width: 96px;">
                <?php print $linkverifier->getEntryStateHTML($record['id'], $record['lang']); ?>
            </td>
            <td style="width: 16px;">
                <a href="javascript:void(0);"
                   onclick="javascript:deleteRecord(<?php print $record['id']; ?>, '<?php print $record['lang']; ?>'); return false;"
                   title="<?php print $PMF_LANG["ad_user_delete"]; ?>">
                    <i class="icon-trash"></i>
                </a>
            </td>
            <td style="width: 16px;">
                <a href="?action=copyentry&amp;id=<?php print $record['id']; ?>&amp;lang=<?php print $record['lang']; ?>"
                   title="<?php print $PMF_LANG['ad_categ_copy']; ?>">
                    <i class="icon-share"></i>
                </a>
            </td>
            <td>
                <?php if ($permission['approverec'] && isset($numVisits[$record['id']])) { ?>
                    <input type="checkbox" lang="<?php print $record['lang'] ?>"
                           onclick="saveStatus(<?php print $cid . ', [' . $record['id'] . ']' ?>, 'active');"
                           id="active_record_<?php print $cid . '_' . $record['id'] ?>"
                           <?php 'yes' == $record['active'] ? print 'checked="checked"' : print '    ' ?> />
                <?php }  else { ?>
                    <span class="label label-important"><i class="icon-white icon-ban-circle"></i></span>
                <?php } ?>
            </td>
        </tr>
<?php
            $old = $cid;
            
            $all_ids[$cid][] = $record['id'];
        }
?>
        </tbody>
        </table>
        </div>
        </form>
    
        <script type="text/javascript">
        /* <![CDATA[ */

        /**
         * Saves the sticky record status for the whole category
         *
         * @param integer id   id
         * @param string  type status type
         *
         * @return void
         */
        function saveStatusForCategory(id, type)
        {
            var id_map = [];
<?php 
foreach ($all_ids as $cat_id => $record_ids) {
    echo "        id_map[" . $cat_id . "] = [" . implode(',', $record_ids) . "];\n";
}
?>
        for (var i = 0; i < id_map[id].length; i++) {
                var status = $('#' + type + '_category_block_' + id).attr('checked');

                $('#' + type + '_record_' + id + '_' + id_map[id][i]).attr('checked', status);
            }

            saveStatus(id, id_map[id], type);
        }

        /**
         * Ajax call for saving the sticky record status
         *
         * @param integer cid  category id
         * @param integer ids  ids
         * @param string  type status type
         *
         * @return void
         */
        function saveStatus(cid, ids, type)
        {
            $('#saving_data_indicator').html('<img src="images/indicator.gif" /> saving ...');
            var data = {action: "ajax", ajax: 'records', ajaxaction: "save_" + type + "_records"};

            for (var i = 0; i < ids.length; i++) {
                var status = $('#' + type + '_record_' + cid + '_' + ids[i]).attr('checked');
                var lang   = $('#' + type + '_record_' + cid + '_' + ids[i]).attr('lang');

                data['items[' + i + '][]'] = [ids[i], lang, status];

                // Updating the current record if it's also contained in another category
                var same_records = $('input').filter(function() {
                    return this.id.match(new RegExp(type + '_record_(\\d+)_' + ids[i]));
                });

                if ('active' === type) {
                    for (var j = 0; j < same_records.length; j++) {
                        $('#' + same_records[j].id).attr('checked', status);

                        var catid              = same_records[j].id.match(/active_record_(\d+)_\d+/)[1];
                        var current_item_count = $('#js-active-records-' + catid).html();
                        var delta              = status ? 1 : -1;

                        $('#js-active-records-' + catid).html(current_item_count * 1 + delta);
                    }
                } else {
                    for (var j = 0; j < same_records.length; j++) {
                        $('#' + same_records[j].id).attr('checked', status);
                    }
                }
            }

            $.get("index.php", data, null);
            $('#saving_data_indicator').html('<?php print $PMF_LANG['ad_entry_savedsuc']; ?>');
        }

        /**
         * Ajax call for deleting records
         *
         * @param  integer record_id   Record id
         * @param  string  record_lang Record language
         * @return void
         */
        function deleteRecord(record_id, record_lang)
        {
            if (confirm('<?php print addslashes($PMF_LANG["ad_entry_del_1"] . " " . $PMF_LANG["ad_entry_del_3"]); ?>')) {
                $('#saving_data_indicator').html('<img src="images/indicator.gif" /> deleting ...');
                $.ajax({
                    type:    "POST",
                    url:     "index.php?action=ajax&ajax=records&ajaxaction=delete_record",
                    data:    "record_id=" + record_id + "&record_lang=" + record_lang,
                    success: function(msg) {
                        $('#saving_data_indicator').html('<?php print $PMF_LANG['ad_entry_delsuc']; ?>');
                        $('.record_' + record_id + '_' + record_lang).fadeOut('slow');
                    }
                });
            }
        }

        /* ]]> */
        </script>
<?php
    } else {
        print $PMF_LANG['err_nothingFound'];
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
