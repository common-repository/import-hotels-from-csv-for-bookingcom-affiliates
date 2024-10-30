<?php

class IhfcAdminSettings {

    /** @var IhfcAdminSettings _instance */
    protected static $_instance = null;

    /** @var IhfcCsvHelper csvHelper */
    protected $csvHelper = null;

    /** @var IhfcWpUtils wpHelper */
    protected $wpHelper;

    static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
            self::$_instance->setCvsHelper(IhfcCsvHelper::get_instance());
            self::$_instance->setWpHelper(new IhfcWpUtils());
        }
        return self::$_instance;
    }

    public function setCvsHelper($ch) {
        $this->csvHelper = $ch;
    }

    public function setWpHelper($wh) {
        $this->wpHelper = $wh;
    }

    public function getCvsHelper() {
        return $this->csvHelper;
    }

    function getWpHelper() {
        return $this->wpHelper;
    }

    function __construct() {
        add_action('admin_menu', array($this, 'ihfc_admin_menu'));
        add_action('admin_init', array($this, 'ihfc_admin_init'));
        add_filter('query_vars', array($this, 'add_query_vars_filter'));
        $maxExecutionTime = ini_get('max_execution_time');
        if ($maxExecutionTime < IHFC_MIN_EXECUTION_TIME) {
            set_time_limit(IHFC_MIN_EXECUTION_TIME);
        }
    }

    function add_query_vars_filter($vars) {
        $vars[] = "maxError";
        return $vars;
    }

    function ihfc_admin_menu() {
        $this->titleOptionMenuPage = __('Import hotels for Booking.com affiliates', IHFC_TEXT_DOMAIN);
        $this->titleOptionTitlePage = $this->titleOptionMenuPage . " - " . __('Setting & import page', IHFC_TEXT_DOMAIN);
        add_options_page($this->titleOptionTitlePage, $this->titleOptionMenuPage, 'manage_options', IHFC_SETTINGS_OPTIONS_PAGE, array($this, "ihfc_settings_page"));
    }

    function ihfc_admin_init() {
        register_setting(IHFC_SETTINGS_OPTIONS, IHFC_SETTINGS_OPTIONS, array($this, 'ihfc_validate_admin_form_callback'));
    }

    function ihfc_validate_admin_form_generate_cache($inputs, &$messages) {
        $result = array();
        $result["ihfc-action"] = $inputs["ihfc-action"];
        $result["ihfc-generate-cache"] = true;
        $ret = $this->csvHelper->generateCacheIndexes();
        foreach ($ret as $k => $v) {
            if (!$v) {
                $messages[] = sprintf(__("Cache index for %s file could not be saved ", IHFC_TEXT_DOMAIN), basename($k));
                $result["ihfc-generate-cache"] = false;
            }
        }
        $result["ihfc-generate-cache-results"] = $ret;
        return $result;
    }

    function extract_city_list($cities) {
        $citiesList = array();
        $auxList = explode(',', $cities);
        foreach ($auxList as $city) {
            if (!empty($city)) {
                $city = trim($city);
                $citiesList[$city] = $city;
            }
        }
        return $citiesList;
    }

    function ihfc_validate_admin_form_validate_options($inputs, &$messages) {
        $result = array();
        $result["ihfc-action"] = $inputs["ihfc-action"];
        $result["ihfc-validate-options"] = false;
        $cities = $inputs["ufi-cities"];
        $postId = $inputs["post-id"];
        $postStatus = $inputs["post-status"];
        $result["ufi-cities"] = $cities;
        $result["post-id"] = $postId;
        $result["post-status"] = $postStatus;
        if ($this->csvHelper->hasValidCacheIndexes()) {
            $citiesList = $this->extract_city_list($cities);
            if (count($citiesList) > 0) {
                $citiesListValidated = $this->csvHelper->existCity($citiesList);
                if (count($citiesList) == count($citiesListValidated)) {
                    $post = $this->wpHelper->getPost($postId);
                    if ($post && $post["ID"] == $postId) {
                        if (!$this->wpHelper->getHotelIdByPostId($postId)) {
                            $allPostStatus = array("unchanged", "draft", "publish");
                            if (in_array($postStatus, $allPostStatus)) {
                                if ($this->wpHelper->isWPMLActive()) {
                                    $originalPostId = $this->wpHelper->getDefaultLangObjectId($post["ID"], $post["post_type"]);
                                    //error_log("originalPostId: $originalPostId");
                                    if ($originalPostId == $post["ID"]) {
                                        $result["ihfc-validate-options"] = true;
                                    } else {
                                        $messages[] = sprintf(__("You can not use a translation post (id:%s), change it ID %s ", IHFC_TEXT_DOMAIN), $post["ID"], $originalPostId);
                                    }
                                } else {
                                    $result["ihfc-validate-options"] = true;
                                }
                            } else {
                                $messages[] = sprintf(__("Post status %s Not found", IHFC_TEXT_DOMAIN), $postStatus);
                            }
                        } else {
                            $messages[] = __("Booking Hotel found is this Post. You can not use this post as template", IHFC_TEXT_DOMAIN);
                        }
                    } else {
                        $messages[] = sprintf(__("Post ID %s Not found", IHFC_TEXT_DOMAIN), $postId);
                    }
                } else {
                    $auxDiff = array_diff(array_keys($citiesList), array_keys($citiesListValidated));
                    $messages[] = sprintf(__("%s UFI ID or destination city ID not found", IHFC_TEXT_DOMAIN), implode(", ", $auxDiff));
                }
            } else {
                $messages[] = __("UFI cities empty", IHFC_TEXT_DOMAIN);
            }
        } else {
            $messages[] = __("The cache indexes must be created before options validation", IHFC_TEXT_DOMAIN);
        }
        return $result;
    }

    function ihfc_validate_admin_form_import_hotels($inputs, &$messages) {
        $result = $this->ihfc_validate_admin_form_validate_options($inputs, $messages);
        if ($result["ihfc-validate-options"]) {
            $result = array();
            $result["ihfc-action"] = $inputs["ihfc-action"];
            $result["ihfc-import-hotels"] = false;
            $cities = $inputs["ufi-cities"];
            $templatePostId = $inputs["post-id"];
            $postStatus = $inputs["post-status"];
            $result["ufi-cities"] = $cities;
            $result["post-id"] = $templatePostId;
            $result["post-status"] = $postStatus;
            $citiesList = $this->extract_city_list($cities);
            $citiesListValidated = $this->csvHelper->existCity($citiesList);
            $allPostStatus = array("unchanged", "draft", "publish");
            if (count($citiesList) == count($citiesListValidated) && $templatePostId && in_array($postStatus, $allPostStatus)) {
                $ihfcProccess = new IhfcImportHotelsProcess($this->getCvsHelper(), $this->getWpHelper());
                $res = $ihfcProccess->importHotels($citiesListValidated, $templatePostId, $postStatus, $messages);
            } else {
                $messages[] = __("Double check validation failed", IHFC_TEXT_DOMAIN);
            }
        }

        return $result;
    }

    function ihfc_validate_admin_form_callback($inputs, $messages = array()) {
        $messages = array();
        $action = sanitize_text_field($inputs["ihfc-action"]);
        $result = array();
        $messagetype = "error";
        if ($action == "generate-cache") {
            $result = $this->ihfc_validate_admin_form_generate_cache($inputs, $messages);
        } else if ($action == "validate-options") {
            $result = $this->ihfc_validate_admin_form_validate_options($inputs, $messages);
        } else if ($action == "import-hotels") {
            $result = $this->ihfc_validate_admin_form_import_hotels($inputs, $messages);
            $messagetype = "updated";
        } else {
            $cities = $inputs["ufi-cities"];
            $templatePostId = $inputs["post-id"];
            $postStatus = $inputs["post-status"];
            $result["ufi-cities"] = $cities;
            $result["post-id"] = $templatePostId;
            $result["post-status"] = $postStatus;
        }
        if (count($messages) > 0) {
            add_settings_error(IHFC_SETTINGS_OPTIONS, 'ihfc_admin_options_texterror', implode('<br/>', $messages), $messagetype);
        } else {
            add_settings_error(IHFC_SETTINGS_OPTIONS, 'ihfc_admin_options_texterror', "PROCESSED", 'updated');
        }
        return $result;
    }

    function ihfc_render_section_select_options() {
        $ihfcSettings = get_option(IHFC_SETTINGS_OPTIONS);
        $list = $this->csvHelper->getCvsFiles();
        $listPostStatus = array(
            "unchanged" => __('Leave all post hotels already created unchanged', IHFC_TEXT_DOMAIN),
            "draft" => __('Force all post hotels to draft status', IHFC_TEXT_DOMAIN),
            "publish" => __('Force all post hotels to publish status', IHFC_TEXT_DOMAIN)
        );
        $enableImportButton = ($ihfcSettings["ihfc-action"] == "validate-options" && $ihfcSettings["ihfc-validate-options"]);
        ?><h3><?php _e('Select your options for import Booking.com hotels', IHFC_TEXT_DOMAIN); ?></h3>
        <p>
            <label><?php _e('Insert UFI ID or (city ID/destination ID) to import', IHFC_TEXT_DOMAIN); ?></label>
            <input type="text" name="<?php echo IHFC_SETTINGS_OPTIONS; ?>[ufi-cities]" value="<?php echo $ihfcSettings["ufi-cities"]; ?>"  <?php if ($enableImportButton) echo "readonly"; ?> placeholder="-392301,-372490">            
            <small><?php _e('These IDs must be in the TSV files uploaded by you. Ex: -392301, -372490 for importing hotels from Mojácar and Barcelona', IHFC_TEXT_DOMAIN); ?></small>
        </p>
        <p>
            <label><?php _e('Insert Post ID as template', IHFC_TEXT_DOMAIN); ?></label>
            <input type="text" name="<?php echo IHFC_SETTINGS_OPTIONS; ?>[post-id]" value="<?php echo $ihfcSettings["post-id"]; ?>"  <?php if ($enableImportButton) echo "readonly"; ?>>            
            <small><?php _e('This plugin will use this post to generate all the hotels from the cities you have selected.', IHFC_TEXT_DOMAIN); ?></small>
        </p>
        <p>
            <label><?php _e('Select the status for created and updated posts', IHFC_TEXT_DOMAIN); ?></label>
        <?php if ($enableImportButton) { ?>
                <input type="text" name="<?php echo IHFC_SETTINGS_OPTIONS; ?>[post-status-text]" value="<?php echo esc_attr($listPostStatus[$ihfcSettings["post-status"]]); ?>"  readonly>            
                <input type="hidden" name="<?php echo IHFC_SETTINGS_OPTIONS; ?>[post-status]" value="<?php echo $ihfcSettings["post-status"]; ?>">            
        <?php } else { ?>
                <select name="<?php echo IHFC_SETTINGS_OPTIONS; ?>[post-status]">
                <?php
                foreach ($listPostStatus as $k => $v) {
                    echo '<option value="' . $k . '" ' . ($ihfcSettings["post-status"] == $k ? 'selected' : '') . '>' . $v . '</option>';
                }
                ?>
                </select>
                <?php } ?>
            <small><?php _e('The post status for the post hotels created or updated.', IHFC_TEXT_DOMAIN); ?></small>
        </p>
        <p class="submit">
        <?php if ($enableImportButton) { ?>
                <input id="ihfc-btn-validate-options" onclick="ihfcSubmitAction(this, 'cancel');" type="button" class="button" value="<?php _e('Cancel', IHFC_TEXT_DOMAIN); ?>" />
                <input id="ihfc-btn-validate-options" onclick="ihfcSubmitAction(this, 'import-hotels');" type="button" class="button-primary" value="<?php _e('Import hotels', IHFC_TEXT_DOMAIN); ?>" /> 
                <br/><br/>
                <span><b><?php printf(__('Only %s hotels will be imported or modified each time', IHFC_TEXT_DOMAIN), IHFC_MAX_HOTELS_IMPORT_AT_ONCE); ?></b></span>
        <?php } else { ?>             
                <input id="ihfc-btn-validate-options" onclick="ihfcSubmitAction(this, 'validate-options');" type="button" class="button-primary" value="<?php _e('Validate options', IHFC_TEXT_DOMAIN); ?>" />                
            <?php } ?>
        </p><?php
        }

        function ihfc_render_section_product_callback() {
            echo '<div><p><em>';
            echo __('Copy & Paste the generated product box (search box and  inspitating search box) in "Search box code"', IHFC_TEXT_DOMAIN);
            echo '<br/>' . __('After save these settings you will be able to see a preview of the generated code and override some parameters in widgets, shortcodes and posts', IHFC_TEXT_DOMAIN);
            echo '</em></p></div>';
        }

        function ihfc_settings_page() {
            $maxExecutionTime = ini_get('max_execution_time');
            ?><h2><?php echo $this->titleOptionTitlePage; ?></h2>
        <div class="wrap"> 
        <?php
        $maxError = filter_input(INPUT_GET, 'maxError', FILTER_SANITIZE_SPECIAL_CHARS);
        if ($maxError == "1") {
            ?>
                <div class="error"><p><b><?php
                printf(__('Maximum execution time of %s seconds exceeded. Click in "%s" button to continue', IHFC_TEXT_DOMAIN), $maxExecutionTime, (__('Generate Cache Indexes', IHFC_TEXT_DOMAIN)));
                ?></b></p></div>
                        <?php } ?>
            <div class="updated"><p><?php
            printf(__('Import hotels from tsv/csv files downloaded from <a href="%s" target="_blank">Booking.com Affiliate Partner Center interface</a> and upload by FTP to:', IHFC_TEXT_DOMAIN), 'https://www.booking.com/partner/');
            ?><br/><b><?php echo IHFC_PLUGIN_UPLOAD_CSV_DIR_TEXT; ?></b>
                    <br/>Max Execution time: <b><?php echo $maxExecutionTime; ?></b> secs.</p></div>
            <div class="ihfc-settings-page">
                <form action="options.php" method="post">
                    <input id="ihfc-action" type="hidden" name="<?php echo IHFC_SETTINGS_OPTIONS; ?>[ihfc-action]" value=""/>
        <?php
        settings_fields(IHFC_SETTINGS_OPTIONS);
        $list = $this->csvHelper->getCvsFiles();
        if (empty($list) || count($list) < 1) {
            ?>
                        <div class="error">
                            <p style="font-size:110%;">
            <?php
            _e('You must download VALID hotels files (TSV format) from Booking.com affiliate center and upload by FTP to location below', IHFC_TEXT_DOMAIN);
            ?>
                                <br/>
                                <b><?php echo IHFC_PLUGIN_UPLOAD_CSV_DIR_TEXT; ?></b>
                            </p>
                        </div>
        <?php } else { ?>
                        <h3><?php _e('Valid Hotel TSV/CSV FILES detected') ?>:</h3>                        
                        <table>
                            <thead><td  class="li-file"><?php _e('TSV/CSV FILES') ?></td><td><?php _e('booking hotel format') ?></td><td><?php _e('Indexed and cached') ?></td></thead>
            <?php
            $continue = true;
            foreach ($list as $i => $file) {
                $isValid = $this->csvHelper->isValid($file);
                $isValidText = ($isValid ? __("YES") : __("NO"));
                $hasCache = null;
                $hasCacheText = "";
                $hasCache = $this->csvHelper->hasValidCache($file);
                $hasCacheText = ($hasCache ? __("YES") : __("NO"));
                $continue = $continue && $hasCache;
                ?><tr>
                                    <td class="li-file"><?php echo basename($file); ?></td>
                                    <td class="li-is-valid"><?php echo $isValidText; ?></td>
                                    <td class="li-is-cached"><?php echo $hasCacheText; ?></td>                        
                                </tr><?php } ?>
                        </table>
            <?php if (!$continue) { ?>
                            <p class="submit">                            
                                <b><?php _e("You need to generate cache and indexes before continue"); ?></b>
                                <br/>
                                <br/>
                                <input id="ihfc-btn-generate-cache" onclick="ihfcSubmitAction(this, 'generate-cache');" type="button" class="button-primary" value="<?php
                _e('Generate Cache Indexes', IHFC_TEXT_DOMAIN);
                ?>" />                
                            </p>
                                       <?php
                                   } else {
                                       $this->ihfc_render_section_select_options();
                                   }
                                   ?>
                    <?php } ?>                                       
                </form>
                <pre>         
                </pre>
                <div class="clear"></div>
            </div>
        </div>
        <?php
    }

}
